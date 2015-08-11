<?php

class LaterPay_Migrator_Helper_Subscription
{

    /**
     * Default migration status.
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
     * Get data of current WordPress user.
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
     * Get expiry time of a subscription as absolute date (not as relative time remaining).
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

        return strtotime( $data['expiry'] . ' ' . '23:59:59' );
    }

    /**
     * Get subscription data for a given user.
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

        if ( $data['is_migrated_to_laterpay'] || self::get_expiry_time( $data ) < time() ) {
            return false;
        }

        return true;
    }

    /**
     * Get time pass id from subscriber data mapping.
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
        if ( ! $products_mapping || ! $data['product'] || ! isset( $products_mapping[ $data['product'] ] ) ) {
            return false;
        }
        $map = $products_mapping[ $data['product'] ];

        return isset( $map['timepass'] ) ? $map['timepass'] : 0;
    }

    /**
     * Change user role according to subscriber data mapping.
     *
     * @param string $email user email
     * @param array  $data  user subscription data
     *
     * @return bool result of operation
     */
    public static function change_user_role( $email = null, $data = null ) {
        if ( ! $email ) {
            $user = self::get_current_user_data();
        } else {
            $user = get_user_by( 'email', $email );
        }

        if ( ! $data ) {
            $data = self::get_user_subscription_data( $user );
        }

        if ( ! $user instanceof WP_User || ! $data ) {
            return false;
        }

        $products_mapping = get_option( 'laterpay_migrator_products_mapping' );
        if ( ! $products_mapping || ! $data['product'] || ! isset( $products_mapping[ $data['product'] ] ) ) {
            return false;
        }

        $map = $products_mapping[ $data['product'] ];

        // assign role
        if ( isset( $map['assign'] ) && $map['assign'] ) {
            $user->add_role( $map['assign'] );
        }

        // remove role
        if ( isset( $map['remove'] ) && $map['remove'] ) {
            // remove role, if user has at least one role
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

        $expired = count( LaterPay_Migrator_Model_Migration::get_expired_subscriptions( true ) );
        foreach ( $subscriptions as $data ) {
            // increase total subscriber data count
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
        $status['expiry']      = date_i18n( $localized_date_format, strtotime( $status['expiry'] ) );

        // calculate remaining subscribers to be migrated count
        $status['remaining']   = $status['valid'] - $status['migrated'] - $expired;
        $status['invalid']     = (int) get_option( 'laterpay_migrator_invalid_count' );

        return $status;
    }

    /**
     * Check, if the current user has already migrated to LaterPay and is still within his paid subscription period,
     * but lost access to his LaterPay time pass.
     *
     * @return bool has access
     */
    public static function lost_access() {
        // check, if user has data and is migrated to LaterPay already
        $data = self::get_user_subscription_data();
        if ( ! $data || ! $data['is_migrated_to_laterpay'] ) {
            return false;
        }

        // check, if subscription of user has expired
        if ( self::get_expiry_time( $data ) < time() ) {
            return false;
        }

        // get user product mapping
        $products_mapping = get_option( 'laterpay_migrator_products_mapping' );
        if ( ! $products_mapping || ! $data['product'] || ! isset( $products_mapping[ $data['product'] ] ) ) {
            return false;
        }
        $map = $products_mapping[ $data['product'] ];

        // get corresponding time pass id
        $time_pass_id = $map['timepass'];

        // check, if user does not have access to the corresponding time pass
        $tokenized_pass_id = LaterPay_Helper_TimePass::get_tokenized_time_pass_id( $time_pass_id );

        $client_options  = LaterPay_Helper_Config::get_php_client_options();
        $laterpay_client = new LaterPay_Client(
            $client_options['cp_key'],
            $client_options['api_key'],
            $client_options['api_root'],
            $client_options['web_root'],
            $client_options['token_name']
        );
        $result = $laterpay_client->get_access( array( $tokenized_pass_id ) );

        // some error occurred, allow user to restore his switching time pass
        if ( empty( $result ) || ! array_key_exists( 'articles', $result ) ) {
            return true;
        }

        $has_access = false;

        foreach ( $result['articles'] as $article_access ) {
            if ( $article_access['access'] ) {
                $has_access = true;
            }
        }

        return ! $has_access;
    }
}
