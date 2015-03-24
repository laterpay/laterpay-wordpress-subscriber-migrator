<?php

class LaterPay_Migrator_Main
{

    /**
     * Init WP hooks.
     *
     * @return void
     */
    public function init() {
        if ( ! is_plugin_active( 'laterpay/laterpay.php' ) ) {
            return;
        }

        // register Ajax actions
        $config = get_laterpay_migrator_config();
        add_action( 'wp_ajax_laterpay_migrator_get_purchase_url',   array( $this, 'ajax_get_purchase_link' ) );
        add_action( 'wp_ajax_laterpay_migrator_file_upload',        array( 'LaterPay_Migrator_Parse', 'file_upload' ) );
        add_action( 'wp_ajax_laterpay_migrator_activate',           array( 'LaterPay_Migrator_Subscription', 'activate_subscription' ) );
        add_action( 'template_redirect',                            array( $this, 'remove_subscriber_role' ) );
        add_filter( 'modify_menu',                                  array( $this, 'add_menu' ) );

        if ( get_option( 'laterpay_migrator_is_active' ) && ! LaterPay_Migrator_Subscription::is_migration_completed() ) {
            add_action( 'notify_subscription_expired',              array( 'LaterPay_Migrator_Mail', 'notify_subscription_expired' ) );
            add_action( 'notify_subscription_about_to_expiry',      array( 'LaterPay_Migrator_Mail', 'notify_subscription_about_to_expiry' ) );

            // include styles and scripts only if user is logged in and not in admin area
            if ( ! is_admin() && is_user_logged_in() ) {
                $sitenotice = new LaterPay_Migrator_Sitenotice( $config );
                add_action( 'wp_footer', array( $sitenotice, 'render_page' ) );
            }
        }
    }

    /**
     * Ajax method to get purchase URL.
     *
     * @wp-hook wp_ajax_laterpay_migrator_get_purchase_url
     *
     * @return void
     */
    public function ajax_get_purchase_link() {
        if ( is_user_logged_in() ) {
            $url = self::get_purchase_url();

            if ( $url ) {
                wp_send_json(
                    array(
                        'success' => true,
                        'url'     => $url,
                    )
                );
            }
        }

        wp_send_json(
            array(
                'success' => false,
            )
        );
    }

    /**
     * Get purchased url for user subscription leftover time
     *
     * @return string
     */
    public function get_purchase_url() {
        $currency = get_option( 'laterpay_currency' );
        $price = 0;

        $client_options = LaterPay_Helper_Config::get_php_client_options();
        $client = new LaterPay_Client(
            $client_options['cp_key'],
            $client_options['api_key'],
            $client_options['api_root'],
            $client_options['web_root'],
            $client_options['token_name']
        );

        $subscription_data = LaterPay_Migrator_Subscription::get_user_subscription_data();
        $time_pass_id      = LaterPay_Migrator_Subscription::get_time_pass_id( $subscription_data );
        $time_pass         = (array) LaterPay_Helper_TimePass::get_time_pass_by_id( $time_pass_id );

        if ( ! $time_pass || ! $subscription_data ) {
            return false;
        }

        $expiry_time = LaterPay_Migrator_Subscription::get_expiry_time( $subscription_data );

        // prepare purchase URL
        $url_params = array(
            'tpid' => LaterPay_Helper_TimePass::get_tokenized_time_pass_id( $time_pass['pass_id'] ),
            'time' => time(),
            'subp' => true,
        );

        $url  = add_query_arg( $url_params , home_url() );
        $hash = LaterPay_Helper_Pricing::get_hash_by_url( $url );
        $url  = $url . '&hash=' . $hash;

        // parameters for LaterPay purchase form
        $params = array(
            'article_id'    => LaterPay_Helper_TimePass::get_tokenized_time_pass_id( $time_pass['pass_id'] ),
            'pricing'       => $currency . ( $price * 100 ),
            'expiry'        => $expiry_time,
            'url'           => $url,
            'title'         => $time_pass['title'],
        );

        return $client->get_add_url( $params );
    }

    /**
     * Process user migration to the LaterPay
     *
     * @wp-hook template_redirect
     *
     * @return void
     */
    public function remove_subscriber_role() {
        if ( ! isset( $_GET['subp'] ) || ! $_GET['subp'] ) {
            return;
        }

        if ( ! isset( $_GET['tpid'] ) || ! $_GET['tpid'] ) {
            return;
        }

        $redirect_url = home_url();

        if ( ! is_user_logged_in() ) {
            wp_redirect( $redirect_url );
            // exit script after redirect was set
            exit;
        }

        // check access for the respective time pass
        $client_options = LaterPay_Helper_Config::get_php_client_options();
        $laterpay_client = new LaterPay_Client(
            $client_options['cp_key'],
            $client_options['api_key'],
            $client_options['api_root'],
            $client_options['web_root'],
            $client_options['token_name']
        );

        // merge time passes and post id arrays before check
        $result = $laterpay_client->get_access( array( $_GET['tpid'] ) );
        if ( empty( $result ) || ! array_key_exists( 'articles', $result ) ) {
            wp_redirect( $redirect_url );
            // exit script after redirect was set
            exit;
        }

        $has_access = false;

        foreach ( $result['articles'] as $article_access ) {
            $access = (bool) $article_access['access'];
            if ( $access ) {
                $has_access = true;
            }
        }

        if ( $has_access ) {
            // mark user as migrated to LaterPay
            LaterPay_Migrator_Subscription::mark_user( 'is_migrated_to_laterpay' );
            LaterPay_Migrator_Subscription::change_user_role();
        }

        wp_redirect( $redirect_url );
        // exit script after redirect was set
        exit;
    }

    /**
     * Add 'migration' tab to the 'laterpay' plugin backend.
     *
     * @param $menu
     *
     * @return mixed
     */
    public function add_menu( $menu ) {
        $menu_page = new LaterPay_Migrator_Menu( get_laterpay_migrator_config() );

        $menu[ 'migration' ] = array(
            'url'   => 'laterpay-migration-tab',
            'title' => __( 'Migration', 'laterpay_migrator' ),
            'cap'   => 'activate_plugins',
            'run'   => array( $menu_page, 'render_page' ),
        );

        return $menu;
    }

    /**
     * Install callback to create custom database tables.
     *
     * @wp-hook register_activation_hook
     *
     * @return void
     */
    public static function activate() {
        // check if LaterPay plugin installed
        if ( ! is_plugin_active( 'laterpay/laterpay.php' ) ) {
            _e( 'LaterPay plugin should be installed and activated.', 'laterpay_migrator');
            exit;
        }

        // install table for storing users to be migrated and their respective migration status
        $install = new LaterPay_Migrator_Install;
        $install->install();

        // register cron jobs for email sending
        wp_schedule_event( mktime( 23, 59, 0, date( 'n' ), date( 'j' ), date( 'Y' ) ), 'daily', 'notify_subscription_expired' );
        wp_schedule_event( mktime( 23, 58, 0, date( 'n' ), date( 'j' ), date( 'Y' ) ), 'daily', 'notify_subscription_about_to_expiry' );
    }

    /**
     * Callback to deactivate the plugin.
     *
     * @wp-hook register_deactivation_hook
     *
     * @return void
     */
    public static function deactivate() {
        // pause migration process on deacttivation
        update_option( 'laterpay_migrator_is_active', 0 );

        wp_clear_scheduled_hook( 'notify_subscription_expired' );
        wp_clear_scheduled_hook( 'notify_subscription_about_to_expiry' );
    }
}
