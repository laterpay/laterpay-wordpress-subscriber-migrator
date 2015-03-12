<?php

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    // exit, if uninstall was not called from WordPress
    exit;
}

global $wpdb;

$table_subsriptions_data  = $wpdb->prefix . 'lpcustom_subsriptions_data';

// remove custom tables
$sql = "
    DROP TABLE IF EXISTS
        $table_subsriptions_data
    ;
";
$wpdb->query( $sql );
