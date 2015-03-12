<?php

class LaterPay_Migrator_Main {

    /**
     * Init WP hooks.
     *
     * @return [type] [description]
     */
    public function init() {
        // register Ajax actions
        add_action( 'wp_ajax_laterpay_migrator_get_purchase_url',   array( $this, 'ajax_get_purchase_link' ) );
        add_action( 'admin_menu',                                   array( new LaterPay_Migrator_Settings, 'add_settings_page' ) );
        add_action( 'template_redirect',                            array( $this, 'remove_subscriber_role') );
        // TODO: add file upload and processing to the settings?
        add_action( 'wp_ajax_laterpay_migrator_upload_file',        array( $this, 'ajax_upload_file' ) );
        // TODO: why is wp_ajax_parse_csv not prefixed with 'laterpay_migrator_'?
        add_action( 'wp_ajax_parse_csv',                            array( $this, 'ajax_parse_csv' ) );

        // include styles and scripts only if user is logged in and not in admin area
        if ( ! is_admin() && is_user_logged_in() ) {
            add_action( 'wp_footer', array( $this, 'render_migration_sitenotice' ) );

            wp_register_style(
                'laterpay-migrator-frontend',
                LATERPAY_MIGRATOR_CSS_URL . 'laterpay-migrator-frontend.css'
            );
            wp_enqueue_style( 'laterpay-migrator-frontend' );

            wp_register_script(
                'laterpay-migrator-frontend',
                LATERPAY_MIGRATOR_JS_URL . 'laterpay-migrator-frontend.js',
                array( 'jquery' ),
                false,
                true
            );
            wp_enqueue_script( 'laterpay-migrator-frontend' );

            wp_localize_script(
                'lpcustom-front',
                'lpMigratorVars',
                array(
                    'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                )
            );
        }
    }

    /**
     * Display sitenotice with LatePay purchase link for existing subscribers.
     *
     * @return [type] [description]
     */
    public function render_migration_sitenotice() {
        if ( is_user_logged_in() ) {
            if ( LaterPay_Migrator_Subscription::is_active() ) {
                $text = '
                    <div style="float: right;">
                        <a href="#" id="lp_buySubscription">Purchase</a>
                        <a href="#" id="lp_fakeButton" class="lp_js_doPurchase" style="display:none;" data-laterpay=""></a>
                    </div>
                ';

                // render notice with LaterPay purchase button
                echo $text;
            }
        }

        return;
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
     * Ajax method to upload file.
     *
     * @wp-hook wp_ajax_laterpay_migrator_upload_file
     *
     * @return void
     */
    public function ajax_upload_file() {
        exit;
    }

    /**
     * Ajax method to parse CSV.
     *
     * @wp-hook wp_ajax_laterpay_migrator_upload_file
     *
     * @return void
     */
    public function ajax_parse_csv() {
        $result = LaterPay_Migrator_ParseCSV::parse_csv();

        exit;
    }

    /**
     * [get_purchase_url description]
     *
     * @return [type] [description]
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

        $subscription_data = LaterPay_Migrator_Subscription::get_subscription_data();
        $time_pass         = LaterPay_Migrator_Subscription::get_time_pass_by_subscription( $subscription_data );

        if ( ! $time_pass ) {
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
            'expiry'        => '+' . $expiry_time,
            'url'           => $url,
            'title'         => $time_pass['title'],
        );

        return $client->get_add_url( $params );
    }

    /**
     * Remove role 'subscriber' from user, if he has already migrated to using LaterPay time passes.
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
            LaterPay_Migrator_Subscription::mark_as_migrated_to_laterpay( true );

            // remove role 'subscriber' from user, if he has already migrated to using LaterPay time passes
            // TODO: Should we really remove this role from the user?
        }

        wp_redirect( $redirect_url );
        // exit script after redirect was set
        exit;
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
        LaterPay_Migrator_Install::install();

        // register the notify about_subscription_expiry cron job
        // wp_schedule_event( time(), 'hourly', 'laterpay_notify_about_subscription_expiry' );
    }

    /**
     * Callback to deactivate the plugin.
     *
     * @wp-hook register_deactivation_hook
     *
     * @return void
     */
    public static function deactivate() {
        // wp_clear_scheduled_hook( 'laterpay_notify_about_subscription_expiry' );
    }
}
