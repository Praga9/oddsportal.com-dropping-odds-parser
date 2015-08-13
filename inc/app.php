<?php
error_reporting( E_ALL ^ E_NOTICE ^ E_DEPRECATED );

//date_default_timezone_get( 'Asia/Bangkok' ); // timezone (default: php.ini date.timezone)
mb_internal_encoding( 'UTF-8' ); // everythin in UTF-8
ini_set( 'default_charset', 'UTF-8' );
define( 'INC', ROOT.'inc/' );

require( INC.'nokogiri.php' );
require( INC.'dump.php' );

class App{
    
    public function __construct() {
    }
    
    protected function get( $url ){
        $cache_file = md5( $url.date("Y/m/d H:i") );
        $cache_dir  = ROOT.'cache/';
        //if( file_exists( $cache_dir.$cache_file) ){
        //    $page = file_get_contents( $cache_dir.$cache_file );
        //}else{
            $page = file_get_contents( $url );
        //    file_put_contents( $cache_dir.$cache_file, $page );
        //}
        return $page;
    }
}
