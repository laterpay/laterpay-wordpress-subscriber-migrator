<?php

class LaterPay_Migrator_Subscription {

    /**
     * Get WP user.
     *
     * @return [type] [description]
     */
    public static function get_user_data() {
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
            $data = self::get_subscription_data();

            if ( ! $data ) {
                return null;
            }
        }

        return strtotime( $data['subscription_end'] . ' ' . '23:59' );
    }

    /**
     * [get_subscription_data description]
     *
     * @return array|null $result
     */
    public static function get_subscription_data() {
        global $wpdb;

        $table     = $wpdb->prefix . LaterPay_Migrator_Install::$subscriptions_table_name;
        $user_data = self::get_user_data();

        if ( ! $user_data ) {
            return null;
        }

        $email     = $user_data->user_email;

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
            $data = self::get_subscription_data();

            if ( ! $data ) {
                return false;
            }
        }

        if ( $data['migrated_to_laterpay'] ) {
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

        $table      = $wpdb->prefix . LaterPay_Migrator_Install::$subscriptions_table_name;
        $user_data  = self::get_user_data();
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
    public static function get_time_pass_by_subscription( $data = null ) {
        if ( ! $data ) {
            $data = self::get_subscription_data();

            if ( ! $data ) {
                return null;
            }
        }

        global $wpdb;

        $opts  = LaterPay_Migrator_Install::$time_pass_seed_data[$data['subscription_duration']];
        $table = $wpdb->prefix . 'laterpay_passes';

        $sql = "
            SELECT
                *
            FROM
                {$table}
            WHERE
                duration = {$opts['duration']} AND
                period   = {$opts['period']}
            ;";

        $result = $wpdb->get_results( $sql, ARRAY_A );

        if ( ! $result ) {
            return null;
        }

        return $result[0];
    }

    /**
     * [get_subsriptions_by_expiry description]
     *
     * @return array|null $result
     */
    public static function get_subsriptions_by_expiry( $is_expired = false ) {
        global $wpdb;

        $modifier     = get_option( 'lpmigrator_about_to_expiry_modifier' );
        $table        = $wpdb->prefix . LaterPay_Migrator_Install::$subscriptions_table_name;
        $current_date = date( 'Y-m-d', time() );

        $sql = "
            SELECT
                *
            FROM
                {$table}
            WHERE ";

        if ( $is_expired ) {
            $sql .= "subscription_end = '$current_date';";
        } else {
            $sql .= "subscription_end <= DATE_ADD( '$current_date', INTERVAL $modifier );";
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

        $table = $wpdb->prefix . LaterPay_Migrator_Install::$subscriptions_table_name;
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
            if ( $data['expired_notified'] && ! $data['migrated_to_laterpay'] ) {
                $state['ignored'] += 1;
            }
            // set migrated
            if ( $data['migrated_to_laterpay'] ) {
                $state['migrated'] += 1;
            }
            // set last_expiry
            if ( ! $state['expiry'] || strtotime( $data['subscription_end'] ) > strtotime( $state['expiry'] ) ) {
                $state['expiry'] = date( 'm-d-Y', strtotime( $data['subscription_end'] ) );
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

        update_option( 'lpmigrator_mailchimp_api_key',                 $post_form['mailchimp_api_key'] );
        update_option( 'lpmigrator_mailchimp_campaign_before_expired', $post_form['mailchimp_campaign_before_expired'] );
        update_option( 'lpmigrator_mailchimp_campaign_after_expired',  $post_form['mailchimp_campaign_after_expired'] );
        update_option( 'lpmigrator_sitenotice_message',                $post_form['sitenotice_message'] );
        update_option( 'lpmigrator_sitenotice_button_text',            $post_form['sitenotice_button_text'] );
        update_option( 'lpmigrator_sitenotice_bg_color',               $post_form['sitenotice_bg_color'] );
        update_option( 'lpmigrator_sitenotice_text_color',             $post_form['sitenotice_text_color'] );

        // TODO: clear table ?? or prevent if table has data
        // parse uploaded csv file
        if ( ! LaterPay_Migrator_Parse::parse_csv() ) {
            wp_send_json(
                array(
                    'success' => false,
                    'message' => __( 'Error during file processing, probably file not uploaded.', 'laterpay_migrator' ),
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
