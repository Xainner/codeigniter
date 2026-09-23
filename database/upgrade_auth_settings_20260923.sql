-- Apply after the existing Ion Auth schema. Safe to run again.
-- An existing installation must never expose the first-admin setup page.

CREATE TABLE IF NOT EXISTS `app_settings` (
  `name` varchar(64) NOT NULL,
  `value` text NOT NULL,
  PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `settings_audit` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `actor_user_id` int unsigned NOT NULL,
  `changed_keys` text NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_settings_audit_actor` (`actor_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `request_limits` (
  `key_hash` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `hits` int unsigned NOT NULL,
  `expires_at` int unsigned NOT NULL,
  PRIMARY KEY (`key_hash`),
  KEY `idx_request_limits_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `app_settings` (`name`, `value`)
SELECT 'setup_completed', IF(EXISTS(SELECT 1 FROM `users`), '1', '0')
ON DUPLICATE KEY UPDATE `value` = IF(`value` = '1' OR EXISTS(SELECT 1 FROM `users`), '1', '0');
