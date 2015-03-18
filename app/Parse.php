<?php

class LaterPay_Migrator_Parse {

    public static $column_mapping = array(
        'date'      => 'Nächste Zahlung am',
        'product'   => 'Produkt',
        'status'    => 'Zahlungsstatus',
        'email'     => 'E-Mail',
    );

    /**
     * Parse provided CSV file with subscriber data.
     *
     * @return bool|int false on mysql error or total rows affected
     */
    public static function parse_csv() {
        $config  = get_laterpay_migrator_config();

        $csvFile = null;
        // search csv file in upload folder
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

        // export all data from file into the array
        $data = array();
        foreach ( $csvFile as $line ) {
            $data[] = str_getcsv( $line );
        }

        // array to store mapped data
        $final_data = array();
        // get column names
        $columns    = array_shift( $data );
        $columns    = explode( ';', $columns[0] );

        // check if data has necessary columns and at least 1 row
        if ( ! $data || array_diff( array_intersect( self::$column_mapping, $columns ), self::$column_mapping ) ) {
            return 0;
        }

        // clear migration table
        self::clear_migration_table();

        // build array of values for query
        foreach ( $data as $row ) {
            $final_row = array();
            $values    = explode( ';', $row[0] );
            foreach ( $values as $key => $value ) {
                if ( in_array( $columns[$key], self::$column_mapping ) ) {
                    $final_row[array_search( $columns[$key], self::$column_mapping )] = $value;
                }
            }

            // check data and ignore non-active subscriptions
            // TODO: need standartize this
            $status = strpos( $final_row['status'], 'aktiv' ) !== false ? 1 : 0;
            if ( ! $status || ! $final_row['product'] || ! $final_row['email'] || ! $final_row['date'] ) {
                continue;
            }

            if ( ! in_array( $final_row['product'], $products ) ) {
                $products[] = $final_row['product'];
            }

            // prepare data and set as final
            $final_data[] = array(
                'expiry'  => date( 'Y-m-d', strtotime( $final_row['date'] ) ),
                'product' => $final_row['product'],
                'email'   => $final_row['email'],
            );
        }

        // save products in options
        update_option( 'laterpay_migrator_products', $products );

        // import data into database
        return self::import_data_into_migration_table( $final_data );
    }

    /**
     * Upload file
     *
     * @return void
     */
    public static function file_upload() {
        if ( ! isset( $_POST['_wpnonce'] ) || $_POST['_wpnonce'] !== wp_create_nonce( 'laterpay_migrator_form' ) ) {
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
        foreach($_FILES as $file)
        {
            if ( ! move_uploaded_file( $file['tmp_name'], $config->get( 'upload_dir' ) . basename( $file['name'] ) ) ) {
                wp_send_json(
                    array(
                        'success' => false,
                        'message' => __( 'Can\'t upload file.', 'laterpay_migrator' ),
                    )
                );
            }
        }

        // parse csv file
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
                    'message' => __( 'File contains wrong data.', 'laterpay_migrator' ),
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
     * Clear migration table
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
     * Clear migration table
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
                        {$table} (expiry, product, email)
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
     * Check if data exists in migration table
     *
     * @return bool
     */
    public static function check_migration_table_data() {
        global $wpdb;

        $table_subscriber_migrations = LaterPay_Migrator_Install::get_migration_table_name();
        $sql = "
            SELECT
                *
            FROM
                {$table_subscriber_migrations}
            LIMIT
                1
            ;";

        return (bool) $wpdb->get_results( $sql );
    }
}
