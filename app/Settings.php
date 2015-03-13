<?php

class LaterPay_Migrator_Settings {

    /**
     * [add_settings_page description]
     */
    public function add_settings_page() {
        add_options_page(
            'Subscription Migration Settings',
            'Subscription Migration Settings',
            'manage_options',
            'lpmigrator-settings',
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

        $this->get_content();
    }

    /**
     * [init_settings_page description]
     *
     * @return [type] [description]
     */
    public function init_settings_page() {
        // add sections with fields
        add_settings_section(
            'lpmigrator-data',
            __( 'Parsing', 'laterpay' ),
            array( $this, 'get_section_description' ),
            'lpmigrator-settings'
        );

        add_settings_field(
            'lpmigrator_data',
            __( 'CSV Data', 'laterpay' ),
            array( $this, 'get_textarea' ),
            'lpmigrator-settings',
            'lpmigrator-data'
        );

        register_setting( 'lpmigrator-settings', 'lpmigrator_data', array( $this, 'parse_text' ) );
    }

    /**
     * [get_content description]
     *
     * @return [type] [description]
     */
    public function get_content() {
        $content_head = '<div class="wrap">
                         <h2>Laterpay Migration Settings</h2>
                         <form id="lpmigration" method="POST" action="options.php">';
        echo $content_head;
        settings_fields( 'lpmigrator-settings' );
        do_settings_sections( 'lpmigrator-settings' );
        submit_button();
        echo '</form></div>';
    }

    /**
     * [get_section_description description]
     *
     * @return [type] [description]
     */
    public function get_section_description( $args ) {
        echo 'Enter your CSV Data here and press save to parse it:';
    }

    /**
     * [get_textarea description]
     *
     * @return [type] [description]
     */
    public function get_textarea( $args ) {
        $inputs_markup = '';

        $inputs_markup .= '<textarea rows="20" cols="80" name="lpmigrator_data">';
        $inputs_markup .= '</textarea>';

        echo $inputs_markup;
    }

    /**
     * [parse_text description]
     *
     * @param $input
     *
     * @return [type] [description]
     */
    public function parse_text( $input ) {
        // limit and parse data here
    }
}
