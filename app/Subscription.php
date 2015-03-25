<?php

class LaterPay_Migrator_Subscription
{

    /**
     * Get WP user.
     *
     * @return object WP_User
     */
    public static function get_current_user_data() {
        if ( ! is_user_logged_in() ) {
            return false;
        }

        return get_userdata( get_current_user_id() );
    }

    /**
     * Get expiry time of a subscription.
     *
     * @param  array $data subscription data
     *
     * @return null|int
     */
    public static function get_expiry_time( $data = null ) {
        if ( ! $data ) {
            $data = self::get_user_subscription_data();

            if ( ! $data ) {
                return null;
            }
        }

        return strtotime( $data['expiry'] . ' ' . '23:59' );
    }

    /**
     * Get user subscription data.
     *
     * @param null|object $user user instanse
     *
     * @return array|null $result
     */
    public static function get_user_subscription_data( $user = null ) {
        global $wpdb;

        $table = LaterPay_Migrator_Install::get_migration_table_name();
        $user  = $user ? $user : self::get_current_user_data();

        if ( ! $user instanceof WP_User ) {
            return null;
        }

        $email = $user->user_email;

        $sql = "
            SELECT
                *
            FROM
                {$table}
            WHERE
                email = '$email'
            ;"
        ;

        $result = $wpdb->get_results( $sql, ARRAY_A );

        if ( ! $result ) {
            return null;
        }

        return $result[0];
    }

    /**
     * Check, if subscription is active and not yet migrated to LaterPay.
     *
     * @param  array $data subscription data
     *
     * @return boolean
     */
    public static function is_active( $data = null ) {
        if ( ! $data ) {
            $data = self::get_user_subscription_data();

            if ( ! $data ) {
                return false;
            }
        }

        if ( $data['is_migrated_to_laterpay'] ) {
            return false;
        }

        return true;
    }

    /**
     * Set a given value to a given flag.
     *
     * @param  string $flag  flag field name
     * @param  bool   $value flag value
     *
     * @return boolean
     */
    public static function mark_user( $flag, $value = true ) {
        global $wpdb;

        $table = LaterPay_Migrator_Install::get_migration_table_name();
        $user  = self::get_user_subscription_data();
        $email = $user->user_email;
        $value = (int) $value;

        $sql   = "
            UPDATE
                {$table}
            SET
                {$flag} = {$value}
            WHERE
                email = '$email'
            ;";

        $result = $wpdb->query( $sql );

        if ( ! $result ) {
            return false;
        }

        return true;
    }

    /**
     * Get time pass id from mapping.
     *
     * @param  array    $data subscription data
     *
     * @return bool|int false on error or time pass id
     */
    public static function get_time_pass_id( $data = null ) {
        if ( ! $data ) {
            $data = self::get_user_subscription_data();

            if ( ! $data ) {
                return false;
            }
        }

        $products_mapping = get_option( 'laterpay_migrator_products_mapping' );
        if ( ! $products_mapping || ! $data['product'] || ! isset( $products_mapping[$data['product']] ) ) {
            return false;
        }
        $map = $products_mapping[$data['product']];

        return isset( $map['timepass'] ) ? $map['timepass'] : 0;
    }

    /**
     * Get about to expire or already expired subscriptions (depends on param usage).
     *
     * @param  bool $is_expired need to get expired subscriptions
     *
     * @return array|null $result
     */
    public static function get_subscriptions_by_expiry( $is_expired = false ) {
        global $wpdb;

        $modifier     = get_option( 'laterpay_migrator_expiry_modifier' );
        $table        = LaterPay_Migrator_Install::get_migration_table_name();
        $current_date = date( 'Y-m-d', time() );

        $sql = "
            SELECT
                *
            FROM
                {$table}
            WHERE
                is_migrated_to_laterpay = 0 AND ";

        if ( $is_expired ) {
            $sql .= "expiry = '$current_date';";
        } else {
            $sql .= "expiry <= DATE_ADD( '$current_date', INTERVAL $modifier );";
        }

        $result = $wpdb->get_results( $sql, ARRAY_A );

        if ( ! $result ) {
            return null;
        }

        return $result;
    }

    /**
     * Get status of migration process.
     *
     * @return array $state
     */
    public static function get_migration_status() {
        global $wpdb;

        $status = array(
            'valid'     => 0,
            'invalid'   => 0,
            'migrated'  => 0,
            'remaining' => 0,
            'expiry'    => 0,
        );

        $table = LaterPay_Migrator_Install::get_migration_table_name();
        $sql   = "
            SELECT
                *
            FROM
                {$table}
            ;"
        ;

        $results = $wpdb->get_results( $sql, ARRAY_A );

        if ( ! $results ) {
            return $status;
        }

        foreach ( $results as $data ) {
            // increase valid subscriber data count
            $status['valid'] += 1;

            // increase migrated subscribers count
            if ( $data['is_migrated_to_laterpay'] ) {
                $status['migrated'] += 1;
            }

            // set last expiry date
            if ( ! $status['expiry'] || strtotime( $data['expiry'] ) > strtotime( $status['expiry'] ) ) {
                $status['expiry'] = $data['expiry'];
            }
        }

        // format expiry date
        $localized_date_format = substr( get_locale(), 0, 2 ) == 'de' ? 'd.m.Y' : 'm-d-Y';
        $status['expiry'] = date_i18n( $localized_date_format, strtotime( $status['expiry'] ) );

        // calculate remaining subscribers to be migrated count
        $status['remaining'] = $status['valid'] - $status['migrated'];

        return $status;
    }

    /**
     * Activate migration process. The plugin will now render sitenotices and send email notifications.
     *
     * @return void
     */
    public static function activate_migration_process() {
        // check, if migration is active already
        if ( get_option( 'laterpay_migrator_is_active' ) ) {
            update_option( 'laterpay_migrator_is_active', 0 );

            wp_send_json(
                array(
                    'success' => true,
                    'message' => __( 'The migration process is paused now.', 'laterpay_migrator' ),
                    'mode'    => array(
                        'text'  => __( 'Start Migration', 'laterpay_migrator' ),
                        'value' => 'setting-up',
                    ),
                )
            );
        }

        if ( ! isset( $_POST['_wpnonce'] ) || $_POST['_wpnonce'] !== wp_create_nonce( 'laterpay_migrator' ) ) {
            wp_send_json(
                array(
                    'success' => false,
                    'message' => __( 'Incorrect nonce.', 'laterpay_migrator' ),
                )
            );
        }

        $post_form = new LaterPay_Migrator_Validation( $_POST );

        if ( ! $post_form->is_valid() ) {
            wp_send_json(
                array(
                    'success' => false,
                    'message' => __( 'You have to configure the Subscription Mapping and Subscriber Communication sections before you can start the migration process.', 'laterpay_migrator' ),
                    'data'    => array(
                        'errors' => $post_form->get_errors(),
                    ),
                )
            );
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
            $mailchimp = LaterPay_Migrator_Mail::init_mailchimp();

            // validate settings for pre-expiry campaign
            $pre_expiry_campaign = $mailchimp->campaigns->getList( array( 'title' => $post_form->get_field_value( 'mailchimp_campaign_before_expired' ) ) );
            if ( ! $pre_expiry_campaign['data'] ) {
                throw new Exception( sprintf ( __( 'Campaign %s does not exist', 'laterpay_migrator' ), $post_form->get_field_value( 'mailchimp_campaign_before_expired' ) ) );
            } else {
                $list_id = $pre_expiry_campaign['data'][0]['list_id'];
                // set new fields to the list
                LaterPay_Migrator_Mail::add_fields( $mailchimp, $list_id );
            }

            // validate settings for post-expiry campaign
            $post_expiry_campaign = $mailchimp->campaigns->getList( array( 'title' => $post_form->get_field_value( 'mailchimp_campaign_after_expired' ) ) );
            if ( ! $post_expiry_campaign['data'] ) {
                throw new Exception( sprintf( __( 'Campaign %s does not exist', 'laterpay_migrator' ), $post_form->get_field_value( 'mailchimp_campaign_after_expired' ) ) );
            } else {
                $list_id = $post_expiry_campaign['data'][0]['list_id'];
                // set new fields to the list
                LaterPay_Migrator_Mail::add_fields( $mailchimp, $list_id );
            }
        } catch ( Exception $e ) {
            wp_send_json(
                array(
                    'success' => false,
                    'message' => __( 'Mailchimp error: ', 'laterpay_migrator' ) . $e->getMessage(),
                )
            );
        }

        // save product mapping
        $products     = get_option( 'laterpay_migrator_products' );
        $timepasses   = $post_form->get_field_value( 'timepasses' );
        $assign_roles = $post_form->get_field_value( 'assign_roles' );
        $remove_roles = $post_form->get_field_value( 'remove_roles' );

        if ( count( $timepasses )   != count( $products ) ||
             count( $assign_roles ) != count( $products ) ||
             count( $remove_roles ) != count( $products ) ) {
            wp_send_json(
                array(
                    'success' => false,
                    'message' => __( 'Wrong product mapping params.', 'laterpay_migrator' ),
                )
            );
        }

        if ( ! $products || ! is_array( $products ) ) {
            wp_send_json(
                array(
                    'success' => false,
                    'message' => __( 'There are no products in the system. Please upload a CSV file with correct products.', 'laterpay_migrator' ),
                )
            );
        }

        $products_mapping = array();
        foreach ( $products as $key => $product_name ) {
            $map = array(
                'timepass' => $timepasses[$key],
                'assign'   => $assign_roles[$key],
                'remove'   => $remove_roles[$key],
            );
            $products_mapping[$product_name] = $map;
        }

        update_option( 'laterpay_migrator_products_mapping', $products_mapping );

        // check, if migration table has data
        if ( ! LaterPay_Migrator_Parse::check_migration_table_data() ) {
            wp_send_json(
                array(
                    'success' => false,
                    'message' => __( 'Missed data, probably was not uploaded or not processed.', 'laterpay_migrator' ),
                )
            );
        }

        // activate migration process
        update_option( 'laterpay_migrator_is_active', 1 );

        wp_send_json(
            array(
                'success' => true,
                'message' => __( 'The plugin is now migrating your subscribers to LaterPay.', 'laterpay_migrator' ),
                'mode'    => array(
                    'text'  => __( 'Pause Migration', 'laterpay_migrator' ),
                    'value' => 'migrating',
                ),
            )
        );
    }

    /**
     * Change user role according to subscriber data mapping.
     *
     * @param string $email user email
     *
     * @return bool result of operation
     */
    public static function change_user_role( $email = null ) {
        if ( ! $email ) {
            $user = self::get_current_user_data();
            $data = self::get_user_subscription_data();
        } else {
            $user = get_user_by( 'email', $email );
            $data = self::get_user_subscription_data( $user );
        }

        if ( ! $user || ! $data ) {
            return false;
        }

        $products_mapping = get_option( 'laterpay_migrator_products_mapping' );
        if ( ! $products_mapping || ! $data['product'] || ! isset( $products_mapping[$data['product']] ) ) {
            return false;
        }

        $map = $products_mapping[$data['product']];

        // assign role
        if ( isset( $map['assign'] ) && $map['assign'] ) {
            $user->add_role( $map['assign'] );
        }

        // remove role
        if ( isset( $map['remove'] ) && $map['remove'] ) {
            $user->remove_role( $map['remove'] );
        }

        return true;
    }

    /**
     * Check, if the migration process is completed.
     *
     * We consider the migration process to be completed, if there are NO users left,
     * - who have neither migrated to LaterPay yet,
     * - nor expired (passed the renewal date of) their subscription.
     *
     * @return bool
     */
    public static function is_migration_completed() {
        global $wpdb;

        $table = LaterPay_Migrator_Install::get_migration_table_name();
        $sql   = "
            SELECT
                *
            FROM
                {$table}
            WHERE
                is_migrated_to_laterpay = 0 AND
                expiry >= CURDATE()
            ;"
        ;

        $result = $wpdb->get_results( $sql );

        if ( ! $result && LaterPay_Migrator_Parse::check_migration_table_data() ) {
            return true;
        }

        return false;
    }
}
