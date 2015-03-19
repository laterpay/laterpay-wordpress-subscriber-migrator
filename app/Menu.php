<?php

class LaterPay_Migrator_Menu extends LaterPay_Controller_Abstract
{

    /**
     * Load assets.
     *
     * @return void
     */
    public function load_assets() {
        // load LaterPay admin styles
        wp_register_style(
            'laterpay-backend',
            $this->config->get( 'lp_css_url' ) . 'laterpay-backend.css',
            array(),
            $this->config->get( 'lp_version' )
        );
        wp_register_style(
            'open-sans',
            '//fonts.googleapis.com/css?family=Open+Sans:300italic,400italic,600italic,300,400,600&subset=latin,latin-ext'
        );
        wp_register_style(
            'laterpay-migrator-backend',
            $this->config->get( 'css_url' ) . 'laterpay-migrator-backend.css'
        );
        wp_enqueue_style( 'laterpay-backend' );
        wp_enqueue_style( 'open-sans' );
        wp_enqueue_style( 'laterpay-migrator-backend' );

        // load LaterPay-specific JS
        wp_register_script(
            'laterpay-backend',
            $this->config->get( 'lp_js_url' ) . 'laterpay-backend.js',
            array( 'jquery' ),
            $this->config->get( 'lp_version' ),
            true
        );
        wp_register_script(
            'laterpay-migrator-backend',
            $this->config->get( 'js_url' ) . 'laterpay-migrator-backend.js',
            array( 'jquery' ),
            false,
            true
        );
        wp_enqueue_script( 'laterpay-backend' );
        wp_enqueue_script( 'laterpay-migrator-backend' );

        wp_localize_script(
            'laterpay-migrator-backend',
            'lpMigratorVars',
            array(
                'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            )
        );
    }

    /**
     * Render page.
     *
     * @return void
     */
    public function render_page() {
        $this->load_assets();

        global $wp_roles;

        $timepasses = LaterPay_Helper_TimePass::get_all_time_passes();
        $roles      = $wp_roles->roles;

        // assign variables to the view template
        $view_args = array(
            'plugin_is_in_live_mode'            => (bool) get_option( 'laterpay_plugin_is_in_live_mode', false ),
            'top_nav'                           => $this->get_menu( 'backend/partials/navigation', $this->config->get( 'lp_view_dir' ) ),
            'admin_menu'                        => LaterPay_Helper_View::get_admin_menu(),
            'subscriptions_state'               => LaterPay_Migrator_Subscription::get_subscriptions_state(),
            'mailchimp_api_key'                 => get_option( 'laterpay_migrator_mailchimp_api_key' ),
            'mailchimp_campaign_before_expired' => get_option( 'laterpay_migrator_mailchimp_campaign_before_expired' ),
            'mailchimp_campaign_after_expired'  => get_option( 'laterpay_migrator_mailchimp_campaign_after_expired' ),
            'mailchimp_ssl_connection'          => get_option( 'laterpay_migrator_mailchimp_ssl_connection' ),
            'sitenotice_message'                => get_option( 'laterpay_migrator_sitenotice_message' ),
            'sitenotice_button_text'            => get_option( 'laterpay_migrator_sitenotice_button_text' ),
            'sitenotice_bg_color'               => get_option( 'laterpay_migrator_sitenotice_bg_color' ),
            'sitenotice_text_color'             => get_option( 'laterpay_migrator_sitenotice_text_color' ),
            'products'                          => get_option( 'laterpay_migrator_products' ),
            'timepasses'                        => $timepasses,
            'roles'                             => $roles,
            'products_mapping'                  => get_option( 'laterpay_migrator_products_mapping' ),
        );

        $this->assign( 'laterpay', $view_args );
        $this->render( 'backend/migration' );
    }
}
