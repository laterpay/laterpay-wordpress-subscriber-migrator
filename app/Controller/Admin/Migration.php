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

        // workaround to make sure the default sitenotice message is translated
        if ( get_option( 'laterpay_migrator_sitenotice_message' ) === false ) {
            add_option( 'laterpay_migrator_sitenotice_message', __( 'Get a free time pass for the rest of your subscription period', 'laterpay-migrator' ) );
        }

        // workaround to make sure the default sitenotice button text is translated
        if ( get_option( 'laterpay_migrator_sitenotice_button_text' ) === false ) {
            add_option( 'laterpay_migrator_sitenotice_button_text', __( 'Switch for Free Now', 'laterpay-migrator' ) );
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
            'title'   => __( 'LaterPay Subscriber Migration', 'laterpay-migrator' ),
            'content' => __( '
                            <p>
                                <strong>What is LaterPay Migrator?</strong><br>
                                LaterPay Migrator is an extension for the official LaterPay WordPress plugin, that helps you to migrate users from an existing subscription or membership plugin to LaterPay. It was primarily developed to migrate users from the Digimember WordPress plugin to LaterPay, but can also be used for all other subscription or membership plugins, which give access to logged-in users with a certain user role.
                            </p>
                            <p>
                                <strong>How Does It Work?</strong><br>
                                <ul>
                                    <li> You import user data from your current subscription plugin (all data remain on your server).</li>
                                    <li> Each user receives two notifications per email (via MailChimp), that you will switch to LaterPay.</li>
                                    <li> One email is sent 14 days before his current subscription expires, one on the day after it has expired.</li>
                                    <li> Users with a running subscription can login and access your content just as always.</li>
                                    <li> If they want, they can switch to LaterPay before their subscription has ended with a free time-pass for the rest of the subscription period.<br>
                                         For that purpose, logged-in users with a running subscription will see a site notice, that offers them to switch to LaterPay with a free time pass.</li>
                                    <li> After a subscription has ended, the user can buy a time pass or individual posts with LaterPay.</li>
                                </ul>
                            </p>
                            <br>
                            <br>
                            <strong>Step-by-Step Migration Process</strong>
                            <p>
                                <strong>0. Prerequisites</strong><br>
                                To use this plugin, the following prerequisites have to be met:
                                <ul>
                                    <li> The LaterPay Migrator plugin requires an installed, activated and configured installation of the <a href="http://wordpress.org/plugins/laterpay">LaterPay WordPress plugin</a>.</li>
                                    <li> The LaterPay plugin should be in test mode.
                                    <li> You have to have a LaterPay merchant account, which you can <a href="https://laterpay.net/become-seller/">register here for free</a>.</li>
                                    <li> You should be familiar with MailChimp.</li>
                                </ul>
                            </p>
                            <p>
                                <strong>1. Prepare User Data</strong><br>
                                Very likely, your subscription plugin provides the option to export user data. The LaterPay Migrator plugin needs a .csv file that contains the following information (in exactly that order):
                                <ul>
                                    <li> email address</li>
                                    <li> first name</li>
                                    <li> last name</li>
                                    <li> expiry date of the current subscription</li>
                                    <li> name of the current subscription</li>
                                </ul>
                                The data have to fulfill the following requirements:
                                <ul>
                                    <li> UTF-8 encoded .csv file</li>
                                    <li> No column heading (i.e. the first row is not "email|first name|..."" but "test@laterpay.net|Max|...")</li>
                                    <li> Fields must be semicolon-separated (;)</li>
                                    <li> The fields must not contain spaces</li>
                                    <li> The expiry data has to have the format DD-MM-YYYY (04-06-2018 for the 4 June 2018)</li>
                                    <li> Text must be qualified by double-quotes ("...")</li>
                                </ul>
                                You can download a template .csv on the "Migration" tab.
                            </p>
                            <p>
                                <strong>2. Prepare Users and User Roles</strong><br>
                                You have to make sure, that the users, who should be migrated to LaterPay, have a custom user role – I.e. it is not sufficient if they have a WordPress standard role like "subscriber". To create a custom user role, we recommend the WordPress plugin <a href="https://wordpress.org/plugins/user-role-editor/">User Role Editor</a>.
                                <ul>
                                    <li> Assign all the users, who should be migrated, to a custom user role (e.g. "pre_migration").</li>
                                    <li> Create another custom user role for migrated users (e.g. "migrated").</li>
                                </ul>
                            </p>
                            <p>
                                <strong>3. Set Up MailChimp</strong><br>
                                Email notifications will be sent with MailChimp.
                                <ul>
                                    <li> Go to <a href="https://mailchimp.com">MailChimp</a> and create an account (if you don\'t have one already).</li>
                                    <li> You have to create two empty lists: One for the notification 14 days before a subscription ends (e.g. "List 14 Days") and one for the notification one day after the subscription has expired (e.g. "List Expired").</li>
                                    <li> You also have to create two campaigns for these two notifications. If you don\'t want to create the templates for these campaigns yourself, the plugin comes with two neutral templates for the <a href="XXX">14 Days Notification</a> and the <a href="XXX">Expiry Notification</a> that you can import to MailChimp.</li>
                                    <li> As you are currently in your MailChimp account: Please copy the following information, you will need it later: The names of the "14 Days" and "Expiry" campaigns, and your MailChimp API key (You will find it on "Account > Extras > API Keys").</li>
                                </ul>
                            </p>
                            <p>
                                <strong>4. Create Time Passes</strong><br>
                                Go to the LaterPay WordPress plugin\'s "Pricing" tab and create all the time passes, that you want to offer to your users after the migration.
                            </p>
                            <p>
                                <strong>5. Give Unlimited Access to Subscribers</strong><br>
                                In the next step (6.) we will deactivate your current subscription plugin. Before, we have to make sure that users with a running subscription will still have access to your content.
                                <ul>
                                    <li> Go to "Settings > LaterPay".</li>
                                    <li> Scroll down to the "Unlimited Access to Paid Content" section.</li>
                                    <li> Choose "all" for the custom user role you created for the users, who have not yet been migrated to LaterPay (in our example "pre_migration"). This setting will give them unlimited access to all LaterPay protected content (After they have been migrated, they will be unassigned from that user role and lose this free access again.).</li>
                                </ul>
                            </p>
                            <p>
                                <strong>6. Deactivate Your Current Migration Plugin and Switch to Live Mode</strong><br>
                                <ul>
                                    <li> Go to the LaterPay plugin\'s "Account" tab and switch the plugin to live mode. All users, who are not logged-in and don\'t have the custom user role "pre_migration" (i.e. your subscribers), will be asked to pay for your content with LaterPay or purchase a time pass.</li>
                                    <li> Deactivate your current migration plugin.</li>
                                </ul>
                            </p>
                            <p>
                                <strong>7. Import User Data</strong><br>
                                Now we have everything at hand to prepare the migration.
                                <ul>
                                    <li> Click "Select CSV File to Upload" on the LaterPay plugin\'s "Migration" tab (that should be, where you currently are) and select the .csv file that contains your user data. Please note again: This file will never be uploaded to LaterPay, it remains on your server.</li>
                                    <li> If there are major issues with the uploaded file (like none of the provided data is valid), you will see an error message.</li>
                                    <li> If all (or most) data is valid, you will see some statistics in the upper right corner:<br>
                                    "Valid" is the number of valid data.<br>
                                    "Invalid" is the number of ignored data, that didn\'t meet the formal requirements.<br>
                                    "Migrated" is the number of users, who either switched to LaterPay before their subscription ended or whose subscription already expired.<br>
                                    "Remaining" is the number of users, who have a running subscription and haven\'t yet switched to LaterPay.<br>
                                    "Last Expiry" is the last date in the imported user data, when a subscription ends. By that point in time, the migration will be finished.<br></li>
                                </ul>
                            </p>
                            <p>
                                <strong>8. Map Subscriptions</strong><br>
                                After you successfully imported the user data, you have to map your current subscriptions to your existing time passes and define, which user role should be assigned to and unassigned from users, after they have switched to LaterPay or their subscription has expired.<br>
                                So the setting<br>
                                "Product: 1-Year-Subscription | Unassign from user role: pre-migration | Assign to user role: migrated | Time Pass: 1 Year"<br>
                                would usassign a user, who has currently a "1-Year-Subscription", from the user role "pre-migration" and assign him to the user role "migrated" once he has switched to LaterPay or his subscription has expired.<br>
                                By unassigning the user from the "pre_migration" role and assigning him to another user role, this user loses the full access, you gave him in step 5.
                            </p>
                            <p>
                                <strong>9. Configure Site Notice</strong><br>
                                Users with a running subscription will see a site notice at the top of the screen and a button, which will open a purchase dialog for a free time pass for the rest of their subscription period. I.e. users can switch to LaterPay even before their subscription has ended. You can change the text and color of the site notice and the purchase button.
                            </p>
                            <p>
                                <strong>10. Fill In MailChimp Settings</strong><br>
                                We are almost done – now you have to fill in your MailChimp infos (see 3.):<br>
                                Fill in the campaign names for the "14 Days" and "Expired" campaigns as well as your Mailchimp API Key.
                            </p>
                            <p>
                                <strong>11. Start the Migration</strong><br>
                                You\'re done – Click "Start Migration"
                                <ul>
                                    <li> Users in the imported user data, whose subscription had already expired before this point in time, will automatically be marked as "migrated" and won\'t receive any notifications.</li>
                                    <li> Every night, MailChimp will send out the respective notifications to your users (Don\'t worry: Each user will at most receive two mails).</li>
                                    <li> Logged-in users with a running subscription will see the sitenotice and can switch to LaterPay. They will be able to access the content just as always, until their subscription has expired.</li>
                                    <li> If their subscription expires, they won\'t see this site notice anymore.</li>
                                    <li> Users, who switch to LaterPay before their subscription has expired, won\'t receive emails anymore.</li>
                                </ul>
                            </p>',
                'laterpay-migrator'
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
