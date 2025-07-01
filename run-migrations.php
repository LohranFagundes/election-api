<?php
// run-migrations.php (criar na raiz do projeto)

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Running Database Migrations...\n";
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

try {
    $host = $_ENV['DB_HOST'] ?? 'localhost';
    $port = $_ENV['DB_PORT'] ?? '3306';
    $database = $_ENV['DB_DATABASE'] ?? 'election_system';
    $username = $_ENV['DB_USERNAME'] ?? 'root';
    $password = $_ENV['DB_PASSWORD'] ?? '';

    // Connect to MySQL server
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

    // Migration files in order
    $migrationFiles = [
        'database/migrations/001_create_admins_table.sql',
        'database/migrations/002_create_voters_table.sql',
        'database/migrations/003_create_elections_table.sql',
        'database/migrations/004_create_positions_table.sql',
        'database/migrations/005_create_candidates_table.sql',
        'database/migrations/006_create_votes_table.sql',
        'database/migrations/007_create_vote_sessions_table.sql',
        'database/migrations/008_create_audit_logs_table.sql'
    ];

    foreach ($migrationFiles as $file) {
        if (!file_exists($file)) {
            echo "⚠ Migration file not found: $file\n";
            continue;
        }

        echo "Running: $file\n";
        
        $sql = file_get_contents($file);
        $statements = array_filter(array_map('trim', explode(';', $sql)));

        foreach ($statements as $statement) {
            if (!empty($statement) && !preg_match('/^--/', $statement)) {
                try {
                    $pdo->exec($statement);
                } catch (PDOException $e) {
                    if (strpos($e->getMessage(), 'already exists') === false) {
                        throw $e;
                    }
                }
            }
        }
        
        echo "✓ Completed: $file\n";
    }

    echo "\n";
    echo "Running seeders...\n";
    
    // Run seed files
    $seedFiles = [
        'database/seeds/admin_seeder.sql',
        'database/seeds/sample_data.sql'
    ];

    foreach ($seedFiles as $file) {
        if (!file_exists($file)) {
            echo "⚠ Seed file not found: $file\n";
            continue;
        }

        echo "Running: $file\n";
        
        $sql = file_get_contents($file);
        $statements = array_filter(array_map('trim', explode(';', $sql)));

        foreach ($statements as $statement) {
            if (!empty($statement) && !preg_match('/^--/', $statement)) {
                try {
                    $pdo->exec($statement);
                } catch (PDOException $e) {
                    // Ignore duplicate entry errors for seeds
                    if (strpos($e->getMessage(), 'Duplicate entry') === false) {
                        echo "Warning: " . $e->getMessage() . "\n";
                    }
                }
            }
        }
        
        echo "✓ Completed: $file\n";
    }

    echo "\n";
    echo "Checking tables...\n";
    
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($tables as $table) {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM `$table`");
        $count = $stmt->fetch()['count'];
        echo "✓ Table '$table': $count records\n";
    }

    echo "\n==============================\n";
    echo "Migrations completed successfully!\n";
    echo "You can now test the login endpoints.\n";

} catch (PDOException $e) {
    echo "✗ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}