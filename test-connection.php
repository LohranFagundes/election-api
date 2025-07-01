<?php
// test-connection.php (VERSÃO CORRIGIDA)

echo "Testing Election API Setup\n";
echo "==========================\n\n";

// Carregar .env
if (file_exists('.env')) {
    $lines = file('.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $_ENV[trim($name)] = trim($value);
        }
    }
}

// Testar extensões PHP
echo "PHP Version: " . PHP_VERSION . "\n\n";

$extensions = ['pdo', 'pdo_mysql', 'json', 'openssl', 'mbstring', 'gd', 'fileinfo'];
echo "PHP Extensions:\n";
foreach ($extensions as $ext) {
    $status = extension_loaded($ext) ? "✅" : "❌";
    echo "$status Extension $ext\n";
}

echo "\n";

// Testar conexão com banco
echo "Database Connection Test:\n";
try {
    $host = $_ENV['DB_HOST'] ?? 'localhost';
    $port = $_ENV['DB_PORT'] ?? '3306';
    $database = $_ENV['DB_DATABASE'] ?? 'election_system';
    $username = $_ENV['DB_USERNAME'] ?? 'root';
    $password = $_ENV['DB_PASSWORD'] ?? '';
    
    echo "Trying to connect to: $username@$host:$port\n";
    echo "Database: $database\n";
    echo "Password: " . (empty($password) ? "(empty)" : "(set)") . "\n\n";
    
    $dsn = "mysql:host=$host;port=$port;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    echo "✅ MySQL connection: OK\n";
    
    // Verificar se banco existe
    $stmt = $pdo->query("SHOW DATABASES LIKE '$database'");
    if ($stmt->rowCount() > 0) {
        echo "✅ Database '$database': EXISTS\n";
    } else {
        echo "❌ Database '$database': NOT FOUND\n";
        echo "Creating database...\n";
        $pdo->exec("CREATE DATABASE `$database` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        echo "✅ Database '$database': CREATED\n";
    }
    
} catch (PDOException $e) {
    echo "❌ Database connection: FAILED\n";
    echo "Error: " . $e->getMessage() . "\n\n";
    echo "Troubleshooting:\n";
    echo "1. Verifique se MySQL está rodando no XAMPP\n";
    echo "2. Confirme usuário/senha no .env\n";
    echo "3. Para XAMPP padrão, use senha vazia\n";
    echo "4. Tente acessar phpMyAdmin: http://localhost/phpmyadmin\n\n";
}

// Testar permissões de diretório
echo "Directory Permissions:\n";
$dirs = ['logs', 'storage', 'public/uploads', 'public/uploads/candidates'];
foreach ($dirs as $dir) {
    if (!is_dir($dir)) {
        if (mkdir($dir, 0755, true)) {
            echo "✅ Created and writable: $dir\n";
        } else {
            echo "❌ Failed to create: $dir\n";
        }
    } else {
        $writable = is_writable($dir);
        echo ($writable ? "✅" : "❌") . " $dir " . ($writable ? "(writable)" : "(not writable)") . "\n";
    }
}

echo "\n";

// Verificar configuração atual do .env
echo "Current .env Configuration:\n";
$envVars = ['DB_HOST', 'DB_PORT', 'DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD'];
foreach ($envVars as $var) {
    $value = $_ENV[$var] ?? 'NOT SET';
    if ($var === 'DB_PASSWORD') {
        $value = empty($value) ? '(empty)' : '(hidden)';
    }
    echo "$var = $value\n";
}

echo "\n";

// Verificar se arquivos importantes existem
echo "Important Files Check:\n";
$files = [
    '.env' => file_exists('.env'),
    'src/utils/Database.php' => file_exists('src/utils/Database.php'),
    'config/database.php' => file_exists('config/database.php'),
    'database/migrations/' => is_dir('database/migrations'),
    'database/seeds/' => is_dir('database/seeds')
];

foreach ($files as $file => $exists) {
    echo ($exists ? "✅" : "❌") . " $file\n";
}

echo "\n";

// Verificar se pode criar arquivo de teste
echo "Write Permission Test:\n";
$testFile = 'logs/test_write.txt';
if (file_put_contents($testFile, 'test')) {
    echo "✅ Can write to logs directory\n";
    unlink($testFile);
} else {
    echo "❌ Cannot write to logs directory\n";
}

echo "\n";
echo "Next Steps:\n";
echo "===========\n";

$allGood = true;

// Verificar extensões críticas
$criticalExtensions = ['pdo', 'pdo_mysql', 'gd'];
foreach ($criticalExtensions as $ext) {
    if (!extension_loaded($ext)) {
        echo "❌ Missing critical extension: $ext\n";
        $allGood = false;
    }
}

// Verificar se consegue conectar no banco
try {
    $host = $_ENV['DB_HOST'] ?? 'localhost';
    $port = $_ENV['DB_PORT'] ?? '3306';
    $username = $_ENV['DB_USERNAME'] ?? 'root';
    $password = $_ENV['DB_PASSWORD'] ?? '';
    
    $dsn = "mysql:host=$host;port=$port";
    $pdo = new PDO($dsn, $username, $password);
} catch (PDOException $e) {
    echo "❌ Cannot connect to database\n";
    $allGood = false;
}

if ($allGood) {
    echo "✅ All checks passed! You can now run:\n";
    echo "   php database/migrate.php\n";
    echo "   php database/seed.php\n";
    echo "   php run.php\n";
} else {
    echo "\n❌ Some issues need to be fixed first:\n";
    echo "1. Install missing PHP extensions\n";
    echo "2. Fix database connection\n";
    echo "3. Set proper directory permissions\n";
}

echo "\nFor XAMPP users:\n";
echo "- Make sure Apache and MySQL are started\n";
echo "- Check php.ini: C:\\xampp\\php\\php.ini\n";
echo "- Uncomment: extension=gd\n";
echo "- Restart Apache after changing php.ini\n";
?>