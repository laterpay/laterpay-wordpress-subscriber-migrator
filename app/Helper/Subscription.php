<?php

class LaterPay_Migrator_Helper_Subscription
{
    /**
     * Default migration status
     *
     * @var array
     */
    public static $status = array(
        'valid'     => 0,
        'invalid'   => 0,
        'migrated'  => 0,
        'remaining' => 0,
        'expiry'    => 0,
    );

    /**
     * Get WP user.
     *
     * @return false|object WP_User
     */
    public static function get_current_user_data() {
        if ( ! is_user_logged_in() ) {
            return false;
        }

        $user = get_userdata( get_current_user_id() );
        if ( ! $user instanceof WP_User ) {
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
     * @return array|null
     */
    public static function get_user_subscription_data( $user = null ) {
        $user  = $user ? $user : self::get_current_user_data();

        if ( ! $user instanceof WP_User ) {
            return null;
        }

        return LaterPay_Migrator_Model_Migration::get_subscription_by_email( $user->user_email );
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

        if ( ! $user instanceof WP_User || ! $data ) {
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
            // check if user has roles
            if ( is_array( $user->roles ) && count( $user->roles ) > 1 ) {
                $user->remove_role( $map['remove'] );
            }
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
        $not_migrated = LaterPay_Migrator_Model_Migration::get_not_migrated_subscriptions();

        if ( ! $not_migrated && LaterPay_Migrator_Model_Migration::get_all_data() ) {
            return true;
        }

        return false;
    }

    /**
     * Get status of migration process.
     *
     * @return array $state
     */
    public static function get_migration_status() {
        // default migration status
        $status = self::$status;

        $subscriptions = LaterPay_Migrator_Model_Migration::get_all_data();
        if ( ! $subscriptions ) {
            return $status;
        }

        foreach ( $subscriptions as $data ) {
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
}
