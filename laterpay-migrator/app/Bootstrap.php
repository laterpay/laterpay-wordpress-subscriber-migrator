<?php

class LaterPay_Migrator_Bootstrap
{

    /**
     * Init WP hooks.
     *
     * @return void
     */
    public function init() {

        // get plugin config
        $config = get_laterpay_migrator_config();

        // register Ajax actions
        $parse_controller = new LaterPay_Migrator_Controller_Parse();
        add_action( 'wp_ajax_laterpay_migrator_file_upload',        array( $parse_controller, 'file_upload' ) );

        $subscription_controller = new LaterPay_Migrator_Controller_Subscription();
        add_action( 'wp_ajax_laterpay_migrator_activate',           array( $subscription_controller, 'activate_migration_process' ) );

        $migration_controller = new LaterPay_Migrator_Controller_Migration();
        add_action( 'template_redirect',                            array( $migration_controller, 'migrate_subscriber_to_laterpay' ) );

        $admin_migration_controller = new LaterPay_Migrator_Controller_Admin_Migration( $config );
        add_filter( 'modify_menu',                                  array( $admin_migration_controller, 'add_menu' ) );
        add_action( 'admin_print_footer_scripts',                   array( $admin_migration_controller, 'add_pointers' ) );
        add_action( 'admin_enqueue_scripts',                        array( $admin_migration_controller, 'add_pointers_script' ) );

        $sitenotice_controller = new LaterPay_Migrator_Controller_Sitenotice( $config );
        add_action( 'wp_ajax_laterpay_migrator_get_purchase_url',   array( $sitenotice_controller, 'ajax_get_purchase_link' ) );

        if ( get_option( 'laterpay_migrator_is_active' ) && ! LaterPay_Migrator_Helper_Subscription::is_migration_completed() ) {
            add_action( 'send_expiry_notification',                 array( $this, 'send_expiry_notification' ), 10, 1 );

            // include styles and scripts only, if user is logged in and not in admin area
            if ( ! is_admin() && is_user_logged_in() ) {
                add_action( 'wp_footer',                            array( $sitenotice_controller, 'render_page' ) );
            }
        }

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

    /**
     *  Send email notification.
     */
    public function send_expiry_notification( $modifier ) {
        $mail_controller = new LaterPay_Migrator_Controller_Mail();

        return $mail_controller->send_notification_email( $modifier );
    }
}
