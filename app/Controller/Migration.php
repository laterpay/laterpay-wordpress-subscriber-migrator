<?php

class LaterPay_Migrator_Controller_Migration
{
    /**
     * Process user migration to LaterPay.
     *
     * @wp-hook template_redirect
     *
     * @return void
     */
    public function migrate_subscriber_to_laterpay() {
        if ( ! isset( $_GET['subp'] ) || ! $_GET['subp'] ) {
            return;
        }

        if ( ! isset( $_GET['tpid'] ) || ! $_GET['tpid'] ) {
            return;
        }

        $redirect_url = home_url();

        $user = LaterPay_Migrator_Helper_Subscription::get_current_user_data();
        if ( ! $user ) {
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
            LaterPay_Migrator_Helper_Subscription::change_user_role();
            LaterPay_Migrator_Model_Migration::set_flag( $user->user_email, 'is_migrated_to_laterpay' );
        }

        wp_redirect( $redirect_url );
        // exit script after redirect was set
        exit;
    }
}