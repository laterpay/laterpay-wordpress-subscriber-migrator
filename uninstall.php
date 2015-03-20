<?php

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    // exit, if uninstall was not called from WordPress
    exit;
}

global $wpdb;

$table_subscriber_migrations = $wpdb->prefix . 'laterpay_subscriber_migrations';

// remove custom tables
$sql = "
    DROP TABLE IF EXISTS
        $table_subscriber_migrations
    ;
";
$wpdb->query( $sql );

// remove added options
delete_option( 'laterpay_migrator_products' );
delete_option( 'laterpay_migrator_products_mapping' );
delete_option( 'laterpay_migrator_limit' );
delete_option( 'laterpay_migrator_expiry_modifier' );

delete_option( 'laterpay_migrator_sitenotice_message' );
delete_option( 'laterpay_migrator_sitenotice_button_text' );
delete_option( 'laterpay_migrator_sitenotice_bg_color' );
delete_option( 'laterpay_migrator_sitenotice_text_color' );

delete_option( 'laterpay_migrator_mailchimp_api_key' );
delete_option( 'laterpay_migrator_mailchimp_ssl_connection' );
delete_option( 'laterpay_migrator_mailchimp_campaign_after_expired' );
delete_option( 'laterpay_migrator_mailchimp_campaign_before_expired' );
