<?php
ini_set( 'error_reporting', E_ALL );
ini_set( 'display_errors', 1 );
ini_set( 'display_startup_errors', 1 );

set_time_limit( 0 );

require ( $_SERVER['DOCUMENT_ROOT'].'/writing-games/wp-load.php' );


$ch = curl_init();
$data = array( 'game_id' => 100 );
curl_setopt( $ch, CURLOPT_URL, 'http://localhost/writing-games/writing-games/23/score/' );
curl_setopt( $ch, CURLOPT_POST, 1 );
curl_setopt( $ch, CURLOPT_POSTFIELDS, $data );
// curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

$res = curl_exec( $ch );
curl_close( $ch );

echo "<pre>";
// print_r( $res );
echo "</pre>";
