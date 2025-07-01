<?php
// test-database.php (criar na raiz do projeto)

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Testing Database Connection...\n";
echo "==============================\n\n";

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

// Test database connection directly
try {
    $host = $_ENV['DB_HOST'] ?? 'localhost';
    $port = $_ENV['DB_PORT'] ?? '3306';
    $database = $_ENV['DB_DATABASE'] ?? 'election_system';
    $username = $_ENV['DB_USERNAME'] ?? 'root';
    $password = $_ENV['DB_PASSWORD'] ?? '';

    echo "Attempting to connect to:\n";
    echo "Host: $host\n";
    echo "Port: $port\n";
    echo "Database: $database\n";
    echo "Username: $username\n";
    echo "Password: " . (empty($password) ? 'empty' : '***hidden***') . "\n\n";

    $dsn = "mysql:host=$host;port=$port;charset=utf8mb4";
    
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
    
    echo "✓ Connection to MySQL server: SUCCESS\n";
    
    // Check if database exists
    $stmt = $pdo->prepare("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?");
    $stmt->execute([$database]);
    $dbExists = $stmt->fetch();
    
    if ($dbExists) {
        echo "✓ Database '$database' exists: SUCCESS\n";
        
        // Connect to specific database
        $dsn = "mysql:host=$host;port=$port;dbname=$database;charset=utf8mb4";
        $pdo = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]);
        
        echo "✓ Connection to database '$database': SUCCESS\n";
        
        // List tables
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        echo "\nTables in database:\n";
        if (empty($tables)) {
            echo "  (no tables found - run migrations)\n";
        } else {
            foreach ($tables as $table) {
                echo "  - $table\n";
            }
        }
        
    } else {
        echo "✗ Database '$database' does not exist\n";
        echo "Creating database...\n";
        
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$database` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        echo "✓ Database '$database' created successfully\n";
    }
    
} catch (PDOException $e) {
    echo "✗ Database connection failed: " . $e->getMessage() . "\n";
    echo "\nPossible solutions:\n";
    echo "1. Make sure MySQL/MariaDB is running\n";
    echo "2. Check your .env database credentials\n";
    echo "3. Verify the database exists\n";
    echo "4. Check if the user has proper permissions\n";
    exit(1);
}

echo "\n==============================\n";
echo "Database test completed!\n";