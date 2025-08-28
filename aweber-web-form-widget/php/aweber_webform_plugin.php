<?php
namespace AWeberWebFormPluginNamespace;

/**
    * AWeber Web Form Plugin object
    *
    * Main wordpress interface for integrating your AWeber Web Forms into
    * your blog.
    */
class AWeberWebformPlugin {
    var $adminOptionsName = 'AWeberWebformPluginAdminOptions';
    var $widgetOptionsName = 'AWeberWebformPluginWidgetOptions';
    var $webPushOptionsName = 'AWeberWebPushNotificationOptions';
    var $oauthOptionsName = 'AWeberWebformOauth';
    var $oauth2AuthorizedOptions = 'AWeberOAuth2AuthorizeOptions';
    var $oauth2TokensOptions = 'AWeberOauth2TokensOptions';
    var $messages = array();

    /**
        * Constructor
        */
    function __construct() {
        $aweber_settings_url = admin_url('admin.php?page=aweber.php');
        
        $this->messages['auth_required'] = 'AWeber Sign Up Form requires authentication. You will need you to update your <a href="' . $aweber_settings_url . '">settings</a> in order to continue to use AWeber Sign Up Form.';
        $this->messages['auth_error'] = 'AWeber Sign Up Form authentication failed.  Please verify the <a href="' . $aweber_settings_url . '">settings</a> to continue to use AWeber Sign Up Form.';
        $this->messages['auth_failed'] = '<div id="aweber_auth_failed" class="error">AWeber Sign Up Form authentication failed, <a href="' . $aweber_settings_url . '">please reconnect</a>.';
        $this->messages['signup_text_too_short'] = '<div id="aweber_signup_text_too_long" class="error">The subscriber label was too short. Please make sure it is at least 7 characters.</div>';
        $this->messages['no_list_selected'] = '<div id="aweber_no_list_selected" class="error">Your changes were not saved, as no list was selected.</div>';
        $this->messages['temp_error'] = '<div id="aweber_temp_error" class="error">Unable to connect to AWeber\'s API.  Please refresh the page, or <a href="' . admin_url('admin.php?page=aweber.php&reauth=true') . '">attempt to reauthorize.</a></div>';

        $this->ensure_defaults();
    }

    /**
        * Plugin initializer
        *
        * Main plugin initialization hook.
        * @return void
        */
    function init() {
    }

    function ensure_defaults() {
        $pluginAdminOptions = get_option($this->adminOptionsName);
        update_option('AWeberWebformPluginAdminOptions', array(
            'consumer_key'    => isset($pluginAdminOptions['consumer_key']) ? $pluginAdminOptions['consumer_key'] : NULL,
            'consumer_secret' => isset($pluginAdminOptions['consumer_secret']) ? $pluginAdminOptions['consumer_secret'] : NULL,
            'access_key'      => isset($pluginAdminOptions['access_key']) ? $pluginAdminOptions['access_key'] : NULL,
            'access_secret'   => isset($pluginAdminOptions['access_secret']) ? $pluginAdminOptions['access_secret'] : NULL,
        ));
        $options = get_option($this->widgetOptionsName, array());
        $keys = array(
            'list',
            'webform',
            'form_snippet',
            'selected_signup_form_list_id',
            'selected_landing_page_list_id',
            'selected_split_test_form_list_id',
        );
        foreach ($keys as $key) {
            $options[$key] = isset($options[$key]) ? $options[$key] : '';
        }
        $keys = array(
            'create_subscriber_comment_checkbox' => 'ON',
            'create_subscriber_registration_checkbox' => 'ON',
            'aweber_comment_subscriber_text' => "Sign up to our newsletter!",
            'aweber_register_subscriber_text' => "Sign up to our newsletter!",
            'aweber_register_subscriber_listid' => null,
            'aweber_comment_subscriber_listid'  => null,
            'aweber_register_subscriber_tags'   => '',
            'aweber_comment_subscriber_tags'    => '',
            'aweber_add_analytics_checkbox' => 'OFF',
            'aweber_analytics_src'  => null,
            'create_sub_comment_ids' => array(),
            'aweber_web_push_listid' => 'FALSE',
        );
        foreach ($keys as $key => $value) {
            if (!isset($options[$key]) or ($options[$key] == null))
                $options[$key] = $value;
        }

        /* Delete OLD Keys: Code will be executed, only if the create_subscriber_signup_text key is set.  */
        if (isset($options['create_subscriber_signup_text'])):
            $options['aweber_comment_subscriber_text']  = $options['create_subscriber_signup_text'];
            $options['aweber_register_subscriber_text'] = $options['create_subscriber_signup_text'];

            $options['aweber_register_subscriber_listid'] = $options['list_id_create_subscriber'];
            $options['aweber_comment_subscriber_listid']  = $options['list_id_create_subscriber'];

            unset($options['create_subscriber_signup_text']);
            unset($options['list_id_create_subscriber']);

            delete_option('aweber_signup_text_value');
            update_option('aweber_webform_oauth_removed', 'FALSE');
        endif;
        update_option($this->widgetOptionsName, $options);
    }

    // Create the function to output the contents of our Dashboard Widget

    function aweber_dashboard_widget_function() {
        $this->aweber_wp_dashboard_cached_rss_widget('aweber_dashboard_widget', 'wp_dashboard_rss_output');
        $pluginAdminOptions = get_option($this->adminOptionsName);
        $options = get_option($this->widgetOptionsName);
        $oauth2TokensOptions = get_option($this->oauth2TokensOptions);

        // Fetch Aweber account, using OAuth1 or OAuth2.
        $response = $this->getAWeberAccount($pluginAdminOptions, $oauth2TokensOptions);
        if (!isset($response['account'])) {
            echo esc_html($response['message']);
        } else {
            // Get AWeber account reference.
            $account = $response['account'];

            /*  Subscribers after leaving a comment.  */
            try {
                if (is_numeric($options['aweber_comment_subscriber_listid'])) {
                    $list = $account->loadFromUrl('/accounts/' . $account->id . '/lists/' . $options['aweber_comment_subscriber_listid']);
                ?>
                <ul>
                    <li>
                        <strong>List name: </strong><?php echo esc_html($list->name); ?><br>
                    </li>
                    <li>
                        <strong>Subscribed today to this list: </strong><?php echo esc_html($list->total_subscribers_subscribed_today); ?><br>
                    </li>
                    <li>
                        <strong>Subscribed yesterday to this list: </strong><?php echo esc_html($list->total_subscribers_subscribed_yesterday); ?><br>
                    </li>
                    <li>
                        <strong>Total subscribers on this list: </strong><?php echo esc_html($list->total_subscribed_subscribers); ?><br>
                    </li>
                </ul>
                <?php
                }
            } catch (AWeberAPIException $exc) {
                #List ID was not in this account
                if ($exc->type === 'NotFoundError') {
                    $options = get_option($this->widgetOptionsName);
                    $options['aweber_comment_subscriber_listid'] = null;
                    update_option($this->widgetOptionsName, $options);
                }
            }

            /*  Subscribers when register to the blog.  */
            try {
                if (is_numeric($options['aweber_register_subscriber_listid'])) {
                    $list = $account->loadFromUrl('/accounts/' . $account->id . '/lists/' . $options['aweber_register_subscriber_listid']);
                ?>
                <ul>
                    <li>
                        <strong>List name: </strong><?php echo esc_html($list->name); ?><br>
                    </li>
                    <li>
                        <strong>Subscribed today to this list: </strong><?php echo esc_html($list->total_subscribers_subscribed_today); ?><br>
                    </li>
                    <li>
                        <strong>Subscribed yesterday to this list: </strong><?php echo esc_html($list->total_subscribers_subscribed_yesterday); ?><br>
                    </li>
                    <li>
                        <strong>Total subscribers on this list: </strong><?php echo esc_html($list->total_subscribed_subscribers); ?><br>
                    </li>
                </ul>
                <?php
                }
            } catch (AWeberAPIException $exc) {
                #List ID was not in this account
                if ($exc->type === 'NotFoundError') {
                    $options = get_option($this->widgetOptionsName);
                    $options['aweber_register_subscriber_listid'] = null;
                    update_option($this->widgetOptionsName, $options);
                }
            }
        }

        # Display AWeber Footer content
        echo '<div class="aweber-dashboard-footer">
                <ul>
                    <li class="e-overview__blog">
                        <a href="https://blog.aweber.com/" target="_blank">
                            Blog <span aria-hidden="true" class="dashicons dashicons-external"></span>
                        </a>
                    </li>
                    <li class="e-overview__help">
                        <a href="https://help.aweber.com/hc/en-us" target="_blank">
                            Help <span aria-hidden="true" class="dashicons dashicons-external"></span>
                        </a>
                    </li>
                    <li class="e-overview__help">
                        <a href="https://www.aweber.com/" target="_blank">
                            Home page <span aria-hidden="true" class="dashicons dashicons-external"></span>
                        </a>
                    </li>
                </ul>
            </div>';
    }
    /* Copied from WP code
    /  Modified to remove doing_AJAX global
    */
    function aweber_wp_dashboard_cached_rss_widget( $widget_id, $callback, $check_urls = array() ) {
        # Get AWeber Widget options.
        $widgets = get_option( 'dashboard_widget_options' );
        # Display the AWeber Dashboard Header. Icon and version
        echo wp_kses_post('<div class="aweber-dashboard-header">
                <div class="aw-logo">
                    <img style="width: 100%" src="'.$widgets['aweber_dashboard_widget']['logo'].'" />
                </div>
                <div class="aw-versions">
                    <span>AWeber for WordPress ' . $widgets['aweber_dashboard_widget']['version'] . '</span>
                </div>
            </div>');

        $loading = '<p class="widget-loading hide-if-no-js">' . __( 'Loading&#8230;', 'aweber-web-form-widget' ) . '</p><p class="hide-if-js">' . __( 'This widget requires JavaScript.', 'aweber-web-form-widget' ) . '</p>';
        if ( empty($check_urls) ) {
            if ( empty($widgets[$widget_id]['url']) ) {
                echo wp_kses_post($loading);
                return false;
            }
        }
        # Display the AWeber Feed Header
        echo '<div class="aweber-dashboard-feed"><h3>News &amp; Updates</h3></div>';
        $cache_key = 'dash_' . md5( $widget_id );
        if ( false !== ( $output = get_transient( $cache_key ) ) ) {
            echo wp_kses_post($output);
            return true;
        }
        if ( $callback && is_callable( $callback ) ) {
            ob_start();
            call_user_func_array( $callback, array($widget_id) );
            set_transient( $cache_key, ob_get_flush(), 43200); // Default lifetime in cache of 12 hours (same as the feeds)
        }
        return true;
    }

    // Create the function use in the action hook
    function aweber_add_dashboard_widgets() {
        $widget_options = get_option('dashboard_widget_options');
        if (!isset($widget_options['aweber_dashboard_widget'])) {
            $widget_options = array('aweber_dashboard_widget' => array(
                'items' => 2,
                'show_summary' => 1,
                'show_author' => 0,
                'show_date' => 1,
            ));
        }
        $widget_options['aweber_dashboard_widget']['link'] = apply_filters('aweber_dashboard_widget_link', __('https://www.aweber.com/blog/', 'aweber-web-form-widget'));
        $widget_options['aweber_dashboard_widget']['url'] = apply_filters('aweber_dashboard_widget_url', __('https://www.aweber.com/blog/feed', 'aweber-web-form-widget'));
        $widget_options['aweber_dashboard_widget']['title'] = apply_filters('aweber_dashboard_widget_title', __('AWeber Overview', 'aweber-web-form-widget'));
        $plugin_path = join(DIRECTORY_SEPARATOR, array(dirname(__FILE__), '..', 'aweber.php'));
        $widget_options['aweber_dashboard_widget']['version'] = 'v' . get_plugin_data($plugin_path)['Version'];
        $widget_options['aweber_dashboard_widget']['logo'] = plugin_dir_url(__FILE__) . '../AWeber_widget_blue.png';

        update_option('dashboard_widget_options', $widget_options);

        wp_add_dashboard_widget('aweber_dashboard_widget', $widget_options['aweber_dashboard_widget']['title'], array($this, 'aweber_dashboard_widget_function'), array($this, 'aweber_dashboard_widget_control'));
        // Globalize the metaboxes array, this holds all the widgets for wp-admin

        global $wp_meta_boxes;

        // Get the regular dashboard widgets array
        // (which has our new widget already but at the end)

        $normal_dashboard = $wp_meta_boxes['dashboard']['normal']['core'];

        // Backup and delete our new dashbaord widget from the end of the array

        $example_widget_backup = array('aweber_dashboard_widget' => $normal_dashboard['aweber_dashboard_widget']);
        unset($normal_dashboard['aweber_dashboard_widget']);

        // Merge the two arrays together so our widget is at the beginning

        $sorted_dashboard = array_merge($example_widget_backup, $normal_dashboard);

        // Save the sorted array back into the original metaboxes

        $wp_meta_boxes['dashboard']['normal']['core'] = $sorted_dashboard;
    }

    function aweber_dashboard_widget_control() {
        wp_dashboard_rss_control( 'aweber_dashboard_widget' );
    }

    function add_register_checkbox() {
        $this->add_checkbox('aweber_register_subscriber_text');
    }

    function add_comment_checkbox() {
        $this->add_checkbox('aweber_comment_subscriber_text');
    }

    function add_checkbox($key) {
        $options = get_option($this->widgetOptionsName);
        ?>
        <p>
        <input value="1" id="aweber_checkbox" type="checkbox" style="width:inherit;" name="aweber_signup_checkbox"/>
            <label for="aweber_checkbox">
            <?php echo esc_html($options[$key]); ?>
            </label>
        </p>
        </br>
        <?php
    }

    function deauthorize()
    {
        $admin_options = get_option($this->adminOptionsName);
        $admin_options = array(
            'consumer_key' => null,
            'consumer_secret' => null,
            'access_key' => null,
            'access_secret' => null,
        );
        update_option($this->adminOptionsName, $admin_options);
        $options = get_option($this->widgetOptionsName);
        $options['aweber_register_subscriber_listid'] = null;
        $options['aweber_comment_subscriber_listid'] = null;
        $options['aweber_web_push_listid'] = 'FALSE';
        update_option($this->widgetOptionsName, $options);
        delete_option('aweber_webform_oauth_id');
        delete_option('aweber_webform_oauth_removed');
        delete_option('aweber_oauth_error');
        delete_option($this->webPushOptionsName);

        // Check if the OAuth2 tokens exists in the DB.
        $oauth2TokensOptions = get_option($this->oauth2TokensOptions);
        if (isset($oauth2TokensOptions['access_token'])) {
            // Revoke the OAuth2 tokens
            $this->revokeAccessToken();
        }
    }

    function create_subscriber($email, $ip, $list_id, $name, $tags, $custom_fields = null)
    {
        /*
        FILTER_VALIDATE_IP: Checks If ip is valid.
        FILTER_FLAG_NO_RES_RANGE: Fails validation for reserved IPv4 ranges.
        FILTER_FLAG_NO_PRIV_RANGE: Fails validation for private IPv4 ranges.
        */
        $ip = filter_var($ip, FILTER_VALIDATE_IP, (FILTER_FLAG_NO_PRIV_RANGE|FILTER_FLAG_NO_RES_RANGE));
        // Get the Tags for the subscriber. Return Empty array, if no tags.
        $tags = empty($tags) ? array() : explode(',', $tags);
        $tags = array_map('trim', $tags);
        $tags = json_encode($tags);
        $admin_options = get_option($this->adminOptionsName);
        $subscriber = array(
            'email' => $email,
            'tags'  => $tags,
            'update_existing'  => 'true',
            'name' => $name,
            'ad_tracking' => 'Wordpress'
        );
        if (!empty($custom_fields)) {
            $subscriber['custom_fields'] = $custom_fields;
        }
        if (!empty($ip)) {
            $subscriber['ip_address'] = $ip;
        }

        try {
            $aweber = $this->getAWeberAPI();
            // Get the active OAuth1 or OAuth2 connection.
            if (get_class($aweber) == 'AWeberWebFormPluginNamespace\AWeberOAuth2API') {
                $account = $aweber->getAccount();
            } else {
                $account = $aweber->getAccount(
                    $admin_options['access_key'], $admin_options['access_secret']);
            }
            return $account->create_subscriber($list_id, $subscriber);
        } catch (AWeberAPIException $exc){
            #List ID was not in this account
            if ($exc->type === 'NotFoundError') {
                $options = get_option($this->widgetOptionsName);
                $options['list_id_create_subscriber'] = null;
                if ($list_id == $options['aweber_register_subscriber_listid']) {
                    $options['aweber_register_subscriber_listid'] = null;
                }
                if ($list_id == $options['aweber_comment_subscriber_listid']) {
                    $options['aweber_comment_subscriber_listid'] = null;
                }
                update_option($this->widgetOptionsName, $options);
            }
            #Authorization is invalid
            if ($exc->type === 'UnauthorizedError')
                $this->deauthorize();
            
            // Logging the error in the Log file.
            error_log("Create Subscriber Error Occurred. Error Email: " . $email . " Type: " . $exc->type . " Message: " . $exc->message);
        } catch (\Exception $exc) {
            // Fail Silently and log the error in the Log file.
            $message = $exc->getMessage();
            $trace = var_export($exc->getTraceAsString(), true);

            error_log("Create Subscriber Error Occurred. Message: " . $message . " Trace: " . $trace);
        }
    }

    function comment_approved($comment)
    {
        $options = get_option($this->widgetOptionsName);
        $send_coi = $this->find_comment_id($comment->comment_ID);
        if ($send_coi) {
            $this->create_from_comment($comment);
        }
    }

    function find_comment_id($comment_id)
    {
        $options = get_option($this->widgetOptionsName);
        $index = array_search($comment_id, $options['create_sub_comment_ids']);
        if ($index !== false) {
            unset($options['create_sub_comment_ids'][$index]);
            #re-index the array
            $options['create_sub_comment_ids'] = array_values($options['create_sub_comment_ids']);
            update_option($this->widgetOptionsName, $options);
            return true;
        }
        else {
            return false;
        }
    }

    function comment_deleted($comment_id)
    {
        $this->find_comment_id($comment_id);
    }

    function create_from_comment($comment) {
        $options = get_option($this->widgetOptionsName);

        $email = $comment->comment_author_email;
        $name = $comment->comment_author;

        $sub = $this->create_subscriber($email, null, 
            $options['aweber_comment_subscriber_listid'], $name, 
            $options['aweber_comment_subscriber_tags']);
    }

    function grab_email_from_comment($comment_id, $comment = null)
    {
        if ($_POST['aweber_signup_checkbox'] != 1)
            return;

        $comment_id = (int) $comment_id;
        $comment = get_comment($comment_id);
        if(!$comment)
            return;

        $options = get_option($this->widgetOptionsName);

        if ($comment->comment_approved == 1)
            $this->create_from_comment($comment);
        else {
            if(count($options['create_sub_comment_ids']) >= 10000) {
                array_shift($options['create_sub_comment_ids']);
            }
            array_push($options['create_sub_comment_ids'], $comment->comment_ID);
            update_option($this->widgetOptionsName, $options);
        }
    }

    function get_request_ip()
    {
        if(array_key_exists('HTTP_X_REAL_IP', $_SERVER)) {
            return $_SERVER['HTTP_X_REAL_IP'];
        }
        if(array_key_exists('HTTP_X_FORWARDED_FOR', $_SERVER)) {
            return trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'], 2)[0]);
        }
        if(array_key_exists('REMOTE_ADDR', $_SERVER)) {
            return $_SERVER['REMOTE_ADDR'];
        }
        return null;
    }

    function grab_email_from_registration()
    {
        if ($_POST['aweber_signup_checkbox'] != 1)
            return;
        if(isset($_POST['user_email'])) {
            $email = $_POST['user_email'];
            $username = $_POST['user_login'];
            $ip = $this->get_request_ip();

            $options = get_option($this->widgetOptionsName);
            $sub = $this->create_subscriber($email, $ip,
                $options['aweber_register_subscriber_listid'], $username,
                $options['aweber_register_subscriber_tags']);
        }
    }

    /**
        * Add content to the header tag.
        *
        * Hook for adding additional tags to the document's HEAD tag.
        * @return void
        */
    function addHeaderCode() {
        if (function_exists('wp_enqueue_script')) {
            // Admin page scripts
            if (is_admin()) {
                wp_enqueue_script('jquery');
            }
        }
    }

    /**
        * Get admin panel options.
        *
        * Retrieve admin panel settings variables as stored within wordpress.
        * @return array
        */
    function getAdminOptions() {
        $pluginAdminOptions = array(
            'consumer_key'    => null,
            'consumer_secret' => null,
            'access_key'      => null,
            'access_secret'   => null,
        );
        $options = get_option($this->adminOptionsName);
        if (!empty($options)) {
            foreach ($options as $key => $option) {
                $pluginAdminOptions[$key] = $option;
            }
        }
        update_option($this->adminOptionsName, $pluginAdminOptions);
        return $pluginAdminOptions;
    }

    /**
        * Print admin panel settings page.
        *
        * Echo the HTML for the admin panel settings page.
        * @return void
        */
    function printAdminPage() {
        $options = get_option($this->adminOptionsName);
        include(dirname(__FILE__) . '/aweber_forms_import_admin.php');
    }

    function printSignupInfo () {
        $options = get_option($this->adminOptionsName);
        require_once(dirname(__FILE__) . '/aweber_track_signup_form.php');
    }

    public function showLandingPage() {
        require_once(dirname(__FILE__) . '/aweber_landing_page.php');
    }

    function printSystemInfo () {
        $options = get_option($this->adminOptionsName);
        require_once(dirname(__FILE__) . '/aweber_system_info.php');
    }

    function showConnectionPage() {
        require_once(dirname(__FILE__) . '/aweber_connection.php');
    }

    function attachAWeberheader() {
        require_once(dirname(__FILE__) . '/aweber_header_section.php');
    }

    function add_alert_message_html($message_type = '', $message = '', $extra_classes = '') {
        $css_class_names = $svg = '';
        if ($message_type == 'positive') {
            $css_class_names .= ' aweber-alert-positive';
            $svg = '<svg xmlns="https://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24px" height="24px" xmlns:xlink="https://www.w3.org/1999/xlink" role="img" data-src="assets/toolkit/images/svg/check-circle.svg" class="injected-svg icon"><path d="M12 0C5.4 0 0 5.4 0 12s5.4 12 12 12 12-5.4 12-12S18.6 0 12 0zm6.8 8.9L11 16.7c-.2.2-.6.4-.9.4s-.7-.1-1-.4L5 12.6c-.5-.5-.5-1.3 0-1.8s1.3-.5 1.8 0L10 14l7-6.9c.5-.5 1.3-.5 1.8 0s.5 1.3 0 1.8z" role="presentation"></path></svg>';
        } else if($message_type == 'negative') {
            $css_class_names .= ' aweber-alert-negative';
            $svg = '<svg xmlns="https://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24px" height="24px" xmlns:xlink="https://www.w3.org/1999/xlink" role="img" data-src="assets/toolkit/images/svg/alert.svg" class="injected-svg icon"><path d="M22.3 11.3l-9.6-9.6c-.4-.4-1-.4-1.4 0l-9.6 9.6c-.4.4-.4 1 0 1.4l9.6 9.6c.4.4 1 .4 1.4 0l9.6-9.6c.4-.4.4-1 0-1.4zM10.8 6.9c0-.7.6-1.2 1.2-1.2s1.2.6 1.2 1.2v6.4c0 .7-.6 1.2-1.2 1.2s-1.2-.6-1.2-1.2V6.9zM12 18.5c-.7 0-1.3-.6-1.3-1.3s.6-1.3 1.3-1.3 1.3.6 1.3 1.3-.6 1.3-1.3 1.3z" role="presentation"></path></svg>';
        }
        $css_class_names .= ' ' . $extra_classes;
        echo wp_kses(
            '<div class="aweber-alert ' . $css_class_names . '">' . $svg . '<p>'.  $message . '</p></div>', $this->getAllowedTags());
    }

    /**
        * Auto load the AWeber Analytics script.
        *
        * Adds the analytics plugin in head tag
        * @return void
        */
    function loadAWeberPluginScripts() {
        $options = get_option($this->widgetOptionsName);

        //Add Analytics plugins only if: 1. AWeber connection exists 2. Auto add Analytics javascript is enabled 3. AWeber Analytics src not null  
        if (get_option('aweber_webform_oauth_removed')
            && isset($options['aweber_add_analytics_checkbox']) 
            && $options['aweber_add_analytics_checkbox'] == "ON" 
            && !empty($options['aweber_analytics_src'])) {

            // Parameters: $handle, $src, $deps, $ver, $in_footer
            wp_enqueue_script( 'script', $options['aweber_analytics_src'], [], AWEBER_PLUGIN_VERSION, false);
        }
    }

    /**
        * Get widget options.
        *
        * Retrieve widget control settings variables as stored within wordpress.
        * @return array
        */
    function getWidgetOptions() {
        $pluginWidgetOptions = array(
            'list'         => null,
            'webform'      => null,
            'form_snippet' => null,
            'list_id_create_subscriber' => null,
            'create_subscriber_comment_checkbox' => 'ON',
            'create_subscriber_registration_checkbox' => 'ON',
            'aweber_comment_subscriber_text' => "Sign up to our newsletter!",
            'aweber_register_subscriber_text' => "Sign up to our newsletter!",
            'aweber_register_subscriber_listid' => null,
            'aweber_comment_subscriber_listid'  => null,
            'aweber_add_analytics_checkbox' => 'OFF',
            'aweber_analytics_src'  => null,
            'create_sub_comment_ids' => array(),
        );
        $options = get_option($this->widgetOptionsName);
        if (!empty($options)) {
            foreach ($options as $key => $option) {
                $pluginWidgetOptions[$key] = $option;
            }
        }
        update_option($this->widgetOptionsName, $pluginWidgetOptions);
        return $pluginWidgetOptions;
    }

    /**
        * Fetch AWeber Shortcodes
        *
        * Retrives the AWeber shortcodes from the result passed and pushes into the Shortcode array.
        * @return Void
        */
    private function fetchShortCodesFromPOSTContent($results, &$shortcodes) {
        foreach($results as $result) {
            preg_match_all( '/\[aweber(.*?)\]/', $result->post_content, $matches);
            foreach ($matches[1] as $list) {
                $attributes = shortcode_parse_atts($list);

                if (!empty($attributes['formid'])) {
                    $location = array('type' => ucfirst($result->post_type), 'title' => $result->post_title, 'link' => get_permalink($result->ID));

                    if (!array_key_exists($attributes['formid'], $shortcodes)) {
                        $shortcodes[$attributes['formid']] = array('shortcode' => $list, 'areas' => array());
                    }
                    array_push($shortcodes[$attributes['formid']]['areas'], $location);
                }
            }
        }
    }

    /**
        * Fetch AWeber Shortcodes
        *
        * Retrives the AWeber shortcodes from the result passed and pushes into the Shortcode array.
        * @return Void
        */
    private function fetchShortCodesFromWidgetContent($results, &$shortcodes) {
        foreach($results as $result) {
            $widget_options = get_option($result->option_name);
            foreach ($widget_options as $key => $option) {
                $matches = preg_grep('/\[aweber(.*?)\]/', is_array($option) ? $option : array());
                foreach ($matches as $k => $list) {
                    $attributes = shortcode_parse_atts($list);
                    if (!empty($attributes['formid'])) {
                        $widget_name  = explode('_', $result->option_name)[1] . '-' . $key;
                        $location = array('type' => 'Shortcode Widget', 'title' => $this->getWidgetSidebarName($widget_name), 'link' => FALSE);

                        if (!array_key_exists($attributes['formid'], $shortcodes)) {
                            $shortcodes[$attributes['formid']] = array('shortcode' => $list, 'areas' => array());
                        }

                        if (array_search($location['title'], array_column($shortcodes[$attributes['formid']]['areas'], 'title')) === false) {
                            array_push($shortcodes[$attributes['formid']]['areas'], $location);
                        }
                    }
                }
            }
        }
    }

    /**
        * Fetch Shortcodes from sites
        *
        * Retrives the AWeber shortcodes from the wp_posts and wp_options table.
        * @return array
        */
    public function getShortCodeFromSite() {
        $shortcodes = array();

        global $wpdb;
        $args = array(
            'post_status'   => 'publish',
            'post_type'     => array('page', 'post'),
            'numberposts'   => 100,
            's'             => '[aweber ',
            'offset'        => 0
        );

        do {
            $result = get_posts($args);
            $this->fetchShortCodesFromPOSTContent($result, $shortcodes);
            $args['offset'] += count($result);
        } while (count($result) >= $args['numberposts']);

        $this->fetchShortCodesFromWidgetContent(
            $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT option_name FROM {$wpdb->prefix}options WHERE option_value LIKE %s AND option_name LIKE %s",
                    ['%[aweber %', 'widget_%']
                )
            ),
            $shortcodes
        );
        return $shortcodes;
    }

    public function createWordpressPage() {
        $nonce = isset($_REQUEST['nonce']) ? $_REQUEST['nonce'] : '';
        if (!current_user_can('edit_pages') || !wp_verify_nonce($nonce, 'aweber_create_page')) {
            wp_send_json_error('Unauthorized', 403);
        }

        // Create post object
        $my_post = array(
            'post_title'    => wp_strip_all_tags( $_POST['page_name'] ),
            'post_status'   => 'publish',
            'post_name'     => sanitize_title_with_dashes($_POST['page_path']),
            'post_type'     => 'page',
            'page_template' => 'aweber_landing_page',
        );
        // Insert the post into the database
        $post_id = wp_insert_post( $my_post );
        if (is_wp_error($post_id)) {
            $response = array(
                'status'  => 'error',
                'message' => $post_id->get_error_message()
            );
        } else {
            $response = $this->loadLandingPageContent($post_id, False);
        }
        echo json_encode($response);
        $this->_end_response();
    }

    private function isLinkedToPost($post_id) {
        $links = get_option('aweber_landing_page_links');
        if (empty($links))
            return False;
        if (array_search($post_id, array_column($links, 'post_id')) !== False) {
            return True;
        }
        return False;
    }

    public function getWordpressPages() {
        $nonce = isset($_REQUEST['nonce']) ? $_REQUEST['nonce'] : '';
        if (!current_user_can('edit_pages') || !wp_verify_nonce($nonce, 'get_wordpress_pages')) {
            wp_send_json_error('Unauthorized', 403);
        }

        $pages = array();
        foreach(get_pages(array('sort_order' => 'DESC', 'sort_column'  => 'post_date')) as $page) {
            if (!empty($page->post_title) && !empty($page->post_name)) {
                array_push($pages, array(
                    'post_id'=> $page->ID,
                    'name'  => $page->post_title,
                    'path'  => '/' . $page->post_name,
                    'linked'=> $this->isLinkedToPost($page->ID)
                ));
            }
        }
        echo json_encode(array('pages' => $pages));
        $this->_end_response(); 
    }

    private function link_post_page($landing_page_id, $post_id) {
        $links = get_option('aweber_landing_page_links');
        if (empty($links))
            $links = array(); 
        $links[$landing_page_id] = array(
            'post_id'   => $post_id,
            'synced_on' => gmdate('Y-m-d H:i:s'),
        );
        update_option('aweber_landing_page_links', $links);
    }

    private function get_linked_post($landing_page_id) {
        $links = get_option('aweber_landing_page_links');
        if (isset($links[$landing_page_id])) {
            $page = get_post($links[$landing_page_id]['post_id']);
            if ($page) {
                return array(
                    'post_id'  => $page->ID,
                    'synced_on' => gmdate('D, M j, Y, g:i A T', strtotime($links[$landing_page_id]['synced_on'])),
                    'page_path' => '/' . $page->post_name,
                    'page_title' => $page->post_title,
                    'page_link' => get_permalink($page->ID)
                );
            }
        }
        return False;
    }

    private function updatePageData($landing_page_id, $post_data) {
        $post = wp_update_post($post_data);
        if (is_wp_error($post)) {
            $response = array(
                'status'  => 'error',
                'message' => $post->get_error_message()
            );
        } else {
            $this->link_post_page($landing_page_id, $post_data['ID']);
            $response = array(
                'status' => 'success',
                'page' => $this->get_linked_post($landing_page_id)
            );
        }
        return $response;
    }

    public function getAWeberLandingpages() {
        $nonce = isset($_REQUEST['nonce']) ? $_REQUEST['nonce'] : '';
        if (!current_user_can('edit_pages') || !wp_verify_nonce($nonce, 'get_landing_pages')) {
            wp_send_json_error('Unauthorized', 403);
        }

        $list_id = $_GET['list_id'];

        if (empty($list_id)) {
            echo json_encode(array('status' => 'error', 'message' => 'list id not found in the request.'));
            $this->_end_response();
        }

        $pluginAdminOptions = get_option($this->adminOptionsName);
        $oauth2TokensOptions = get_option($this->oauth2TokensOptions);
        $options = get_option($this->widgetOptionsName);
        $response = $this->getAWeberAccount($pluginAdminOptions, $oauth2TokensOptions);
        if (isset($response['account'])) {
            // Get the AWeber account reference
            $account = $response['account'];

            $page_url = '/accounts/' . $account->id . '/lists/' . $list_id . '/landing_pages';
            $landing_pages = $account->loadFromUrl($page_url);

            $pages = array();
            foreach ($landing_pages->data['entries'] as $page) {
                if ($page['status'] == 'published') {
                    $row = array(
                        'id'    => $page['id'],
                        'name'  => is_null($page['name']) ? 'Untitled Landing Page': $page['name'],
                        'preview'   => $page['published_url'],
                        'published_date'  => gmdate('D, M j, Y, g:i A T', strtotime($page['published_at'])),
                        'link_date' => 'NA',
                        'post_id'   => 0,
                        'page_title'=> '-',
                        'page_path' => '-',
                        'page_link' => '#'
                    );
                    $post = $this->get_linked_post($page['id']);
                    if ($post) {
                        $row['post_id'] = $post['post_id'];
                        $row['link_date'] = $post['synced_on'];
                        $row['page_title'] = $post['page_title'];
                        $row['page_path'] = $post['page_path'];
                        $row['page_link'] = get_permalink($post['post_id']);
                    }
                    array_push($pages, $row);
                }
            }
            if (empty($pages)){
                $response = array(
                    'status' => 'error',
                    'message' => 'You do not have any landing pages for the selected list.'
                );
            } else {
                $response = array('status' => 'success', 'data' => $pages);
            }

            $options['selected_landing_page_list_id'] = $list_id;
            update_option($this->widgetOptionsName, $options);
        }

        echo json_encode($response);
        $this->_end_response();
    }

    private function createCurrentPostCopy($post_id) {
        # Get the current post information.
        $post = (array) get_post($post_id);

        # returns an empty string when the value of the page_template is either empty or 'default'.
        $page_template = get_page_template_slug($post_id);
        if (!empty($page_template)) {
            $post['page_template'] = $page_template;
        }
        # Unset few columns.
        unset($post['ID']);
        unset($post['post_date_gmt']);
        unset($post['post_modified']);
        unset($post['post_modified_gmt']);
        unset($post['post_modified_gmt']);
        # So that, it will visible only to admin users.
        $post['post_status'] = 'private';
        $post['post_title'] = $post['post_title'] . ' (' . gmdate("c") . ')';

        // Insert the post into the database
        wp_insert_post($post);
    }

    public function loadLandingPageContent($post_id = NULL, $create_post_copy=True) {
        $nonce = isset($_REQUEST['nonce']) ? $_REQUEST['nonce'] : '';
        // createWordpressPage uses the aweber_create_page nonce and calls this current function.
        $valid_nonce = wp_verify_nonce($nonce, 'aweber_create_page') || wp_verify_nonce($nonce, 'aweber_link_page');
        if (!current_user_can('edit_pages') || !$valid_nonce) {
            wp_send_json_error('Unauthorized', 403);
        }

        if (empty($post_id)) {
            $post_id = sanitize_post_field('ID', $_POST['post_id'], $_POST['post_id'], 'db');
        }
        $landing_page_id = $_POST['landing_page_id'];

        $pluginAdminOptions = get_option($this->adminOptionsName);
        $options = get_option($this->widgetOptionsName);
        $oauth2TokensOptions = get_option($this->oauth2TokensOptions);

        $response = $this->getAWeberAccount($pluginAdminOptions, $oauth2TokensOptions);
        if (isset($response['account'])) {
            // Get the AWeber account reference
            $account = $response['account'];

            $list_id = $options['selected_landing_page_list_id'];
            $landing_page_url = '/accounts/' . $account->id . '/lists/' . $list_id . '/landing_pages/'.$landing_page_id;
            $landing_page_content = $account->loadFromUrl($landing_page_url);

            $content = $landing_page_content->data['published_html'];
            $content = preg_replace("/[\r\n\t]+/", "", $content);

            if ($create_post_copy) {
                # Create a copy of the current page, only if it is exisitng post.
                $this->createCurrentPostCopy($post_id);
            }
            # Update the Landing page content into the current post.
            $post_data = array(
                'ID'    => $post_id,
                'post_content'  => $content,
                'page_template' => 'aweber_landing_page'
            );
            $response = $this->updatePageData($landing_page_id, $post_data);
        }
        echo json_encode($response);
        $this->_end_response();
    }

    public function getPreviousPostContent($post_id) {
        $current_post = get_post($post_id);
        # Parse the URL and get the query param.
        $query_param = wp_parse_url($current_post->guid, PHP_URL_QUERY);
        global $wpdb;

        # By using the guid value from current_post, get the previous post_id.
        $previous_post = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}posts WHERE
                ID != %d AND post_status NOT IN ('trash', 'inherit') AND guid LIKE %s
                ORDER BY ID DESC LIMIT 1",
                [$post_id, '%?' . $query_param]
            )
        );
        if (empty($previous_post)) {
            return False;
        }
        # Get the first record and convert to array.
        $previous_post = (array) $previous_post[0];
        # returns an empty string when the value of the page_template is either empty or 'default'.
        $page_template = get_page_template_slug($previous_post['ID']);
        if (empty($page_template)) {
            $previous_post['page_template'] = 'default';
        } else{
            $previous_post['page_template'] = $page_template;
        }

        # Now delete the previous Post. True => Force delete
        # wp_delete_post($previous_post['ID'], true);

        # Return the content of the Previous post.
        return $previous_post;
    }

    private function validISO8601Date($matched) {
        # strtotime returns timestamp if valid date or else False.
        if (strtotime(trim($matched[0], '()'))) {
            return '';
        }
        return $matched[0];
    }

    public function unLinklandingPage() {
        $nonce = isset($_REQUEST['nonce']) ? $_REQUEST['nonce'] : '';
        if (!current_user_can('edit_pages') || !wp_verify_nonce($nonce, 'aweber_unlink_page')) {
            wp_send_json_error('Unauthorized', 403);
        }

        $post_id = sanitize_post_field('ID', $_POST['post_id'], $_POST['post_id'], 'db');
        $landing_page_id = $_POST['landing_page_id'];

        $links = get_option('aweber_landing_page_links');
        if (isset($links[$landing_page_id])) {
            # Get post content before linking to AWeber Landing Pages.
            $previous_post = $this->getPreviousPostContent($post_id);
            if (empty($previous_post)) {
                $post_data = array(
                    'post_content'  => '<div><h3 class="aweber-page-not-reverted">
                    The customer page could not be reverted</h3></div>',
                );
            } else {
                $title = preg_replace_callback("/\([^)]+\)/",
                            array($this, "validISO8601Date"), $previous_post['post_title']);
                $post_data = array(
        'post_author'   => $previous_post['post_author'],
                    'post_content'  => $previous_post['post_content'],
                    'page_template' => $previous_post['page_template'],
                    'post_title'    => $title,
                    'post_excerpt'  => $previous_post['post_excerpt'],
                    'comment_status'=> $previous_post['comment_status'],
                    'ping_status'   => $previous_post['ping_status'],
                    'post_password' => $previous_post['post_password'],
                    'to_ping'       => $previous_post['to_ping'],
                    'pinged'        => $previous_post['pinged'],
                    'post_content_filtered' => $previous_post['post_content_filtered'],
                    'post_parent'   => $previous_post['post_parent'],
                    'menu_order'    => $previous_post['menu_order'],
                    'post_mime_type'=> $previous_post['post_mime_type'],
                    'comment_count' => $previous_post['comment_count']
                );
            }
            $post_data['ID'] = $post_id;
            $response = $this->updatePageData($landing_page_id, $post_data);
            if ($response['status'] == 'success') {
                unset($links[$landing_page_id]);
                update_option('aweber_landing_page_links', $links);
            }
        }
        echo json_encode($response);
        $this->_end_response();
    }

    function getSignupWebformsList() {
        $nonce = isset($_REQUEST['nonce']) ? $_REQUEST['nonce'] : '';
        if (!current_user_can('manage_options') || !wp_verify_nonce($nonce, 'get_signup_webforms')) {
            wp_send_json_error('Unauthorized', 403);
        }

        $listId = $_GET['list_id'];
        $type    = $_GET['type'];

        if (empty($listId)) {
            echo json_encode(array('status' => 'error', 'message' => 'list id not found in the request.'));
            $this->_end_response();
        }

        $pluginAdminOptions = get_option($this->adminOptionsName);
        $options = get_option($this->widgetOptionsName);
        $oauth2TokensOptions = get_option($this->oauth2TokensOptions);

        // Fetch Aweber account, using OAuth1 or OAuth2.
        $response = $this->getAWeberAccount($pluginAdminOptions, $oauth2TokensOptions);
        if (!isset($response['account'])) {
            echo json_encode($response);
            $this->_end_response();
        }
        // Get the AWeber account reference
        $account = $response['account'];

        $shortcodes = $this->getShortCodeFromSite();
        $getWidgetSidebarName = $this->getWidgetSidebarName($this->widgetOptionsName);

        $listWebForms = array();
        $activeWebformId = NULL;
        if (!empty($options['webform'])) {
            $activeWebformId = explode('/', $options['webform'])[6];
        }
        if ($type == 'sigup-form') {
            foreach ($account->getWebFormsForList($listId) as $webform) {
                $tagLists = $webform->tags;
                if(!empty($tagLists)){
                    $tagLists = implode(", ", iterator_to_array($tagLists));
                }
                $conPerc = number_format((float)$webform->conversion_percentage, 2, '.', '');

                $currentWebformId = explode('/', $webform->url)[6];
                $widgetLocations = array();
                if ($activeWebformId == $currentWebformId && $getWidgetSidebarName != '-') {
                    array_push($widgetLocations, array(
                        'type'  => 'Widget',
                        'title' => $getWidgetSidebarName,
                        'link'  => FALSE
                    ));
                }
                if (array_key_exists($currentWebformId, $shortcodes)) {
                    if (!empty($widgetLocations)) {
                        array_push($shortcodes[$currentWebformId]['areas'], $widgetLocations[0]);
                    }
                    $widgetLocations = $shortcodes[$currentWebformId]['areas'];
                }

                array_push($listWebForms, array(
                    'name'  => $webform->name,
                    'tags'  => $tagLists,
                    'type'  => $webform->type,
                    'preview_form'  => $webform->html_source_link,
                    'displays'      => $webform->total_displays,
                    'submissions'   => $webform->total_submissions,
                    'conversion_rate' => $conPerc.'%',
                    'shortcode' => '[aweber listid="'.$listId.'" formid="'.$currentWebformId.'" formtype="webform"]',
                    'location'  => json_encode($widgetLocations)
                ));
            }
            $options['selected_signup_form_list_id'] = $listId;
            update_option($this->widgetOptionsName, $options);
        } elseif ($type == 'split-test') {
            foreach ($account->getWebFormSplitTestsForList($listId) as $webform) {
                $components = $account->loadFromUrl($webform->data['components_collection_link']);
                $testWebforms = array();

                $currentWebformId = explode('/', $webform->url)[6];
                $widgetLocations = array();
                if ($activeWebformId == $currentWebformId && $getWidgetSidebarName != '-') {
                    array_push($widgetLocations, array(
                        'type'  => 'Widget',
                        'title' => $getWidgetSidebarName,
                        'link'  => FALSE
                    ));
                }
                if (array_key_exists($currentWebformId, $shortcodes)) {
                    if (!empty($widgetLocations)) {
                        array_push($shortcodes[$currentWebformId]['areas'], $widgetLocations[0]);
                    }
                    $widgetLocations = $shortcodes[$currentWebformId]['areas'];
                }

                foreach ($components as $component) {
                    $tagLists = $component->tags;
                    if(!empty($tagLists)){
                        $tagLists = implode(", ", iterator_to_array($tagLists));
                    }
                    $s_d = '0.00';
                    $totalDisplays = $component->total_displays;
                    $totalSubmissions = $component->total_submissions;
                    $uniqueDisplays = $component->total_unique_displays;
                    if (!empty(($totalDisplays)) && !empty(($totalSubmissions))) {
                        $s_d = number_format(100 * ($totalSubmissions / $totalDisplays), 2, '.', '');
                    }

                    $s_ud = '0.00';
                    if (!empty(($totalDisplays)) && !empty(($uniqueDisplays))) {
                        $s_ud = number_format(100 * ($totalSubmissions / $uniqueDisplays), 2, '.', '');
                    }
                    array_push($testWebforms, array(
                        'sign_up_form'  => $component->name,
                        'tags'          => $tagLists,
                        'probability'   => $component->weight.'%',
                        'displays'      => $component->total_displays,
                        'subscribers'   => $component->total_submissions,
                        's_d'           => $s_d.'%',
                        'unique_displays' => $component->total_unique_displays,
                        's_ud'          => $s_ud.'%'
                    ));
                }
                array_push($listWebForms, array(
                    'test_name' => $webform->name,
                    'size'      => $components->total_size,
                    'location' => json_encode($widgetLocations),
                    'shortcode' => '[aweber listid="'.$listId.'" formid="'.$currentWebformId.'" formtype="split_tests"]',
                    'webform_split_tests'  => $testWebforms
                ));
            }
            $options['selected_split_test_form_list_id'] = $listId;
            update_option($this->widgetOptionsName, $options);

            usort($listWebForms, function($a, $b) {
                return strcmp($a['test_name'], $b['test_name']);
            });
        }

        if (empty($listWebForms)):
            $response = array(
                'status' => 'error',
                'message' => 'You do not have any Sign Up Forms for the selected list.'
            );
        else:
            $response = array('status' => 'success', 'data' => $listWebForms);
        endif;

        echo json_encode($response);
        $this->_end_response();
    }

    public function getAWeberWebformShortCodes(){
        $nonce = isset($_REQUEST['nonce']) ? $_REQUEST['nonce'] : '';
        $is_capable = current_user_can('edit_posts') || current_user_can('edit_pages');
        if (!$is_capable || !wp_verify_nonce($nonce, 'get_aweber_webform_shortcodes')) {
            wp_send_json_error('Unauthorized', 403);
        }

        $pluginAdminOptions = get_option($this->adminOptionsName);
        $options = get_option($this->widgetOptionsName);
        $oauth2TokensOptions = get_option($this->oauth2TokensOptions);

        // Fetch Aweber account, using OAuth1 or OAuth2.
        $response = $this->getAWeberAccount($pluginAdminOptions, $oauth2TokensOptions);
        if (!isset($response['account'])) {
            echo json_encode($response);
            $this->_end_response();
        }
        // Get AWeber account reference
        $account = $response['account'];

        $lists = array();
        foreach ($account->lists as $list) {
            $lists[$list->id] = $list->name;
        }

        $list_of_shortcodes = array();
        foreach ($account->getWebForms() as $webform) {
            $list_id = explode('/', $webform->url)[4];
            if (array_key_exists($list_id, $lists)) {
                array_push($list_of_shortcodes, array(
                    'text'      => $webform->name,
                    'list_id'   => $list_id,
                    'list_name' => $lists[$list_id],
                    'value' => $list_id . '-' . explode('/', $webform->url)[6] . '-webform'
                ));
            }
        }
        foreach ($account->getWebFormSplitTests() as $webform) {
            $list_id = explode('/', $webform->url)[4];
            if (array_key_exists($list_id, $lists)) {
                array_push($list_of_shortcodes, array(
                    'text'      => $webform->name,
                    'list_id'   => $list_id,
                    'list_name' => $lists[$list_id],
                    'value' => $list_id . '-' . explode('/', $webform->url)[6] . '-split_tests'
                ));
            }
        }

        if (empty($list_of_shortcodes)):
            $response = array('status' => 'error', 'message' => 'No short codes found for the selected list.');
        else:
            usort($list_of_shortcodes, function($a, $b){
                return $b['list_id'] - $a['list_id'];
            });

            $response = array('status' => 'success', 'short_codes' => $list_of_shortcodes);
        endif;

        echo json_encode($response);
        $this->_end_response();
    }

    private function getWidgetSidebarName($widget_name) {
        $widget_sidebar_name = '-';
        foreach (wp_get_sidebars_widgets() as $key => $value) {
            if (!empty($value) && in_array(strtolower($widget_name), $value)) {
                global $wp_registered_sidebars;

                foreach ($wp_registered_sidebars as $sidebars) {
                    if (!empty($sidebars['id']) && $sidebars['id'] == $key) {
                        $widget_sidebar_name = $sidebars['name'];
                        break;
                    }
                }
            }
        }
        return $widget_sidebar_name;
    }

    /**
        * Print error message in the browser.
        *
        * Echo the HTML with the respective error code and error message.
        * @return void
        */
    function displayCustomErrorMessages($response_error_code = '', $response_error_message = '', $return_message = 0) {
        $kb_link = "https://help.aweber.com/hc/en-us/articles/204027976-How-Do-I-Use-AWeber-s-Webform-Widget-For-Wordpress-";
        $message = '';
        switch ($response_error_code) {
            case '400':
                // Suppressed the error from the users.
                if ($response_error_message == 'invalid_request') {
                    // True, means it OAuth2 400 error occured.
                    // When the user disconnect the Wordpress integration in his account.
                    $message = 'OAuth2 Authorization not granted. Please <a href="' . admin_url('admin.php?page=aweber.php&reauth=true') . '">click here</a> to reauthorize.';
                    // Update the error in the table.
                    update_option('aweber_oauth_error', $message);
                }
                break;

            case '401':
                // Trim the whitespaces at the end.
                $response_error_message = trim($response_error_message);
                // Trim the dot at the end. So that the dot will not be duplicated in the message.
                $response_error_message = trim($response_error_message, '.');

                $message = 'Authorization not granted. (401) ' . $response_error_message . '. Please <a href="' . admin_url('admin.php?page=aweber.php&reauth=true') . '">click here</a> to reauthorize.';
                // Update the error in the tale.
                update_option('aweber_oauth_error', $message);
                if (stripos($message, 'expired timestamp') !== false) {
                    $message = 'An unexpected error occurred. 
                        The clock time on your server doesn\'t appear to be the current time.';
                }
                break;
            
            case '403':
                $message = 'An unexpected error occurred: (403) ' . $response_error_message;
                if (stripos($response_error_message, 'suspended') !== false || stripos($response_error_message, 'hold') !== false) {
                    $message = 'Your AWeber account is not active or temporarily suspended.';
                } else if (stripos($response_error_message, 'at this time') !== false) {
                    // Suppressed the error from the users.
                    $message = '';
                }
                break;

            default:
                $message = "An unexpected error occurred. (".$response_error_code.") " . $response_error_message;
                if (empty($response_error_code) && stripos($response_error_message, 'Authorization code entered') !== false) {
                    $message = $response_error_message;
                } else if (empty($response_error_code) || stripos($response_error_message, 'Connection time') !== false || stripos($response_error_message, 'http_request_failed') !== false) {
                    $message = 'An unexpected error occurred. Please check your internet connection and try again.';
                }
                break;
        }
        if ($return_message) {
            return $message;
        }

        $message = $message . '<br><br> Please refer to the <a target="_blank" href="' . $kb_link . '">knowledge base
                </a> for assistance or email us at <a href="mailto:help@aweber.com">help@aweber.com</a>';
        $this->add_alert_message_html('negative', $message, 'aweber-body-wrapper');
    }

    private function getAWeberWPNOption($aweber_web_push_list_id) {
        // Get the Web Push Notification Options.
        $aweber_wpn_details = get_option($this->webPushOptionsName);
        // As a back support, Try to get the web push notification info.
        if (empty($aweber_wpn_details)):
            $pluginAdminOptions = get_option($this->adminOptionsName);
            if(isset($pluginAdminOptions['access_key'])):
                $response = $this->getAWeberAccount($pluginAdminOptions);
                if (isset($response['account'])):
                    $account = $response['account'];
                    foreach ($account->lists as $list):
                        if ($aweber_web_push_list_id == $list->id):
                            $aweber_wpn_details = array(
                                'account_uuid'    => $account->uuid,
                                'list_id'       => $list->id,
                                'list_uuid'     => $list->uuid,
                                'vapid_public_key' => $list->vapid_public_key
                            );
                            // Save the information in the below option name
                            update_option($this->webPushOptionsName, $aweber_wpn_details);
                            break;
                        endif;
                    endforeach;
                endif;
            endif;
        endif;
        return $aweber_wpn_details;
    }

    /**
     * Fetches WPN details from DB or API, update the WPN Snippet
     */
    public function printAWeberWPNSnippet() {
        $widget_options = get_option($this->widgetOptionsName);
        $register_aweber_service_worker = false;

        if ($widget_options['aweber_web_push_listid'] != 'FALSE') {
            $aweber_wpn_details = $this->getAWeberWPNOption($widget_options['aweber_web_push_listid']);
            if (!empty($aweber_wpn_details)) {
                // Enable registering the AWeber Service Worker
                $register_aweber_service_worker = true;
            }
        }
        // Register/Unregister the AWeber Service Worker
        wp_enqueue_script( 'aweber-wpn-script-handle', plugins_url('../src/js/aweber-wpn-script.js', __FILE__), [], AWEBER_PLUGIN_VERSION, false);
        wp_localize_script( 'aweber-wpn-script-handle', 'aweber_wpn_vars', array(
            'plugin_base_path' => esc_html(plugin_dir_url(__FILE__)),
            'register_aweber_service_worker' => esc_html($register_aweber_service_worker)
        ));
        if ($register_aweber_service_worker):
            // AWeber Web Push Notification Snippet
            wp_enqueue_script( 'aweber-wpn-static-assests', 'https://assets.aweber-static.com/aweberjs/aweber.js' ); ?>
            <script>
                var AWeber = window.AWeber || [];
                AWeber.push(function() {
                    AWeber.WebPush.init(
                        '<?php echo esc_html($aweber_wpn_details["vapid_public_key"]) ?>',
                        '<?php echo esc_html($aweber_wpn_details["account_uuid"]) ?>',
                        '<?php echo esc_html($aweber_wpn_details["list_uuid"]) ?>',
                    );
                });
            </script>
        <?php endif;
    }

    /**
        * Print widget in sidepanel.
        *
        * Echo the HTML for the widget handle.
        * @return void
        */
    function printWidget($args) {
        extract($args, EXTR_SKIP);

        if (isset($before_widget) && !empty($before_widget)) {
            echo wp_kses_post($before_widget);
        }

        if (isset($before_title) && !empty($before_title)) {
            echo wp_kses_post($before_title);
        }
        if (isset($title) && !empty($title)) {
            echo wp_kses_post($title);
        }
        if (isset($after_title) && !empty($after_title)) {
            echo wp_kses_post($after_title);
        }

        echo wp_kses_post('<!-- AWeber for WordPress ' . AWEBER_PLUGIN_VERSION . ' -->' . $this->getWebformSnippet());

        if (isset($after_widget) && !empty($after_widget)) {
            echo wp_kses_post($after_widget);
        }
    }

    /**
        * Get a new AWeber API object
        *
        * Wrapper for AWeber API generation
        * @return AWeberAPI
        */
    function _get_aweber_api($consumer_key, $consumer_secret) {
        return new AWeberAPI($consumer_key, $consumer_secret);
    }

    public function getAWeberOAuth2API() {
        $oauth2TokensOptions = get_option($this->oauth2TokensOptions);
        if (isset($oauth2TokensOptions['access_token'])) {
            return new AWeberOAuth2API(
                $oauth2TokensOptions['access_token'],
                $oauth2TokensOptions['refresh_token'],
                $oauth2TokensOptions['expires_on']
            );
        }
        return new AWeberOAuth2API();
    }

    public function doAWeberTokenExists($pluginAdminOptions, $oauth2TokensOptions) {
        if (
            isset($pluginAdminOptions['access_key'])
            || isset($oauth2TokensOptions['access_token'])
        ) {
            return True;
        }
        return False;
    }

    public function generateAccessToken($authorizeCode) {
        $aweberOAuth2 = $this->getAWeberOAuth2API();
        $response = $aweberOAuth2->generateAccessToken($authorizeCode);
        if (isset($response['access_token'])) {
            // Retrieved the access token successfully. Store in the DB.
            update_option($this->oauth2TokensOptions, $response);
        }
        return $response;
    }

    public function revokeAccessToken() {
        $aweber = $this->getAWeberOAuth2API();
        // Revoke the access token from AWeber
        $aweber->revokeAccessToken();
        // Remove the tokens from the Database.
        delete_option($this->oauth2TokensOptions);
        delete_option($this->oauth2AuthorizedOptions);
    }

    public function getAWeberConnection() {
        $adminOptions = get_option($this->adminOptionsName);
        if (isset($adminOptions['access_key'])) {
            // Get the OAuth2 account.
            $account = $this->getAWeberAccount($adminOptions);
        } else {
            // Get OAuth2 account
            $aweber = $this->getAWeberOAuth2API();
            $account = $aweber->getAccount();

            $message = 'Please reconnect your AWeber account.';
            if (isset($account['message']) and $account['error_code'] != '401') {
                $account['message'] = $this->displayCustomErrorMessages($account['error_code'], $account['message'], 1);
            }
            if (strrpos($account['error_'], 'AWeberTokenRefreshException')  !== false or 
                    strrpos($account['error_'], 'AWeberTokenRefreshException') !== false) {
                // Remove the existing connection and try connection to AWeber Again.
                $this->deauthorize();
            }
        }
        return $account;
    }

    public function getAWeberAPI() {
        $adminOptions = get_option($this->adminOptionsName);
        if (isset($adminOptions['access_key'])) {
            return $this->_get_aweber_api($adminOptions['consumer_key'], $adminOptions['consumer_secret']);
        }
        return $this->getAWeberOAuth2API();
    }

    public function handleOAuth2Exception($exc, $dontshowError=0) {
        $error_ = get_class($exc);
        $error_code = $exc->status;
        $description = $exc->getMessage();
        if (stripos($description, 'labs.aweber.com') !== false) {
            // strip labs.aweber.com documentation url from error message
            $description = preg_replace('/http.*$/i', '', $description);
        }

        if (stripos($error_, 'AWeberOAuth')  !== false or
                stripos($error_, 'AWeberTokenRefreshException') !== false) {
            // if the excpetion because of OAuth1 or OAuth2, then Deauthorize
            $this->deauthorize();
        }
        // Output the error message.
        return $this->displayCustomErrorMessages($error_code, $description, $dontshowError);
    }

    function getFormSnippet($account, $options) {
        $this_form = $account->loadFromUrl($options['webform']);
        $attrs = $this_form->attrs();
        if (isset($attrs['components'])) {
            $components = $account->loadFromUrl($this_form->components_collection_link);
            $componentIds = array();
            $options['form_snippet'] = '';
            foreach ($components as $component) {
                $component_form = $account->loadFromUrl($component->web_form_link);
                $componentIds[] = $component_form->id;
                $options['form_snippet'] .= '<div class="AW-Form-'.$component_form->id.'" style="display: none;"></div>';
            };
            $options['form_snippet'] .= '
                <script type="text/javascript">(function(d,s,id) {
                    var js;
                    var fjs = d.getElementsByTagName(s)[0];
                    var cids = [' . implode(',', $componentIds) .'];
                    var mos = [];
                    cids.forEach(function(cid) {
                        mo = new MutationObserver(function(m) {
                            m[0].target.style = "";
                            mos.forEach(function(omo) { omo.disconnect(); });
                        });
                        mo.observe(d.getElementsByClassName("AW-Form-"+cid)[0], {childList: true});
                        mos.push(mo);
                    });
                    if (d.getElementById(id)) return; js = d.createElement(s);
                    js.id = id; js.src = "'.$this->_getWebformJsUrl($this_form).'";
                    fjs.parentNode.insertBefore(js, fjs);
                    }(document, "script", "aweber-wjs-'.(string)wp_rand().'"));
                </script>';
        } else {
            $options['form_snippet'] = '<div class="AW-Form-' . $this_form->id . '"></div>
                <script type="text/javascript">(function(d,s,id) {
                    var js;
                    var fjs = d.getElementsByTagName(s)[0];
                    if (d.getElementById(id)) return; js = d.createElement(s);
                    js.id = id; js.src = "' . $this->_getWebformJsUrl($this_form) . '";
                    fjs.parentNode.insertBefore(js, fjs);
                    }(document, "script", "aweber-wjs-' . (string)wp_rand() . '"));
                </script>';
        }
        return $options;
    }

    function updateWebFormSnippet($options) {
        if (!empty($options['webform'])) {
            $admin_options = get_option($this->adminOptionsName);
            $oauth2TokensOptions = get_option($this->oauth2TokensOptions);

            // Fetch Aweber account, using OAuth1 or OAuth2.
            $response = $this->getAWeberAccount($pluginAdminOptions, $oauth2TokensOptions);
            if (isset($response['account'])) {
                $options = $this->getFormSnippet($response['account'], $options);
            }
        } else {
            $options['form_snippet'] = '';
        }
        update_option($this->widgetOptionsName, $options);
    }

    /**
        * Print widget settings control in admin panel.
        *
        * Store settings and echo HTML for the widget control.
        * @return void
        */
    function printWidgetControl() {
        if (isset($_POST[$this->widgetOptionsName])) {
            $options = get_option($this->widgetOptionsName);
            $widget_data = $_POST[$this->widgetOptionsName];
            if (isset($widget_data['submit']) && $widget_data['submit']) {
                $options['list'] = $widget_data['list'];
                $options['webform'] = $widget_data[$widget_data['list']]['webform'];

                $this->updateWebFormSnippet($options);
            }
        } elseif(class_exists('WP_Block_Editor_Context') &&
                    (
                        stripos($_SERVER['REQUEST_URI'], strtolower($this->widgetOptionsName)) !== false
                        || (isset($_GET['rest_route']) && stripos($_GET['rest_route'], strtolower($this->widgetOptionsName)) !== false))
                    ) {
            echo "<p class='aweber-legacy-message' style='font-size: 12px;'>You are using our legacy WordPress widget. Please remove and re-add the widget.</p>";
        } else {
            ?>
            <div 
                id="<?php echo esc_attr($this->widgetOptionsName . '-content'); ?>" 
                class="<?php echo esc_attr($this->widgetOptionsName . '-content'); ?>">
                <img src="images/loading.gif" height="16" width="16" id="aweber-webform-loading" style="float: left; padding-right: 5px" />
                 Loading...
            </div>
            <script type="text/javascript">
            jQuery(document).ready(function($) {
                if (typeof(<?php echo esc_html($this->widgetOptionsName); ?>) != 'undefined') { return; }
                <?php echo esc_html($this->widgetOptionsName); ?> = true;

                var data = {
                    action: 'get_widget_control'
                    nonce: '<?php echo esc_html(wp_create_nonce('get_widget_control')); ?>'
                };

                // since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
                jQuery.post(ajaxurl, data, function(response) {
                    var primary_content = jQuery('.<?php echo esc_attr($this->widgetOptionsName . '-content'); ?>');
                    primary_content.each(function() { jQuery(this).html(response); });
                });
            });
            </script>
            <?php
        }
    }

    /**
        * Get Web Form javascript url
        *
        * Returns hosted javascript url of a given form.
        * @param AWeberEntry
        * @return string
        */
    function _getWebformJsUrl($webform) {
        $form_hash = $webform->id % 100;
        $form_hash = (($form_hash < 10) ? '0' : '') . $form_hash;
        $prefix = ($this->_isSplitTest($webform)) ? 'split_' : '';
        return 'https://forms.aweber.com/form/' . $form_hash . '/' . $prefix . $webform->id . '.js';
    }

    function reloadWidgetWebForm() {
        $nonce = isset($_REQUEST['nonce']) ? $_REQUEST['nonce'] : '';
        if (!current_user_can('manage_options') || !wp_verify_nonce($nonce, 'reload_aweber_cache')) {
            wp_send_json_error('Unauthorized', 403);
        }

        if (isset($_GET[$this->widgetOptionsName])) {
            $options = get_option($this->widgetOptionsName);

            $this->updateWebFormSnippet($options);
            $response = array('status' => 'success', 'message' => 'Cache reloaded successfully');
        } else {
            $response = array('status' => 'error', 'message' => 'You cannot access the API directly.');
        }
        echo json_encode($response);
        $this->_end_response();
    }

    function getAWeberAccount($pluginAdminOptions, $oauth2TokensOptions){
        // Check if the token exists, if not return the error message.
        if (!$this->doAWeberTokenExists($pluginAdminOptions, $oauth2TokensOptions)) {
            return array(
                'status' => 'error',
                'message' => 'Please reconnect your AWeber account.'
            );
        }

        $errorCode = $description = '';
        try {
            $aweber = $this->getAWeberAPI();
            // Get the active OAuth1 or OAuth2 connection.
            if (get_class($aweber) == 'AWeberWebFormPluginNamespace\AWeberOAuth2API') {
                $account = $aweber->getAccount();
            } else {
                $account = $aweber->getAccount(
                    $pluginAdminOptions['access_key'], $pluginAdminOptions['access_secret']);
            }
            // API is called, just to make sure that the connection with the AWeber exists.
            $webforms = $account->getWebForms();
        } catch (AWeberException $exc) {
            // Exception raised while getting the AWeber account.
            $errorCode = $exc->status;
            $description = $this->handleOAuth2Exception($exc, 1);
            $account = null;
        } catch (\Exception $exc) {
            $description = $exc->getMessage();
            $account = null;
        } catch (\Throwable $exc) {
            $description = $exc->getMessage();
            $account = null;
        }

        if (!$account) {
            // if its an OAuth2 error. then only display reconnect message.
            // When the OAuth2 connection is disconnected on the AWeber account.
            // Then the getAccount returns 400 (invalid_request)
            if ($errorCode == '401' || $errorCode == '400'):
                $description = 'Please reconnect your AWeber Account';
            endif;
            return array(
                'status' => 'error',
                'redirect' => admin_url('admin.php?page=aweber.php'),
                'message' => $description,
                'error_code' => $errorCode
            );
        }
        return array('account' => $account);
    }

    public function getAweberLists() {
        $nonce = isset($_REQUEST['nonce']) ? $_REQUEST['nonce'] : '';
        $is_capable = current_user_can('edit_posts') || current_user_can('edit_pages');
        $valid_nonce = wp_verify_nonce($nonce, 'get_aweber_lists') || wp_verify_nonce($nonce, 'get_aweber_lists_for_elementor');
        if (!$is_capable || !$valid_nonce) {
            wp_send_json_error('Unauthorized', 403);
        }

        $pluginAdminOptions = get_option($this->adminOptionsName);
        $oauth2TokensOptions = get_option($this->oauth2TokensOptions);

        $response = $this->getAWeberAccount($pluginAdminOptions, $oauth2TokensOptions);
        if (isset($response['account'])) {
            // Get the AWeber account reference
            $account = $response['account'];

            $lists = array();
            foreach ($account->lists as $list) {
                array_push($lists, array('list_id'  => $list->id, 'list_name' => $list->name));
            }
            $response = array('status' => 'success', 'lists' => $lists);
        }

        echo json_encode($response);
        $this->_end_response();
    }

    public function getWebPushLists($account_uuid, $lists, $selectedListId, $formSubmitted=False) {
        $webPushLists = array();
        foreach ($lists as $list) {
            if ($list->vapid_public_key) {
                array_push($webPushLists, array(
                    'id'    => $list->id,
                    'name'  => $list->name,
                    'vapid_public_key'  => $list->vapid_public_key
                ));
                // $formSubmitted = True, means form got submitted and info saved.
                // Fetch the list and account details and store in the db.
                if ($formSubmitted) {
                    if ($selectedListId == $list->id) {
                        $web_push_details = array(
                            'account_uuid'  => $account_uuid,
                            'list_id'       => $list->id,
                            'list_uuid'     => $list->uuid,
                            'vapid_public_key' => $list->vapid_public_key
                        );
                        // Save the information in the below option name
                        update_option($this->webPushOptionsName, $web_push_details);
                    }
                }
            }
        }
        return $webPushLists;
    }

    public function updateAnalyticsURL($options, $analyticsSrc, $formSubmitted) {
        if ($formSubmitted) {
            if ($options['aweber_add_analytics_checkbox'] == 'ON') {
                $options['aweber_analytics_src'] = $analyticsSrc;
            } else {
                $options['aweber_analytics_src'] = null;
            }
            update_option($this->widgetOptionsName, $options);
        }
    }

    function getAWeberCustomFields() {
        $nonce = isset($_REQUEST['nonce']) ? $_REQUEST['nonce'] : '';
        $is_capable = current_user_can('edit_posts') || current_user_can('edit_pages');
        if (!$is_capable || !wp_verify_nonce($nonce, 'get_aweber_custom_fields')) {
            wp_send_json_error('Unauthorized', 403);
        }

        $response = array('status' => 'error', 'message' => 'Please reconnect your AWeber account.');

        $pluginAdminOptions = get_option($this->adminOptionsName);
        $oauth2TokensOptions = get_option($this->oauth2TokensOptions);
        // Fetch Aweber account, using OAuth1 or OAuth2.
        $response = $this->getAWeberAccount($pluginAdminOptions, $oauth2TokensOptions);
        if (isset($response['account'])) {
            // Get AWeber accout reference
            $account = $response['account'];
            $fields = array();

            $custom_field_url = '/accounts/' . $account->id . '/lists/' . $_GET['list_id'] . '/custom_fields';
            $custom_fields = $account->loadFromUrl($custom_field_url);
            foreach ($custom_fields->data['entries'] as $field) {
                array_push($fields, $field['name']);
            }
            $response = array('status' => 'success', 'custom_fields' => $fields);
        }
        echo json_encode($response);
        $this->_end_response();
    }

    function aweberShortcodeHandler($attr) {
        if (empty($attr['formid'])) {
            return "Form Id not found. Please copy paste the correct shortcode text.";
        }
        if (empty($attr['listid'])) {
            return "List Id not found. Please copy paste the correct shortcode text.";
        }
        if (empty($attr['formtype']) || ($attr['formtype'] != 'webform' && $attr['formtype'] != 'split_tests')) {
            return "Form Type not found. Please copy paste the correct shortcode text.";
        }

        $option_name = $attr['listid'] . '-' . $attr['formid'];
        $options = get_option($option_name);
        if (empty($options['form_snippet'])) {
            $admin_options = get_option($this->adminOptionsName);
            $oauth2TokensOptions = get_option($this->oauth2TokensOptions);

            // Fetch Aweber account, using OAuth1 or OAuth2.
            $response = $this->getAWeberAccount($admin_options, $oauth2TokensOptions);
            if (!isset($response['account'])) {
                return $this->messages['auth_error'];
            }
            // Get the AWeber account reference.
            $account = $response['account'];

            $webform = '/accounts/' . $account->id . '/lists/' . $attr['listid'] . '/web_forms/' . $attr['formid'];
            if ($attr['formtype'] == 'split_tests') {
                $webform = '/accounts/' . $account->id . '/lists/' . $attr['listid'] . '/web_form_split_tests/' . $attr['formid'];
            }

            $options = array(
                'list'      => $attr['listid'],
                'webform'   => $webform,
                'form_snippet'  => ''
            );

            try{
                $options = $this->getFormSnippet($account, $options);
            } catch (AWeberAPIException $e) {
                return "AWeber Exception Occurred: " . $e->type;
            }
            update_option($option_name, $options);
        }

        return '<!-- AWeber for WordPress ' . AWEBER_PLUGIN_VERSION . ' -->' . $options['form_snippet'];
    }

    /**
        * Is a split test?
        *
        * Returns whether form object is a splittest.
        * @param AWeberEntry
        * @return bool
        */
    function _isSplitTest($webform) {
        return $webform->type == 'web_form_split_test';
    }

    function _end_response() {
        die();
    }

    /**
        * Response to be given to print action via AJAX.
        *
        * Echo HTML for widget control form asynchronously.
        * @return void
        */
    function printWidgetControlAjax() {
        $nonce = isset($_REQUEST['nonce']) ? $_REQUEST['nonce'] : '';
        $is_capable = current_user_can('edit_posts') || current_user_can('edit_pages');
        if (!$is_capable || !wp_verify_nonce($nonce, 'get_widget_control')) {
            wp_send_json_error('Unauthorized', 403);
        }

        $options = get_option($this->widgetOptionsName);
        $admin_options = get_option($this->adminOptionsName);
        $oauth2TokensOptions = get_option($this->oauth2TokensOptions);

        // Render form
        $list = $options['list'];
        $webform = $options['webform'];

        // Fetch Aweber account, using OAuth1 or OAuth2.
        $response = $this->getAWeberAccount($admin_options, $oauth2TokensOptions);
        if (!isset($response['account'])) {
            echo wp_kses_post($this->messages['auth_error']);
            return $this->_end_response();
        }
        // Get the AWeber account reference.
        $account = $response['account'];

        $list_web_forms = array();
        foreach ($account->getWebForms() as $this_webform) {
            $link_parts = explode('/', $this_webform->url);
            $list_id = $link_parts[4];
            if (!array_key_exists($list_id, $list_web_forms)) {
                $list_web_forms[$list_id] = array(
                    'web_forms' => array(),
                    'split_tests' => array()
                );
            }
            $list_web_forms[$list_id]['web_forms'][] = $this_webform;
        }
        foreach ($account->getWebFormSplitTests() as $this_webform) {
            $link_parts = explode('/', $this_webform->url);
            $list_id = $link_parts[4];
            if (!array_key_exists($list_id, $list_web_forms)) {
                $list_web_forms[$list_id] = array(
                    'web_forms' => array(),
                    'split_tests' => array()
                );
            }
            $list_web_forms[$list_id]['split_tests'][] = $this_webform;
        }
        $lists = $account->lists;
        foreach ($lists as $this_list) {
            if (array_key_exists($this_list->id, $list_web_forms)) {
                $list_web_forms[$this_list->id]['list'] = $this_list;
            }
        }

        // The HTML form will go here
?>
<?php if (!empty($list_web_forms)): ?>
<select 
    class="widefat <?php echo esc_attr($this->widgetOptionsName . '-list'); ?>" 
    name="<?php echo esc_attr($this->widgetOptionsName . '[list]'); ?>" 
    id="<?php echo esc_attr($this->widgetOptionsName . '-list'); ?>" 
    style="margin-top: 13px; margin-bottom: 13px;">
    <option value="">Step 1: Select A List</option>
    <?php foreach ($list_web_forms as $this_list_data): ?>
    <?php $this_list = $this_list_data['list']; ?>
    <option value="<?php echo esc_html($this_list->id); ?>"<?php echo ($this_list->id == $list) ? ' selected="selected"' : ""; ?>>
        <?php echo esc_html($this_list->name); ?>
    </option>
    <?php endforeach; ?>
</select>

<?php foreach ($list_web_forms as $this_list_id => $forms): ?>
<select 
    class="widefat <?php echo esc_attr($this->widgetOptionsName . '-form-select'); ?> <?php echo esc_html($this->widgetOptionsName); ?>-<?php echo esc_html($this_list_id); ?>-webform" 
    name="<?php echo esc_html($this->widgetOptionsName); ?>[<?php echo esc_html($this_list_id); ?>][webform]" 
    id="<?php echo esc_html($this->widgetOptionsName); ?>-<?php echo esc_html($this_list_id); ?>-webform" 
    style="margin-bottom: 13px;">
    <option value="">Step 2: Select A Sign Up Form</option>
    <?php foreach ($forms['web_forms'] as $this_form): ?>
        <option value="<?php echo esc_url($this_form->url); ?>"<?php echo ($this_form->url == $webform) ? ' selected="selected"' : ''; ?>>
            <?php echo esc_html($this_form->name); ?>
        </option>
    <?php endforeach; ?>
    <?php foreach ($forms['split_tests'] as $this_form): ?>
        <option value="<?php echo esc_url($this_form->url); ?>"<?php echo ($this_form->url == $webform) ? ' selected="selected"' : ''; ?>>
            Split test: <?php echo esc_html($this_form->name); ?>
        </option>
    <?php endforeach; ?>
</select>
<?php endforeach; ?>

<input type="hidden"
    name="<?php echo esc_html($this->widgetOptionsName); ?>[submit]"
    value="1"/>

<div style="margin-bottom: 13px;">
<a id="<?php echo esc_html($this->widgetOptionsName); ?>-form-preview" class="<?php echo esc_html($this->widgetOptionsName); ?>-form-preview" href="#" target="_blank">Preview form</a>
</div>
<?php else: ?>
This AWeber account does not currently have any completed Sign Up Forms.
<div style="margin-top: 13px; margin-bottom: 13px;">
    Please <a href="https://www.aweber.com/users/web_forms/index">create a web
    form</a> in order to place it on your Wordpress blog.
</div>
<?php endif; ?>
<script type="text/javascript">
    jQuery(document).ready(function() {
        function hideFormSelectors() {
            jQuery('.<?php echo esc_attr($this->widgetOptionsName . '-form-select'); ?>').each(function() {
                jQuery(this).hide();
            });
        }

        function listDropDown() {
            return jQuery('.<?php echo esc_attr($this->widgetOptionsName . '-list'); ?>');
        }

        function currentFormDropDown() {
            var list;
            listDropDown().each(function() {
                list = jQuery(this).val();
            });
            if (list != "") {
                return jQuery('.<?php echo esc_html($this->widgetOptionsName); ?>'. '-' + list + '-webform');
            }
            return undefined;
        }

        function updateViewableFormSelector() {
            hideFormSelectors();
            var dropdown = currentFormDropDown();
            if (dropdown != undefined) {
                dropdown.each(function() {
                    jQuery(this).show();
                });
            }
        }

        function updatePreviewLink() {
            var form_url = "";
            var preview = jQuery('.<?php echo esc_attr($this->widgetOptionsName . '-form-preview'); ?>');
            var form_dropdown = currentFormDropDown();
            if (form_dropdown != undefined) {
                form_dropdown.each(function() {
                    form_url = jQuery(this).val();
                });
            }
            if (form_url == "") {
                preview.each(function() {
                    jQuery(this).attr('href', '#');
                    jQuery(this).hide();
                });
            } else {
                form_url = form_url.split('/');
                var form_id = form_url.pop();
                var form_type = form_url.pop();
                if (form_type == 'web_form_split_tests') {
                    preview.each(function() {
                        jQuery(this).attr('href', '#');
                        jQuery(this).hide();
                    });
                } else {
                    preview.each(function() { jQuery(this).show(); });
                    var hash = form_id % 100;
                    hash = ((hash < 10) ? '0' : '') + hash;
                    preview.each(function() {
                        jQuery(this).attr('href', 'https://forms.aweber.com/form/' + hash + '/' + form_id + '.html');
                    });
                }
            }
        }

        updateViewableFormSelector();
        updatePreviewLink();

        jQuery(document.body).on('change', '.<?php echo esc_attr($this->widgetOptionsName . '-list'); ?>', function() {
            updateViewableFormSelector();
            var form_dropdown = currentFormDropDown();
            if (form_dropdown !== undefined) {
                form_dropdown.val('');
            }
            updatePreviewLink();
        });
        jQuery('.<?php echo esc_attr($this->widgetOptionsName . '-form-select'); ?>').each(function() {
            jQuery(this).change(function() {
                updatePreviewLink();
            });
        });
    });
</script>

<?php
        $this->_end_response();
    }

    /**
        * Get web form snippet
        *
        * Retrieve webform snippet to be inserted in blog page.
        * @return string
        */
    function getWebformSnippet() {
        $options = get_option($this->widgetOptionsName);
        return $options['form_snippet'];
    }

    function getAllowedTags() {
        return array_merge(wp_kses_allowed_html('post'), array(
            'svg'   => array(
                'class'           => true,
                'aria-hidden'     => true,
                'aria-labelledby' => true,
                'role'            => true,
                'xmlns'           => true,
                'width'           => true,
                'height'          => true,
                'viewbox'         => true
            ),
            'g'     => array( 'fill' => true ),
            'title' => array( 'title' => true ),
            'path'  => array( 
                'd'               => true, 
                'fill'            => true  
            ),
            'style' => array(
              'type' => 'text/css',
            ),
            'script' => array(
              'type' => 'text/javascript',
              'href' => true,
            ),
        ));
    }
}
?>
