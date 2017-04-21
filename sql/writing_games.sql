--
-- Структура таблицы `wp_writing_games`
--

CREATE TABLE IF NOT EXISTS `wp_writing_games` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY UNIQUE KEY,
  `name` varchar(255) NOT NULL,
  `words` text NOT NULL,
  `time` int(11) NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
