<?php

class LaterPay_Migrator_Bootstrap {
    /**
     * Contains all settings for the plugin.
     *
     * @var LaterPay_Model_Config
     */
    private $config;

    /**
     * @param LaterPay_Model_Config $config
     *
     * @return LaterPay_Migrator_Bootstrap
     */
    public function __construct( LaterPay_Model_Config $config ) {
        $this->config = $config;

        // load the textdomain for 'plugins_loaded', 'register_activation_hook', and 'register_deactivation_hook'
        $textdomain_dir     = dirname( $config->get( 'plugin_base_name' ) );
        $textdomain_path    = $textdomain_dir . $config->get( 'text_domain_path' );
        load_plugin_textdomain(
            'laterpay-migrator',
            false,
            $textdomain_path
        );
    }

    /**
     * Init WP hooks.
     *
     * @return void
     */
    public function run() {
        // register Ajax actions
        $parse_controller = new LaterPay_Migrator_Controller_Admin_Parse( $this->config );
        laterpay_event_dispatcher()->add_subscriber( $parse_controller );

        $subscription_controller = new LaterPay_Migrator_Controller_Admin_Subscription( $this->config );
        laterpay_event_dispatcher()->add_subscriber( $subscription_controller );

        $migration_controller = new LaterPay_Migrator_Controller_Frontend_Migration( $this->config );
        laterpay_event_dispatcher()->add_subscriber( $migration_controller );

        $admin_migration_controller = new LaterPay_Migrator_Controller_Admin_Migration( $this->config );
        laterpay_event_dispatcher()->add_subscriber( $admin_migration_controller );

        $sitenotice_controller = new LaterPay_Migrator_Controller_Admin_Sitenotice( $this->config );
        laterpay_event_dispatcher()->add_subscriber( $sitenotice_controller );

        LaterPay_Hooks::add_wp_action( 'send_expiry_notification', 'laterpay_send_expiry_notification' );
    }

    /**
     * Install callback to create custom database tables.
     *
     * @wp-hook register_activation_hook
     *
     * @return void
     */
    public static function activate() {
        // install table for storing users to be migrated and their respective migration status
        $install_controller = new LaterPay_Migrator_Controller_Install();
        $install_controller->install();

        // register cron jobs for email sending
        wp_schedule_event( mktime( 0, 0, 0, date( 'n' ), date( 'j' ) + 1, date( 'Y' ) ), 'daily', 'send_expiry_notification', array( 'before' ) );
        wp_schedule_event( mktime( 0, 0, 0, date( 'n' ), date( 'j' ) + 1, date( 'Y' ) ), 'daily', 'send_expiry_notification', array( 'after' ) );
    }

    /**
     * Callback to deactivate the plugin.
     *
     * @wp-hook register_deactivation_hook
     *
     * @return void
     */
    public static function deactivate() {
        // pause migration process on deactivation
        update_option( 'laterpay_migrator_is_active', 0 );

        wp_clear_scheduled_hook( 'send_expiry_notification', array( 'before' ) );
        wp_clear_scheduled_hook( 'send_expiry_notification', array( 'after' ) );
    }
}
