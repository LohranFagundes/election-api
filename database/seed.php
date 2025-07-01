<?php
// database/seed.php (VERSÃO CORRIGIDA)

echo "Running Database Seeds\n";
echo "======================\n\n";

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
    // Conectar ao banco
    $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['username'], $config['password'], $config['options']);
    
    echo "✅ Database connection established\n\n";
    
    // Verificar se tabelas existem
    $requiredTables = ['admins', 'voters', 'elections', 'positions', 'candidates'];
    foreach ($requiredTables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() == 0) {
            echo "❌ Table '$table' not found. Please run migrations first.\n";
            exit(1);
        }
    }
    
    echo "✅ All required tables found\n\n";
    
    // Executar seeds
    $seedFiles = glob(__DIR__ . '/seeds/*.sql');
    sort($seedFiles);
    
    if (empty($seedFiles)) {
        echo "❌ No seed files found in database/seeds/\n";
        exit(1);
    }
    
    foreach ($seedFiles as $file) {
        $filename = basename($file);
        echo "Running seed: $filename\n";
        
        try {
            $sql = file_get_contents($file);
            $statements = array_filter(array_map('trim', explode(';', $sql)));
            
            foreach ($statements as $statement) {
                if (!empty($statement) && !preg_match('/^--/', $statement)) {
                    $pdo->exec($statement);
                }
            }
            
            echo "✅ $filename completed\n";
            
        } catch (Exception $e) {
            echo "⚠️  $filename warning: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n✅ All seeds completed!\n\n";
    echo "Default credentials created:\n";
    echo "============================\n";
    echo "Super Admin:\n";
    echo "  Email: superadmin@election.com\n";
    echo "  Password: password123\n\n";
    echo "Admin:\n";
    echo "  Email: admin@election.com\n";
    echo "  Password: password123\n\n";
    echo "Sample Voter:\n";
    echo "  Email: joao.silva@email.com\n";
    echo "  Password: password123\n\n";
    
    // Mostrar estatísticas
    $stats = [
        'admins' => $pdo->query("SELECT COUNT(*) FROM admins")->fetchColumn(),
        'voters' => $pdo->query("SELECT COUNT(*) FROM voters")->fetchColumn(),
        'elections' => $pdo->query("SELECT COUNT(*) FROM elections")->fetchColumn(),
        'positions' => $pdo->query("SELECT COUNT(*) FROM positions")->fetchColumn(),
        'candidates' => $pdo->query("SELECT COUNT(*) FROM candidates")->fetchColumn()
    ];
    
    echo "Database Statistics:\n";
    foreach ($stats as $table => $count) {
        echo "  $table: $count records\n";
    }
    
} catch (Exception $e) {
    echo "❌ Seeding failed: " . $e->getMessage() . "\n";
    echo "Please make sure migrations were run first\n";
    exit(1);
}