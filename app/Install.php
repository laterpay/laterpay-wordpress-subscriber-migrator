<?php

class LaterPay_Migrator_Install {

    public static $subscriptions_table_name = 'laterpay_subscriber_migrations';

    /**
     * [install description]
     *
     * @return [type] [description]
     */
    public function install() {
        // create table for storing parsed subscriber data
        $this->create_migration_table();

        // only allow time pass purchases and no purchases of individual posts
        update_option( 'laterpay_only_time_pass_purchases_allowed', 1 );
        update_option( 'laterpay_migrator_limit', 200 );
        update_option( 'laterpay_migrator_expiry_modifier', '2 week' );
    }

    /**
     * [create_custom_table description]
     *
     * @return [type] [description]
     */
    protected function create_migration_table() {
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

        $table_subscriber_migrations = self::get_migration_table_name();
        $sql = "
            CREATE TABLE $table_subscriber_migrations (
                id                          INT(11)       NOT NULL AUTO_INCREMENT,
                expiry                      DATE          NOT NULL,
                product                     varchar(255)  NOT NULL,
                email                       varchar(255)  NOT NULL,
                is_migrated_to_laterpay     tinyint(1)    NOT NULL DEFAULT 0,
                is_notified_before_expired  tinyint(1)    NOT NULL DEFAULT 0,
                is_notified_after_expired   tinyint(1)    NOT NULL DEFAULT 0,
                PRIMARY KEY  (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;";

        dbDelta( $sql );
    }

    /**
     * Get migration table name
     *
     * @return string
     */
    public static function get_migration_table_name() {
        global $wpdb;

        return $wpdb->prefix . self::$subscriptions_table_name;
    }
}
