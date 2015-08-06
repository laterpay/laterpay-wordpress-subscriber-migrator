<?php

class LaterPay_Migrator_Controller_Frontend_Migration extends LaterPay_Controller_Base {
    /**
     * @see LaterPay_Core_Event_SubscriberInterface::get_subscribed_events()
     */
    public static function get_subscribed_events() {
        return array(
            'laterpay_loaded' => array(
                array( 'laterpay_on_admin_view', 200 ),
                array( 'migrate_subscriber_to_laterpay' ),
                array( 'redirect', 5 ),
            ),
        );
    }

    /**
     * Redirect user if redirect_url was setup
     * @param LaterPay_Core_Event $event
     */
    public function redirect( LaterPay_Core_Event $event ) {
        if ( $event->has_argument( 'redirect_url' ) ) {
            wp_redirect( $event->get_argument( 'redirect_url' ) );
            // exit script after redirect was set
            exit;
        }
    }

    /**
     * Process user migration to LaterPay.
     *
     * @wp-hook template_redirect
     * @param LaterPay_Core_Event $event
     *
     * @return void
     */
    public function migrate_subscriber_to_laterpay( LaterPay_Core_Event $event ) {
        // subp == subscription purchase
        if ( ! isset( $_GET['subp'] ) || ! sanitize_text_field( $_GET['subp'] ) ) {
            return;
        }

        // tpid == time pass id
        if ( ! isset( $_GET['tpid'] ) || ! sanitize_text_field( $_GET['tpid'] ) ) {
            return;
        }

        $redirect_url = home_url();
        $event->set_argument( 'redirect_url', $redirect_url );

        $user = LaterPay_Migrator_Helper_Subscription::get_current_user_data();
        if ( ! $user ) {
            return;
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
        $result = $laterpay_client->get_access( array( sanitize_text_field( $_GET['tpid'] ) ) );
        if ( empty( $result ) || ! array_key_exists( 'articles', $result ) ) {
            return;
        }

        $has_access = false;

        foreach ( $result['articles'] as $article_access ) {
            $access = (bool) $article_access['access'];
            if ( $access ) {
                $has_access = true;
            }
        }

        if ( $has_access ) {
            // remove or add user roles as specified
            LaterPay_Migrator_Helper_Subscription::change_user_role();

            // mark user as migrated to LaterPay
            LaterPay_Migrator_Model_Migration::set_flag( $user->user_email, 'is_migrated_to_laterpay' );
        }
    }
}
