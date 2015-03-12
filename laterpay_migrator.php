<?php
/*
 * Plugin Name: LaterPay Subscriber Migrator
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-subscriber-migrator
 * Description: Extension for LaterPay plugin to migrate existing subscribers to LaterPay.
 * Author: LaterPay GmbH and Aliaksandr Vahura
 * Version: 0.1
 * Author URI: https://laterpay.net/
 * Textdomain: laterpay
 * Domain Path: /languages
 */

// make sure we don't expose any info if called directly
if ( ! function_exists( 'add_action' ) ) {
    echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
    exit;
}

define( 'LATERPAY_MIGRATOR_CSS_URL',    plugin_dir_url( __FILE__ ) . 'built_assets/css/' );
define( 'LATERPAY_MIGRATOR_JS_URL',     plugin_dir_url( __FILE__ ) . 'built_assets/js/' );
define( 'LATERPAY_MIGRATOR_UPLOAD_DIR', plugin_dir_url( __FILE__ ) . 'upload/' );

$directory = dirname( __FILE__ ) . DIRECTORY_SEPARATOR;

register_activation_hook( __FILE__, array( 'LaterPay_Migrator_Main', 'activate' ) );

register_deactivation_hook( __FILE__, array( 'LaterPay_Migrator_Main', 'deactivate' ) );

if ( ! class_exists( 'LaterPay_Autoloader' ) ) {
    require_once( WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . 'laterpay' . DIRECTORY_SEPARATOR . 'laterpay_load.php' );
}

require_once( $directory . 'app' . DIRECTORY_SEPARATOR . 'Install.php' );
require_once( $directory . 'app' . DIRECTORY_SEPARATOR . 'Mail.php' );
require_once( $directory . 'app' . DIRECTORY_SEPARATOR . 'Main.php' );
require_once( $directory . 'app' . DIRECTORY_SEPARATOR . 'Parse.php' );
require_once( $directory . 'app' . DIRECTORY_SEPARATOR . 'Settings.php' );
require_once( $directory . 'app' . DIRECTORY_SEPARATOR . 'Subscription.php' );

$main = new LaterPay_Migrator_Main();

add_action( 'init', array( $main, 'init' ) );
