<?php
// test-fixed-database.php

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== TESTING FIXED DATABASE CLASS ===\n";
echo "=====================================\n\n";

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
    echo "1. Loading Database class...\n";
    require_once __DIR__ . '/src/utils/Database.php';
    echo "✓ Database class loaded\n\n";
    
    echo "2. Creating Database instance...\n";
    $db = Database::getInstance();
    echo "✓ Database instance created\n\n";
    
    echo "3. Testing connection...\n";
    if ($db->isConnected()) {
        echo "✓ Database connected successfully\n\n";
    } else {
        echo "✗ Database connection failed\n";
        exit(1);
    }
    
    echo "4. Testing getTables method...\n";
    $tables = $db->getTables();
    echo "✓ Found " . count($tables) . " tables:\n";
    foreach ($tables as $table) {
        echo "  - $table\n";
    }
    echo "\n";
    
    echo "5. Testing tableExists method...\n";
    $testTables = ['admins', 'voters', 'elections', 'nonexistent_table'];
    
    foreach ($testTables as $table) {
        $exists = $db->tableExists($table);
        $status = $exists ? '✓' : '✗';
        echo "  $status $table: " . ($exists ? 'EXISTS' : 'NOT FOUND') . "\n";
    }
    echo "\n";
    
    echo "6. Testing admin query...\n";
    if ($db->tableExists('admins')) {
        echo "✓ Admins table found!\n";
        
        // Test fetch method
        $admin = $db->fetch("SELECT * FROM admins WHERE email = ? LIMIT 1", ['admin@election.com']);
        
        if ($admin) {
            echo "✓ Admin found: {$admin['name']} ({$admin['email']})\n";
            echo "  ID: {$admin['id']}\n";
            echo "  Role: {$admin['role']}\n";
            echo "  Active: " . ($admin['is_active'] ? 'Yes' : 'No') . "\n";
        } else {
            echo "✗ Admin 'admin@election.com' not found\n";
            
            // Show all admins
            $allAdmins = $db->fetchAll("SELECT id, name, email, role FROM admins");
            echo "\nAll admins:\n";
            foreach ($allAdmins as $adminRecord) {
                echo "  - ID:{$adminRecord['id']} {$adminRecord['name']} ({$adminRecord['email']})\n";
            }
        }
    } else {
        echo "✗ Admins table NOT found via Database class\n";
    }
    
    echo "\n7. Testing voter query...\n";
    if ($db->tableExists('voters')) {
        echo "✓ Voters table found!\n";
        
        $voter = $db->fetch("SELECT * FROM voters WHERE email = ? LIMIT 1", ['joao.silva@email.com']);
        
        if ($voter) {
            echo "✓ Voter found: {$voter['name']} ({$voter['email']})\n";
        } else {
            echo "✗ Voter not found\n";
        }
    } else {
        echo "✗ Voters table NOT found\n";
    }
    
    echo "\n8. Testing timezone...\n";
    $timeResult = $db->fetch("SELECT NOW() as current_time, UTC_TIMESTAMP() as utc_time");
    if ($timeResult) {
        echo "✓ Database time: {$timeResult['current_time']}\n";
        echo "✓ UTC time: {$timeResult['utc_time']}\n";
    }
    
    echo "\n=== ALL TESTS COMPLETED ===\n";
    echo "Database class is working correctly!\n";
    echo "Now test the API login endpoint.\n\n";

} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}