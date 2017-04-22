<?php


if ( $_SERVER['REQUEST_METHOD' ] === 'GET' ) {

	if ( get_end_point() == FALSE ) {

		wg_debuger( get_all_info() );

	} else wg_debuger( get_end_point() );

}


if ( $_SERVER['REQUEST_METHOD' ] == 'POST' ) {
	// $current_url = get_current_url();
	// $game_id = get_id_score( $current_url );
	wg_debuger( insert_stat_table( $_POST ) );

}


if ( $_SERVER[ 'REQUEST_METHOD' ] == 'PUT' ) {

	wg_debuger( update_game_table() );

}
