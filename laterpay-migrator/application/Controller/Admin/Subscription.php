<?php

class LaterPay_Migrator_Controller_Admin_Subscription extends LaterPay_Controller_Base {
    /**
     * @see LaterPay_Core_Event_SubscriberInterface::get_subscribed_events()
     */
    public static function get_subscribed_events() {
        return array(
            'wp_ajax_laterpay_migrator_activate' => array(
                array( 'laterpay_on_admin_view', 200 ),
                array( 'laterpay_on_ajax_send_json', 0 ),
                array( 'activate_migration_process' ),
            ),
        );
    }

    /**
     * Activate migration process. The plugin will then render sitenotices and send email notifications from then on.
     *
     * @wp-hook wp_ajax_laterpay_migrator_activate
     *
     * @return void
     */
    public function activate_migration_process( LaterPay_Core_Event $event ) {
        // check, if migration is active already
        if ( get_option( 'laterpay_migrator_is_active' ) ) {
            update_option( 'laterpay_migrator_is_active', 0 );

            $event->set_result(
                array(
                    'success' => true,
                    'message' => __( 'The migration process is paused now.', 'laterpay-migrator' ),
                    'mode'    => array(
                        'text'  => __( 'Start Migration', 'laterpay-migrator' ),
                        'value' => 'setting-up',
                    ),
                )
            );
            return;
        }

        if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( $_POST['_wpnonce'] ), 'laterpay-migrator' ) ) {
            $event->set_result(
                array(
                    'success' => false,
                    'message' => __( 'Incorrect nonce.', 'laterpay-migrator' ),
                )
            );
            return;
        }

        $post_form = new LaterPay_Migrator_Form_Activation( $_POST );

        if ( ! $post_form->is_valid() ) {
            $event->set_result(
                array(
                    'success' => false,
                    'message' => __( 'You have to configure the Subscription Mapping and Subscriber Communication sections before you can start the migration process.', 'laterpay-migrator' ),
                    'data'    => array(
                        'errors' => $post_form->get_errors(),
                    ),
                )
            );
            return;
        }

        // save sitenotice settings
        update_option( 'laterpay_migrator_sitenotice_message',                  $post_form->get_field_value( 'sitenotice_message' ) );
        update_option( 'laterpay_migrator_sitenotice_button_text',              $post_form->get_field_value( 'sitenotice_button_text' ) );
        update_option( 'laterpay_migrator_sitenotice_bg_color',                 $post_form->get_field_value( 'sitenotice_bg_color' ) );
        update_option( 'laterpay_migrator_sitenotice_text_color',               $post_form->get_field_value( 'sitenotice_text_color' ) );

        // save MailChimp settings
        update_option( 'laterpay_migrator_mailchimp_api_key',                   $post_form->get_field_value( 'mailchimp_api_key' ) );
        update_option( 'laterpay_migrator_mailchimp_campaign_before_expired',   $post_form->get_field_value( 'mailchimp_campaign_before_expired' ) );
        update_option( 'laterpay_migrator_mailchimp_campaign_after_expired',    $post_form->get_field_value( 'mailchimp_campaign_after_expired' ) );
        update_option( 'laterpay_migrator_mailchimp_ssl_connection',            $post_form->get_field_value( 'mailchimp_ssl_connection' ) );

        // check MailChimp settings
        try {
            $mailchimp = LaterPay_Migrator_Helper_Mail::init_mailchimp();

            // validate settings for pre-expiry campaign
            $pre_expiry_campaign = $mailchimp->campaigns->getList( array( 'title' => $post_form->get_field_value( 'mailchimp_campaign_before_expired' ) ) );
            if ( ! $pre_expiry_campaign['data'] ) {
                throw new Exception( sprintf( __( 'Campaign %s does not exist', 'laterpay-migrator' ), $post_form->get_field_value( 'mailchimp_campaign_before_expired' ) ) );
            } else {
                $list_id = $pre_expiry_campaign['data'][0]['list_id'];
                // add available variables to the MailChimp list, if it does not have them already
                LaterPay_Migrator_Helper_Mail::add_fields( $mailchimp, $list_id );
            }

            // validate settings for post-expiry campaign
            $post_expiry_campaign = $mailchimp->campaigns->getList( array( 'title' => $post_form->get_field_value( 'mailchimp_campaign_after_expired' ) ) );
            if ( ! $post_expiry_campaign['data'] ) {
                throw new Exception( sprintf( __( 'Campaign %s does not exist', 'laterpay-migrator' ), $post_form->get_field_value( 'mailchimp_campaign_after_expired' ) ) );
            } else {
                $list_id = $post_expiry_campaign['data'][0]['list_id'];
                // add available variables to the MailChimp list, if it does not have them already
                LaterPay_Migrator_Helper_Mail::add_fields( $mailchimp, $list_id );
            }
        } catch ( Exception $e ) {
            $event->set_result(
                array(
                    'success' => false,
                    'message' => __( 'Mailchimp error: ', 'laterpay-migrator' ) . $e->getMessage(),
                )
            );
            return;
        }

        // save product mapping
        $products     = get_option( 'laterpay_migrator_products' );
        $timepasses   = $post_form->get_field_value( 'timepasses' );
        $assign_roles = $post_form->get_field_value( 'assign_roles' );
        $remove_roles = $post_form->get_field_value( 'remove_roles' );

        if ( count( $timepasses ) !== count( $products ) ||
             count( $assign_roles ) !== count( $products ) ||
             count( $remove_roles ) !== count( $products ) ) {
            $event->set_result(
                array(
                    'success' => false,
                    'message' => __( 'Wrong product mapping parameters.', 'laterpay-migrator' ),
                )
            );
            return;
        }

        if ( ! $products || ! is_array( $products ) ) {
            $event->set_result(
                array(
                    'success' => false,
                    'message' => __( 'There are no products in the system. Please upload a CSV file with valid products.', 'laterpay-migrator' ),
                )
            );
            return;
        }

        $products_mapping = array();
        foreach ( $products as $key => $product_name ) {
            $map = array(
                'timepass' => $timepasses[ $key ],
                'assign'   => $assign_roles[ $key ],
                'remove'   => $remove_roles[ $key ],
            );
            $products_mapping[ $product_name ] = $map;
        }

        update_option( 'laterpay_migrator_products_mapping', $products_mapping );

        // check, if migration table has data
        if ( ! LaterPay_Migrator_Model_Migration::get_all_data() ) {
            $event->set_result(
                array(
                    'success' => false,
                    'message' => __( 'No subscriber data available. Please upload a CSV file with your subscriber data.', 'laterpay-migrator' ),
                )
            );
            return;
        }

        // activate migration process
        update_option( 'laterpay_migrator_is_active', 1 );

        // change roles of all users whose subscriptions have already expired
        $exp_subscriptions = LaterPay_Migrator_Model_Migration::get_expired_subscriptions();
        if ( $exp_subscriptions ) {
            foreach ( $exp_subscriptions as $exp_data ) {
                LaterPay_Migrator_Helper_Subscription::change_user_role( $exp_data['email'], $exp_data );

                // flag users as already notified to prevent them from receiving notification emails
                LaterPay_Migrator_Model_Migration::set_flag( $exp_data['email'], 'was_notified_after_expiry' );
            }
        }

        $event->set_result(
            array(
                'success' => true,
                'message' => __( 'The plugin is now migrating your subscribers to LaterPay.', 'laterpay-migrator' ),
                'mode'    => array(
                    'text'  => __( 'Pause Migration', 'laterpay-migrator' ),
                    'value' => 'migrating',
                ),
            )
        );
    }
}
