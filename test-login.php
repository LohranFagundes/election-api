<?php
// test-login.php (criar na raiz do projeto)

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Testing Login Credentials...\n";
echo "============================\n\n";

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

    $dsn = "mysql:host=$host;port=$port;dbname=$database;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    echo "✓ Connected to database\n\n";

    // Test admin table
    echo "ADMIN ACCOUNTS:\n";
    echo "---------------\n";
    
    $stmt = $pdo->query("SELECT id, name, email, role, is_active FROM admins");
    $admins = $stmt->fetchAll();
    
    foreach ($admins as $admin) {
        $status = $admin['is_active'] ? 'Active' : 'Inactive';
        echo "ID: {$admin['id']} | Email: {$admin['email']} | Role: {$admin['role']} | Status: $status\n";
    }
    
    echo "\nVOTER ACCOUNTS:\n";
    echo "---------------\n";
    
    $stmt = $pdo->query("SELECT id, name, email, cpf, is_active, is_verified FROM voters LIMIT 5");
    $voters = $stmt->fetchAll();
    
    foreach ($voters as $voter) {
        $status = $voter['is_active'] ? 'Active' : 'Inactive';
        $verified = $voter['is_verified'] ? 'Verified' : 'Not Verified';
        echo "ID: {$voter['id']} | Email: {$voter['email']} | CPF: {$voter['cpf']} | Status: $status | $verified\n";
    }

    echo "\nTEST CREDENTIALS:\n";
    echo "-----------------\n";
    echo "For testing, use password: 'password' with any of the above emails\n";
    echo "\nExample Admin Login:\n";
    echo "Email: superadmin@election.com\n";
    echo "Password: password\n";
    echo "\nExample Voter Login:\n";
    echo "Email: joao.silva@email.com\n";
    echo "Password: password\n";

} catch (PDOException $e) {
    echo "✗ Database error: " . $e->getMessage() . "\n";
    exit(1);
}