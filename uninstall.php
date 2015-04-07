<?php

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    // exit, if uninstall was not called from WordPress
    exit;
}

global $wpdb;

$table_usermeta              = $wpdb->usermeta;
$table_subscriber_migrations = $wpdb->prefix . 'laterpay_subscriber_migrations';

// remove custom table
$sql = "
    DROP TABLE IF EXISTS
        $table_subscriber_migrations
    ;
";
$wpdb->query( $sql );

// remove added options
delete_option( 'laterpay_migrator_is_active' );

delete_option( 'laterpay_migrator_products' );
delete_option( 'laterpay_migrator_products_mapping' );
delete_option( 'laterpay_migrator_limit' );
delete_option( 'laterpay_migrator_expiry_modifier' );
delete_option( 'laterpay_migrator_invalid_count' );

delete_option( 'laterpay_migrator_sitenotice_message' );
delete_option( 'laterpay_migrator_sitenotice_button_text' );
delete_option( 'laterpay_migrator_sitenotice_bg_color' );
delete_option( 'laterpay_migrator_sitenotice_text_color' );

delete_option( 'laterpay_migrator_mailchimp_api_key' );
delete_option( 'laterpay_migrator_mailchimp_ssl_connection' );
delete_option( 'laterpay_migrator_mailchimp_campaign_after_expired' );
delete_option( 'laterpay_migrator_mailchimp_campaign_before_expired' );

$directory = dirname( __FILE__ ) . DIRECTORY_SEPARATOR;

define( 'DS', DIRECTORY_SEPARATOR );
define( 'LP_MIGRATOR_DIR', $directory );

// check, if LaterPay plugin is active
if ( is_plugin_active( 'laterpay/laterpay.php' ) ) {

    if ( ! class_exists( 'LaterPay_Autoloader' ) ) {
        require_once( WP_PLUGIN_DIR . DS . 'laterpay' . DS . 'laterpay_load.php' );
        LaterPay_AutoLoader::register_namespace( WP_PLUGIN_DIR . DS . 'laterpay' . DS . 'application', 'LaterPay' );
    }

    require_once( LP_MIGRATOR_DIR . 'app' . DS . 'Controller' . DS . 'Admin' . DS . 'Migration.php' );

    // remove all dismissed wp pointers
    $pointers = LaterPay_Migrator_Controller_Admin_Migration::get_all_pointers();
    if ( ! empty( $pointers ) && is_array( $pointers ) ) {
        $replace_string = 'meta_value';

        foreach ( $pointers as $pointer ) {
            // we need to use prefix ',' before pointer names to remove them properly from string
            $replace_string = "REPLACE($replace_string, ',$pointer', '')";
        }

        $sql = "
        UPDATE
            $table_usermeta
        SET
            meta_value = $replace_string
        WHERE
            meta_key = 'dismissed_wp_pointers'
        ;
    ";

        $wpdb->query( $sql );
    }
}
