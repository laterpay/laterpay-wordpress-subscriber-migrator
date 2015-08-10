<?php

class LaterPay_Migrator_Controller_Frontend_Migration extends LaterPay_Controller_Base {
    /**
     * @see LaterPay_Core_Event_SubscriberInterface::get_subscribed_events()
     */
    public static function get_subscribed_events() {
        return array(
            'laterpay_loaded' => array(
                array( 'migrate_subscriber_to_laterpay' ),
            ),
        );
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
        $request_method = isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( $_SERVER['REQUEST_METHOD'] ) : '';
        $request        = new LaterPay_Core_Request();
        $buy            = $request->get_param( 'subp' ); // subp == subscription purchase

        if ( ! isset( $buy ) ) {
            return;
        }

        $user = LaterPay_Migrator_Helper_Subscription::get_current_user_data();
        if ( ! $user ) {
            return;
        }

        $lptoken = $request->get_param( 'lptoken' );
        $hmac    = $request->get_param( 'hmac' );
        $tpid    = $request->get_param( 'tpid' ); // tpid == time pass id

        $client_options  = LaterPay_Helper_Config::get_php_client_options();
        $laterpay_client = new LaterPay_Client(
            $client_options['cp_key'],
            $client_options['api_key'],
            $client_options['api_root'],
            $client_options['web_root'],
            $client_options['token_name']
        );

        if ( LaterPay_Client_Signing::verify( $hmac, $laterpay_client->get_api_key(), $request->get_data( 'get' ), get_permalink(), $request_method ) ) {
            // check token
            if ( ! empty( $lptoken ) ) {
                $laterpay_client->set_token( $lptoken );
            } elseif ( ! $laterpay_client->has_token() ) {
                $laterpay_client->acquire_token();
            }
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
        $result = $laterpay_client->get_access( array( $tpid ) );
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

        wp_redirect( home_url() );
        // exit script after redirect was set
        exit;
    }
}
