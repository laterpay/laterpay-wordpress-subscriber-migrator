<?php

class LaterPay_Migrator_Mail {

    /**
     * Notify users about their subscriptions expired
     *
     * @return array $expired_subscriptions
     */
    public static function notify_subscription_expired() {
        $expired_subscriptions = LaterPay_Migrator_Subscription::get_subsriptions_by_expiry( true );
        if ( $expired_subscriptions ) {
            $data = array();
            foreach ( $expired_subscriptions as $subscription ) {
                // set user email to data
                $data[] = array( 'email' => $subscription['email'] );
                // set user notified about subscription expired flag
                LaterPay_Migrator_Subscription::mark_user( 'was_notified_after_expiry' );
            }
            if ( $data ) {
                // notify user that his subscription expired
                $campaign_name = get_option( 'laterpay_migrator_mailchimp_campaign_after_expired' );
                self::send_notification_email( $campaign_name, $data );
            }
        }

        return $expired_subscriptions;
    }

    /**
     * Notify user that his subscription expired already
     *
     * @param string $campaign_name mailchimp campaign name
     * @param array  $data          array of emails
     *
     * @return bool|string          bool or error message
     */
    public static function send_notification_email( $campaign_name, $data = array() ) {
        if ( ! $data || ! is_array( $data ) || ! $campaign_name ) {
            return false;
        }

        // wrap in try catch block
        try {
            // init mailchimp
            $mailchimp    = self::init_mailchimp();

            // get campaign
            $campaign     = $mailchimp->campaigns->getList( array( 'title' => $campaign_name ) );
            $campaign_id  = $campaign['data'][0]['id'];
            $list_id      = $campaign['data'][0]['list_id'];

            // subsribe users from $data to this list
            $subscribe_data = array();
            foreach ( $data as $email ) {
                $subscribe_data[] = array( 'email' => $email );
            }
            $mailchimp->lists->batchSubscribe( $list_id, $subscribe_data, false );

            // send campaign
            $r_campaign    = $mailchimp->campaigns->replicate( $campaign_id );
            $r_campaign_id = $r_campaign['id'];
            $mailchimp->campaigns->send( $r_campaign_id );

            // unsubscribe users from $data
            $mailchimp->lists->batchUnsubscribe( $list_id, $data, false, false );

        } catch ( Exception $e ) {
            return $e->getMessage();
        }

        return true;
    }

    /**
     * Notify users about their subscriptions about to expiry ( 2 weeks )
     */
    public static function notify_subscription_about_to_expiry() {
        $subscriptions = LaterPay_Migrator_Subscription::get_subsriptions_by_expiry();
        if ( $subscriptions ) {
            $data = array();
            foreach ( $subscriptions as $subscription ) {
                // set user email to data
                $data[] = array( 'email' => $subscription['email'] );
                // set user notified about subscription expired flag
                LaterPay_Migrator_Subscription::mark_user( 'was_notified_before_expiry' );
            }
            if ( $data ) {
                // notify user that his subscription expired
                $campaign_name = get_option( 'laterpay_migrator_mailchimp_campaign_before_expired' );
                self::send_notification_email( $campaign_name, $data );
            }
        }

        return $subscriptions;
    }

    /**
     * Init mailchimp
     *
     * @return Mailchimp
     */
    public static function init_mailchimp() {
        $api_key   = get_option( 'laterpay_migrator_mailchimp_api_key' );
        $mailchimp = new Mailchimp( $api_key );
        // disable ssl verification
        curl_setopt($mailchimp->ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($mailchimp->ch, CURLOPT_SSL_VERIFYPEER, 0);

        return $mailchimp;
    }
}
