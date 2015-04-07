<?php

class LaterPay_Migrator_Helper_Parse
{
    /**
     * @var array of column mapping
     */
    public static $column_mapping = array(
        0 => 'email',
        1 => 'first_name',
        2 => 'last_name',
        3 => 'date',
        4 => 'product',
    );

    /**
     * Parse provided CSV file with subscriber data.
     *
     * @return bool|int false on mysql error or total rows affected
     */
    public static function parse_csv() {
        $config  = get_laterpay_migrator_config();
        $logger  = new LaterPay_Migrator_Controller_Logger( $config->get( 'parse_log' ) );
        $csvFile = null;

        // search CSV file in upload folder
        $files = glob( $config->get( 'upload_dir' ) . '*', GLOB_MARK );
        foreach ( $files as $file ) {
            if ( substr( $file, -4, 4 ) === '.csv' ) {
                // get all data from CSV file
                $csvFile = @file( $file );
                break;
            }
        }

        if ( ! $csvFile ) {
            return false;
        }

        // initialize products array
        $products = array();

        // extract all data from the uploaded file into an array
        $data = array();
        foreach ( $csvFile as $line ) {
            $data[] = str_getcsv( $line );
        }

        // initialize array with mapped data
        $final_data = array();

        // check, if data has at least 1 row
        if ( ! $data ) {
            return 0;
        }

        // clear migration table
        LaterPay_Migrator_Model_Migration::clear_table();

        $invalid_count = 0;

        // build array of values for query
        foreach ( $data as $row ) {
            $final_row = array();
            $values    = explode( ';', $row[0] );
            foreach ( $values as $key => $value ) {
                if ( isset( self::$column_mapping[$key] ) ) {
                    $final_row[self::$column_mapping[$key]] = trim( $value, ' "' );
                    continue;
                }
                break;
            }

            // validate data
            if ( ! isset( $final_row['product'] ) || ! $final_row['product'] ) {
                $logger->log( 'Missed product: ', $final_row );
                $invalid_count++;
                continue;
            } else if ( ! isset( $final_row['email'] ) || ! $final_row['email'] ) {
                $logger->log( 'Missed email: ', $final_row );
                $invalid_count++;
                continue;
            } else if ( ! isset( $final_row['date'] ) || ! $final_row['date'] ) {
                $logger->log( 'Missed date: ', $final_row );
                $invalid_count++;
                continue;
            } else if ( ! strtotime( $final_row['date'] ) ) {
                $logger->log( 'Wrong date format: ', $final_row );
                $invalid_count++;
                continue;
            }

            // check, if user exists as WordPress user
            $user = get_user_by( 'email', $final_row['email'] );
            if ( ! $user instanceof WP_User ) {
                $logger->log( 'User not exist: ', $final_row );
                $invalid_count++;
                continue;
            }

            if ( ! in_array( $final_row['product'], $products ) ) {
                $products[] = $final_row['product'];
            }

            // make sure we have a name we can address the subscriber with in emails
            $subscriber_name = trim( $final_row['first_name'] . ' ' . $final_row['last_name'] );
            if ( $subscriber_name == '' ) {
                $subscriber_name = __( 'Subscriber', 'laterpay-migrator' );
            }

            // prepare data and set as final
            $final_data[] = array(
                'expiry'          => date( 'Y-m-d', strtotime( $final_row['date'] ) ),
                'product'         => $final_row['product'],
                'email'           => $final_row['email'],
                'subscriber_name' => $subscriber_name,
            );
        }

        // update invalid count
        update_option( 'laterpay_migrator_invalid_count', $invalid_count );

        // save products in options
        update_option( 'laterpay_migrator_products', $products );

        // reset mapping
        update_option( 'laterpay_migrator_products_mapping', false );

        // import data into database
        return LaterPay_Migrator_Model_Migration::import_data( $final_data );
    }
}
