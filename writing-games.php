<?php
/*
Plugin Name: Writing Games Plugin
Plugin URI: ----
Description: Описание плагина
Author: r0ma.ru
Author URI: http://r0ma.ru
Version: 0.1
*/

// Регистрируем константу - урл директории с данным плагином
define( 'WGPLUGINDIR', plugin_dir_url(__FILE__) );

// Получаем префикс таблиц WP и устанавливаем названия своих таблиц
global $wpdb;
$prefix = $wpdb->prefix;
define( 'TABLEWRITINGGAMES', $prefix . 'writing_games');
define( 'TABLEGAMESTAT', $prefix . 'game_statistics');

// Функция для отладки
function wg_debuger($arr) {
    echo "<pre>";
    print_r($arr);
    echo "</pre>";
}

// Регистрируем пользовательский тип поста  (CPT)
add_action( 'init', 'create_writing_games_post_type' );
function create_writing_games_post_type() {
    $labels = array(
	'name' => 'Почта Банк', // Основное название типа записи
	'singular_name' => 'Почта Банк', // отдельное название записи типа Book
	'add_new' => 'Добавить Почта Банк',
	'add_new_item' => 'Добавить новый офис/банкомат Почта Банк',
	'edit_item' => 'Редактировать офис/банкомат Почта Банк',
	'new_item' => 'Новый Почта Банк',
	'view_item' => 'Посмотреть Почта Банк',
	'search_items' => 'Найти Почта Банк',
	'not_found' =>  'Почта Банк не найден',
	'not_found_in_trash' => 'Почта Банк в корзине не найден',
	'parent_item_colon' => '',
	'menu_name' => 'Все отделения и банкоматы Почта Банка'
    );

    $args = array(
    	'labels' => $labels,
    	// 'labels' => '',
    	'public' => true,
    	'publicly_queryable' => true,
    	'show_ui' => true,
    	'show_in_menu' => true,
    	'query_var' => true,
    	'rewrite' => true,
    	'capability_type' => 'post',
    	'has_archive' => true,
    	'hierarchical' => false,
    	'taxonomies' => array(),
    	'menu_position' => 101,
    	// 'supports' => array('title','editor','author','thumbnail','excerpt','comments')
    );
    register_post_type( 'writing-games',$args );
}


// Подключаем стили и скрипты
// только на странице архива пользовательского типа постов is_post_type_archive()
add_action( 'wp_enqueue_scripts', 'add_writing_games_scripts' );
function add_writing_games_scripts() {
    if ( is_post_type_archive( 'writing-games' ) ) {
        wp_enqueue_style( 'writing-games-style', WGPLUGINDIR . 'style.css' );
        wp_enqueue_script( 'writing-games-js', WGPLUGINDIR . 'js_code.js' );
    }
}

// Добавляем свои правила переадресации
function add_rewrite_rules( $wp_rewrite ) {
	$new_rules = array(
        'writing-games/([0-9]{1,})/?$' => 'index.php?post_type=writing-games',
        'writing-games/([0-9]{1,})/score/?$' => 'index.php?post_type=writing-games',
	);
    $wp_rewrite->rules = $new_rules + $wp_rewrite->rules;

    // $rules = get_option( 'rewrite_rules' );
    // if ( !isset($rules[$new_rules]) ) {
    //     $wp_rewrite->flush_rules();
    // }
    return $wp_rewrite->rules;
}
add_action('generate_rewrite_rules', 'add_rewrite_rules');


// Парсим текущий URL (маршрутизатор)
// site.com/writing-games/34/
// site.com/writing-games/34/score/
// site.com/writing-games/
function get_end_point () {
    global $wp;
    $current_url = add_query_arg($wp->query_string, '', home_url($wp->request));
    $end_point = array_shift( explode( '?', array_pop( explode( '/', $current_url) ) ) );
    if ( ctype_digit( $end_point ) ) {
        return get_game_info( $end_point );
    } elseif ( $end_point == 'score') {
        $i = explode( 'score', $current_url );
        $i = explode( '/', $i[0] );
        $i = array_diff( $i, array('') );
        $end_point = array_pop( $i );
        return get_game_score( $end_point );
    } elseif ( $end_point == 'writing-games' ) {
        return false;
    }
}


// Действия при активации плагина
register_activation_hook( __FILE__, 'wgwp_activation' );
function wgwp_activation() {
    // СОЗДАЕМ ТАБЛИЦЫ В БД
    global $wpdb;

    $sql_create_table_statistics = file_get_contents( WGPLUGINDIR . 'sql/game_statistics.sql' );
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql_create_table_statistics);

    $sql_create_table_games = file_get_contents( WGPLUGINDIR . 'sql/writing_games.sql' );
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql_create_table_games );
}


// Создаем страницу плагина
add_action( 'admin_menu', 'wgwp_create_menu' );
function wgwp_create_menu() {
    add_menu_page('Плагин Writing Games', 'Writing Games', 'manage_options', 'wgwp-main-menu', 'wgwp_createmainmenu');
    // add_submenu_page( 'wgwp-main-menu', 'Заголовок под-меню', 'Основное', 'manage_options', 'wgwp-main-menu');
    // add_submenu_page( 'wgwp-main-menu', 'Заголовок = title', 'Настройки', 'manage_options', 'wgwp-settings', 'wgwp_settingsmenu');
}


// Основная страница плагина
function wgwp_createmainmenu() {
    echo "<div>основная страница</div>";
    echo plugin_dir_url(__FILE__);
    echo WGPLUGINDIR;
    echo TABLEGAMESTAT;
}


// ФУНКЦИИ ДЛЯ РАБОТЫ С БД

// ВСЯ информация из двух таблиц
function get_all_info() {
    global $wpdb;
    $table_writing_games = TABLEWRITINGGAMES;
    $table_stat = TABLEGAMESTAT;
    $info = $wpdb->get_results("SELECT * FROM $table_writing_games, $table_stat WHERE ($table_writing_games.id = $table_stat.game_id)", ARRAY_A);
	return $info;
}

// Проверяем наличие игры с id в БД
function game_in_db( $id ) {

}


// Вставляем в БД
function insert_in_db() {

}

// Информация об игре по ее `id`
function get_game_info( $id ) {
    global $wpdb;
    $table_writing_games = TABLEWRITINGGAMES;
    $info = $wpdb->get_row( "SELECT * FROM $table_writing_games WHERE `id` = $id", ARRAY_A );
    return json_encode( $info );
}

// Получаем `score` для игры с `id`
function get_game_score( $id ) {
    global $wpdb;
    $table_stat = TABLEGAMESTAT;
    $info = $wpdb->get_row( "SELECT * FROM $table_stat WHERE `game_id` = $id ORDER BY `score` DESC", ARRAY_A );
    return json_encode( $info );
}
