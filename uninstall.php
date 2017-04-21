<?php
if( ! defined('WP_UNINSTALL_PLUGIN') ) exit;


// удаляем таблицы из БД
global $wpdb;
$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'game_statistics' );
$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'writing_games' );


// не даем по настоящему удалить плагин
// die();
