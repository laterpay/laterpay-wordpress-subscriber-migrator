<?php

class LaterPay_Migrator_Controller_Mail
{
    /**
     * Send notification emails to the users.
     *
     * @param string  $modifier  before | after expiry
     *
     * @return bool|string       bool or error message
     */
    public static function send_notification_email( $modifier ) {
        $config = get_laterpay_migrator_config();
        $logger = new LaterPay_Migrator_Controller_Logger( $config->get( 'cron_log' ) );

        $data          = LaterPay_Migrator_Helper_Mail::prepare_mail_data( $modifier );
        $campaign_name = get_option( 'laterpay_migrator_mailchimp_campaign_' . $modifier . '_expired' );

        $logger->log( 'LaterPay Migrator cron started with modifier: ', $modifier );
        $logger->log( 'Mail data: ', $data );
        $logger->log( 'Campaign name: ', $campaign_name );

        if ( ! $data || ! is_array( $data ) || ! $campaign_name ) {
            return false;
        }

        // wrap in try catch block
        try {
            // init MailChimp client
            $mailchimp    = LaterPay_Migrator_Helper_Mail::init_mailchimp();

            // get campaign
            $campaign     = $mailchimp->campaigns->getList( array( 'title' => $campaign_name ) );
            $campaign_id  = $campaign['data'][0]['id'];
            $list_id      = $campaign['data'][0]['list_id'];

            $logger->log( 'MailChimp campaign info: ', $campaign );
            $logger->log( 'List id: ', $list_id );

            // unsubscribe users from MailChimp list
            $users = $mailchimp->lists->members( $list_id );
            if ( $users['data'] ) {
                $mailchimp->lists->batchUnsubscribe( $list_id, $users['data'], false, false );
            }

            $logger->log( 'List members data: ', $users );

            // subscribe users from $data to MailChimp list
            $subscribe_data = array();
            foreach ( $data as $fields ) {
                $subscribe_data[] = array(
                    'email'      => array( 'email' => $fields['email'] ),
                    'merge_vars' => $fields['data'],
                );
            }
            $res = $mailchimp->lists->batchSubscribe( $list_id, $subscribe_data, false );

            $logger->log( 'Subscription result: ', $res );

            // replicate campaign
            $r_campaign    = $mailchimp->campaigns->replicate( $campaign_id );
            $r_campaign_id = $r_campaign['id'];
            $send          = $mailchimp->campaigns->send( $r_campaign_id );

            $logger->log( 'Campaign replication result: ', $r_campaign );
            $logger->log( 'Campaign send result: ', $send );
        } catch ( Exception $e ) {
            return $e->getMessage();
        }

        return true;
    }
}
