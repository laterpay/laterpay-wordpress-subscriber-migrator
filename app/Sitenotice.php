<?php

class LaterPay_Migrator_Sitenotice extends LaterPay_Controller_Abstract
{

    /**
     * Load assets.
     *
     * @return void
     */
    public function load_assets() {
        wp_register_style(
            'laterpay-migrator-frontend',
            $this->config->get( 'css_url' ) . 'laterpay-migrator-frontend.css'
        );
        wp_enqueue_style( 'laterpay-migrator-frontend' );

        wp_register_script(
            'laterpay-migrator-frontend',
            $this->config->get( 'js_url' ) . 'laterpay-migrator-frontend.js',
            array( 'jquery' ),
            false,
            true
        );
        wp_enqueue_script( 'laterpay-migrator-frontend' );

        wp_localize_script(
            'laterpay-migrator-frontend',
            'lpMigratorVars',
            array(
                'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            )
        );
    }

    /**
     * Display sitenotice with LatePay purchase link for existing subscribers.
     *
     * @return void
     */
    public function render_page() {
        if ( is_user_logged_in() ) {
            if ( LaterPay_Migrator_Subscription::is_active() ) {
                $this->load_assets();

                // assign variables to the view template
                $view_args = array(
                    'message'     => get_option( 'laterpay_migrator_sitenotice_message' ),
                    'button_text' => get_option( 'laterpay_migrator_sitenotice_button_text' ),
                    'bg_color'    => get_option( 'laterpay_migrator_sitenotice_bg_color' ),
                    'text_color'  => get_option( 'laterpay_migrator_sitenotice_text_color' ),
                );

                // render sitenotice with LaterPay purchase button
                $this->assign( 'laterpay_migrator', $view_args );
                $this->render( 'frontend/partials/sitenotice' );
            }
        }
    }
}