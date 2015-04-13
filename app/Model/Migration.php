<?php

class LaterPay_Migrator_Model_Migration {

    /**
     * Migration table name
     *
     * @var string
     */
    public static $table = 'laterpay_subscriber_migrations';

    /**
     * Create a table for managing all the user and process data required for the migration.
     *
     * @return void
     */
    public static function create_table() {
        global $wpdb;

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

        $table = $wpdb->prefix . self::$table;
        $sql = "
            CREATE TABLE $table (
                id                          INT(11)       NOT NULL AUTO_INCREMENT,
                expiry                      DATE          NOT NULL,
                product                     varchar(255)  NOT NULL,
                email                       varchar(255)  NOT NULL,
                subscriber_name             varchar(255)  NOT NULL,
                is_migrated_to_laterpay     tinyint(1)    NOT NULL DEFAULT 0,
                was_notified_before_expiry  tinyint(1)    NOT NULL DEFAULT 0,
                was_notified_after_expiry   tinyint(1)    NOT NULL DEFAULT 0,
                PRIMARY KEY  (id)
                UNIQUE KEY idx_migration_email (email)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;";

        dbDelta( $sql );
    }

    /**
     * Clear migration table.
     *
     * @return void
     */
    public static function clear_table() {
        global $wpdb;

        $table = $wpdb->prefix . self::$table;

        $sql = "TRUNCATE TABLE {$table};";

        $wpdb->query( $sql );
    }

    /**
     * Get user subscription data by email.
     *
     * @param $email
     *
     * @return mixed
     */
    public static function get_subscription_by_email( $email ) {
        global $wpdb;

        $table = $wpdb->prefix . self::$table;

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
     * Get expired subscriptions.
     *
     * @param  bool $ignore_notifications  ignore notification flag for subscriptions
     *
     * @return array|null $result
     */
    public static function get_expired_subscriptions( $ignore_notifications = false ) {
        global $wpdb;

        $table = $wpdb->prefix . self::$table;

        $sql = "
            SELECT
                *
            FROM
                {$table}
            WHERE
                is_migrated_to_laterpay = 0 AND ";

        $sql .= $ignore_notifications ? '' : "was_notified_after_expiry = 0 AND ";
        // expiry date is in the past
        $sql .= "expiry < CURDATE();";

        $result = $wpdb->get_results( $sql, ARRAY_A );

        if ( ! $result ) {
            return null;
        }

        return $result;
    }

    /**
     * Get about to expire subscriptions.
     *
     * @param  bool $ignore_notifications  ignore notification flag for subscriptions
     *
     * @return array|null $result
     */
    public static function get_about_to_expire_subscriptions( $ignore_notifications = false ) {
        global $wpdb;

        $table    = $wpdb->prefix . self::$table;
        $modifier = get_option( 'laterpay_migrator_expiry_modifier' );

        $sql = "
            SELECT
                *
            FROM
                {$table}
            WHERE
                is_migrated_to_laterpay = 0 AND ";

        $sql .= $ignore_notifications ? '' : "was_notified_before_expiry = 0 AND ";
        // expiry date equal or greater than current date
        $sql .= "expiry >= CURDATE() AND ";
        // and expiry date equal or less than modified date (14 days by default)
        $sql .= "expiry <= DATE_ADD( CURDATE(), INTERVAL $modifier );";

        $result = $wpdb->get_results( $sql, ARRAY_A );

        if ( ! $result ) {
            return null;
        }

        return $result;
    }

    /**
     * Set a given value to a given flag for a given user.
     *
     * @param  string $email user email
     * @param  string $flag  flag field name
     * @param  bool   $value flag value
     *
     * @return boolean
     */
    public static function set_flag( $email, $flag, $value = true ) {
        global $wpdb;

        $table = $wpdb->prefix . self::$table;
        $value = (int) $value;

        $sql   = "
            UPDATE
                {$table}
            SET
                {$flag} = {$value}
            WHERE
                email = '$email'
            ;"
        ;

        $result = $wpdb->query( $sql );

        if ( ! $result ) {
            return false;
        }

        return true;
    }

    /**
     * Get not yet migrated subscriptions.
     *
     * @return mixed
     */
    public static function get_not_migrated_subscriptions() {
        global $wpdb;

        $table = $wpdb->prefix . self::$table;

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

        return $wpdb->get_results( $sql );
    }

    /**
     * Get all subscriptions data.
     *
     * @return mixed
     */
    public static function get_all_data() {
        global $wpdb;

        $table = $wpdb->prefix . self::$table;

        $sql   = "
            SELECT
                *
            FROM
                {$table}
            ;"
        ;

        return $wpdb->get_results( $sql, ARRAY_A );
    }

    /**
     * Write data extracted from CSV file to database.
     *
     * @param array $data
     *
     * @return bool|int false on SQL error or total rows affected
     */
    public static function import_data( $data ) {
        global $wpdb;

        $table      = $wpdb->prefix . self::$table;
        $total_rows = count( $data );
        $last_key   = 0;
        $key        = 0;
        $limit      = get_option( 'laterpay_migrator_limit' );

        if ( $data && is_array( $data ) ) {
            while ( $total_rows > 0 ) {
                // create SQL statement from final data
                $is_first   = true;
                $count      = 0;

                $sql        = "
                    INSERT INTO
                        {$table} (expiry, product, email, subscriber_name)
                    VALUES
                ";

                // construct values section of SQL statement
                foreach ( $data as $key => $values ) {
                    if ( $key < $last_key ) {
                        continue;
                    }

                    if ( ! $is_first ) {
                        $sql .= ',';
                    }

                    $sql .= '(\'' . implode( '\',\'', $values ) . '\')';

                    $is_first = false;
                    $count++;

                    if ( $count > $limit ) {
                        break;
                    }
                }

                $sql .= " ON DUPLICATE KEY UPDATE
                            expiry = IF(VALUES(expiry) > expiry, VALUES(expiry), expiry)";

                // conclude SQL statement
                $sql .= ';';

                $last_key   = $key + 1;
                $total_rows = $total_rows - $limit;

                if ( ! $wpdb->query( $sql ) ) {
                    return false;
                }
            }
        }

        return $total_rows;
    }
}
