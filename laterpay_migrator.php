<?php
/*
 * Plugin Name: LaterPay Subscriber Migrator
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-subscriber-migrator
 * Description: Extension for LaterPay plugin to migrate existing subscribers to LaterPay.
 * Author: LaterPay GmbH and Aliaksandr Vahura
 * Version: 0.2.0
 * Author URI: https://laterpay.net/
 * Textdomain: laterpay_migrator
 * Domain Path: /languages
 */

// make sure we don't expose any info if called directly
if ( ! function_exists( 'add_action' ) ) {
    echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
    exit;
}

$directory = dirname( __FILE__ ) . DIRECTORY_SEPARATOR;
register_activation_hook( __FILE__, array( 'LaterPay_Migrator_Main', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'LaterPay_Migrator_Main', 'deactivate' ) );

if ( ! class_exists( 'LaterPay_Autoloader' ) ) {
    require_once( WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . 'laterpay' . DIRECTORY_SEPARATOR . 'laterpay_load.php' );
    require_once( WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . 'laterpay/application/Controller/' . 'Abstract.php' );
    require_once( WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . 'laterpay/application/Form/' . 'Abstract.php' );
}

require_once( $directory . 'app' . DIRECTORY_SEPARATOR . 'Install.php' );
require_once( $directory . 'app' . DIRECTORY_SEPARATOR . 'Mail.php' );
require_once( $directory . 'app' . DIRECTORY_SEPARATOR . 'Main.php' );
require_once( $directory . 'app' . DIRECTORY_SEPARATOR . 'Menu.php' );
require_once( $directory . 'app' . DIRECTORY_SEPARATOR . 'Parse.php' );
require_once( $directory . 'app' . DIRECTORY_SEPARATOR . 'Subscription.php' );
require_once( $directory . 'app' . DIRECTORY_SEPARATOR . 'Sitenotice.php' );
require_once( $directory . 'app' . DIRECTORY_SEPARATOR . 'Validation.php' );

require_once( 'vendor/autoload.php' );

$main = new LaterPay_Migrator_Main();

add_action( 'init', array( $main, 'init' ) );

if ( ! function_exists( 'get_plugin_data' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
}

/**
 * Get the plugin settings.
 *
 * @return LaterPay_Model_Config
 */
function get_laterpay_migrator_config() {
    $config = new LaterPay_Model_Config();

    // plugin default settings for paths and directories
    $plugin_dir_path = plugin_dir_path( __FILE__ );
    $config->set( 'plugin_dir_path',    $plugin_dir_path );
    $config->set( 'plugin_file_path',   __FILE__ );
    $config->set( 'plugin_base_name',   plugin_basename( __FILE__ ) );
    $config->set( 'plugin_url',         plugins_url( '/', __FILE__ ) );
    $config->set( 'view_dir',           $plugin_dir_path . 'views/' );

    // 'laterpay' plugin paths
    $laterpay_plugin_url  = plugins_url( '/laterpay/', 'laterpay' );
    $laterpay_plugin_dir  = WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . 'laterpay/';
    $laterpay_plugin_data = get_plugin_data( $laterpay_plugin_dir . 'laterpay.php' );
    $config->set( 'lp_version',         $laterpay_plugin_data['Version'] );
    $config->set( 'lp_plugin_url',      $laterpay_plugin_url );
    $config->set( 'lp_view_dir',        $laterpay_plugin_dir . 'views/' );
    $config->set( 'lp_css_url',         $laterpay_plugin_url . 'built_assets/css/' );
    $config->set( 'lp_js_url',          $laterpay_plugin_url . 'built_assets/js/' );
    $config->set( 'lp_image_url',       $laterpay_plugin_url . 'built_assets/img/' );

    // migration plugin assets
    $plugin_url = $config->get( 'plugin_url' );
    $config->set( 'css_url',            $plugin_url . 'built_assets/css/' );
    $config->set( 'js_url',             $plugin_url . 'built_assets/js/' );
    $config->set( 'image_url',          $plugin_url . 'built_assets/img/' );
    $config->set( 'upload_dir',         $plugin_dir_path . 'upload/' );

    return $config;
}
