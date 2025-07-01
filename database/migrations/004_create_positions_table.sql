CREATE TABLE IF NOT EXISTS `positions` (
    `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `election_id` bigint(20) UNSIGNED NOT NULL,
    `title` varchar(255) NOT NULL,
    `description` text DEFAULT NULL,
    `order_position` int(11) NOT NULL DEFAULT 0,
    `max_candidates` int(11) NOT NULL DEFAULT 10,
    `min_votes` int(11) NOT NULL DEFAULT 0,
    `max_votes` int(11) NOT NULL DEFAULT 1,
    `is_active` tinyint(1) NOT NULL DEFAULT 1,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_positions_election` (`election_id`),
    KEY `idx_positions_active` (`is_active`),
    KEY `idx_positions_order` (`order_position`),
    CONSTRAINT `fk_positions_election` FOREIGN KEY (`election_id`) REFERENCES `elections` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
