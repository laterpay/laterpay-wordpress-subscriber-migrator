<?php

class LaterPay_Migrator_Settings {

    /**
     * [add_settings_page description]
     */
    public function add_settings_page() {
        add_options_page(
            'Subscription Migration Settings',
            'Lpcustom',
            'manage_options',
            'lpcustom',
            array( $this, 'render_settings_page' )
        );
    }

    /**
     * [render_settings_page description]
     *
     * @return [type] [description]
     */
    public function render_settings_page() {
        // register and enqueue stylesheet
        wp_register_style(
            'laterpay-migrator-settings',
            LATERPAY_MIGRATOR_CSS_URL . 'laterpay-migrator-settings.css'
        );
        wp_enqueue_style( 'laterpay-migrator-settings' );

        // register and enqueue Javascript
        wp_register_script(
            'laterpay-migrator-settings',
            LATERPAY_MIGRATOR_JS_URL . 'laterpay-migrator-settings.js',
            array( 'jquery' ),
            false,
            true
        );
        wp_enqueue_script( 'laterpay-migrator-settings' );

        wp_localize_script(
            'laterpay-migrator-settings',
            'lpMigratorVars',
            array(
                'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            )
        );

        echo $this->get_content();
    }

    /**
     * [get_content description]
     *
     * @return [type] [description]
     */
    public function get_content() {
        $content = '
            <p>Upload your exported subscriptions here</p>
            <form id="lp_migrator_uploadForm">
                <input type="file" id="lp_migrator_fileInput" name="lp_migrator_file" multiple="false" />
                <input type="submit" id="lp_migrator_startUpload" value="Upload" />
            </form>
        ';

        return $content;
    }
}
