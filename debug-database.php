<?php
// debug-database.php (criar na raiz do projeto)

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== DATABASE DEBUG ===\n";
echo "======================\n\n";

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

echo "1. Environment Variables:\n";
echo "DB_HOST: " . ($_ENV['DB_HOST'] ?? 'not set') . "\n";
echo "DB_PORT: " . ($_ENV['DB_PORT'] ?? 'not set') . "\n";
echo "DB_DATABASE: " . ($_ENV['DB_DATABASE'] ?? 'not set') . "\n";
echo "DB_USERNAME: " . ($_ENV['DB_USERNAME'] ?? 'not set') . "\n";
echo "DB_PASSWORD: " . (isset($_ENV['DB_PASSWORD']) ? '***set***' : 'not set') . "\n\n";

try {
    $host = $_ENV['DB_HOST'] ?? 'localhost';
    $port = $_ENV['DB_PORT'] ?? '3306';
    $database = $_ENV['DB_DATABASE'] ?? 'election_system';
    $username = $_ENV['DB_USERNAME'] ?? 'root';
    $password = $_ENV['DB_PASSWORD'] ?? '';

    echo "2. Testing MySQL Server Connection:\n";
    $dsn = "mysql:host=$host;port=$port;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    echo "✓ Connected to MySQL server\n\n";

    echo "3. Checking if database exists:\n";
    $stmt = $pdo->prepare("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?");
    $stmt->execute([$database]);
    $dbExists = $stmt->fetch();
    
    if ($dbExists) {
        echo "✓ Database '$database' exists\n\n";
    } else {
        echo "✗ Database '$database' does NOT exist\n";
        echo "Creating database...\n";
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$database` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        echo "✓ Database '$database' created\n\n";
    }

    echo "4. Connecting to specific database:\n";
    $dsn = "mysql:host=$host;port=$port;dbname=$database;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    echo "✓ Connected to database '$database'\n\n";

    echo "5. Checking tables:\n";
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($tables)) {
        echo "✗ No tables found in database\n\n";
        echo "6. Creating tables now...\n";
        createTables($pdo);
    } else {
        echo "✓ Found tables:\n";
        foreach ($tables as $table) {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM `$table`");
            $count = $stmt->fetch()['count'];
            echo "  - $table: $count records\n";
        }
    }

    echo "\n7. Testing Database class:\n";
    require_once __DIR__ . '/src/utils/Database.php';
    $db = Database::getInstance();
    
    if ($db->isConnected()) {
        echo "✓ Database class connection: OK\n";
        
        if ($db->tableExists('admins')) {
            echo "✓ Admins table exists via Database class\n";
        } else {
            echo "✗ Admins table NOT found via Database class\n";
        }
    } else {
        echo "✗ Database class connection: FAILED\n";
    }

    echo "\n=== DEBUG COMPLETE ===\n";

} catch (PDOException $e) {
    echo "✗ Database error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

function createTables($pdo) {
    try {
        // Create admins table
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

        // Create voters table
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

        // Insert sample data
        $hashedPassword = password_hash('password', PASSWORD_DEFAULT);
        $hashedLohran = password_hash('lohran', PASSWORD_DEFAULT);
        
        $pdo->exec("
            INSERT IGNORE INTO `admins` (`name`, `email`, `password`, `role`, `permissions`, `is_active`, `email_verified_at`) VALUES
            ('Lohran Admin', 'lohran@election.com', '$hashedLohran', 'super_admin', '[\"*\"]', 1, NOW()),
            ('Super Administrator', 'superadmin@election.com', '$hashedPassword', 'super_admin', '[\"*\"]', 1, NOW()),
            ('Election Administrator', 'admin@election.com', '$hashedPassword', 'admin', '[\"elections.*\"]', 1, NOW())
        ");
        echo "✓ Sample admins inserted (including lohran@election.com)\n";

        $pdo->exec("
            INSERT IGNORE INTO `voters` (`name`, `email`, `password`, `cpf`, `birth_date`, `phone`, `vote_weight`, `is_active`, `is_verified`, `email_verified_at`) VALUES
            ('João da Silva', 'joao.silva@email.com', '$hashedPassword', '12345678901', '1985-05-15', '(11) 99999-1234', 1.00, 1, 1, NOW()),
            ('Maria Santos', 'maria.santos@email.com', '$hashedPassword', '23456789012', '1990-08-22', '(11) 99999-5678', 1.00, 1, 1, NOW())
        ");
        echo "✓ Sample voters inserted\n";

        echo "\n✓ All tables and data created successfully!\n";
        echo "\nTest credentials:\n";
        echo "Admin: lohran@election.com / lohran\n";
        echo "Admin: admin@election.com / password\n";
        echo "Voter: joao.silva@email.com / password\n";

    } catch (PDOException $e) {
        echo "✗ Error creating tables: " . $e->getMessage() . "\n";
    }
}