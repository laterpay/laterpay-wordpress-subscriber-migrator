<?php

class LaterPay_Migrator_Parse
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

        // array of products
        $products = array();

        // extract all data from file into an array
        $data = array();
        foreach ( $csvFile as $line ) {
            $data[] = str_getcsv( $line );
        }

        // array to store mapped data
        $final_data = array();

        // check, if data has at least 1 row
        if ( ! $data  ) {
            return 0;
        }

        // clear migration table
        self::clear_migration_table();

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
            if ( ! $final_row['product'] || ! $final_row['email'] || ! $final_row['date'] ) {
                continue;
            } else if ( ! strtotime( $final_row['date'] ) ) {
                continue;
            }

            if ( ! in_array( $final_row['product'], $products ) ) {
                $products[] = $final_row['product'];
            }

            $first_name  = isset( $final_row['first_name'] ) ? $final_row['first_name'] : 'User';
            $second_name = isset( $final_row['last_name'] ) ? $final_row['last_name'] : 'User';

            // prepare data and set as final
            $final_data[] = array(
                'expiry'          => date( 'Y-m-d', strtotime( $final_row['date'] ) ),
                'product'         => $final_row['product'],
                'email'           => $final_row['email'],
                'subscriber_name' => trim( $first_name . ' ' . $second_name ),
            );
        }

        // save products in options
        update_option( 'laterpay_migrator_products', $products );

        // reset mapping
        update_option( 'laterpay_migrator_products_mapping', false );

        // import data into database
        return self::import_data_into_migration_table( $final_data );
    }

    /**
     * Upload CSV file with subscriber data.
     *
     * @return void
     */
    public static function file_upload() {
        // do not parse if migration process is active
        if ( get_option( 'laterpay_migrator_is_active' ) ) {
            wp_send_json(
                array(
                    'success' => false,
                    'message' => __( 'You can\'t upload file while migration process is active.', 'laterpay_migrator' ),
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
        $result = self::parse_csv();

        if ( $result === false ) {
            wp_send_json(
                array(
                    'success' => false,
                    'message' => __( 'Error during writing to the database.', 'laterpay_migrator' ),
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

    /**
     * Clear migration table.
     *
     * @return void
     */
    public static function clear_migration_table() {
        global $wpdb;

        $table = LaterPay_Migrator_Install::get_migration_table_name();
        $sql   = "TRUNCATE TABLE {$table};";

        $wpdb->query( $sql );
    }

    /**
     * Write data extracted from CSV file to database.
     *
     * @param array $data
     *
     * @return bool|int false on mysql error or total rows affected
     */
    public static function import_data_into_migration_table( $data ) {
        global $wpdb;

        $table      = LaterPay_Migrator_Install::get_migration_table_name();
        $total_rows = count( $data );
        $last_key   = 0;
        $limit      = get_option( 'laterpay_migrator_limit' );

        if ( $data && is_array( $data ) ) {
            while ( $total_rows > 0 ) {
                // create SQL from final data
                $is_first   = true;
                $count      = 0;

                $sql        = "
                    INSERT INTO
                        {$table} (expiry, product, email, subscriber_name)
                    VALUES
                ";

                foreach ( $data as $key => $values ) {
                    if ( $key < $last_key ) {
                        continue;
                    }

                    if ( ! $is_first ) {
                        $sql .= ',';
                    }

                    $sql .= '(\'' . implode( '\',\'', $values ) . '\')';

                    $is_first = false;
                    $count++;

                    if ( $count > $limit ) {
                        break;
                    }
                }

                $sql .= ';';

                $last_key   = $key + 1;
                $total_rows = $total_rows - $limit;

                if ( ! $wpdb->query( $sql ) ) {
                    return false;
                }
            }
        }

        return $total_rows;
    }

    /**
     * Check, if migration table has data (at least one row).
     *
     * @return bool
     */
    public static function check_migration_table_data() {
        global $wpdb;

        $table = LaterPay_Migrator_Install::get_migration_table_name();
        $sql = "
            SELECT
                *
            FROM
                {$table}
            LIMIT
                1
            ;";

        return (bool) $wpdb->get_results( $sql );
    }
}
