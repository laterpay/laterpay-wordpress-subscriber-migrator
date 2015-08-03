<?php
/*
 * Plugin Name: LaterPay Subscriber Migrator
 * Plugin URI: https://github.com/laterpay/laterpay-wordpress-subscriber-migrator
 * Description: Extension for the LaterPay plugin to migrate existing subscribers to LaterPay. Requires the LaterPay WordPress plugin > v0.9.11.2.
 * Author: LaterPay GmbH and Aliaksandr Vahura
 * Version: 0.9
 * Author URI: https://laterpay.net/
 * Text Domain: laterpay-migrator
 * Domain Path: /languages
 */

// make sure we don't expose any info when called directly
if ( ! function_exists( 'add_action' ) ) {
    echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
    exit;
}

// Kick-off
// Specific LaterPay hook, this plugin will work only if main plugin is loaded
add_action( 'laterpay_ready', 'laterpay_migrator_init' );
add_action( 'admin_init', 'laterpay_migrator_force_deactivate' );

register_activation_hook( __FILE__, 'laterpay_migrator_activate' );
register_deactivation_hook( __FILE__, 'laterpay_migrator_deactivate' );

/**
 * Callback for starting the plugin.
 *
 * @wp-hook plugins_loaded
 *
 * @return void
 */
function laterpay_migrator_init() {
    laterpay_migrator_before_start();
    // Write init code here
    $bootstrap = new LaterPay_Migrator_Bootstrap();
    $bootstrap->init();
}

/**
 * Callback for activating the plugin.
 *
 * @wp-hook register_activation_hook
 *
 * @return void
 */
function laterpay_migrator_activate() {
    if ( ! is_plugin_active( 'laterpay/laterpay.php' ) ) {
        return;
    }
    laterpay_migrator_before_start();
    LaterPay_Migrator_Bootstrap::activate();
}

/**
 * Callback for deactivating the plugin.
 *
 * @wp-hook register_deactivation_hook
 *
 * @return void
 */
function laterpay_migrator_deactivate() {
    LaterPay_Migrator_Bootstrap::deactivate();
}

/**
 * Callback for deactivating the plugin.
 *
 * @wp-hook register_deactivation_hook
 *
 * @return void
 */
function laterpay_migrator_force_deactivate() {
    if ( ! is_plugin_active( 'laterpay/laterpay.php' ) ) {
        deactivate_plugins( plugin_basename( __FILE__ ) );
    }
}

/**
 * Run before init, activate and deactivate to register our autoload paths.
 *
 * @return void
 */
function laterpay_migrator_before_start() {
     $dir = dirname( __FILE__ ) . DIRECTORY_SEPARATOR;
    LaterPay_AutoLoader::register_namespace( $dir . 'app', 'LaterPayMigrator' );
    LaterPay_AutoLoader::register_directory( $dir . 'vendor' . DIRECTORY_SEPARATOR . 'mailchimp' . DIRECTORY_SEPARATOR . 'mailchimp' . DIRECTORY_SEPARATOR . 'src' );
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
    $laterpay_plugin_dir  = WP_PLUGIN_DIR . DS . 'laterpay/';
    $laterpay_plugin_data = get_plugin_data( $laterpay_plugin_dir . 'laterpay.php' );
    $config->set( 'lp_version',         $laterpay_plugin_data['Version'] );
    $config->set( 'lp_plugin_url',      $laterpay_plugin_url );
    $config->set( 'lp_view_dir',        $laterpay_plugin_dir . 'views/' );
    $config->set( 'lp_css_url',         $laterpay_plugin_url . 'built_assets/css/' );
    $config->set( 'lp_js_url',          $laterpay_plugin_url . 'built_assets/js/' );
    $config->set( 'lp_image_url',       $laterpay_plugin_url . 'built_assets/img/' );

    // migrator plugin assets
    $plugin_url = $config->get( 'plugin_url' );
    $config->set( 'css_url',            $plugin_url . 'built_assets/css/' );
    $config->set( 'js_url',             $plugin_url . 'built_assets/js/' );
    $config->set( 'image_url',          $plugin_url . 'built_assets/img/' );
    $config->set( 'upload_dir',         $plugin_dir_path . 'upload/' );

    // migrator logger
    $upload_dir = wp_upload_dir();
    $config->set( 'log_dir',            $upload_dir['basedir'] . '/laterpay_migrator_logs/' );
    $config->set( 'cron_log',           'cron.log' );
    $config->set( 'parse_log',          'parse.log' );

    // plugin headers
    $plugin_headers = get_file_data(
        __FILE__,
        array(
            'plugin_name'       => 'Plugin Name',
            'plugin_uri'        => 'Plugin URI',
            'description'       => 'Description',
            'author'            => 'Author',
            'version'           => 'Version',
            'author_uri'        => 'Author URI',
            'textdomain'        => 'Textdomain',
            'text_domain_path'  => 'Domain Path',
        )
    );
    $config->import( $plugin_headers );

    return $config;
}

// test travis build
