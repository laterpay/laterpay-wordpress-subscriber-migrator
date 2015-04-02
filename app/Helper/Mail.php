<?php

class LaterPay_Migrator_Helper_Mail
{
    /**
     * @var array of MailChimp fields
     */
    public static $fields = array(
        'EMAIL' => array(
            'name'          => 'Email',
            'field_type'    => 'text',
            'req'           => true,
        ),
        'FNAME' => array(
            'name'          => 'First Name',
            'field_type'    => 'text',
            'req'           => true,
        ),
        'LNAME' => array(
            'name'          => 'Last Name',
            'field_type'    => 'text',
            'req'           => true,
        ),
        'EDATE' => array(
            'name'          => 'Expiry Date',
            'field_type'    => 'date',
            'req'           => true,
        ),
        'PROD' => array(
            'name'          => 'Product',
            'field_type'    => 'text',
            'req'           => true,
        ),
    );

    /**
     * Init MailChimp library.
     *
     * @return Mailchimp
     */
    public static function init_mailchimp() {
        $api_key   = get_option( 'laterpay_migrator_mailchimp_api_key' );
        $mailchimp = new Mailchimp( $api_key );

        if ( ! get_option( 'laterpay_migrator_mailchimp_ssl_connection' ) ) {
            // disable SSL verification, if the site does not have SSL
            curl_setopt( $mailchimp->ch, CURLOPT_SSL_VERIFYHOST, 0 );
            curl_setopt( $mailchimp->ch, CURLOPT_SSL_VERIFYPEER, 0 );
        }

        return $mailchimp;
    }

    /**
     * Add fields to the list.
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

        // filter vars that already exist by tags
        $fields = LaterPay_Migrator_Helper_Mail::$fields;
        foreach ( $vars as $var ) {
            if ( isset( $fields[$var['tag']], $fields ) ) {
                unset( $fields[$var['tag']] );
            }
        }

        // add new fields to the list
        if ( $fields ) {
            foreach ( $fields as $tag => $data ) {
                $mailchimp->lists->mergeVarAdd(
                    $list_id,
                    $tag,
                    $data['name'],
                    array(
                        'field_type'    => $data['field_type'],
                        'req'           => $data['req'],
                    )
                );
            }
        }
    }

    /**
     * Prepare mail data.
     *
     * @param string       $modifier  before|after
     *
     * @return null|array  $data      prepared data
     */
    public static function prepare_mail_data( $modifier ) {
        // init defaults
        $subscriptions    = array();
        $need_to_change_role = false;

        // check modifier
        if ( $modifier === 'before' ) {
            $subscriptions = LaterPay_Migrator_Model_Migration::get_subscriptions_by_expiry();
        } elseif ( $modifier === 'after' ) {
            $subscriptions = LaterPay_Migrator_Model_Migration::get_subscriptions_by_expiry( true );
            $need_to_change_role = true;
        }

        if ( ! $subscriptions || ! is_array( $subscriptions ) ) {
            return null;
        }

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

            if ( $need_to_change_role ) {
                // remove the role from the user that gives a subscriber unlimited access to paid content, if configured
                LaterPay_Migrator_Helper_Subscription::change_user_role( $subscription['email'] );
            }

            // flag user as notified about upcoming / happened expiry of subscription
            LaterPay_Migrator_Model_Migration::set_flag( $subscription['email'], 'was_notified_' . $modifier . '_expiry' );
        }

        return $data;
    }
}
