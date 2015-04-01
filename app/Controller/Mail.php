<?php

class LaterPay_Migrator_Controller_Mail
{
    /**
     * Send notification emails  to the users.
     *
     * @param string  $modifier  before | after expiry
     *
     * @return bool|string       bool or error message
     */
    public static function send_notification_email( $modifier ) {
        $data          = LaterPay_Migrator_Helper_Mail::prepare_mail_data( $modifier );
        $campaign_name = get_option( 'laterpay_migrator_mailchimp_campaign_' . $modifier . '_expired' );

        if ( ! $data || ! is_array( $data ) || ! $campaign_name ) {
            return false;
        }

        // wrap in try catch block
        try {
            // init mailchimp
            $mailchimp    = LaterPay_Migrator_Helper_Mail::init_mailchimp();

            // get campaign
            $campaign     = $mailchimp->campaigns->getList( array( 'title' => $campaign_name ) );
            $campaign_id  = $campaign['data'][0]['id'];
            $list_id      = $campaign['data'][0]['list_id'];

            // unsubscribe existent users in list from it
            $users = $mailchimp->lists->members( $list_id );
            if ( $users['data'] ) {
                $mailchimp->lists->batchUnsubscribe( $list_id, $users['data'], false, false );
            }

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
        } catch ( Exception $e ) {
            return $e->getMessage();
        }

        return true;
    }
}
