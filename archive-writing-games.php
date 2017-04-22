<?php

if ( $_SERVER['REQUEST_METHOD'] == GET ) {

	if ( get_end_point() == FALSE ) {

		wg_debuger( get_all_info() );

	} else wg_debuger( get_end_point() );

}



if ( $_SERVER['REQUEST_METHOD'] == POST ) {
	$id = get_end_point();

	wg_debuger( $_POST );
	$game_id = $_POST['game_id'];
	global $wpdb;
    $info = $wpdb->insert( 'wp_game_statistics', ['game_id' => $game_id], ['%d'] );
    var_dump( $info );
	// wg_debuger( $id );
}




$path = plugin_dir_url('writing-games/sent_post.php');
$url = $path . 'sent_post.php';
$args = array(
	'method' => 'POST',
	'body' => array(
		'game_id' => '99',
		'game_type' => 'logic',
		'user_name' => 'Leo',
		'score' => '1.2',
		'score_desc' => 'Description Leo game',
	),
);
$response = wp_remote_post( $url, $args );

// проверка ошибки
if ( is_wp_error( $response ) ) {
   $error_message = $response->get_error_message();
   echo "Что-то пошло не так: $error_message";
} else {
   $bodys = $response['body'];
   // echo 'Ответ: <pre>';
   // print_r( $bodys );
   // echo '</pre>';
   // extract($bodys, EXTR_PREFIX_SAME);
   // echo $game_id;
   // global $wpdb;
   // $table_stat = TABLEGAMESTAT;
   // $info = $wpdb->get_row( "SELECT * FROM $table_stat WHERE `game_id` = $id ORDER BY `score` DESC", ARRAY_A );
   // return json_encode( $info );
}



?>
