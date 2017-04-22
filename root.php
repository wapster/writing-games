<?php
ini_set( 'error_reporting', E_ALL );
ini_set( 'display_errors', 1 );
ini_set( 'display_startup_errors', 1 );

set_time_limit( 0 );

// require ( $_SERVER['DOCUMENT_ROOT'].'/writing-games/wp-load.php' );


// Получить id для страниц формата site.com/writing-games/12/score/
function get_id( $url ) {
	$i = explode( 'score', $url );
	$i = explode( '/', $i[0] );
	$i = array_diff( $i, array('') );
	$id = array_pop( $i );
    return $id;
}


$url = 'http://localhost/writing-games/writing-games/18/score/';

// получаем id из url
$id = get_id( $url );

$data = array(
    'game_id' => $id,
    'game_type' => 'writing-games',
    'user_name' => 'Roman',
    'score' => '32.9',
    'score_desc' => 'description of score',
    );

$ch = curl_init( $url );
// curl_setopt( $ch, CURLOPT_URL, $url );
curl_setopt( $ch, CURLOPT_POST, 1 );
curl_setopt( $ch, CURLOPT_POSTFIELDS, $data );
// curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

$res = curl_exec( $ch );
curl_close( $ch );

echo "<pre>";
// print_r( $res );
echo "</pre>";
