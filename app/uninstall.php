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
