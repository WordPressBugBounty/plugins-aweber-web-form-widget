<?php
use AWeberWebFormPluginNamespace as AWeberWebformPluginAlias;

/*
Plugin Name: AWeber for WordPress
Plugin URI: http://www.aweber.com/faq/questions/588/How+Do+I+Use+AWeber%27s+Webform+Widget+for+Wordpress%3F
Description: Add AWeber Landing Pages and Sign Up Forms to your WordPress site
Version: 7.3.24
Author: AWeber
Author URI: http://www.aweber.com
License: MIT
*/


// Defined the AWeber Wordpress plugin version that can be used accross the plugin.
define ('AWEBER_PLUGIN_VERSION', 'v7.3.24');

function AWeberMandatoryPHPVersionMessage() {
    global $aweber_webform_plugin;
    $aweber_webform_plugin->add_alert_message_html('negative', 'Sorry, AWeber Sign Up Form requires PHP 5.6 or higher. Please deactivate AWeber Sign Up Form.', 'aweber-version-message');
}

// Initialize plugin.
if (!class_exists('AWeberWebformPlugin')) {
    require_once(dirname(__FILE__) . '/php/aweber_api/aweber_api.php');
    require_once(dirname(__FILE__) . '/php/aweber_webform_plugin.php');
    $aweber_webform_plugin = new AWeberWebformPluginAlias\AWeberWebformPlugin();

    $options = get_option('AWeberWebformPluginWidgetOptions');

    if ($options['create_subscriber_comment_checkbox'] == 'ON' && is_numeric($options['aweber_comment_subscriber_listid']))
    {
        /* The following line adds the checkbox to the comment form.
         * If problems persist, the code can be changed to
         * any of the following 3 lines. Just add a # before
         * the line that is currently active, and remove the
         * # from the line you wish to use. */
        add_action('comment_form',array(&$aweber_webform_plugin,'add_comment_checkbox'));
        #add_filter('comment_form_after_fields',array(&$aweber_webform_plugin,'add_comment_checkbox'));
        #add_filter('thesis_hook_after_comment_box',array(&$aweber_webform_plugin,'add_comment_checkbox'));
        // End
        add_action('comment_post',array(&$aweber_webform_plugin,'grab_email_from_comment'));
    }

    if ($options['create_subscriber_registration_checkbox'] == 'ON' && is_numeric($options['aweber_register_subscriber_listid']))
    {
        add_action('register_form',array(&$aweber_webform_plugin,'add_register_checkbox'));
        add_action('register_post',array(&$aweber_webform_plugin,'grab_email_from_registration'));
    }
    add_action('comment_unapproved_to_approved',array(&$aweber_webform_plugin,'comment_approved'));
    add_action('comment_spam_to_approved',array(&$aweber_webform_plugin,'comment_approved'));
    add_action('delete_comment',array(&$aweber_webform_plugin,'comment_deleted'));
}

// Initialize admin panel.
if (!function_exists('AWeberFormsWidgetController_ap')) {
    function AWeberFormsWidgetController_ap() {
        global $aweber_webform_plugin;
        if (!isset($aweber_webform_plugin)) {
            return;
        }

        // Create AWeber top menu.
        add_menu_page('AWeber Web Form', 'AWeber', 'manage_options', 'aweber.php',
            array(&$aweber_webform_plugin, 'printAdminPage'), plugins_url('AWeber_white_logo.png', __FILE__), 30);

        // Create AWeber Settings Options.
        add_submenu_page('aweber.php', 'Settings', 'Settings', 'manage_options',
            'aweber.php', array(&$aweber_webform_plugin, 'printAdminPage'));

        // Create AWeber 'Forms' sub menu
        add_submenu_page('aweber.php', 'Forms', 'Forms', 'manage_options', 'aweber_web_form',
            array(&$aweber_webform_plugin, 'printSignupInfo'));

        // Create AWeber 'Landing Pages' sub menu
        add_submenu_page('aweber.php', 'Landing Pages', 'Landing Pages', 'manage_options', 'aweber_landing_page',
            array(&$aweber_webform_plugin, 'showLandingPage'));
    }
}

if (!function_exists('AWeberRegisterSettings')) {

    function AWeberAuthMessage() {
        global $aweber_webform_plugin;
        if (isset($_GET['page']) && $_GET['page'] == 'aweber.php') {
            $aweber_webform_plugin->add_alert_message_html('negative', 'AWeber Sign Up Form requires authentication. Please follow below steps to connect to AWeber.', 'aweber-admin-notice');
        }
    }

    function AWeberRegisterSettings() {
        if (is_admin()) {
            global $aweber_webform_plugin;
            register_setting($aweber_webform_plugin->oauthOptionsName, 'aweber_webform_oauth_id',
                             array('sanitize_callback' => 'sanitize_text_field'));
            register_setting($aweber_webform_plugin->oauthOptionsName, 'aweber_webform_oauth_removed',
                             array('sanitize_callback' => 'sanitize_text_field'));
            register_setting($aweber_webform_plugin->oauthOptionsName, 'aweber_comment_checkbox_toggle',
                             array('sanitize_callback' => 'sanitize_text_field'));
            register_setting($aweber_webform_plugin->oauthOptionsName, 'aweber_registration_checkbox_toggle',
                             array('sanitize_callback' => 'sanitize_text_field'));
            register_setting($aweber_webform_plugin->oauthOptionsName, 'aweber_analytics_checkbox_toggle',
                             array('sanitize_callback' => 'sanitize_text_field'));

            register_setting($aweber_webform_plugin->oauthOptionsName, 'aweber_comment_subscriber_text',
                             array('sanitize_callback' => 'sanitize_text_field'));
            register_setting($aweber_webform_plugin->oauthOptionsName, 'aweber_register_subscriber_text',
                             array('sanitize_callback' => 'sanitize_text_field'));
            register_setting($aweber_webform_plugin->oauthOptionsName, 'aweber_register_subscriber_listid',
                             array('sanitize_callback' => 'sanitize_text_field'));
            register_setting($aweber_webform_plugin->oauthOptionsName, 'aweber_comment_subscriber_listid',
                             array('sanitize_callback' => 'sanitize_text_field'));
            register_setting($aweber_webform_plugin->oauthOptionsName, 'aweber_comment_subscriber_tags',
                             array('sanitize_callback' => 'sanitize_text_field'));
            register_setting($aweber_webform_plugin->oauthOptionsName, 'aweber_register_subscriber_tags',
                             array('sanitize_callback' => 'sanitize_text_field'));
            register_setting($aweber_webform_plugin->oauthOptionsName, 'aweber_option_submitted',
                             array('sanitize_callback' => 'sanitize_text_field'));
            register_setting($aweber_webform_plugin->oauthOptionsName, 'aweber_oauth_error',
                             array('sanitize_callback' => 'sanitize_text_field'));
            register_setting($aweber_webform_plugin->oauthOptionsName, 'aweber_web_push_listid',
                             array('sanitize_callback' => 'sanitize_text_field'));

            $pluginAdminOptions = get_option($aweber_webform_plugin->adminOptionsName);
            $oauth2TokensOptions = get_option($aweber_webform_plugin->oauth2TokensOptions);
            if ($pluginAdminOptions['access_key'] == '' && !isset($oauth2TokensOptions['access_token'])) {
                add_action('admin_notices', 'AWeberAuthMessage');
                return;
            }
        }
    }
}
// Initialize widget.
if (!function_exists('AWeberFormsWidgetController_widget')) {
    function AWeberFormsWidgetController_widget() {
        global $aweber_webform_plugin;
        if (!isset($aweber_webform_plugin)) {
            return;
        }

        if (function_exists('wp_register_sidebar_widget') and function_exists('wp_register_widget_control')) {
            wp_register_sidebar_widget($aweber_webform_plugin->widgetOptionsName, __('AWeber Sign Up Form', 'aweber-web-form-widget'), array(&$aweber_webform_plugin, 'printWidget'));
            wp_register_widget_control($aweber_webform_plugin->widgetOptionsName, __('AWeber Sign Up Form', 'aweber-web-form-widget'), array(&$aweber_webform_plugin, 'printWidgetControl'));
        }
    }
}

if (!function_exists('AddAWeberShortCodes')) {
    function AddAWeberShortCodes() {
        // check user permissions
        if ( !current_user_can( 'edit_posts' ) &&  !current_user_can( 'edit_pages' ) ) {
            return;
        }
        // check if WYSIWYG is enabled
        if ( 'true' == get_user_option( 'rich_editing' ) ) {
            add_filter( 'mce_external_plugins', 'AddAWeberShortCodeScript' );
            add_filter( 'mce_buttons', 'RegisterAWeberShortCodeButton' );
        }
    }

    // register new button in the editor
    function RegisterAWeberShortCodeButton( $buttons ) {
        array_push( $buttons, 'aweber_shortcode_button' );
        return $buttons;
    }

    // declare a script for the new button
    // the script will insert the shortcode on the click event
    function AddAWeberShortCodeScript( $plugin_array ) {
        // Creates a cryptographic token for 'get_aweber_webform_shortcodes' action.
        echo '<input type="hidden" value="' . esc_attr(wp_create_nonce('get_aweber_webform_shortcodes')) . '" id="aweber-tinymice-shotcode-nonce" />';
        $plugin_array['aweber_shortcode_button'] = plugin_dir_url(__FILE__) . '/src/js/aweber_tinymce_shortcode_button.js';
        return $plugin_array;
    }
}

if (!function_exists('addAWeberBlockToGutenbergEditor')) {
    function addAWeberBlockToGutenbergEditor() {
        $nonces = [
            'get_aweber_webform_shortcodes' => wp_create_nonce('get_aweber_webform_shortcodes'),
        ];

        wp_register_script(
            'aweber-signupform-block',
            plugin_dir_url( __FILE__ ) . 'src/js/aweber_gutenberg_webform_block-react.js',
            array( 'wp-blocks', 'wp-element', 'wp-components', 'wp-editor', 'wp-i18n')
        );

        // Used to send the Data to the Javascript File.
        wp_localize_script( 'aweber-signupform-block', 'gutenberg_php_vars', array(
            'plugin_base_path'  => plugin_dir_url(__FILE__),
            'aweber_logo'       => plugin_dir_url(__FILE__) . 'AWeber_widget_blue.png',
            'aweber_nonce'      => $nonces,
        ));

        // Check the register_block_type function exists.
        if (function_exists('register_block_type')) {
            register_block_type( 'aweber-signupform-block/aweber-shortcode', array(
                'editor_script' => 'aweber-signupform-block',
            ));
        }
    }
}

if (!function_exists('registerAWeberElementorWidget')) {
    function registerAWeberElementorWidget() {
        require_once(dirname(__FILE__) . '/php/aweber_elementor_widget.php');

        \Elementor\Plugin::instance()->widgets_manager->register_widget_type( new \Elementor\AWeberElementorWidget() );
    }
}

if (!function_exists('registerAWeberFormAction')) {
    function registerAWeberFormAction() {
        require_once(dirname(__FILE__) . '/php/aweber_elementor_form_action.php');

        $aweber_action = new AWeberElementorFormAction();

        \ElementorPro\Plugin::instance()->modules_manager->get_modules( 'forms' )->add_form_action( $aweber_action->get_name(), $aweber_action );
    }
}

// Update CSS within in Admin
function load_admin_page_styles() {
    global $aweber_webform_plugin;

    $nonces = [
        'reload_aweber_cache' => wp_create_nonce('reload_aweber_cache'),
        'get_signup_webforms' => wp_create_nonce('get_signup_webforms'),
        'get_aweber_lists'    => wp_create_nonce('get_aweber_lists'),
        'aweber_unlink_page'  => wp_create_nonce('aweber_unlink_page'),
        'get_wordpress_pages' => wp_create_nonce('get_wordpress_pages'),
        'get_landing_pages'   => wp_create_nonce('get_landing_pages'),
        'aweber_link_page'    => wp_create_nonce('aweber_link_page'),
        'aweber_create_page'  => wp_create_nonce('aweber_create_page'),
    ];

    wp_enqueue_style( 'aweber_wp_admin_css', plugins_url('/src/css/aweber-plugin-styles.css', __FILE__), array(), '1.0.0');
    wp_enqueue_script( 'aweber_wp_admin_js', plugins_url('/src/js/aweber-plugin-script.js', __FILE__), array(), '1.0.0' );
    // Used to send the Data to the Javascript File.
    wp_localize_script( 'aweber_wp_admin_js', 'php_vars', array(
        'plugin_base_path'  => plugin_dir_url(__FILE__),
        'aweber_copy_png'   => plugin_dir_url(__FILE__) . 'assets/copy.png',
        'aweber_widget_id'  => strtolower($aweber_webform_plugin->widgetOptionsName),
        'aweber_nonce'      => $nonces,
    ));
}

if (!function_exists('AWeberAdminFooterSettings')) {
    function AWeberAdminFooterSettings() {
        global $aweber_webform_plugin;

        $pluginAdminOptions = get_option($aweber_webform_plugin->adminOptionsName);
        $oauth2TokensOptions = get_option($aweber_webform_plugin->oauth2TokensOptions);
        if (
            !$aweber_webform_plugin->doAWeberTokenExists($pluginAdminOptions, $oauth2TokensOptions)
            || !empty(get_option('aweber_oauth_error'))
        ) {
            // Remove the Forms Submenu, Only if it is set.
            echo "<script>jQuery('#toplevel_page_aweber .wp-submenu > li :contains(Form)').parent().remove()</script>";
            echo "<script>jQuery('#toplevel_page_aweber .wp-submenu > li :contains(Landing)').parent().remove()</script>";
        }
    }
}

if (!function_exists('addAWeberPageTemplate')) {
    function addAWeberPageTemplate($templates) {
        $templates['aweber_landing_page'] = 'AWeber Landing Page';
        return $templates;
    }
}

if (!function_exists('loadPageTemplate')) {
    function loadPageTemplate($template) {
        $post = get_post();
        $page_template = get_post_meta( $post->ID, '_wp_page_template', true );
        if ('aweber_landing_page' == basename ($page_template))
            $template = __DIR__ . '/php/template/aweber_landing_page_template.php';
        return $template;
    }
}

if (!function_exists('add_settings_link')) {
    function add_settings_link($links) {
        global $aweber_webform_plugin;

        $pluginAdminOptions = get_option($aweber_webform_plugin->adminOptionsName);
        $oauth2TokensOptions = get_option($aweber_webform_plugin->oauth2TokensOptions);
        if ( $aweber_webform_plugin->doAWeberTokenExists($pluginAdminOptions, $oauth2TokensOptions)) {
            $settings_link = '<a href="admin.php?page=aweber.php">Settings</a>';
            array_unshift($links, $settings_link);
        } else {
            $settings_link = '<a class="aweber-connect-activate" href="admin.php?page=aweber.php">Connect Now</a>';
            $links = array_merge($links, array($settings_link));
        }
        return $links;
    }
}

if (!function_exists('add_plugin_description_link')) {
    function add_plugin_description_link($links, $file) {
        if ( strpos( $file, 'aweber.php' ) !== false ){
            $links = array_merge(
                $links, array('<a href="https://help.aweber.com/hc/en-us/articles/204027976-How-do-I-install-the-AWeber-for-WordPress-plugin-" target="_blank">Help & FAQs</a>'));
        }
        return $links;
    }
}

/*
* Function to hide the AWeber Webform Widget from the Legacy Widget Dropdown.
* (Only for WordPress > v5.8)
*/
if (!function_exists('hideWebformLegacyWidget')) {
    function hideWebformLegacyWidget($widget_types) {
        global $aweber_webform_plugin;

        $widget_types[] = strtolower($aweber_webform_plugin->widgetOptionsName);
        return $widget_types;
    }
}


// Actions and filters.
if (isset($aweber_webform_plugin)) {
    // Actions
    add_action('aweber/aweber.php',  array(&$aweber_webform_plugin, 'init'));
    add_action('admin_menu', 'AWeberFormsWidgetController_ap');
    add_action('admin_init', 'AWeberRegisterSettings');
    add_action('plugins_loaded', 'AWeberFormsWidgetController_widget');
    add_action('admin_print_scripts', array(&$aweber_webform_plugin, 'addHeaderCode'));
    add_action('wp_ajax_get_widget_control', array(&$aweber_webform_plugin, 'printWidgetControlAjax'));
    add_action('wp_dashboard_setup', array(&$aweber_webform_plugin, 'aweber_add_dashboard_widgets'));
    // Filters
    add_action('wp_enqueue_scripts', array(&$aweber_webform_plugin, 'loadAWeberPluginScripts'));
    // Ajax calls.
    add_action('wp_ajax_get_signup_webforms', array(&$aweber_webform_plugin, 'getSignupWebformsList'));
    add_action('wp_ajax_reload_aweber_cache', array(&$aweber_webform_plugin, 'reloadWidgetWebForm'));

    // Add AWeber ShortCode to Editor
    add_action('wp_ajax_get_aweber_webform_shortcodes', array(&$aweber_webform_plugin, 'getAWeberWebformShortCodes'));
    add_action('admin_head', 'AddAWeberShortCodes');

    // Add AWeber Landing Pgaes
    add_action('wp_ajax_get_landing_pages', array(&$aweber_webform_plugin, 'getAWeberLandingpages'));
    add_action('wp_ajax_aweber_create_page', array(&$aweber_webform_plugin, 'createWordpressPage'));
    add_action('wp_ajax_get_wordpress_pages', array(&$aweber_webform_plugin, 'getWordpressPages'));
    add_action('wp_ajax_aweber_link_page', array(&$aweber_webform_plugin, 'loadLandingPageContent'));
    add_action('wp_ajax_aweber_unlink_page', array(&$aweber_webform_plugin, 'unLinklandingPage'));

    add_filter ('theme_page_templates', 'addAWeberPageTemplate');
    add_filter ('page_template', 'loadPageTemplate');

    # Add settings link to the WordPress Plugin page.
    add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'add_settings_link');
    # Add Link in the Wordpress Plugin description section.
    add_filter('plugin_row_meta', 'add_plugin_description_link', 10, 2);

    // Add Block to Gutenberg editor
    add_action( 'init', 'addAWeberBlockToGutenbergEditor');

    // Add AWeber Signup form to Elementor Widgets
    if (in_array('elementor/elementor.php', apply_filters('active_plugins', get_option('active_plugins')))) {
        add_action('elementor/widgets/widgets_registered', 'registerAWeberElementorWidget');
        // Register AWeber for Custom Form Action
        add_action('elementor_pro/init', 'registerAWeberFormAction');
        // Register the Ajax call to get the lists
        add_action('wp_ajax_get_aweber_custom_fields', array(&$aweber_webform_plugin, 'getAWeberCustomFields'));
    }
    // Hide the Widget from the Legacy Widget Block Dropdown
    add_filter('widget_types_to_hide_from_legacy_widget_block', 'hideWebformLegacyWidget');

    // Register the Ajax call to get the lists
    add_action('wp_ajax_get_aweber_lists', array(&$aweber_webform_plugin, 'getAweberLists' ));

    add_action( 'admin_footer', 'AWeberAdminFooterSettings');

    // Update AWeber WPN Script or Snippets. 99999999 is the priority value to call at last.
    add_action('wp_footer', array(&$aweber_webform_plugin, 'printAWeberWPNSnippet'));

    // Triggers the AWeber ShortCode.
    add_shortcode('aweber', array(&$aweber_webform_plugin, 'aweberShortcodeHandler'));

    add_action('admin_enqueue_scripts', 'load_admin_page_styles');

    if (isset($_GET['page']) && in_array($_GET['page'], array('aweber.php', 'aweber_web_form', 'aweber_landing_page'))) {
        add_action('in_admin_header', array(&$aweber_webform_plugin, 'attachAWeberheader'));

        # if PHP Version is less than 5.6, the error message will be shown in AWeber Pages.
        if (version_compare(phpversion(), '5.6', '<')) {
            add_action('admin_notices', 'AWeberMandatoryPHPVersionMessage');
        }
    }
}
?>
