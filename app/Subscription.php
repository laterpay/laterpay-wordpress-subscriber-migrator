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
     * @return [type]       [description]
     */
    public static function get_expiry_time( $data = null ) {
        if ( ! $data ) {
            $data = self::get_subscription_data();

            if ( ! $data ) {
                return false;
            }
        }

        return strtotime( $data['subscription_end'] . ' ' . '23:59' );
    }

    /**
     * [get_subscription_data description]
     *
     * @return [type] [description]
     */
    public static function get_subscription_data() {
        global $wpdb;

        $table     = $wpdb->prefix . LaterPay_Migrator_Install::$subscriptions_table_name;
        $user_data = self::get_user_data();

        if ( ! $user_data ) {
            return false;
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

        $result = $wpdb->get_results( $sql );

        if ( ! $result ) {
            return false;
        }

        return (array) $result[0];
    }

    /**
     * Check, if subscription is active and not yet migrated to LaterPay.
     *
     * @param  [type]  $data [description]
     *
     * @return boolean       [description]
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
     * [mark_as_migrated_to_laterpay description]
     *
     * @param  [type] $is_migrated_to_laterpay [description]
     *
     * @return [type]                          [description]
     */
    public static function mark_as_migrated_to_laterpay( $is_migrated_to_laterpay ) {
        global $wpdb;

        $table                      = $wpdb->prefix . LaterPay_Migrator_Install::$subscriptions_table_name;
        $user_data                  = self::get_user_data();
        $email                      = $user_data->user_email;
        $is_migrated_to_laterpay    = (int) $is_migrated_to_laterpay;

        $sql   = "
            UPDATE
                {$table}
            SET
                migrated_to_laterpay = {$is_migrated_to_laterpay}
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
     * @return [type]       [description]
     */
    public static function get_time_pass_by_subscription( $data = null ) {
        if ( ! $data ) {
            $data = self::get_subscription_data();

            if ( ! $data ) {
                return false;
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

        $result = $wpdb->get_results( $sql );

        if ( ! $result ) {
            return false;
        }

        return (array) $result[0];
    }
}
