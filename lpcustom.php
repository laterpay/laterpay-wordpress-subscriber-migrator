<?php
/*
 * Plugin Name: Lpcustom
 * Author: avahura@scnsoft.com
 * Version: 0.1.0
 */

// Make sure we don't expose any info if called directly
if ( !function_exists( 'add_action' ) ) {
    echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
    exit;
}

define( 'LPCUSTOM_CSS_URL', plugin_dir_url( __FILE__ ) . 'assets/css/' );
define( 'LPCUSTOM_JS_URL',  plugin_dir_url( __FILE__ ) . 'assets/js/' );
define( 'LPCUSTOM_UPLOAD_DIR', plugin_dir_url( __FILE__ ) . 'upload/' );
define( 'LPCUSTOM_INSERT_LIMIT', 200 );

$directory = dirname( __FILE__ ) . DIRECTORY_SEPARATOR;

register_activation_hook( __FILE__, array( 'Lpcustom_Main', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Lpcustom_Main', 'deactivate' ) );

if ( ! class_exists( 'LaterPay_Autoloader' ) ) {
    require_once( WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . 'laterpay' . DIRECTORY_SEPARATOR . 'laterpay_load.php' );
}

require_once( $directory . 'app' . DIRECTORY_SEPARATOR . 'Main.php' );
require_once( $directory . 'app' . DIRECTORY_SEPARATOR . 'Install.php' );
require_once( $directory . 'app' . DIRECTORY_SEPARATOR . 'Mail.php' );
require_once( $directory . 'app' . DIRECTORY_SEPARATOR . 'Settings.php' );
require_once( $directory . 'app' . DIRECTORY_SEPARATOR . 'Subscription.php' );
require_once( $directory . 'app' . DIRECTORY_SEPARATOR . 'Parse.php' );

$main = new Lpcustom_Main();

add_action( 'init', array( $main, 'init' ) );