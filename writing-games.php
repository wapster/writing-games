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

    $args = array(
    	// 'labels' => $labels,
    	'labels' => '',
    	'public' => false,
    	'publicly_queryable' => true,
    	'show_ui' => false,
    	'show_in_menu' => false,
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
    return $wp_rewrite->rules;
}
add_action('generate_rewrite_rules', 'add_rewrite_rules');


// Парсим текущий URL (маршрутизатор)
// site.com/writing-games/34/
// site.com/writing-games/34/score/
// site.com/writing-games/
function get_end_point () {
    global $wp;
    $current_url = get_current_url();
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
    add_menu_page('Плагин Writing Games', 'Writing Games', 'manage_options', 'wgwp-main-menu', 'writing_games_list_games');

    $main_menu = add_submenu_page( 'wgwp-main-menu', 'Заголовок под-меню', 'Список игр', 'manage_options', 'wgwp-main-menu');
    // подключаем CSS на странице плагина
    add_action('admin_print_styles-'. $main_menu, 'writing_games_admin_css');

    $add_game_page = add_submenu_page( 'wgwp-main-menu', 'Добавить игру', 'Добавить игру', 'manage_options', 'writing-games-add-game', 'writing_games_add_game');
    // подключаем CSS на странице плагина
    add_action('admin_print_styles-'. $add_game_page, 'writing_games_admin_css');

    $edit_game_page = add_submenu_page( NULL, 'Редактировать игру', 'Редактировать игру', 'manage_options', 'writing-games-edit-game', 'writing_games_edit_game');
    add_action('admin_print_styles-'. $edit_game_page, 'writing_games_admin_css');

    add_action( 'wp_enqueue_scripts', 'my_scripts_method', 11 );
}

function my_scripts_method() {
	wp_deregister_script( 'jquery' );
	wp_register_script( 'jquery', 'http://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js');
	wp_enqueue_script( 'jquery' );
}

// Подключаем файл стилей для страниц плагина в админке сайта
function writing_games_admin_css() {
    wp_enqueue_style( 'writing-game-plugin-page-stylesheet', plugins_url('admin-style.css', __FILE__) );
    wp_enqueue_style( 'writing-game-plugin-include-fontawesome', 'https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css' );
}


// Отображаем список игр
function writing_games_list_games() {

    // срабатывает при удалении игры
    if ( !empty( $_POST['delete_game'] ) ) {
        if ( delete_game() === false ) {
            echo "<h1 class='error'>Ошибка при удалении</h1><hr>";
        } else {
            echo "<h1 class='ok'>Игра удалена</h1><hr>";
        }
    }

    // получаем список всех игр
    $list_games = get_list_games(); // array
?>
<div class="content">
<h1>Список игр</h1>
<h2>Количество: <?php echo count($list_games); ?></h2>
    <table class="list-games" cellspacing='0' cellpadding='20'>
    <tr>
        <th>Name</th>
        <th>Words</th>
        <th>Time</th>
        <th>Enabled</th>
    </tr>
    <?php
        foreach ($list_games as $key => $game) :
            $game_id       =  $game['id'];
            $game_name     =  $game['name'];
            $game_words    =  $game['words'];
            $game_time     =  $game['time'];
            $game_enabled  =  $game['enabled'];
    ?>
        <tr>
            <td><?php echo $game_name; ?></td>
            <td><?php echo $game_words; ?></td>
            <td align="center"><?php echo $game_time; ?></td>
            <td align="center"><?php echo ($game_enabled == 1) ? 'yes' : 'no'; ?></td>
            <td align="right" width="100px">
                <form action="" method="POST">
                    <a style="text-decoration: none;" href="../wp-admin/admin.php?page=writing-games-edit-game&id=<?php echo $game_id; ?>">
                        <button type="button" name="edit_game" value="" title="Редактировать">
                            <i class="fa fa-pencil-square-o" aria-hidden="true"></i>
                        </button>
                    </a>

                    &nbsp;&nbsp;&nbsp;

                    <button type="submit" name="delete_game" value="<?php echo $game_id; ?>" title="Удалить">
                        <i class="fa fa-trash-o" aria-hidden="true"></i>
                    </button>
                </form>
            </td>
        </tr>
    <?php endforeach;?>
    </table>
</div>
<?php } // end writing_games_list_games()


// Добавляем игру
function writing_games_add_game() {

    if ( !empty($_POST['add_game']) ) {
        if ( add_game() ) {
            echo "<h1 class='ok'>Игра добавлена</h1><hr>";
        } else {
            echo "<h1 class='error'>Ошибка при добавлении игры</h1>";
        }
    }
?>

<h1>Добавить игру</h1>
<div class="">
    <form class="" action="" method="POST">
        <p><input type="text" name="game_name" value="" placeholder="название" autofocus="on" required></p>
        <p><textarea name="game_words" rows="8" cols="40" placeholder="текст" required></textarea></p>
        <p>
            <input type="number" name="game_time" value="" placeholder="время" required>
            <span class=""><label><input type="checkbox" checked name="game_enabled">Enabled</label></span>
        </p>
        <input type="submit" name="add_game" value="Добавить">
    </form>
</div>

<?
} // end of writing_games_add_game()


// Редактируем игру
function writing_games_edit_game() {

    if ( !empty( $_POST['update_game']) ) {
        $update = update_game();
        if ( $update > 0 ) {
            echo "<h1 class='ok'>Обновлено успешно</h1><hr>";
        } elseif ( $update === 0) {
            echo "<h1 class='ok'>Запрос был выполнен корректно, но ни одна строка не была обработана</h1><hr>";
        } elseif ( $update === false ) {
            echo "<h1 class='error'>Запрос провалился или ошибка запроса.</h1><hr>";
        }
    }


    global $wpdb;
    $table_writing_games = TABLEWRITINGGAMES;
    $id = $_GET['id'];
    $data = $wpdb->get_row( "SELECT * FROM $table_writing_games WHERE `id` = $id", ARRAY_A );

    $game_name = $data['name'];
    $game_words = $data['words'];
    $game_time = $data['time'];
    $game_enabled = $data['enabled'];

?>
<h1>Обновить игру "<?php echo $game_name; ?>"</h1>
<form action="" method="POST">
    <input type="hidden" name="game_id" value="<?php echo $id; ?>">
    <p><input type="text" name="game_name" value="<?php echo $game_name; ?>" placeholder="название" required></p>
    <p><textarea name="game_words" rows="8" cols="40" placeholder="текст" required><?php echo $game_words; ?></textarea></p>
    <p>
        <input type="number" name="game_time" value="<?php echo $game_time; ?>" placeholder="время" required>

        <?php if ( $game_enabled == 1 ) { ?>
        <span>
            <label>
                <input type="checkbox" checked name="game_enabled" value="<?php echo $game_enabled; ?>">
                Enabled
            </label>
        </span>
        <? } else { ?>
        <span>
            <label>
                <input type="checkbox" name="game_enabled" value="<?php echo $game_enabled; ?>">
                Enabled
            </label>
        </span>
        <?php } ?>
    </p>
    <input type="submit" name="update_game" value="Обновить">
</form>

<?php
    // exit();
} // end writing_games_edit_game()





// ФУНКЦИИ ДЛЯ РАБОТЫ С БД

// добавляем игру в таблицу
function add_game() {
    global $wpdb;
    $data = $_POST;
    $game_name = $data['game_name'];
    $game_words = $data['game_words'];
    $game_time = $data['game_time'];
    $game_enabled = ( $data['game_enabled'] == '' ) ? 0 : 1;
    $insert_game = $wpdb->insert(
        TABLEWRITINGGAMES,
        [
            'name'    => $game_name,
            'words'   => $game_words,
            'time'    => $game_time,
            'enabled' => $game_enabled,
        ],
        [ '%s', '%s', '%d', '%d' ]
    );

    return $insert_game;
}

// удаление игры
function delete_game() {
    global $wpdb;
    $game_id = $_POST['delete_game'];
    $delete_game = $wpdb->delete(
        TABLEWRITINGGAMES,
        [ 'id' => $game_id ],
        [ '%d' ]
    );

    return $delete_game;
}


// Обновляем игру
function update_game() {
    global $wpdb;
    $data = $_POST;
    $game_id = $data['game_id'];
    $game_name = $data['game_name'];
    $game_words = $data['game_words'];
    $game_time = $data['game_time'];
    $game_enabled = ( $data['game_enabled'] == '' ) ? 0 : 1;
    $update = $wpdb->update(
        TABLEWRITINGGAMES,
        [
            'name' => $game_name,
            'words' => $game_words,
            'time' => $game_time,
            'enabled' => $game_enabled,
        ],

        [ 'id' => $game_id ],

        [ '%s', '%s', '%d', '%d' ],

        [ '%d' ]
    );
    return $update;

    /*
    if ( $update > 0 ) {
        $success = [ 'result' => 'success update game table' ];
        return json_encode( $success );
    } elseif ( $update === 0) {
        $no_update = [ 'result' => 'no update game table' ];
        return json_encode( $no_update );
    } elseif ( $update === false ) {
        $fail = [ 'failure' => 'error update game table' ];
        return json_encode( $fail );
    }
    */

}



// ВСЯ информация из двух таблиц
function get_all_info() {
    global $wpdb;
    $table_writing_games = TABLEWRITINGGAMES;
    $table_stat = TABLEGAMESTAT;
    $info = $wpdb->get_results( "SELECT * FROM $table_writing_games, $table_stat WHERE ( $table_writing_games.id = $table_stat.game_id ) ", ARRAY_A );
	return $info;
}

// информация об играх
function get_list_games() {
    global $wpdb;
    $table_writing_games = TABLEWRITINGGAMES;
    $list_games = $wpdb->get_results( "SELECT * FROM $table_writing_games", ARRAY_A );
	return $list_games;
}


// Проверяем наличие игры с id в БД
function is_game_db( $id ) {
    global $wpdb;
    $table_writing_games = TABLEWRITINGGAMES;
    $result = $wpdb->get_row( "SELECT `id` FROM $table_writing_games WHERE `id` = $id ", ARRAY_A );
    $res = ( is_array( $result ) ) ? true : false;
    return $res;
}

function get_current_url() {
    global $wp;
    $current_url = add_query_arg( $wp->query_string, '', home_url( $wp->request ) );
    return $current_url;
}

// Получить id для страниц формата site.com/writing-games/12/score/
function get_id_score( $url ) {
	$i = explode( 'score', $url );
	$i = explode( '/', $i[0] );
	$i = array_diff( $i, array('') );
	$id = array_pop( $i );
    return $id;
}

// Вставляем в таблицу СТАТИСТИКИ
// $data === array();
function insert_stat_table( $data ) {
    global $wpdb;
    extract( $data ); // извлекаем из массива данные и помещаем в переменные

    $insert = $wpdb->insert(
        TABLEGAMESTAT,
        [
            'game_id' => $game_id,
            'game_type' => $game_type,
            'user_name' => $user_name,
            'score' => $score,
            'score_desc' => $score_desc,
        ],
        ['%d', '%s', '%s', '%f', '%s']
    );

    if ( $insert ) {
        $success = [ 'result' => 'success' ];
        return json_encode( $success );
    } else {
        $fail = [ 'failure' => 'message' ];
        return json_encode( $fail );
    }

}


// Обновляем в таблице ИГР (метод PUT)
// $data === array();
// $wpdb->update возвращает:
// * число - сколько строк было обработано
// * 0 - запрос был выполнен корректно, но ни одна строка не была обработана.
// * false - запрос провалился или ошибка запроса.
function update_game_table() {
    parse_str(file_get_contents('php://input'), $data);

    global $wpdb;

    extract( $data ); // извлекаем из массива данные и помещаем в переменные

    $update = $wpdb->update(
        TABLEWRITINGGAMES,
        [
            'name' => $name,
            'words' => $words,
            'time' => $time,
            'enabled' => $enabled,
        ],

        [ 'id' => $game_id ],

        [ '%s', '%s', '%d', '%d' ],

        [ '%d' ]
    );
    // return $update;

    if ( $update > 0 ) {
        $success = [ 'result' => 'success update game table' ];
        return json_encode( $success );
    } elseif ( $update === 0) {
        $no_update = [ 'result' => 'no update game table' ];
        return json_encode( $no_update );
    } elseif ( $update === false ) {
        $fail = [ 'failure' => 'error update game table' ];
        return json_encode( $fail );
    }


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
    $info = $wpdb->get_results( "SELECT * FROM $table_stat WHERE `game_id` = $id ORDER BY `score` DESC", ARRAY_A );
    return json_encode( $info );
}
