<?php

class LaterPay_Migrator_ParseCSV {

    public static $column_mapping = array(
        'date'      => 'Nächste Zahlung am',
        'type'      => 'Erste Zahlung',
        'status'    => 'Zahlungsstatus',
        'email'     => 'E-Mail',
    );

    public static $types_match = array(
        '15' => 0,
        '25' => 1,
        '45' => 2,
    );

    /**
     * Parse provided CSV file with subscriber data.
     *
     * @return [type] [description]
     */
    public static function parse_csv() {
        $config = get_laterpay_migrator_config();
        $file   = $config->get( 'upload_dir' ) . 'export.csv';

        // get all data from CSV file
        $csvFile = @file( $file );

        if ( ! $csvFile ) {
            return false;
        }

        $data = array();
        foreach ( $csvFile as $line ) {
            $data[] = str_getcsv( $line );
        }

        $final_data = array();
        $columns    = array_shift( $data );
        $columns    = explode( ';', $columns[0] );

        // build array of values for query
        foreach ( $data as $row ) {
            $final_row = array();
            $values    = explode( ';', $row[0] );
            foreach ( $values as $key => $value ) {
                if ( in_array( $columns[$key], self::$column_mapping ) ) {
                    $final_row[array_search( $columns[$key], self::$column_mapping )] = $value;
                }
            }

            // check status
            $status = strpos( $final_row['status'], 'aktiv' ) !== false ? 1 : 0;
            if ( ! $status ) {
                continue;
            }

            // process final row and set it to array
            $migrated_to_laterpay_data = array();
            $migrated_to_laterpay_data['subscription_end']      =   '\'' .
                                                                    date(
                                                                        'Y-m-d',
                                                                        strtotime($final_row['date'])
                                                                    ) .
                                                                    '\'';
            $migrated_to_laterpay_data['type']                  = self::$types_match[$final_row['type']];
            $migrated_to_laterpay_data['email']                 = '\'' . $final_row['email'] . '\'';
            $migrated_to_laterpay_data['migrated_to_laterpay']  = 0;

            $final_data[] = $migrated_to_laterpay_data;
        }

        global $wpdb;

        $table      = $wpdb->prefix . 'laterpay_subscriber_migrations';
        $total_rows = count( $final_data );
        $last_key   = 0;
        $limit      = get_option( 'lpmigrator_limit' );

        while ( $total_rows > 0 ) {
            // create SQL from final data
            $is_first   = true;
            $count      = 0;

            $sql        = "
                INSERT INTO
                    {$table} (subscription_end, subscription_duration, email, migrated_to_laterpay)
                VALUES
            ";

            foreach ( $final_data as $key => $data ) {
                if ( $key < $last_key ) {
                    continue;
                }

                if ( ! $is_first ) {
                    $sql .= ',';
                }

                $sql .= '(' . implode( ',', $data ) . ')';

                $is_first = false;
                $count++;

                if ( $count > $limit ) {
                    break;
                }
            }

            $sql .= ';';

            $last_key = $key + 1;

            $total_rows = $total_rows - $limit;

            $result = $wpdb->query( $sql );

            if ( ! $result ) {
                return false;
            }
        }

        return $total_rows;
    }
}
