<?php
// 10. Criar set-timezone.php (script para configurar timezone)

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Setting Timezone to Brasília...\n";
echo "===============================\n\n";

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

// Set PHP timezone
date_default_timezone_set('America/Sao_Paulo');

echo "✓ PHP timezone set to: " . date_default_timezone_get() . "\n";
echo "✓ Current time: " . date('Y-m-d H:i:s T') . "\n";
echo "✓ UTC Offset: " . date('P') . "\n\n";

try {
    // Database connection
    $host = $_ENV['DB_HOST'] ?? 'localhost';
    $port = $_ENV['DB_PORT'] ?? '3306';
    $database = $_ENV['DB_DATABASE'] ?? 'election_system';
    $username = $_ENV['DB_USERNAME'] ?? 'root';
    $password = $_ENV['DB_PASSWORD'] ?? '';

    $dsn = "mysql:host=$host;port=$port;dbname=$database;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    // Set MySQL timezone
    $pdo->exec("SET time_zone = '-03:00'");
    echo "✓ MySQL timezone set to -03:00\n";

    // Test database time
    $stmt = $pdo->query("SELECT NOW() as db_time, UTC_TIMESTAMP() as utc_time");
    $times = $stmt->fetch();
    
    echo "✓ Database time: " . $times['db_time'] . "\n";
    echo "✓ Database UTC time: " . $times['utc_time'] . "\n\n";

    echo "=== TIMEZONE CONFIGURATION COMPLETE ===\n";
    echo "All new records will use Brasília timezone (-03:00)\n";

} catch (PDOException $e) {
    echo "✗ Database error: " . $e->getMessage() . "\n";
}