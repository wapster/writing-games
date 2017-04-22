<?php
// echo $current_url = add_query_arg($wp->query_string, '', home_url($wp->request));

if ( $_SERVER['REQUEST_METHOD'] == GET ) {

	if ( get_end_point() == FALSE ) {

		wg_debuger( get_all_info() );

	} else wg_debuger( get_end_point() );

}


if ( $_SERVER['REQUEST_METHOD'] == POST ) {
	// $current_url = get_current_url();
	// $game_id = get_id_score( $current_url );
	wg_debuger( insert_in_stat_db( $_POST ) );
}


if ( $_SERVER['REQUEST_METHOD'] == PUT ) {


}
