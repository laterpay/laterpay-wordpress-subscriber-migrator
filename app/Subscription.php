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
            $sql .= "subscription_end < '$current_date';";
        } else {
            $sql .= "subscription_end <= DATE_ADD( '$current_date', INTERVAL $modifier );";
        }

        $result = $wpdb->get_results( $sql, ARRAY_A );

        if ( ! $result ) {
            return null;
        }

        return $result;
    }
}
