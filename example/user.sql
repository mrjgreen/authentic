-- Table users
CREATE TABLE `user` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `firstname` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `lastname` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `last_login` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `auth_token` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `reset_password_token` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_user_email` (`email`)
) ENGINE=InnoDB CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Password is: password
INSERT INTO `user` (`id`, `firstname`, `lastname`, `email`, `password`)
VALUES
	(1, 'Admin', 'User', 'admin@example.com', '$2y$10$yM1cA2zDr2/lP2mpgAfHaO3OLed0pEkdDlLZn/.ZRA6cYZEqv.112');