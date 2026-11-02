-- Two-factor authentication (TOTP) and single-use recovery codes. Safe to run again.

CREATE TABLE IF NOT EXISTS `user_mfa` (
  `user_id` int(11) unsigned NOT NULL,
  `secret` varchar(255) NOT NULL,
  `last_step` bigint unsigned NOT NULL DEFAULT 0,
  `enabled_at` int unsigned NOT NULL,
  PRIMARY KEY (`user_id`),
  CONSTRAINT `fk_user_mfa_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `user_mfa_recovery_codes` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL,
  `code_hash` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `used_at` int unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uc_user_mfa_recovery_code` (`user_id`, `code_hash`),
  CONSTRAINT `fk_user_mfa_recovery_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
