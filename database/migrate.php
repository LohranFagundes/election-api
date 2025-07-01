<?php
// database/migrate.php (VERSÃO FINAL CORRIGIDA)

echo "Running Database Migrations\n";
echo "===========================\n\n";

// Carregar .env
if (file_exists(__DIR__ . '/../.env')) {
    $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $_ENV[trim($name)] = trim($value);
        }
    }
}

// Carregar configuração do banco
$config = require __DIR__ . '/../config/database.php';

try {
    // Conectar SEM especificar o banco primeiro
    $dsn = "mysql:host={$config['host']};port={$config['port']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['username'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
    
    echo "✅ MySQL connection established\n";
    
    // Criar banco se não existir
    $database = $config['database'];
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$database` CHARACTER SET {$config['charset']} COLLATE {$config['collation']}");
    echo "✅ Database '$database' ready\n";
    
    // Agora conectar ao banco específico
    $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['username'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
    
    echo "✅ Connected to database '$database'\n\n";
    
    // Executar migrações
    $migrationFiles = glob(__DIR__ . '/migrations/*.sql');
    sort($migrationFiles);
    
    if (empty($migrationFiles)) {
        echo "❌ No migration files found in database/migrations/\n";
        exit(1);
    }
    
    foreach ($migrationFiles as $file) {
        $filename = basename($file);
        echo "Running migration: $filename\n";
        
        try {
            $sql = file_get_contents($file);
            
            // Executar sem transação para evitar problemas
            $pdo->exec($sql);
            
            echo "✅ $filename completed\n";
            
        } catch (Exception $e) {
            echo "❌ $filename failed: " . $e->getMessage() . "\n";
            echo "Continuing with next migration...\n";
        }
    }
    
    echo "\n✅ Migrations process completed!\n";
    echo "Database tables:\n";
    
    // Listar tabelas criadas
    try {
        $stmt = $pdo->query("SHOW TABLES");
        while ($table = $stmt->fetchColumn()) {
            echo "  ✅ $table\n";
        }
    } catch (Exception $e) {
        echo "Could not list tables: " . $e->getMessage() . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
    echo "Please check your database configuration\n";
    exit(1);
}