<?php

class LaterPay_Migrator_Install
{

    public static $subscriptions_table_name = 'laterpay_subscriber_migrations';

    /**
     * [install description]
     *
     * @return [type] [description]
     */
    public function install() {
        $config  = get_laterpay_migrator_config();

        // create table for storing parsed subscriber data
        $this->create_migration_table();

        // create upload directory, if it does not exist and set write access
        wp_mkdir_p( $config->get( 'upload_dir' ) );
        @chown( $config->get( 'upload_dir' ), 0777 );

        // add options
        add_option( 'laterpay_migrator_is_active', 0 );

        add_option( 'laterpay_migrator_products', '' );
        add_option( 'laterpay_migrator_products_mapping', '' );
        add_option( 'laterpay_migrator_limit', 200 );
        add_option( 'laterpay_migrator_expiry_modifier', '2 week' );

        add_option( 'laterpay_migrator_sitenotice_message', __( 'Get a free time pass for the rest of your subscription period', 'laterpay_migrator' ) );
        add_option( 'laterpay_migrator_sitenotice_button_text', __( 'Switch for Free Now', 'laterpay_migrator' ) );
        add_option( 'laterpay_migrator_sitenotice_bg_color', '#e8d20c' );
        add_option( 'laterpay_migrator_sitenotice_text_color', '#555555' );

        add_option( 'laterpay_migrator_mailchimp_api_key', '' );
        add_option( 'laterpay_migrator_mailchimp_ssl_connection', 0 );
        add_option( 'laterpay_migrator_mailchimp_campaign_after_expired', '' );
        add_option( 'laterpay_migrator_mailchimp_campaign_before_expired', '' );
    }

    /**
     * Create a table for managing all the user and process data required for the migration.
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
                subscriber_name             varchar(255)  NOT NULL,
                is_migrated_to_laterpay     tinyint(1)    NOT NULL DEFAULT 0,
                was_notified_before_expiry  tinyint(1)    NOT NULL DEFAULT 0,
                was_notified_after_expiry   tinyint(1)    NOT NULL DEFAULT 0,
                PRIMARY KEY  (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;";

        dbDelta( $sql );
    }

    /**
     * Get migration table name.
     *
     * @return string
     */
    public static function get_migration_table_name() {
        global $wpdb;

        return $wpdb->prefix . self::$subscriptions_table_name;
    }
}
