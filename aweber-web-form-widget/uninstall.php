<?php
use AWeberWebFormPluginNamespace as AWeberWebformPluginAlias;

/**
 * AWeber Web Forms uninstall procedure.
 */

// Make sure this is a legitimate uninstall request
if(!defined('ABSPATH') or !defined('WP_UNINSTALL_PLUGIN') or !current_user_can('delete_plugins')) {
    exit();
}

/**
 * Drop tables used by AWeber Forms plugin
 */
function aweber_webform_uninstall_db() {
    global $wpdb;
    $tables = array();
    foreach ($tables as $table) {
        $table_name = $wpdb->prefix . $table;
        $wpdb->query(
            $wpdb->prepare(
                'DROP TABLE IF EXISTS `%s`',
                $table_name
            )
        );
    }
}

/**
 * Delete AWeber Forms saved options
 */
function aweber_webform_uninstall_options() {
    $options = array(
        'AWeberWebformPluginAdminOptions',
        'AWeberWebformPluginWidgetOptions',
        'aweber_webform_oauth_id',
        'aweber_webform_oauth_removed',
        'aweber_landing_page_links',
    );
    foreach ($options as $option) {
        delete_option($option);
    }
}

/**
 * Revoke the OAuth2 tokens from the Aweber Account.
 */
function aweber_revokeOAuth2Tokens() {
    if (file_exists(dirname(__FILE__) . '/php/aweber_api/aweber_api.php')
        && file_exists(dirname(__FILE__) . '/php/aweber_webform_plugin.php')
    ) {
        // Proceed further, only if file exits.
        require_once(dirname(__FILE__) . '/php/aweber_api/aweber_api.php');
        require_once(dirname(__FILE__) . '/php/aweber_webform_plugin.php');
        $aweber_webform_plugin = new AWeberWebformPluginAlias\AWeberWebformPlugin();

        // Check if the OAuth2 tokens exists in the DB.
        $oauth2TokensOptions = get_option($aweber_webform_plugin->oauth2TokensOptions);
        if (isset($oauth2TokensOptions['access_token'])) {
            // Revoke the OAuth2 tokens
            $aweber_webform_plugin->revokeAccessToken();
        }
    }
}


aweber_revokeOAuth2Tokens();
aweber_webform_uninstall_db();
aweber_webform_uninstall_options();
?>
