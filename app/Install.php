<?php

class LaterPay_Migrator_Install {

    public static $subscriptions_table_name = 'laterpay_subscriber_migrations';

    public static $time_pass_seed_data      = array(
                                                0 => array(
                                                    'duration'      => 3,
                                                    'period'        => 3,
                                                    'price'         => 14.99,
                                                    'revenue_model' => 'sis',
                                                    'title'         => '3-Monats-Pass',
                                                    'description'   => '3 Monate Zugriff auf alle Inhalte dieser Webseite',
                                                ),
                                                1 => array(
                                                    'duration'      => 6,
                                                    'period'        => 3,
                                                    'price'         => 24.99,
                                                    'revenue_model' => 'sis',
                                                    'title'         => '6-Monats-Pass',
                                                    'description'   => '6 Monate Zugriff auf alle Inhalte dieser Webseite',
                                                ),
                                                2 => array(
                                                    'duration'      => 1,
                                                    'period'        => 4,
                                                    'price'         => 44.99,
                                                    'revenue_model' => 'sis',
                                                    'title'         => '1-Jahres-Pass',
                                                    'description'   => '1 Jahr Zugriff auf alle Inhalte dieser Webseite',
                                                ),
                                            );

    /**
     * [install description]
     *
     * @return [type] [description]
     */
    public function install() {
        // create table for storing parsed subscriber data
        $this->create_custom_table();

        // create equivalent time passes for existing subscriptions
        if ( ! LaterPay_Helper_TimePass::get_all_time_passes() ) {
            $this->create_timepasses();
        }

        // parse CSV, if it's present in uploads folder
        $this->parse_csv();

        // only allow time pass purchases and no purchases of individual posts
        update_option( 'laterpay_only_time_pass_purchases_allowed', 1 );
        add_option( 'lpmigrator_limit', 200 );
    }

    /**
     * [create_timepasses description]
     *
     * @return [type] [description]
     */
    protected function create_timepasses() {
        $time_pass_model = new LaterPay_Model_TimePass();

        // create time passes with access to entire site for each time pass seed
        foreach ( self::$time_pass_seed_data as $time_pass_seed ) {
            $time_pass_model->update_time_pass( $time_pass_seed );
        }
    }

    /**
     * [create_custom_table description]
     *
     * @return [type] [description]
     */
    protected function create_custom_table() {
        global $wpdb;

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

        $table_subscriber_migrations = $wpdb->prefix . self::$subscriptions_table_name;
        $sql = "
            CREATE TABLE $table_subscriber_migrations (
                id                      INT(11)        NOT NULL AUTO_INCREMENT,
                subscription_end        DATE           NOT NULL,
                subscription_duration   tinyint(1)     NOT NULL,
                email                   varchar(255)   NOT NULL,
                migrated_to_laterpay    tinyint(1)     NOT NULL,
                PRIMARY KEY  (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;";

        dbDelta( $sql );
    }

    /**
     * [parse_csv description]
     *
     * @return [type] [description]
     */
    protected function parse_csv() {
        global $wpdb;

        $table_subscriber_migrations = $wpdb->prefix . self::$subscriptions_table_name;
        $sql = "
            SELECT
                *
            FROM
                {$table_subscriber_migrations}
            LIMIT
                1
            ;";

        $result = $wpdb->get_results( $sql );

        if ( ! $result ) {
            LaterPay_Migrator_ParseCSV::parse_csv();
        }
    }
}
