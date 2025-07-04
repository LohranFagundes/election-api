CREATE TABLE IF NOT EXISTS `admins` (
    `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL,
    `email` varchar(255) NOT NULL,
    `password` varchar(255) NOT NULL,
    `role` enum('super_admin','admin','moderator') NOT NULL DEFAULT 'admin',
    `permissions` json DEFAULT NULL,
    `is_active` tinyint(1) NOT NULL DEFAULT 1,
    `last_login_at` timestamp NULL DEFAULT NULL,
    `last_login_ip` varchar(45) DEFAULT NULL,
    `email_verified_at` timestamp NULL DEFAULT NULL,
    `remember_token` varchar(100) DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `admins_email_unique` (`email`),
    KEY `idx_admins_email` (`email`),
    KEY `idx_admins_role` (`role`),
    KEY `idx_admins_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;