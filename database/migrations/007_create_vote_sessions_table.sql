CREATE TABLE IF NOT EXISTS `vote_sessions` (
    `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `voter_id` bigint(20) UNSIGNED NOT NULL,
    `election_id` bigint(20) UNSIGNED NOT NULL,
    `session_token` varchar(255) NOT NULL,
    `ip_address` varchar(45) NOT NULL,
    `user_agent` text DEFAULT NULL,
    `device_fingerprint` varchar(255) DEFAULT NULL,
    `status` tinyint(1) NOT NULL DEFAULT 1,
    `vote_completed` tinyint(1) NOT NULL DEFAULT 0,
    `vote_timestamp` timestamp NULL DEFAULT NULL,
    `started_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `completed_at` timestamp NULL DEFAULT NULL,
    `expires_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_session_token` (`session_token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;