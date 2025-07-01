INSERT INTO `admins` (`name`, `email`, `password`, `role`, `permissions`, `is_active`, `email_verified_at`) VALUES
('Super Administrator', 'superadmin@election.com', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'super_admin', '["*"]', 1, NOW()),
('Election Administrator', 'admin@election.com', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', '["elections.*", "positions.*", "candidates.*", "voters.read", "reports.*"]', 1, NOW()),
('Election Moderator', 'moderator@election.com', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'moderator', '["elections.read", "positions.read", "candidates.read", "voters.read", "reports.read"]', 1, NOW());
