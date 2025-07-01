<?php
// create-tables.php (criar na raiz do projeto)

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Creating Database Tables...\n";
echo "===========================\n\n";

// Load environment variables
if (file_exists(__DIR__ . '/.env')) {
    $lines = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $_ENV[trim($name)] = trim($value);
        }
    }
}

try {
    $host = $_ENV['DB_HOST'] ?? 'localhost';
    $port = $_ENV['DB_PORT'] ?? '3306';
    $database = $_ENV['DB_DATABASE'] ?? 'election_system';
    $username = $_ENV['DB_USERNAME'] ?? 'root';
    $password = $_ENV['DB_PASSWORD'] ?? '';

    echo "Connecting to database...\n";
    
    // Connect to MySQL server first
    $dsn = "mysql:host=$host;port=$port;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    // Create database if not exists
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$database` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✓ Database '$database' ready\n";

    // Connect to specific database
    $dsn = "mysql:host=$host;port=$port;dbname=$database;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    echo "✓ Connected to database '$database'\n\n";

    // Create Admins Table
    echo "Creating admins table...\n";
    $pdo->exec("
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
            UNIQUE KEY `admins_email_unique` (`email`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✓ Admins table created\n";

    // Create Voters Table
    echo "Creating voters table...\n";
    $pdo->exec("
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
            `is_verified` tinyint(1) NOT NULL DEFAULT 1,
            `verification_token` varchar(255) DEFAULT NULL,
            `last_login_at` timestamp NULL DEFAULT NULL,
            `last_login_ip` varchar(45) DEFAULT NULL,
            `email_verified_at` timestamp NULL DEFAULT NULL,
            `remember_token` varchar(100) DEFAULT NULL,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `voters_email_unique` (`email`),
            UNIQUE KEY `voters_cpf_unique` (`cpf`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✓ Voters table created\n";

    // Create Elections Table
    echo "Creating elections table...\n";
    $pdo->exec("
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
            `created_by` bigint(20) UNSIGNED NOT NULL DEFAULT 1,
            `updated_by` bigint(20) UNSIGNED DEFAULT NULL,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "✓ Elections table created\n";

    // Insert sample admin users
    echo "\nInserting sample data...\n";
    
    $hashedPassword = password_hash('password', PASSWORD_DEFAULT);
    
    // Insert admins (ignore duplicates)
    try {
        $pdo->exec("
            INSERT IGNORE INTO `admins` (`name`, `email`, `password`, `role`, `permissions`, `is_active`, `email_verified_at`) VALUES
            ('Super Administrator', 'superadmin@election.com', '$hashedPassword', 'super_admin', '[\"*\"]', 1, NOW()),
            ('Election Administrator', 'admin@election.com', '$hashedPassword', 'admin', '[\"elections.*\", \"positions.*\", \"candidates.*\", \"voters.read\", \"reports.*\"]', 1, NOW()),
            ('Election Moderator', 'moderator@election.com', '$hashedPassword', 'moderator', '[\"elections.read\", \"positions.read\", \"candidates.read\", \"voters.read\", \"reports.read\"]', 1, NOW())
        ");
        echo "✓ Sample admins inserted\n";
    } catch (PDOException $e) {
        echo "Note: Admin records may already exist\n";
    }

    // Insert voters (ignore duplicates)
    try {
        $pdo->exec("
            INSERT IGNORE INTO `voters` (`name`, `email`, `password`, `cpf`, `birth_date`, `phone`, `vote_weight`, `is_active`, `is_verified`, `email_verified_at`) VALUES
            ('João da Silva', 'joao.silva@email.com', '$hashedPassword', '12345678901', '1985-05-15', '(11) 99999-1234', 1.00, 1, 1, NOW()),
            ('Maria Santos', 'maria.santos@email.com', '$hashedPassword', '23456789012', '1990-08-22', '(11) 99999-5678', 1.00, 1, 1, NOW()),
            ('Pedro Oliveira', 'pedro.oliveira@email.com', '$hashedPassword', '34567890123', '1978-12-03', '(11) 99999-9012', 1.50, 1, 1, NOW())
        ");
        echo "✓ Sample voters inserted\n";
    } catch (PDOException $e) {
        echo "Note: Voter records may already exist\n";
    }

    echo "\n=== TABLES CREATED SUCCESSFULLY ===\n\n";

    // Show table status
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Tables in database:\n";
    foreach ($tables as $table) {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM `$table`");
        $count = $stmt->fetch()['count'];
        echo "  - $table: $count records\n";
    }

    echo "\n=== TEST CREDENTIALS ===\n";
    echo "Admin Login:\n";
    echo "  Email: admin@election.com\n";
    echo "  Password: password\n\n";
    echo "Voter Login:\n";
    echo "  Email: joao.silva@email.com\n";
    echo "  Password: password\n\n";
    echo "Now you can test the login endpoints!\n";

} catch (PDOException $e) {
    echo "✗ Database error: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}