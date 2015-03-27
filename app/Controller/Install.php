<?php

class LaterPay_Migrator_Controller_Install
{

    /**
     * Install LaterPay migration plugin.
     *
     * @return void
     */
    public function install() {
        $config = get_laterpay_migrator_config();

        // create table for storing parsed subscriber data
        LaterPay_Migrator_Model_Migration::create_table();

        // create upload directory, if it does not exist and set write access
        wp_mkdir_p( $config->get( 'upload_dir' ) );
        @chown( $config->get( 'upload_dir' ), 0777 );

        // add options
        add_option( 'laterpay_migrator_is_active',                          0 );

        add_option( 'laterpay_migrator_products',                           '' );
        add_option( 'laterpay_migrator_products_mapping',                   '' );
        add_option( 'laterpay_migrator_limit',                              200 );
        add_option( 'laterpay_migrator_expiry_modifier',                    '2 week' );

        add_option( 'laterpay_migrator_sitenotice_message',                 __( 'Get a free time pass for the rest of your subscription period', 'laterpay-migrator' ) );
        add_option( 'laterpay_migrator_sitenotice_button_text',             __( 'Switch for Free Now', 'laterpay-migrator' ) );
        add_option( 'laterpay_migrator_sitenotice_bg_color',                '#f1d200' );
        add_option( 'laterpay_migrator_sitenotice_text_color',              '#555555' );

        add_option( 'laterpay_migrator_mailchimp_api_key',                  '' );
        add_option( 'laterpay_migrator_mailchimp_ssl_connection',           0 );
        add_option( 'laterpay_migrator_mailchimp_campaign_after_expired',   '' );
        add_option( 'laterpay_migrator_mailchimp_campaign_before_expired',  '' );
    }
}
