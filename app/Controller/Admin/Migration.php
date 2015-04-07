<?php

class LaterPay_Migrator_Controller_Admin_Migration extends LaterPay_Controller_Abstract
{
    const ADMIN_MENU_POINTER = 'lpsmp01';

    /**
     * Load assets.
     *
     * @return void
     */
    public function load_assets() {
        // load backend styles from 'laterpay' plugin plus migrator plugin-specific styles
        wp_register_style(
            'laterpay-backend',
            $this->config->get( 'lp_css_url' ) . 'laterpay-backend.css',
            array(),
            $this->config->get( 'lp_version' )
        );
        wp_register_style(
            'open-sans',
            '//fonts.googleapis.com/css?family=Open+Sans:300italic,400italic,600italic,300,400,600&subset=latin,latin-ext'
        );
        wp_register_style(
            'laterpay-migrator-backend',
            $this->config->get( 'css_url' ) . 'laterpay-migrator-backend.css'
        );
        wp_enqueue_style( 'laterpay-backend' );
        wp_enqueue_style( 'open-sans' );
        wp_enqueue_style( 'laterpay-migrator-backend' );

        // load backend scripts from 'laterpay' plugin plus migrator plugin-specific Javascript
        wp_register_script(
            'laterpay-backend',
            $this->config->get( 'lp_js_url' ) . 'laterpay-backend.js',
            array( 'jquery' ),
            $this->config->get( 'lp_version' ),
            true
        );
        wp_register_script(
            'laterpay-migrator-backend',
            $this->config->get( 'js_url' ) . 'laterpay-migrator-backend.js',
            array( 'jquery' ),
            false,
            true
        );
        wp_enqueue_script( 'laterpay-backend' );
        wp_enqueue_script( 'laterpay-migrator-backend' );

        // pass variables to Javascript
        wp_localize_script(
            'laterpay-migrator-backend',
            'lpMigratorVars',
            array(
                'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            )
        );
    }

    /**
     * Render page.
     *
     * @return void
     */
    public function render_page() {
        $this->load_assets();

        global $wp_roles;

        $timepasses             = LaterPay_Helper_TimePass::get_all_time_passes();
        $roles                  = $wp_roles->roles;

        $migration_is_active    = get_option( 'laterpay_migrator_is_active' );
        $migration_is_completed = LaterPay_Migrator_Helper_Subscription::is_migration_completed();
        $status_class           = 'lp_is-setting-up';

        if ( $migration_is_active ) {
            $status_class = 'lp_is-migrating';
        } else if ( $migration_is_completed ) {
            $status_class = 'lp_is-completed';
        }

        // assign variables to the view template
        $view_args = array(
            'plugin_is_in_live_mode'            => (bool) get_option( 'laterpay_plugin_is_in_live_mode', false ),
            'top_nav'                           => $this->get_menu( 'backend/partials/navigation', $this->config->get( 'lp_view_dir' ) ),
            'admin_menu'                        => LaterPay_Helper_View::get_admin_menu(),
            'subscriptions_state'               => LaterPay_Migrator_Helper_Subscription::get_migration_status(),
            'mailchimp_api_key'                 => get_option( 'laterpay_migrator_mailchimp_api_key' ),
            'mailchimp_campaign_before_expired' => get_option( 'laterpay_migrator_mailchimp_campaign_before_expired' ),
            'mailchimp_campaign_after_expired'  => get_option( 'laterpay_migrator_mailchimp_campaign_after_expired' ),
            'mailchimp_ssl_connection'          => get_option( 'laterpay_migrator_mailchimp_ssl_connection' ),
            'sitenotice_message'                => get_option( 'laterpay_migrator_sitenotice_message' ),
            'sitenotice_button_text'            => get_option( 'laterpay_migrator_sitenotice_button_text' ),
            'sitenotice_bg_color'               => get_option( 'laterpay_migrator_sitenotice_bg_color' ),
            'sitenotice_text_color'             => get_option( 'laterpay_migrator_sitenotice_text_color' ),
            'products'                          => get_option( 'laterpay_migrator_products' ),
            'timepasses'                        => $timepasses,
            'roles'                             => $roles,
            'products_mapping'                  => get_option( 'laterpay_migrator_products_mapping' ),
            'example_url'                       => $this->config->get( 'plugin_url' ) . 'templates/example.csv',
            'migration_is_active'               => $migration_is_active,
            'migration_is_completed'            => $migration_is_completed,
            'status_class'                      => $status_class,
        );

        // render 'migration' tab in 'laterpay' plugin backend
        $this->assign( 'laterpay', $view_args );
        $this->render( 'backend/migration' );
    }

    /**
     * Add 'migration' tab to the 'laterpay' plugin backend navigation.
     *
     * @param $menu
     *
     * @return mixed
     */
    public function add_menu( $menu ) {
        $migration_tab_url = 'laterpay-migration-tab';

        $menu[ 'migration' ] = array(
            'url'   => $migration_tab_url,
            'title' => __( 'Migration', 'laterpay-migrator' ),
            'cap'   => 'activate_plugins',
            'run'   => array( $this, 'render_page' ),
        );

        // add action for contextual help render
        add_action( 'load-laterpay_page_' . $migration_tab_url, array( $this, 'add_help' ) );

        return $menu;
    }

    /**
     * Add contextual help for migration tab.
     *
     * @return void
     */
    public function add_help() {
        $screen = get_current_screen();
        $screen->add_help_tab( array(
            'id'      => 'laterpay_migration_tab_help',
            'title'   => __( 'Subscriber Migration', 'laterpay_migrator' ),
            'content' => __( '
                            <p>
                                Explanation goes here!
                            </p>',
                'laterpay_migrator'
            ),
        ) );
    }

    /**
     * Add wp pointers.
     *
     * @return void
     */
    public function add_pointers() {
        $pointers = $this->get_active_pointers();

        // don't render the template, if there are no pointers to be shown
        if ( empty( $pointers ) ) {
            return;
        }

        // assign pointers
        $view_args = array(
            'pointers' => $pointers,
        );

        $this->assign( 'laterpay', $view_args );

        echo $this->get_text_view( 'backend/pointers' );
    }

    /**
     * Get all active pointers.
     *
     * @return array $pointers
     */
    public function get_active_pointers() {
        $dismissed_pointers = explode( ',', (string) get_user_meta( get_current_user_id(), 'dismissed_wp_pointers', true ) );
        $pointers = array();

        if ( ! in_array( self::ADMIN_MENU_POINTER, $dismissed_pointers ) ) {
            $pointers[] = self::ADMIN_MENU_POINTER;
        }

        return $pointers;
    }

    /**
     * Return all pointer constants from current class.
     *
     * @return array $pointers
     */
    public static function get_all_pointers() {
        $reflection         = new ReflectionClass( __CLASS__ );
        $class_constants    = $reflection->getConstants();
        $pointers           = array();

        if ( $class_constants ) {
            foreach ( array_keys( $class_constants ) as $key_value ) {
                if ( strpos( $key_value, 'POINTER') !== FALSE ) {
                    $pointers[] = $class_constants[$key_value];
                }
            }
        }

        return $pointers;
    }

    /**
     * Enqueue assets to show wp pointers.
     *
     * @return void
     */
    public function add_pointers_script() {
        $pointers = $this->get_active_pointers();

        // don't enqueue the assets, if there are no pointers to be shown
        if ( empty( $pointers ) ) {
            return;
        }

        wp_enqueue_script( 'wp-pointer' );
        wp_enqueue_style( 'wp-pointer' );
    }
}
