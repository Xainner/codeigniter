-- REST API keys stored as HMACs. Safe to run again.

CREATE TABLE IF NOT EXISTS `api_keys` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL,
  `name` varchar(80) NOT NULL,
  `token_hash` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `token_hint` char(4) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `created_at` int unsigned NOT NULL,
  `last_used_at` int unsigned DEFAULT NULL,
  `revoked_at` int unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uc_api_keys_token_hash` (`token_hash`),
  KEY `idx_api_keys_user` (`user_id`),
  CONSTRAINT `fk_api_keys_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
