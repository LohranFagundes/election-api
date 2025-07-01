<?php
# run.php

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Election API Server\n";
echo "==================\n\n";

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

$host = $_ENV['SERVER_HOST'] ?? 'localhost';
$port = $_ENV['SERVER_PORT'] ?? '8000';
$docRoot = __DIR__ . '/public';

if (!is_dir($docRoot)) {
    echo "Error: Public directory not found at: $docRoot\n";
    exit(1);
}

echo "Starting server...\n";
echo "Host: $host\n";
echo "Port: $port\n";
echo "Document Root: $docRoot\n";
echo "Environment: " . ($_ENV['APP_ENV'] ?? 'development') . "\n";
echo "Debug Mode: " . ($_ENV['APP_DEBUG'] ?? 'true') . "\n\n";

try {
    require_once __DIR__ . '/src/utils/Database.php';
    $db = Database::getInstance();
    
    if ($db->isConnected()) {
        echo "✓ Database connection: OK\n";
    } else {
        echo "✗ Database connection: FAILED\n";
        echo "Warning: Database not connected. Some features may not work.\n";
    }
} catch (Exception $e) {
    echo "✗ Database connection: ERROR - " . $e->getMessage() . "\n";
    echo "Warning: Database error. Please check your configuration.\n";
}

if (!is_writable(__DIR__ . '/logs')) {
    echo "Warning: Logs directory is not writable\n";
}

if (!is_writable(__DIR__ . '/public/uploads')) {
    echo "Warning: Uploads directory is not writable\n";
}

$requiredExtensions = ['pdo', 'pdo_mysql', 'json', 'openssl', 'mbstring', 'gd', 'fileinfo'];
$missingExtensions = [];

foreach ($requiredExtensions as $ext) {
    if (!extension_loaded($ext)) {
        $missingExtensions[] = $ext;
    }
}

if (!empty($missingExtensions)) {
    echo "✗ Missing PHP extensions: " . implode(', ', $missingExtensions) . "\n";
    echo "Please install the required extensions before running the server.\n";
    exit(1);
} else {
    echo "✓ PHP extensions: OK\n";
}

echo "\n";
echo "Server starting at: http://$host:$port\n";
echo "API Documentation: http://$host:$port/api/v1\n";
echo "Health Check: http://$host:$port/health\n";
echo "\n";
echo "Press Ctrl+C to stop the server\n";
echo "=================================\n\n";

$command = "php -S $host:$port -t \"$docRoot\"";

if (PHP_OS_FAMILY === 'Windows') {
    $descriptorspec = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w']
    ];
    
    $process = proc_open($command, $descriptorspec, $pipes);
    
    if (is_resource($process)) {
        while (!feof($pipes[1])) {
            echo fgets($pipes[1]);
        }
        
        fclose($pipes[0]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        
        proc_close($process);
    }
} else {
    passthru($command);
}