-- Records account and group administration. Safe to run again.

CREATE TABLE IF NOT EXISTS `admin_audit` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `actor_user_id` int unsigned NOT NULL,
  `action` varchar(40) NOT NULL,
  `target_type` varchar(20) NOT NULL,
  `target_id` int unsigned NOT NULL,
  `details` text NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_admin_audit_actor` (`actor_user_id`),
  KEY `idx_admin_audit_target` (`target_type`, `target_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
