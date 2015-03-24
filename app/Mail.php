<?php

class LaterPay_Migrator_Mail
{
    public static $fields = array(
        'EMAIL' => array(
            'name'       => 'Email',
            'field_type' => 'text',
            'req'        => true,
        ),
        'FNAME' => array(
            'name'       => 'First Name',
            'field_type' => 'text',
            'req'        => true,
        ),
        'LNAME' => array(
            'name'       => 'Last Name',
            'field_type' => 'text',
            'req'        => true,
        ),
        'EDATE' => array(
            'name'       => 'Expiry Date',
            'field_type' => 'date',
            'req'        => true,
        ),
        'PROD' => array(
            'name'       => 'Product',
            'field_type' => 'text',
            'req'        => true,
        ),
    );

    /**
     * Notify users that their subscription has expired.
     *
     * @return array $expired_subscriptions
     */
    public static function notify_subscription_expired() {
        $expired_subscriptions = LaterPay_Migrator_Subscription::get_subsriptions_by_expiry( true );

        if ( $expired_subscriptions ) {
            $data = array();
            foreach ( $expired_subscriptions as $subscription ) {
                list( $first_name, $last_name ) = explode( ' ', $subscription['subscriber_name'] );
                // set user email to data
                $data[] = array(
                    'email' => $subscription['email'],
                    'data'  => array(
                        'FNAME' => $first_name,
                        'LNAME' => $last_name,
                        'EDATE' => $subscription['expiry'],
                        'PROD'  => $subscription['product'],
                    )
                );
                LaterPay_Migrator_Subscription::change_user_role( $subscription['email'] );

                // set flag to mark user as notified after expiration of subscription
                LaterPay_Migrator_Subscription::mark_user( 'was_notified_after_expiry' );
            }

            if ( $data ) {
                // send email to notify user that his subscription has expired
                $campaign_name = get_option( 'laterpay_migrator_mailchimp_campaign_after_expired' );
                self::send_notification_email( $campaign_name, $data );
            }
        }

        return $expired_subscriptions;
    }

    /**
     * Send notification emails to the users.
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

            // subscribe users from $data to this list
            $subscribe_data = array();
            foreach ( $data as $fields ) {
                $subscribe_data[] = array(
                    'email'      => array( 'email' => $fields['email'] ),
                    'merge_vars' => $fields['data'],
                );
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
     * Notify users that their subscriptions are about to expiry (2 weeks in advance).
     */
    public static function notify_subscription_about_to_expiry() {
        $subscriptions = LaterPay_Migrator_Subscription::get_subsriptions_by_expiry();

        if ( $subscriptions ) {
            $data = array();
            foreach ( $subscriptions as $subscription ) {
                list( $first_name, $last_name ) = explode( ' ', $subscription['subscriber_name'] );
                // set user email to data
                $data[] = array(
                    'email' => $subscription['email'],
                    'data'  => array(
                        'FNAME' => $first_name,
                        'LNAME' => $last_name,
                        'EDATE' => $subscription['expiry'],
                        'PROD'  => $subscription['product'],
                    )
                );

                // set flag to mark user as notified before expiration of subscription
                LaterPay_Migrator_Subscription::mark_user( 'was_notified_before_expiry' );
            }

            if ( $data ) {
                // send email to notify user that his subscription is about to expire
                $campaign_name = get_option( 'laterpay_migrator_mailchimp_campaign_before_expired' );
                self::send_notification_email( $campaign_name, $data );
            }
        }

        return $subscriptions;
    }

    /**
     * Init MailChimp.
     *
     * @return Mailchimp
     */
    public static function init_mailchimp() {
        $api_key   = get_option( 'laterpay_migrator_mailchimp_api_key' );
        $mailchimp = new Mailchimp( $api_key );

        // disable SSL verification
        if ( ! get_option( 'laterpay_migrator_mailchimp_ssl_connection' ) ) {
            curl_setopt($mailchimp->ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($mailchimp->ch, CURLOPT_SSL_VERIFYPEER, 0);
        }

        return $mailchimp;
    }

    /**
     * Add fields to the list
     *
     * @param Mailchimp $mailchimp object
     * @param int       $list_id   list id
     *
     * @return void
     */
    public static function add_fields( $mailchimp, $list_id ) {
        // get list vars
        $mergeVars = $mailchimp->lists->mergeVars( array( $list_id ) );
        $vars      = array();
        if ( $mergeVars['data'] ) {
            $vars = $mergeVars['data'][0]['merge_vars'];
        }

        // filter vars that already exists by tags
        $fields = LaterPay_Migrator_Mail::$fields;
        foreach ( $vars as $var ) {
            if ( isset( $fields[$var['tag']], $fields ) ) {
                unset( $fields[$var['tag']] );
            }
        }

        // set new fields to the list
        if ( $fields ) {
            foreach ( $fields as $tag => $data ) {
                $mailchimp->lists->mergeVarAdd( $list_id, $tag, $data['name'], array( 'field_type' => $data['field_type'], 'req' => $data['req'] ) );
            }
        }
    }
}
