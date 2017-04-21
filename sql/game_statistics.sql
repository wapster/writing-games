--
-- Структура таблицы `wp_game_statistics`
--

CREATE TABLE IF NOT EXISTS `wp_game_statistics` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY UNIQUE KEY,
  `game_id` int(11) NOT NULL,
  `game_type` varchar(255) NOT NULL,
  `user_name` varchar(255) NOT NULL,
  `score` float NOT NULL,
  `score_desc` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
