<?php
// database/simple-migrate.php (ALTERNATIVA SIMPLES)

echo "Simple Database Migration\n";
echo "=========================\n\n";

// Carregar .env
if (file_exists(__DIR__ . '/../.env')) {
    $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $_ENV[trim($name)] = trim($value);
        }
    }
}

$host = $_ENV['DB_HOST'] ?? 'localhost';
$port = $_ENV['DB_PORT'] ?? '3306';
$database = $_ENV['DB_DATABASE'] ?? 'election_system';
$username = $_ENV['DB_USERNAME'] ?? 'root';
$password = $_ENV['DB_PASSWORD'] ?? '';

try {
    // Conectar ao MySQL
    $pdo = new PDO("mysql:host=$host;port=$port;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    echo "✅ Connected to MySQL\n";
    
    // Criar banco
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$database` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✅ Database '$database' created/verified\n";
    
    // Usar o banco
    $pdo->exec("USE `$database`");
    echo "✅ Using database '$database'\n\n";
    
    // SQL direto para criar tabelas
    $tables = [
        'admins' => "
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ",
        
        'voters' => "
            CREATE TABLE IF NOT EXISTS `voters` (
                `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                `name` varchar(255) NOT NULL,
                `email` varchar(255) NOT NULL,
                `password` varchar(255) NOT NULL,
                `cpf` varchar(14) NOT NULL,
                `birth_date` date NOT NULL,
                `phone` varchar(20) DEFAULT NULL,
                `vote_weight` decimal(3,2) NOT NULL DEFAULT 1.00,
                `is_active` tinyint(1) NOT NULL DEFAULT 1,
                `is_verified` tinyint(1) NOT NULL DEFAULT 0,
                `verification_token` varchar(255) DEFAULT NULL,
                `last_login_at` timestamp NULL DEFAULT NULL,
                `last_login_ip` varchar(45) DEFAULT NULL,
                `email_verified_at` timestamp NULL DEFAULT NULL,
                `remember_token` varchar(100) DEFAULT NULL,
                `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `voters_email_unique` (`email`),
                UNIQUE KEY `voters_cpf_unique` (`cpf`),
                KEY `idx_voters_email` (`email`),
                KEY `idx_voters_cpf` (`cpf`),
                KEY `idx_voters_active` (`is_active`),
                KEY `idx_voters_verified` (`is_verified`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ",
        
        'elections' => "
            CREATE TABLE IF NOT EXISTS `elections` (
                `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                `title` varchar(255) NOT NULL,
                `description` text DEFAULT NULL,
                `election_type` enum('federal','state','municipal','internal') NOT NULL DEFAULT 'internal',
                `status` enum('draft','scheduled','active','completed','cancelled') NOT NULL DEFAULT 'draft',
                `start_date` datetime NOT NULL,
                `end_date` datetime NOT NULL,
                `timezone` varchar(50) NOT NULL DEFAULT 'UTC',
                `allow_blank_votes` tinyint(1) NOT NULL DEFAULT 1,
                `allow_null_votes` tinyint(1) NOT NULL DEFAULT 1,
                `require_justification` tinyint(1) NOT NULL DEFAULT 0,
                `max_votes_per_voter` int(11) NOT NULL DEFAULT 1,
                `voting_method` enum('single','multiple','ranked') NOT NULL DEFAULT 'single',
                `results_visibility` enum('private','public','after_end') NOT NULL DEFAULT 'after_end',
                `created_by` bigint(20) UNSIGNED NOT NULL,
                `updated_by` bigint(20) UNSIGNED DEFAULT NULL,
                `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_elections_status` (`status`),
                KEY `idx_elections_dates` (`start_date`, `end_date`),
                KEY `idx_elections_type` (`election_type`),
                KEY `fk_elections_created_by` (`created_by`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ",
        
        'positions' => "
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
                KEY `idx_positions_order` (`order_position`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ",
        
        'candidates' => "
            CREATE TABLE IF NOT EXISTS `candidates` (
                `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                `position_id` bigint(20) UNSIGNED NOT NULL,
                `name` varchar(255) NOT NULL,
                `nickname` varchar(100) DEFAULT NULL,
                `description` text DEFAULT NULL,
                `photo` longblob DEFAULT NULL,
                `photo_filename` varchar(255) DEFAULT NULL,
                `photo_mime_type` varchar(100) DEFAULT NULL,
                `number` varchar(10) DEFAULT NULL,
                `party` varchar(100) DEFAULT NULL,
                `coalition` varchar(255) DEFAULT NULL,
                `is_active` tinyint(1) NOT NULL DEFAULT 1,
                `order_position` int(11) NOT NULL DEFAULT 0,
                `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_candidates_position` (`position_id`),
                KEY `idx_candidates_active` (`is_active`),
                KEY `idx_candidates_number` (`number`),
                KEY `idx_candidates_order` (`order_position`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ",
        
        'votes' => "
            CREATE TABLE IF NOT EXISTS `votes` (
                `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                `election_id` bigint(20) UNSIGNED NOT NULL,
                `position_id` bigint(20) UNSIGNED NOT NULL,
                `voter_id` bigint(20) UNSIGNED NOT NULL,
                `candidate_hash` varchar(255) NOT NULL,
                `vote_hash` varchar(255) NOT NULL,
                `vote_weight` decimal(3,2) NOT NULL DEFAULT 1.00,
                `vote_type` enum('candidate','blank','null') NOT NULL DEFAULT 'candidate',
                `ip_address` varchar(45) NOT NULL,
                `user_agent` text DEFAULT NULL,
                `session_id` varchar(255) DEFAULT NULL,
                `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `unique_voter_position_election` (`voter_id`, `position_id`, `election_id`),
                KEY `idx_votes_election` (`election_id`),
                KEY `idx_votes_position` (`position_id`),
                KEY `idx_votes_voter` (`voter_id`),
                KEY `idx_votes_hash` (`vote_hash`),
                KEY `idx_votes_candidate_hash` (`candidate_hash`),
                KEY `idx_votes_type` (`vote_type`),
                KEY `idx_votes_created` (`created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ",
        
        'vote_sessions' => "
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
                `expires_at` timestamp NOT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `unique_session_token` (`session_token`),
                KEY `idx_vote_sessions_voter` (`voter_id`),
                KEY `idx_vote_sessions_election` (`election_id`),
                KEY `idx_vote_sessions_status` (`status`),
                KEY `idx_vote_sessions_completed` (`vote_completed`),
                KEY `idx_vote_sessions_expires` (`expires_at`),
                KEY `idx_vote_sessions_ip` (`ip_address`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ",
        
        'audit_logs' => "
            CREATE TABLE IF NOT EXISTS `audit_logs` (
                `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                `user_id` bigint(20) UNSIGNED DEFAULT NULL,
                `user_type` enum('admin','voter','system') NOT NULL DEFAULT 'system',
                `action` varchar(100) NOT NULL,
                `resource` varchar(100) NOT NULL,
                `resource_id` bigint(20) UNSIGNED DEFAULT NULL,
                `route` varchar(255) DEFAULT NULL,
                `method` varchar(10) DEFAULT NULL,
                `ip_address` varchar(45) NOT NULL,
                `user_agent` text DEFAULT NULL,
                `request_data` json DEFAULT NULL,
                `response_data` json DEFAULT NULL,
                `status_code` int(11) DEFAULT NULL,
                `execution_time` decimal(8,3) DEFAULT NULL,
                `memory_usage` bigint(20) DEFAULT NULL,
                `session_id` varchar(255) DEFAULT NULL,
                `correlation_id` varchar(255) DEFAULT NULL,
                `level` enum('emergency','alert','critical','error','warning','notice','info','debug') NOT NULL DEFAULT 'info',
                `message` text DEFAULT NULL,
                `context` json DEFAULT NULL,
                `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_audit_logs_user` (`user_id`, `user_type`),
                KEY `idx_audit_logs_action` (`action`),
                KEY `idx_audit_logs_resource` (`resource`, `resource_id`),
                KEY `idx_audit_logs_ip` (`ip_address`),
                KEY `idx_audit_logs_created` (`created_at`),
                KEY `idx_audit_logs_level` (`level`),
                KEY `idx_audit_logs_session` (`session_id`),
                KEY `idx_audit_logs_correlation` (`correlation_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        "
    ];
    
    foreach ($tables as $tableName => $sql) {
        echo "Creating table: $tableName\n";
        try {
            $pdo->exec($sql);
            echo "✅ Table '$tableName' created\n";
        } catch (Exception $e) {
            echo "❌ Failed to create '$tableName': " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n✅ All tables created successfully!\n";
    
    // Verificar tabelas criadas
    echo "\nVerifying tables:\n";
    $stmt = $pdo->query("SHOW TABLES");
    while ($table = $stmt->fetchColumn()) {
        echo "  ✅ $table\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}