<?php

class LaterPay_Migrator_Controller_Admin_Parse extends LaterPay_Controller_Base {
    /**
     * @see LaterPay_Core_Event_SubscriberInterface::get_subscribed_events()
     */
    public static function get_subscribed_events() {
        return array(
            'wp_ajax_laterpay_migrator_file_upload' => array(
                array( 'laterpay_on_admin_view', 200 ),
                array( 'laterpay_on_ajax_send_json', 0 ),
                array( 'file_upload' ),
            ),
        );
    }

    /**
     * Upload CSV file with subscriber data.
     *
     * @param LaterPay_Core_Event $event
     * @wp-hook wp_ajax_laterpay_migrator_file_upload
     *
     * @return void
     */
    public function file_upload( LaterPay_Core_Event $event ) {
        // do not parse, if migration process is active
        if ( get_option( 'laterpay_migrator_is_active' ) ) {
            $event->set_result(
                array(
                    'success' => false,
                    'message' => __( 'You have to pause the migration before you can upload new data.', 'laterpay-migrator' ),
                )
            );
            return;
        }
        if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( $_POST['_wpnonce'] ), 'laterpay-migrator' ) ) {
            $event->set_result(
                array(
                    'success' => false,
                    'message' => __( 'Incorrect token.', 'laterpay-migrator' ),
                )
            );
            return;
        }

        if ( ! isset( $_FILES ) || count( $_FILES ) > 1 ) {
            $event->set_result(
                array(
                    'success' => false,
                    'message' => __( 'Incorrect file.', 'laterpay-migrator' ),
                )
            );
            return;
        }

        // clear upload folder from .csv files
        $files = glob( $this->config->get( 'upload_dir' ) . '*', GLOB_MARK );
        foreach ( $files as $file ) {
            if ( substr( $file, -4, 4 ) === '.csv' ) {
                unlink( $file );
            }
        }

        // upload file
        foreach ( $_FILES as $file ) {
            if ( substr( $file['name'], -4, 4 ) !== '.csv' ) {
                $event->set_result(
                    array(
                        'success' => false,
                        'message' => __( 'The file you tried to upload did not conform to the required format.', 'laterpay-migrator' ),
                    )
                );
                return;
            }

            if ( ! move_uploaded_file( $file['tmp_name'], $this->config->get( 'upload_dir' ) . basename( $file['name'] ) ) ) {
                $event->set_result(
                    array(
                        'success' => false,
                        'message' => __( 'Can\'t upload file. Please make sure the upload folder is writable.', 'laterpay-migrator' ),
                    )
                );
                return;
            }
        }

        // parse CSV file
        $result = LaterPay_Migrator_Helper_Parse::parse_csv();

        if ( $result === false ) {
            $event->set_result(
                array(
                    'success' => false,
                    'message' => __( 'Error when writing to the database.', 'laterpay-migrator' ),
                )
            );
            return;
        } elseif ( $result === 0 ) {
            $event->set_result(
                array(
                    'success' => false,
                    'message' => __( 'File contains invalid data.', 'laterpay-migrator' ),
                )
            );
            return;
        }

        $event->set_result(
            array(
                'success' => true,
                'message' => __( 'File was successfully processed.', 'laterpay-migrator' ),
            )
        );
    }
}
