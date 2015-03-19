<?php

class LaterPay_Migrator_Subscription {

    /**
     * Get WP user.
     *
     * @return [type] [description]
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
     * @param  [type] $data [description]
     *
     * @return null|int
     */
    public static function get_expiry_time( $data = null ) {
        if ( ! $data ) {
            $data = self::get_current_user_subscription_data();

            if ( ! $data ) {
                return null;
            }
        }

        return strtotime( $data['expiry'] . ' ' . '23:59' );
    }

    /**
     * [get_current_user_subscription_data description]
     *
     * @return array|null $result
     */
    public static function get_current_user_subscription_data() {
        global $wpdb;

        $table     = LaterPay_Migrator_Install::get_migration_table_name();
        $user_data = self::get_current_user_data();

        if ( ! $user_data ) {
            return null;
        }

        $email = $user_data->user_email;

        $sql = "
            SELECT
                *
            FROM
                {$table}
            WHERE
                email = '$email'
            ;";

        $result = $wpdb->get_results( $sql, ARRAY_A );

        if ( ! $result ) {
            return null;
        }

        return $result[0];
    }

    /**
     * Check, if subscription is active and not yet migrated to LaterPay.
     *
     * @param  [type]  $data [description]
     *
     * @return boolean
     */
    public static function is_active( $data = null ) {
        if ( ! $data ) {
            $data = self::get_current_user_subscription_data();

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
     * [mark_user description]
     *
     * @param  [type] $flag [description]
     * @param  [type] $value [description]
     *
     * @return boolean
     */
    public static function mark_user( $flag, $value = true ) {
        global $wpdb;

        $table      = LaterPay_Migrator_Install::get_migration_table_name();
        $user_data  = self::get_current_user_subscription_data();
        $email      = $user_data->user_email;
        $value      = (int) $value;

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
     * [get_time_pass_by_subscription description]
     *
     * @param  [type] $data [description]
     *
     * @return array|null $result
     */
    public static function get_time_pass( ) {
        // TODO: implement get time pass
        return null;
    }

    /**
     * [get_subsriptions_by_expiry description]
     *
     * @return array|null $result
     */
    public static function get_subsriptions_by_expiry( $is_expired = false ) {
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
     * [get_subscriptions_state description]
     *
     * @return array $state
     */
    public static function get_subscriptions_state() {
        global $wpdb;

        $state = array(
            'valid'     => 0,
            'invalid'   => 0,
            'offered'   => 0,
            'ignored'   => 0,
            'migrated'  => 0,
            'remaining' => 0,
            'expiry'    => 0,
        );

        $table = LaterPay_Migrator_Install::get_migration_table_name();
        $sql   = "
            SELECT
                *
            FROM
                {$table};";

        $results = $wpdb->get_results( $sql, ARRAY_A );

        if ( ! $results ) {
            return $state;
        }

        foreach ( $results as $data ) {
            // set valid
            $state['valid'] += 1;
            // set ignored
            if ( $data['is_notified_after_expired'] && ! $data['is_migrated_to_laterpay'] ) {
                $state['ignored'] += 1;
            }
            // set migrated
            if ( $data['is_migrated_to_laterpay'] ) {
                $state['migrated'] += 1;
            }
            // set last_expiry
            if ( ! $state['expiry'] || strtotime( $data['expiry'] ) > strtotime( $state['expiry'] ) ) {
                $state['expiry'] = date( 'm-d-Y', strtotime( $data['expiry'] ) );
            }
        }

        // set remaining
        $state['remaining'] = $state['valid'] - ( $state['ignored'] + $state['migrated'] );

        return $state;
    }

    /**
     * [activate_subscription description]
     *
     * @return void
     */
    public static function activate_subscription() {
        $post_form = $_POST;
        // TODO: validate post data via Laterpay Form and send false if no valid

        update_option( 'laterpay_migrator_mailchimp_api_key',                 $post_form['mailchimp_api_key'] );
        update_option( 'laterpay_migrator_mailchimp_campaign_before_expired', $post_form['mailchimp_campaign_before_expired'] );
        update_option( 'laterpay_migrator_mailchimp_campaign_after_expired',  $post_form['mailchimp_campaign_after_expired'] );
        update_option( 'laterpay_migrator_sitenotice_message',                $post_form['sitenotice_message'] );
        update_option( 'laterpay_migrator_sitenotice_button_text',            $post_form['sitenotice_button_text'] );
        update_option( 'laterpay_migrator_sitenotice_bg_color',               $post_form['sitenotice_bg_color'] );
        update_option( 'laterpay_migrator_sitenotice_text_color',             $post_form['sitenotice_text_color'] );

        // parse uploaded csv file
        if ( ! LaterPay_Migrator_Parse::check_migration_table_data() ) {
            wp_send_json(
                array(
                    'success' => false,
                    'message' => __( 'Missed data, probably was not uploaded or not processed.', 'laterpay_migrator' ),
                )
            );
        }

        wp_send_json(
            array(
                'success' => true,
                'message' => __( 'Migration successfully activated.', 'laterpay_migrator' ),
            )
        );
    }
}
