<?php

class LaterPay_Migrator_Controller_Admin_Sitenotice extends LaterPay_Controller_Base {
    /**
     * @see LaterPay_Core_Event_SubscriberInterface::get_subscribed_events()
     */
    public static function get_subscribed_events() {
        return array(
            'wp_ajax_laterpay_migrator_get_purchase_url' => array(
                array( 'laterpay_on_admin_view', 200 ),
                array( 'laterpay_on_ajax_send_json', 300 ),
                array( 'ajax_get_purchase_link' ),
            ),
            'laterpay_post_footer' => array(
                array( 'is_migrator_active_and_working', 200 ),
                array( 'is_frontend_and_loggedin', 100 ),
                array( 'render_page' ),
            ),
            'laterpay_send_expiry_notification' => array(
                array( 'is_migrator_active_and_working', 200 ),
                array( 'send_expiry_notification' ),
            ),
        );
    }

    /**
     * Load assets.
     *
     * @return void
     */
    public function load_assets() {
        // load post-view styles from 'laterpay' plugin plus migrator plugin-specific styles
        wp_register_style(
            'laterpay-post-view',
            $this->config->css_url . 'laterpay-post-view.css',
            array(),
            $this->config->version
        );
        wp_register_style(
            'laterpay-migrator-frontend',
            $this->config->get( 'css_url' ) . 'laterpay-migrator-frontend.css'
        );
        wp_enqueue_style( 'laterpay-post-view' );
        wp_enqueue_style( 'laterpay-migrator-frontend' );

        // load migrator plugin-specific Javascript
        wp_register_script(
            'laterpay-migrator-frontend',
            $this->config->get( 'js_url' ) . 'laterpay-migrator-frontend.js',
            array( 'jquery' ),
            false,
            true
        );
        wp_enqueue_script( 'laterpay-migrator-frontend' );

        // pass variables to Javascript
        wp_localize_script(
            'laterpay-migrator-frontend',
            'lpMigratorVars',
            array(
                'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            )
        );
    }

    /**
     * Display sitenotice with LaterPay purchase link for existing subscribers.
     *
     * @return void
     */
    public function render_page() {
        if ( is_user_logged_in() ) {
            // check, if user has lost access to his switching time pass
            $lost_access = LaterPay_Migrator_Helper_Subscription::lost_access();
            if ( LaterPay_Migrator_Helper_Subscription::is_active() || $lost_access ) {
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

    /**
     * Ajax method to get purchase URL.
     *
     * @wp-hook wp_ajax_laterpay_migrator_get_purchase_url
     * @param LaterPay_Core_Event $event
     *
     * @return void
     */
    public function ajax_get_purchase_link( LaterPay_Core_Event $event ) {
        if ( is_user_logged_in() ) {
            $url = LaterPay_Migrator_Helper_Common::get_purchase_url();

            if ( $url ) {
                $event->set_result(
                    array(
                        'success' => true,
                        'url'     => $url,
                    )
                );
                return;
            }
        }

        $event->set_result(
            array(
                'success' => false,
            )
        );
    }

    /**
     * To include styles and scripts only, if user is logged in and not in admin area
     *
     * @param LaterPay_Core_Event $event
     */
    public function is_frontend_and_loggedin( LaterPay_Core_Event $event ) {
        // include styles and scripts only, if user is logged in and not in admin area
        if ( ! is_admin() && is_user_logged_in() ) {
            return;
        }
        $event->stop_propagation();
    }

    /**
     * @param LaterPay_Core_Event $event
     */
    public function is_migrator_active_and_working( LaterPay_Core_Event $event ) {
        if ( get_option( 'laterpay_migrator_is_active' ) && ! LaterPay_Migrator_Helper_Subscription::is_migration_completed() ) {
            return;
        }
        $event->stop_propagation();
    }

    /**
     *  Send email notification.
     *
     * @param LaterPay_Core_Event $event
     */
    public function send_expiry_notification( LaterPay_Core_Event $event ) {
        list( $modifier ) = $event->get_arguments() + array( '' );
        $mail_controller = new LaterPay_Migrator_Core_Mail();

        $event->set_result( $mail_controller->send_notification_email( $modifier ) );
    }
}
