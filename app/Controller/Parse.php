<?php

class LaterPay_Migrator_Controller_Parse
{
    /**
     * Upload CSV file with subscriber data.
     *
     * @wp-hook wp_ajax_laterpay_migrator_file_upload
     *
     * @return void
     */
    public function file_upload() {
        // do not parse, if migration process is active
        if ( get_option( 'laterpay_migrator_is_active' ) ) {
            wp_send_json(
                array(
                    'success' => false,
                    'message' => __( 'You have to pause the migration before you can upload new data.', 'laterpay_migrator' ),
                )
            );
        }

        if ( ! isset( $_POST['_wpnonce'] ) || $_POST['_wpnonce'] !== wp_create_nonce( 'laterpay_migrator' ) ) {
            wp_send_json(
                array(
                    'success' => false,
                    'message' => __( 'Incorrect token.', 'laterpay_migrator' ),
                )
            );
        }

        if ( ! isset( $_FILES ) || count( $_FILES  ) > 1 ) {
            wp_send_json(
                array(
                    'success' => false,
                    'message' => __( 'Incorrect file.', 'laterpay_migrator' ),
                )
            );
        }

        $config = get_laterpay_migrator_config();

        // clear upload folder from .csv files
        $files = glob( $config->get( 'upload_dir' ) . '*', GLOB_MARK );
        foreach ( $files as $file ) {
            if ( substr( $file, -4, 4 ) === '.csv' ) {
                unlink( $file );
            }
        }

        // upload file
        foreach ( $_FILES as $file ) {
            if ( substr( $file['name'], -4, 4 ) !== '.csv' ) {
                wp_send_json(
                    array(
                        'success' => false,
                        'message' => __( 'The file you tried to upload did not conform to the required format.', 'laterpay_migrator' ),
                    )
                );
            }

            if ( ! move_uploaded_file( $file['tmp_name'], $config->get( 'upload_dir' ) . basename( $file['name'] ) ) ) {
                wp_send_json(
                    array(
                        'success' => false,
                        'message' => __( 'Can\'t upload file. Please make sure the upload folder is writable.', 'laterpay_migrator' ),
                    )
                );
            }
        }

        // parse CSV file
        $result = LaterPay_Migrator_Helper_Parse::parse_csv();

        if ( $result === false ) {
            wp_send_json(
                array(
                    'success' => false,
                    'message' => __( 'Error when writing to the database.', 'laterpay_migrator' ),
                )
            );
        } elseif ( $result === 0 ) {
            wp_send_json(
                array(
                    'success' => false,
                    'message' => __( 'File contains invalid data.', 'laterpay_migrator' ),
                )
            );
        }

        wp_send_json(
            array(
                'success' => true,
                'message' => __( 'File was successfully processed.', 'laterpay_migrator' ),
            )
        );
    }
}
