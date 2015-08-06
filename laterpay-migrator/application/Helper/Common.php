<?php

class LaterPay_Migrator_Helper_Common
{

    /**
     * Get purchase URL for remaining subscription time of user.
     *
     * @return string
     */
    public static function get_purchase_url() {
        $currency   = get_option( 'laterpay_currency' );
        $price      = 0; // switching to a timepass is free

        $client_options = LaterPay_Helper_Config::get_php_client_options();
        $client = new LaterPay_Client(
            $client_options['cp_key'],
            $client_options['api_key'],
            $client_options['api_root'],
            $client_options['web_root'],
            $client_options['token_name']
        );

        $subscription_data = LaterPay_Migrator_Helper_Subscription::get_user_subscription_data();
        $time_pass_id      = LaterPay_Migrator_Helper_Subscription::get_time_pass_id( $subscription_data );
        $time_pass         = LaterPay_Helper_TimePass::get_time_pass_by_id( $time_pass_id );

        if ( ! $time_pass || ! $subscription_data ) {
            return false;
        }

        $expiry_time = LaterPay_Migrator_Helper_Subscription::get_expiry_time( $subscription_data );

        // prepare purchase URL
        $url_params = array(
            'tpid' => LaterPay_Helper_TimePass::get_tokenized_time_pass_id( $time_pass['pass_id'] ),
            'time' => time(),
            'subp' => true,
        );

        $url  = add_query_arg( $url_params, home_url() );
        $hash = LaterPay_Helper_Pricing::get_hash_by_url( $url );
        $url  = $url . '&hash=' . $hash;

        // parameters for LaterPay purchase form
        $params = array(
            'article_id'    => LaterPay_Helper_TimePass::get_tokenized_time_pass_id( $time_pass['pass_id'] ),
            'pricing'       => $currency . ( $price * 100 ),
            'expiry'        => $expiry_time,
            'url'           => $url,
            'title'         => __( 'Free Switching Time Pass' , 'laterpay-migrator' ),
        );

        return $client->get_add_url( $params );
    }
}
