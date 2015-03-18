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
                LaterPay_Migrator_Subscription::mark_user( 'expired_notified' );
            }
            if ( $data ) {
                // notify user that his subscription expired
                $campaign_name = get_option( 'lpmigrator_mailchimp_campaign_after_expired' );
                self::send_notification_email( 'Laterpay Migration Expired', $campaign_name, $data );
            }
        }

        return $expired_subscriptions;
    }

    /**
     * Notify user that his subscription expired already
     *
     * @return [type] [description]
     */
    public static function send_notification_email( $list_name, $campaign_name, $data = array() ) {
        if ( ! $data || ! is_array( $data ) ) {
            return;
        }

        // init mailchimp
        $mailchimp = self::init_mailchimp();
        $list      = $mailchimp->lists->getList( array( 'list_name' => $list_name ) );
        $list_id   = $list['data'][0]['id'];

        // subsribe users from $data to this list
        $subscribe_data = array();
        // TODO: array_map can be used here
        foreach ( $data as $email ) {
            $subscribe_data[] = array( 'email' => $email );
        }
        $mailchimp->lists->batchSubscribe( $list_id, $data, false );

        // send campaign
        // TODO: replicate campaign
        $campaign      = $mailchimp->campaigns->getList( array( 'title' => $campaign_name ) );
        $campaign_id   = $campaign['data'][0]['id'];
        $mailchimp->campaigns->send( $campaign_id );

        // unsubscribe users from $data
        $mailchimp->lists->batchUnsubscribe( $list_id, $data, false, false );
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
                LaterPay_Migrator_Subscription::mark_user( 'about_to_expiry_notified' );
            }
            if ( $data ) {
                // notify user that his subscription expired
                $campaign_name = get_option( 'lpmigrator_mailchimp_campaign_before_expired' );
                self::send_notification_email( 'Laterpay Migration About To Expiry', $campaign_name, $data );
            }
        }

        return $subscriptions;
    }

    public static function init_mailchimp() {
        $api_key   = get_option( 'lpmigrator_mailchimp_api_key' );
        $mailchimp = new Mailchimp( $api_key );
        // disable ssl verification
        curl_setopt($mailchimp->ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($mailchimp->ch, CURLOPT_SSL_VERIFYPEER, 0);

        return $mailchimp;
    }
}
