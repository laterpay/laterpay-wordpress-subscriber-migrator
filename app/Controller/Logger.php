<?php

class LaterPay_Migrator_Controller_Logger
{

    protected $writer = null;

    /**
     * Logger constructor.
     *
     * @param $file_name
     *
     * @return void
     */
    public function __construct( $file_name ) {
        if ( ! $this->writer ) {
            $config       = get_laterpay_migrator_config();
            $writer       = @fopen( $config->get( 'log_dir' ) . $file_name, 'a+' );
            $this->writer = $writer ? $writer : null;
        }
    }

    /**
     * Logger destructor.
     *
     * @return void
     */
    public function __destruct() {
        @fclose( $this->writer );
        $this->writer = null;
    }

    /**
     * Log writer.
     *
     * @param null $message Log message
     * @param null $data    Log data
     *
     * @return bool|int     result of operation
     */
    public function log( $message = null, $data = null ) {
        if ( ! $this->writer ) {
            return false;
        }

        $ts  = date( 'Y.m.d - H:i:s', time() );
        $res = @fwrite( $this->writer, $ts . ": $message" . print_r( $data, true ) . PHP_EOL );

        return $res;
    }
}
