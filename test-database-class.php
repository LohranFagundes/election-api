<?php
// test-database-class.php (criar na raiz do projeto)

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== TESTING DATABASE CLASS ===\n";
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

try {
    // Test Database class
    require_once __DIR__ . '/src/utils/Database.php';
    
    echo "1. Creating Database instance...\n";
    $db = Database::getInstance();
    echo "✓ Database instance created\n\n";
    
    echo "2. Testing connection...\n";
    if ($db->isConnected()) {
        echo "✓ Database connected\n\n";
    } else {
        echo "✗ Database NOT connected\n";
        exit(1);
    }
    
    echo "3. Getting all tables...\n";
    $tables = $db->getTables();
    echo "✓ Found " . count($tables) . " tables:\n";
    foreach ($tables as $table) {
        echo "  - $table\n";
    }
    echo "\n";
    
    echo "4. Testing tableExists method...\n";
    $testTables = ['admins', 'voters', 'elections', 'nonexistent_table'];
    
    foreach ($testTables as $table) {
        $exists = $db->tableExists($table);
        $status = $exists ? '✓' : '✗';
        echo "  $status Table '$table': " . ($exists ? 'EXISTS' : 'NOT FOUND') . "\n";
    }
    echo "\n";
    
    echo "5. Testing admin login query...\n";
    if ($db->tableExists('admins')) {
        $admin = $db->fetch("SELECT * FROM admins WHERE email = ? AND is_active = 1 LIMIT 1", ['lohran@election.com']);
        
        if ($admin) {
            echo "✓ Found admin: {$admin['name']} ({$admin['email']})\n";
            echo "  Role: {$admin['role']}\n";
            echo "  Active: " . ($admin['is_active'] ? 'Yes' : 'No') . "\n";
            
            // Test password
            if (password_verify('lohran', $admin['password'])) {
                echo "✓ Password verification: SUCCESS\n";
            } else {
                echo "✗ Password verification: FAILED\n";
            }
        } else {
            echo "✗ Admin 'lohran@election.com' not found\n";
            
            // Show all admins
            $allAdmins = $db->fetchAll("SELECT name, email, role, is_active FROM admins");
            echo "\nAll admins in database:\n";
            foreach ($allAdmins as $adminRecord) {
                echo "  - {$adminRecord['name']} ({$adminRecord['email']}) - {$adminRecord['role']}\n";
            }
        }
    } else {
        echo "✗ Admins table not found\n";
    }
    
    echo "\n6. Testing voter login query...\n";
    if ($db->tableExists('voters')) {
        $voter = $db->fetch("SELECT * FROM voters WHERE email = ? AND is_active = 1 LIMIT 1", ['joao.silva@email.com']);
        
        if ($voter) {
            echo "✓ Found voter: {$voter['name']} ({$voter['email']})\n";
            echo "  CPF: {$voter['cpf']}\n";
            echo "  Active: " . ($voter['is_active'] ? 'Yes' : 'No') . "\n";
            echo "  Verified: " . ($voter['is_verified'] ? 'Yes' : 'No') . "\n";
        } else {
            echo "✗ Voter 'joao.silva@email.com' not found\n";
        }
    } else {
        echo "✗ Voters table not found\n";
    }
    
    echo "\n=== DATABASE CLASS TEST COMPLETE ===\n";
    echo "Now test the API login endpoint!\n\n";

} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}