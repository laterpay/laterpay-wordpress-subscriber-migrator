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

if ( ! function_exists( 'get_plugin_data' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
}

$directory = dirname( __FILE__ ) . DIRECTORY_SEPARATOR;

define( 'DS', DIRECTORY_SEPARATOR );
define( 'LP_MIGRATOR_DIR', $directory );

register_activation_hook( __FILE__, array( 'LaterPay_Migrator_Bootstrap', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'LaterPay_Migrator_Bootstrap', 'deactivate' ) );

require_once( LP_MIGRATOR_DIR . 'app' . DS . 'Bootstrap.php' );
require_once( LP_MIGRATOR_DIR . 'app' . DS . 'Controller' . DS . 'Install.php' );
require_once( LP_MIGRATOR_DIR . 'app' . DS . 'Model' . DS . 'Migration.php' );

$bootstrap = new LaterPay_Migrator_Bootstrap();
add_action( 'init', array( $bootstrap, 'init' ) );

// check if LaterPay plugin active
if ( is_plugin_active( 'laterpay/laterpay.php' ) ) {

    if ( ! class_exists( 'LaterPay_Autoloader' ) ) {
        require_once( WP_PLUGIN_DIR . DS . 'laterpay' . DS . 'laterpay_load.php' );
        require_once( WP_PLUGIN_DIR . DS . 'laterpay/application/Controller/' . 'Abstract.php' );
        require_once( WP_PLUGIN_DIR . DS . 'laterpay/application/Form/' . 'Abstract.php' );
    }

    // controllers
    require_once( LP_MIGRATOR_DIR . 'app' . DS . 'Controller' . DS . 'Admin' . DS . 'Migration.php' );
    require_once( LP_MIGRATOR_DIR . 'app' . DS . 'Controller' . DS . 'Mail.php' );
    require_once( LP_MIGRATOR_DIR . 'app' . DS . 'Controller' . DS . 'Migration.php' );
    require_once( LP_MIGRATOR_DIR . 'app' . DS . 'Controller' . DS . 'Parse.php' );
    require_once( LP_MIGRATOR_DIR . 'app' . DS . 'Controller' . DS . 'Sitenotice.php' );
    require_once( LP_MIGRATOR_DIR . 'app' . DS . 'Controller' . DS . 'Subscription.php' );

    // helpers
    require_once( LP_MIGRATOR_DIR . 'app' . DS . 'Helper' . DS . 'Common.php' );
    require_once( LP_MIGRATOR_DIR . 'app' . DS . 'Helper' . DS . 'Mail.php' );
    require_once( LP_MIGRATOR_DIR . 'app' . DS . 'Helper' . DS . 'Parse.php' );
    require_once( LP_MIGRATOR_DIR . 'app' . DS . 'Helper' . DS . 'Subscription.php' );

    // form
    require_once( LP_MIGRATOR_DIR . 'app' . DS . 'Form' . DS . 'Activation.php' );

    require_once( 'vendor/autoload.php' );

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
} else {
    deactivate_plugins( plugin_basename( __FILE__) );
}
