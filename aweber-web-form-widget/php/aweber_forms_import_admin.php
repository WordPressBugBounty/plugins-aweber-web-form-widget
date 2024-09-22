<?php
    use AWeberWebFormPluginNamespace as AWeberWebformPluginAlias;
?>
    
<div class="aweber-wrapper">
    <form name="aweber_forms_import_form" class="aweber-advance-option-form" method="post" action="options.php">
        <?php wp_nonce_field('update-options'); ?>
        <input type="hidden" name="aweber_forms_import_hidden" value="Y">

        <?php
            settings_fields('AWeberWebformOauth');

            $pluginAdminOptions = get_option($this->adminOptionsName);
            $options = get_option($this->widgetOptionsName);

            $oauth_removed = get_option('aweber_webform_oauth_removed');
            $oauth_id = get_option('aweber_webform_oauth_id');
            // OAuth2 options.
            $oauth2TokensOptions = get_option($this->oauth2TokensOptions);
            $authorize_success = $changes_saved = False;
            $button_value = 'Connect';
            $error = $incorrect_oauth_id = null;
            $list = $account_id = $account = null;

            // Check to see if they removed the connection
            $authorization_removed = False;
            if ($oauth_removed == 'TRUE' || (!empty($_GET['reauth']) && $_GET['reauth'] == True)) {
                $authorization_removed = True;
            }

            if ($authorization_removed) {
                $this->deauthorize();
                $pluginAdminOptions = get_option($this->adminOptionsName);
                $options = get_option($this->widgetOptionsName);
                // As the connection is disconnected. empty the token values.
                $oauth2TokensOptions = null;
                $error = null;
            } else if ($oauth_id && !$this->doAWeberTokenExists($pluginAdminOptions, $oauth2TokensOptions)) {
                // Then they just saved a key and didn't remove anything
                // Check it's validity then save it for later use
                $error_code  = "";
                $exception_occured = False;
                $description = "Authorization code entered was: $oauth_id <br /> Please make sure you entered the complete authorization code and try again.";

                // Not connected to AWeber. Get the AWeber OAuth2 connection
                $oauth2TokensOptions = $this->generateAccessToken($oauth_id);
                if (isset($oauth2TokensOptions['error'])) {
                    // Check the error is because of the In-correct authorization code was entered.
                    if ($oauth2TokensOptions['status'] == '400' and $oauth2TokensOptions['error'] == 'invalid_request') {
                        $incorrect_oauth_id = True;
                        update_option('aweber_webform_oauth_id', '');
                    } else {
                        $this->displayCustomErrorMessages($error_code, $oauth2TokensOptions['error']);
                        $this->deauthorize();
                    }
                }
            }

            if (get_option('aweber_option_submitted') == 'TRUE') {
                if (get_option('aweber_comment_checkbox_toggle') == 'ON') {
                    if (get_option('aweber_comment_subscriber_listid') == 'FALSE') {
                        echo $this->messages['no_list_selected'];
                        $error = True;
                    }
                }

                if (get_option('aweber_registration_checkbox_toggle') == 'ON') {
                    if (get_option('aweber_register_subscriber_listid') == 'FALSE' && !$error) {
                        echo $this->messages['no_list_selected'];
                        $error = True;
                    }
                }
            
                // Update the widgetOptionsName row, Only if the Form is submitted and no error. 
                if (!$error) {
                    $options['create_subscriber_comment_checkbox'] = get_option('aweber_comment_checkbox_toggle');
                    $options['create_subscriber_registration_checkbox'] = get_option('aweber_registration_checkbox_toggle');
                    $options['aweber_add_analytics_checkbox'] = get_option('aweber_analytics_checkbox_toggle');
                    $options['aweber_register_subscriber_listid'] = get_option('aweber_register_subscriber_listid');
                    $options['aweber_comment_subscriber_listid'] = get_option('aweber_comment_subscriber_listid');
                    $options['aweber_comment_subscriber_tags'] = get_option('aweber_comment_subscriber_tags');
                    $options['aweber_register_subscriber_tags'] = get_option('aweber_register_subscriber_tags');
                    $options['aweber_web_push_listid'] = get_option('aweber_web_push_listid');
                    // IF no web push list_id is selected, then update the wpn options to empty array.
                    if ($options['aweber_web_push_listid'] == 'FALSE') {
                        update_option($this->webPushOptionsName, array());
                    }
                        
                    if (get_option('aweber_comment_checkbox_toggle') == 'ON' && strlen(get_option('aweber_comment_subscriber_text')) < 7) {
                        echo $this->messages['signup_text_too_short'];
                    } else if (get_option('aweber_registration_checkbox_toggle') == 'ON' && strlen(get_option('aweber_register_subscriber_text')) < 7) {
                        echo $this->messages['signup_text_too_short'];
                    } else {
                        $options['aweber_comment_subscriber_text']  = get_option('aweber_comment_subscriber_text');
                        $options['aweber_register_subscriber_text'] = get_option('aweber_register_subscriber_text');
                    }
                    update_option($this->widgetOptionsName, $options);

                    $changes_saved = True;
                }

                // Update the Form submitted values as False.
                update_option('aweber_option_submitted', 'FALSE');
            }

            // Then it means, connection to the AWeber Exists,
            if ($this->doAWeberTokenExists($pluginAdminOptions, $oauth2TokensOptions)) {
                try {
                    $aweber = $this->getAWeberAPI();
                    // Get the active OAuth1 or OAuth2 connection.
                    if (get_class($aweber) == 'AWeberWebFormPluginNamespace\AWeberOAuth2API') {
                        $account = $aweber->getAccount();
                    } else {
                        $account = $aweber->getAccount(
                            $pluginAdminOptions['access_key'], $pluginAdminOptions['access_secret']);
                    }

                    // All the AWeber lists.
                    $lists = $account->lists;
                } catch (AWeberWebformPluginAlias\AWeberException $exc) {
                    // Exception raised while getting the AWeber account.
                    $this->handleOAuth2Exception($exc);
                    $account = null;
                }
                if ($account) {
                    $authorize_success = True;
                    $button_value = 'Remove Connection';

                    // Store the account_id.
                    $account_id = $account->id;
                    // Fetch the AWeber Web Push Lists.
                    $webPushLists = $this->getWebPushLists($account->uuid, $lists, $options['aweber_web_push_listid'], $changes_saved);
                    /*  Update the Analytics URL in DB - If the user enables the 'Auto Add Analytics javascript' */
                    $this->updateAnalyticsURL($options, $account->analytics_src, $changes_saved);
                }
            }
            if (!$account) {
                // No connection exits, So get an OAuth2 authorization URL.
                $aweber = $this->getAWeberOAuth2API();
                // Generate the Authorization URL
                $authorizeUrl = $aweber->getAuthorizeUrl();
            }
        ?>

        <div class="aweber-body-wrapper">
            <div class="aweber-body">

                <?php if(!$error): ?>
                    <ul class="list-inline">
                        <li class="<?php echo $authorize_success ? '' : 'active' ?>">
                            <a  href="javascript:void(0)" data-toggle="#connect">Connect</a>
                        </li>
                        <li class="<?php echo $authorize_success ? 'active' : '' ?>">
                            <a href="javascript:void(0)" class="<?php echo $authorize_success ? '' : 'disabled-tabs' ?>" data-toggle="#advance_opt">Advanced Options</a>
                        </li>
                        <li>
                            <a  href="javascript:void(0)" data-toggle="#system_info">System Info</a>
                        </li>
                    </ul>
                <?php endif; ?>

                <?php if (!$authorize_success): ?>
                    <p class="aweber-signup"> Don't have an AWeber account? 
                        <a target="_blank" type="button" href="https://aweber.com/order.htm?source=wordpressplugin" class="aweber-btn aweber-btn-success">Sign Up</a>
                    </p>
                <?php endif; ?>

                <div class="tab-content">
                    <!-- Connect Tab -->
                    <div id="connect" class="tab-pane <?php echo $authorize_success ? '' : 'active' ?>">
                        <?php if ($authorize_success): ?>
                            <!-- AWeber Authorization successful -->
                            <h1>Connected to AWeber</h1>
                            <p> 
                                You've successfully connected to your AWeber account! 
                                <br /><br />
                                <button type="button" class="aweber-btn aweber-btn-danger aweber-remove-connection-link">Remove Connection</button>
                            </p>
                        <?php elseif($error): ?>
                            <br><br>
                        <?php elseif(!empty(get_option('aweber_oauth_error'))): ?>
                            <label class="reauthorize-font">Please click here to reauthorize your account:</label><br/><br/>
                            <a class="aweber-btn aweber-btn-primary" type="button" href="/wp-admin/admin.php?page=aweber.php&amp;reauth=true">Reauthorize</a>
                        <?php else: ?>
                            <h1>Let's Get Started</h1>
                            <?php
                                if($authorization_removed):
                                    $this->add_alert_message_html('positive', 'You have successfully disconnected from your AWeber account.');
                                endif;
                            ?>
                            <table class="form-table">
                                <tr>
                                    <td><label>Step 1:</label></td>
                                    <td><a type="button" target="_blank" href="<?php echo $authorizeUrl ?>" class="aweber-btn aweber-btn-primary">Get Started</a></td>
                                </tr>
                                <tr>
                                    <td><label>Step 2:</label></td>
                                    <td>
                                        <label>Paste in your Authorization code:</label>
                                        <?php if($incorrect_oauth_id): ?>
                                            <input type="text" name="aweber_webform_oauth_id" class="aweber-form-text" value="<?php echo $oauth_id ?>" />
                                            <span class="aweber-error-message">An incorrect authorization code was entered.</span>
                                        <?php else: ?>
                                            <input type="text" name="aweber_webform_oauth_id" class="aweber-form-text" />
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="3" align="right">
                                        <input type="hidden" name="_wp_http_referer" value="<?php echo admin_url('admin.php?page=aweber.php'); ?>" />
                                        <input type="submit" id="aweber-settings-button" class="aweber-btn aweber-btn-primary" value="Finish" />
                                    </td>
                                </tr>
                            </table>

                        <?php endif; ?>
                    </div>

                    <!-- Advanced Options -->
                    <div id="advance_opt" class="tab-pane <?php echo $authorize_success ? 'active' : '' ?>">
                        <?php if ($authorize_success): ?>
                            <h1>Advanced Options</h1>
                            <?php
                                $this->add_alert_message_html('negative', 'Please select any one subscriber list(s) to save the changes.', 'aweber-hide no-list-selected');
                                if($changes_saved):
                                    $this->add_alert_message_html('positive', 'Changes made to your subscriber list(s) saved successfuly.', 'list-changes-saved');
                                endif;
                            ?>

                            <!-- Sidebar Settings -->
                            <div class="aweber-widget-settings">
                                <h2> Widget Settings </h2>
                                <p>
                                    Go to the <a href="widgets.php">Widgets Page</a> and drag the AWeber widget into your widget area.
                                </p>
                            </div>

                            <div class="aweber-block-space"></div>

                            <!-- Commenting & New Registrations -->
                            <div class="aweber-advanced-options">
                                <h2> Commenting & New Registrations </h2>

                                <div class="aweber-fleft">
                                    <input type="checkbox" name="aweber_comment_checkbox" id="aweber-create-subscriber-comment-checkbox" 
                                    <?php echo ($options['create_subscriber_comment_checkbox'] == 'ON') ? 'checked="checked"' : ''; ?>  value="">
                                    <label for="aweber-create-subscriber-comment-checkbox">Add Subscribers when visitors leave a comment.</label>
                                    
                                    <div class="aweber-block-space"></div>

                                    <div id="aweber-create-subscriber-comment-config" style="margin-left: 20px; <?php echo ($options['create_subscriber_comment_checkbox'] == 'ON') ? '': 'display: none;'; ?>">
                                        <label>Add Subscriber to:</label>
                                        <?php if (!empty($lists)): ?>
                                            <select class="aweber-form-text <?php echo $this->widgetOptionsName; ?>-comment-list">
                                                <option value="FALSE">Select A List</option>
                                                <?php foreach ($lists as $list): ?>
                                                    <option value="<?php echo $list->id; ?>"
                                                        <?php echo ($list->id == $options['aweber_comment_subscriber_listid']) ? ' selected="selected"' : ""; ?>>
                                                        <?php echo $list->name; ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        <?php else: ?>
                                            <p>This AWeber account does not currently have any lists.</p>
                                        <?php endif; ?>

                                        <div class="aweber-block-space"></div>

                                        <label>Add Tags:</label><small>(separate tags with commas)</small>
                                        <input type="text" placeholder="Add Tags" id="aweber-comment-subscriber-tags-input" value="<?php echo $options['aweber_comment_subscriber_tags'];?>" class="aweber-form-text" />

                                        <div class="aweber-block-space"></div>

                                        <label>Subscription Label:</label>
                                        <input type="text" placeholder="Subscription Label" id="aweber-comment-subscriber-input" value="<?php echo $options['aweber_comment_subscriber_text'];?>" class="aweber-form-text" />
                                    </div>
                                </div>
                                <?php if(wp_is_mobile()): ?>
                                    <div class="aweber-fleft" style="margin-top: 50px;">
                                <?php else:?>
                                    <div class="aweber-fleft" style="margin-left: 50px;">
                                <?php endif; ?>
                                
                                    <input type="checkbox" name="aweber_registration_checkbox" id="aweber-create-subscriber-registration-checkbox"
                                    <?php echo ($options['create_subscriber_registration_checkbox'] == 'ON') ? 'checked="checked"' : ''; ?> value="">
                                    <label for="aweber-create-subscriber-registration-checkbox">Add Subscribers when visitors register for your website.</label>

                                    <div class="aweber-block-space"></div>
                                    <div id="aweber-create-subscriber-registration-config" style="margin-left: 20px; <?php echo ($options['create_subscriber_registration_checkbox'] == 'ON') ? '': 'display: none;'; ?>">
                                        <label>Add Subscriber to:</label>
                                        <?php if (!empty($lists)): ?>
                                            <select class="aweber-form-text <?php echo $this->widgetOptionsName; ?>-register-list">
                                                <option value="FALSE">Select A List</option>
                                                <?php foreach ($lists as $list): ?>
                                                    <option value="<?php echo $list->id; ?>"
                                                        <?php echo ($list->id == $options['aweber_register_subscriber_listid']) ? ' selected="selected"' : ""; ?>>
                                                        <?php echo $list->name; ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        <?php else: ?>
                                            <p>This AWeber account does not currently have any lists.</p>
                                        <?php endif; ?>

                                        <div class="aweber-block-space"></div>

                                        <label>Add Tags:</label><small>(separate tags with commas)</small>
                                        <input type="text" placeholder="Add Tags" id="aweber-register-subscriber-tags-input" value="<?php echo $options['aweber_register_subscriber_tags'];?>" class="aweber-form-text" />

                                        <div class="aweber-block-space"></div>

                                        <label>Subscription Label:</label>
                                        <input type="text" placeholder="Subscription Label" id="aweber-register-subscriber-input" value="<?php echo $options['aweber_register_subscriber_text'];?>" class="aweber-form-text" />
                                    </div>
                                </div>
                                <div style="clear: both;"></div>
                            </div>

                            <div class="aweber-block-space"></div>

                            <!-- Auto Add the Analytics JavaScript -->
                            <div class="aweber-event-tracking">
                                <h2> Analytics Event Tracking </h2>
                                <p>
                                    <input type="checkbox" id="aweber-add-analytics-checkbox" <?php echo $options['aweber_add_analytics_checkbox'] == 'ON' ? 'checked' : '' ?> >
                                    <label for="aweber-add-analytics-checkbox">Automatically add analytics JavaScript file to your website.</label>
                                </p>
                            </div>

                            <div class="aweber-block-space"></div>

                            <!-- Web Push Notification Configuration -->
                            <div class="aweber-widget-settings">
                                <h2> Web Push </h2>

                                <ul class="aweber-web-push-help">
                                    <li>
                                        <a href="https://help.aweber.com/hc/en-us/articles/360051632473-What-are-Web-Push-Notifications-and-what-value-do-they-have-">What is Web Push?</a>
                                    </li>
                                </ul>

                                <p>Use an additional tool to communicate with your audience. In your AWeber account, go to the Web Push screen and set the website URL you want to use. After that return to this screen and select your list. Don't forget to save your changes. You may need to refresh this page after visiting the AWeber.</p>

                                <?php if (!empty($webPushLists)): ?>
                                    <select class="aweber-form-text aweber-wpn-select-list">
                                        <option value="FALSE">Select a list</option>
                                        <?php foreach ($webPushLists as $list): ?>
                                            <option value="<?php echo $list['id']; ?>"
                                                <?php echo ($list['id'] == $options['aweber_web_push_listid']) ? ' selected="selected"' : ""; ?>>
                                                <?php echo $list['name']; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php else: ?>
                                    <select class="aweber-form-text" disabled="disabled">
                                        <option>Select a list</option>
                                    </select>
                                    <p class="aweber-error-message">No lists have web push notifications set up.</p>
                                <?php endif; ?>
                            </div>

                            <div class="aweber-block-space"></div>

                            <!-- Submit Button -->
                            <p class="submit" style="text-align: right;">
                                <input type="submit" id="aweber-settings-save-button" class="aweber-btn aweber-btn-success" value="Save" />
                            </p>

                            <!-- Hidden fields -->

                            <!-- Oauth Status field -->
                            <input type="hidden" id="aweber-settings-hidden-value" name="aweber_webform_oauth_removed" value="TRUE" />

                            <!-- Form Submitted status field -->
                            <input type="hidden" name="aweber_option_submitted" value="TRUE" />

                            <!-- AWeber Register Subscriber LIST ID -->
                            <input type="hidden" id="aweber-settings-hidden-register-subscriber-listid" 
                                name="aweber_register_subscriber_listid" value="<?php echo $options['aweber_register_subscriber_listid'];?>" />

                            <!-- AWeber Register Subscriber LIST ID -->
                            <input type="hidden" id="aweber-settings-hidden-comment-subscriber-listid" 
                                name="aweber_comment_subscriber_listid" value="<?php echo $options['aweber_comment_subscriber_listid'];?>" />

                            <!-- AWeber Comment subscriber status field -->
                            <input type="hidden" id="aweber-settings-hidden-comment-checkbox-value" 
                                name="aweber_comment_checkbox_toggle" value="<?php echo $options['create_subscriber_comment_checkbox'];?>" />

                            <!-- AWeber Register subscriber status field -->
                            <input type="hidden" id="aweber-settings-hidden-registration-checkbox-value" 
                                name="aweber_registration_checkbox_toggle" value="<?php echo $options['create_subscriber_registration_checkbox'];?>" />

                            <!-- AWeber Comment subscriber tag field -->
                            <input type="hidden" id="aweber-settings-hidden-comment-tags" 
                                name="aweber_comment_subscriber_tags" value="<?php echo $options['aweber_comment_subscriber_tags'];?>" />

                            <!-- AWeber Register subscriber tag field -->
                            <input type="hidden" id="aweber-settings-hidden-register-tags" 
                                name="aweber_register_subscriber_tags" value="<?php echo $options['aweber_register_subscriber_tags'];?>" />

                            <!-- AWeber Comment Subscriber signup text -->
                            <input type="hidden" id="aweber-settings-hidden-comment-text-value" 
                                name="aweber_comment_subscriber_text" value="<?php echo $options['aweber_comment_subscriber_text'];?>" />

                            <!-- AWeber Regsiter Subscriber signup text -->
                            <input type="hidden" id="aweber-settings-hidden-register-text-value" 
                                name="aweber_register_subscriber_text" value="<?php echo $options['aweber_register_subscriber_text'];?>" />

                            <!-- AWeber Analytics status field -->
                            <input type="hidden" id="aweber-settings-hidden-analytics-checkbox-toggle" 
                                name="aweber_analytics_checkbox_toggle" value="<?php echo $options['aweber_add_analytics_checkbox'];?>" />

                            <!-- AWeber Web Push List ID -->
                            <input type="hidden" id="aweber-settings-hidden-web-push-listid" 
                                name="aweber_web_push_listid" value="<?php echo $options['aweber_web_push_listid'];?>" />

                        <?php endif; ?>
                    </div>

                    <!-- System Info -->
                    <div id="system_info" class="tab-pane">
                        <?php
                            $this->add_alert_message_html('positive', '', 'aweber-hide');
                            $this->add_alert_message_html('negative', '', 'aweber-hide');
                        ?>
                        <h1>System Info</h1>
                        <table>
                            <tr>
                                <td><b>Home URL:</b></td>
                                <td><?php
                                    if (function_exists('get_home_url')):
                                        echo get_home_url();
                                    else:
                                        echo '-';
                                    endif;
                                    ?>
                                </td>
                            </tr>

                            <tr>
                                <td><b>Site URL:</b></td>
                                <td><?php
                                    if (function_exists('get_site_url')):
                                        echo get_home_url();
                                    else:
                                        echo '-';
                                    endif;
                                    ?>
                                </td>
                            </tr>

                            <tr>
                                <?php global $wp_version; ?>
                                <td><b>Wordpress Version:</b></td>
                                <td><?php echo isset($wp_version) ? $wp_version : '-' ?></td>
                            </tr>

                            <tr>
                                <td><b>PHP Version:</b></td>
                                <td><?php 
                                    if (function_exists('phpversion')):
                                        echo phpversion();
                                    else:
                                        echo '-';
                                    endif;
                                    ?>
                                </td>
                            </tr>

                            <tr>
                                <td><b>AWeber Account ID:</b></td>
                                <td><?php echo isset($account_id) ? $account_id : 'Not connected to AWeber' ?></td>
                            </tr>

                            <?php 
                                $plugin_path = join(DIRECTORY_SEPARATOR, array(dirname(__FILE__), '..', 'aweber.php'));
                                $plugin_data = get_plugin_data($plugin_path);
                                $plugin_version = $plugin_data['Version'];
                            ?>
                            <tr>
                                <td><b>AWeber Plugin Version:</b></td>
                                <td><?php echo $plugin_version; ?></td>
                            </tr>

                            <tr>
                                <td><b>Remote IP Address:</b></td>
                                <td><?php echo isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '-' ?></td>
                            </tr>

                            <tr>
                                <td><b>X Forwarded For IPs:</b></td>
                                <td><?php echo isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : '-' ?></td>
                            </tr>

                            <tr>
                                <td><b>X Real IP:</b></td>
                                <td><?php echo isset($_SERVER['HTTP_X_REAL_IP']) ? $_SERVER['HTTP_X_REAL_IP'] : '-' ?></td>
                            </tr>
                        </table>

                        <br>

                        <button class="aweber-btn aweber-btn-primary" type="button" data-widget-name="<?php echo $this->widgetOptionsName ?>" id="aweber-webform-reload-cache">Reload Cache</button>

                    </div>

                </div>
            </div>
        </div>

        <div class="aweber-modal" id="show-remove-connection">
            <div class="aweber-modal-content aweber-modal-md">
                <div class="aweber-modal-header">
                    <h1>Disconnect from AWeber</h1>
                </div>
                <div class="aweber-modal-body">
                    <p>Are you sure you would like to disconnect from your AWeber account?</p>
                </div>
                <div class="aweber-modal-footer">
                    <button type="button" class="aweber-btn aweber-btn-plain aweber-dismiss-modal">Cancel</button>
                    <button class="aweber-btn aweber-btn-danger">Remove</button>
                </div>
            </div>
        </div>

        <input type="hidden" name="action" value="update" />        
        <input type="hidden" name="page_options" value="aweber_webform_oauth_id" />
    </form>

    <?php if ($authorization_removed or $authorize_success): ?>
        <script type="text/javascript" >
            jQuery.noConflict();
            jQuery(".aweber-admin-notice").hide();
        </script>
    <?php endif ?>
</div>
