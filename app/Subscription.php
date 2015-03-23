<?php

class LaterPay_Migrator_Subscription
{

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
     * Get the expiry time of a subscription.
     *
     * @param  [type] $data [description]
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
     * [get_user_subscription_data description]
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
     * Set a given value to the given flag.
     *
     * @param  [type] $flag [description]
     * @param  [type] $value [description]
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
     * @param  [type] $data [description]
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
                {$table}
            ;"
        ;

        $results = $wpdb->get_results( $sql, ARRAY_A );

        if ( ! $results ) {
            return $state;
        }

        foreach ( $results as $data ) {
            // set valid
            $state['valid'] += 1;

            // set ignored
            if ( $data['was_notified_after_expiry'] && ! $data['is_migrated_to_laterpay'] ) {
                $state['ignored'] += 1;
            }

            // set migrated
            if ( $data['is_migrated_to_laterpay'] ) {
                $state['migrated'] += 1;
            }

            // set last_expiry
            if ( ! $state['expiry'] || strtotime( $data['expiry'] ) > strtotime( $state['expiry'] ) ) {
                $state['expiry'] = $data['expiry'];
            }
        }

        // format expiry date
        $state['expiry'] = date_i18n( get_option( 'date_format' ), strtotime( $state['expiry'] ) );

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
        // check if migration active already
        if ( isset( $_POST['migration_active'] ) && $_POST['migration_active'] ) {
            update_option( 'laterpay_migrator_is_active', 0 );

            wp_send_json(
                array(
                    'success' => true,
                    'message' => __( 'The migration process is paused now.', 'laterpay_migrator' ),
                    'data'    => array(
                        'text'  => __( 'Start Migration', 'laterpay_migrator' ),
                        'value' => 'setup',
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
                    'message' => __( 'Invalid data.', 'laterpay_migrator' ),
                    'data'    => array(
                        'errors' => $post_form->get_errors(),
                    ),
                )
            );
        }

        // save usual options
        update_option( 'laterpay_migrator_mailchimp_api_key',                 $post_form->get_field_value( 'mailchimp_api_key' ) );
        update_option( 'laterpay_migrator_mailchimp_campaign_before_expired', $post_form->get_field_value( 'mailchimp_campaign_before_expired' ) );
        update_option( 'laterpay_migrator_mailchimp_campaign_after_expired',  $post_form->get_field_value( 'mailchimp_campaign_after_expired' ) );
        update_option( 'laterpay_migrator_mailchimp_ssl_connection',          $post_form->get_field_value( 'mailchimp_ssl_connection' ) );
        update_option( 'laterpay_migrator_sitenotice_message',                $post_form->get_field_value( 'sitenotice_message' ) );
        update_option( 'laterpay_migrator_sitenotice_button_text',            $post_form->get_field_value( 'sitenotice_button_text' ) );
        update_option( 'laterpay_migrator_sitenotice_bg_color',               $post_form->get_field_value( 'sitenotice_bg_color' ) );
        update_option( 'laterpay_migrator_sitenotice_text_color',             $post_form->get_field_value( 'sitenotice_text_color' ) );

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
                    'message' => __( 'There are no products in system, try to reupload file with correct products.', 'laterpay_migrator' ),
                )
            );
        }

        $products_mapping = array();
        foreach( $products as $key => $product_name ) {
            $map = array(
                'timepass' => $timepasses[$key],
                'assign'   => $assign_roles[$key],
                'remove'   => $remove_roles[$key],
            );
            $products_mapping[$product_name] = $map;
        }

        update_option( 'laterpay_migrator_products_mapping', $products_mapping );

        // parse uploaded CSV file
        if ( ! LaterPay_Migrator_Parse::check_migration_table_data() ) {
            wp_send_json(
                array(
                    'success' => false,
                    'message' => __( 'Missed data, probably was not uploaded or not processed.', 'laterpay_migrator' ),
                )
            );
        }

        // activate migration
        update_option( 'laterpay_migrator_is_active', 1 );

        wp_send_json(
            array(
                'success' => true,
                'message' => __( 'The plugin is now migrating your subscribers to LaterPay.', 'laterpay_migrator' ),
                'data'    => array(
                    'text'  => __( 'Pause Migration', 'laterpay_migrator' ),
                    'value' => 'migrating',
                ),
            )
        );
    }

    /**
     * [change_user_role description]
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
        if ( ! isset( $map['assign'] ) || ! isset( $map['remove'] ) ) {
            return false;
        }

        // change roles
        $user->remove_role( $map['remove'] );
        $user->add_role( $map['assign'] );

        return true;
    }

    /**
     * [is_migration_completed description]
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

        if ( ! $wpdb->get_results( $sql )) {
            return true;
        }

        return false;
    }
}
