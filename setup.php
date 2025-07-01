<?php

echo "Election API Setup\n";
echo "==================\n\n";

// Verificar se o PHP atende aos requisitos
if (version_compare(PHP_VERSION, '8.0.0', '<')) {
    echo "❌ PHP 8.0 ou superior é necessário. Versão atual: " . PHP_VERSION . "\n";
    exit(1);
}

echo "✅ PHP Version: " . PHP_VERSION . "\n";

// Verificar extensões PHP necessárias
$requiredExtensions = [
    'pdo',
    'pdo_mysql', 
    'json',
    'openssl',
    'mbstring',
    'gd',
    'fileinfo',
    'session',
    'hash'
];

$missingExtensions = [];

foreach ($requiredExtensions as $ext) {
    if (extension_loaded($ext)) {
        echo "✅ Extension $ext: OK\n";
    } else {
        echo "❌ Extension $ext: MISSING\n";
        $missingExtensions[] = $ext;
    }
}

if (!empty($missingExtensions)) {
    echo "\n❌ Missing extensions: " . implode(', ', $missingExtensions) . "\n";
    echo "Please install the missing PHP extensions before continuing.\n";
    exit(1);
}

// Criar diretórios necessários
echo "\nCreating directories...\n";

$directories = [
    'logs',
    'storage',
    'storage/sessions',
    'public/uploads',
    'public/uploads/candidates',
    'tests/coverage'
];

foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        if (mkdir($dir, 0755, true)) {
            echo "✅ Created directory: $dir\n";
        } else {
            echo "❌ Failed to create directory: $dir\n";
        }
    } else {
        echo "✅ Directory exists: $dir\n";
    }
}

// Copiar .env.example para .env se não existir
if (!file_exists('.env') && file_exists('.env.example')) {
    if (copy('.env.example', '.env')) {
        echo "✅ Created .env file from .env.example\n";
    } else {
        echo "❌ Failed to create .env file\n";
    }
} else {
    echo "✅ .env file exists\n";
}

// Verificar permissões de escrita
$writableDirectories = ['logs', 'storage', 'public/uploads'];

foreach ($writableDirectories as $dir) {
    if (is_writable($dir)) {
        echo "✅ Directory writable: $dir\n";
    } else {
        echo "❌ Directory not writable: $dir\n";
        echo "   Please set permissions: chmod 755 $dir\n";
    }
}

// Gerar chave de aplicação se não existir
if (file_exists('.env')) {
    $envContent = file_get_contents('.env');
    if (strpos($envContent, 'APP_KEY=base64:YOUR_ENCRYPTION_KEY_HERE') !== false) {
        $newKey = 'base64:' . base64_encode(random_bytes(32));
        $envContent = str_replace('APP_KEY=base64:YOUR_ENCRYPTION_KEY_HERE', 'APP_KEY=' . $newKey, $envContent);
        file_put_contents('.env', $envContent);
        echo "✅ Generated APP_KEY\n";
    }
    
    if (strpos($envContent, 'JWT_SECRET=your-super-secret-jwt-key-change-this-in-production') !== false) {
        $newJwtSecret = bin2hex(random_bytes(32));
        $envContent = str_replace('JWT_SECRET=your-super-secret-jwt-key-change-this-in-production', 'JWT_SECRET=' . $newJwtSecret, $envContent);
        file_put_contents('.env', $envContent);
        echo "✅ Generated JWT_SECRET\n";
    }
}

// Verificar se o Composer está instalado
echo "\nChecking Composer...\n";

exec('composer --version 2>&1', $output, $returnCode);

if ($returnCode === 0) {
    echo "✅ Composer is installed\n";
    echo "Running composer install...\n";
    
    exec('composer install 2>&1', $composerOutput, $composerReturn);
    
    if ($composerReturn === 0) {
        echo "✅ Composer packages installed\n";
    } else {
        echo "⚠️  Composer install failed, but manual autoloader will be used\n";
    }
} else {
    echo "⚠️  Composer not found, using manual autoloader\n";
    echo "For production, install Composer from: https://getcomposer.org/\n";
}

echo "\nSetup completed!\n";
echo "================\n";
echo "Next steps:\n";
echo "1. Configure your .env file with database credentials\n";
echo "2. Create your MySQL database\n";
echo "3. Run: php database/migrate.php\n";
echo "4. Run: php database/seed.php\n";
echo "5. Start server: php run.php\n";
echo "\nOr use the quick start: php -S localhost:8000 -t public/\n";
