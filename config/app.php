<?php
// 2. RESTAURAR config/app.php

return [
    'name' => $_ENV['APP_NAME'] ?? 'Election API',
    'env' => $_ENV['APP_ENV'] ?? 'production',
    'debug' => $_ENV['APP_DEBUG'] ?? false,
    'url' => $_ENV['APP_URL'] ?? 'http://localhost:8000',
    'timezone' => $_ENV['APP_TIMEZONE'] ?? 'UTC',
    
    'api' => [
        'version' => '1.0',
        'prefix' => 'api/v1',
        'rate_limit' => [
            'max_requests' => 100,
            'time_window' => 3600
        ]
    ],
    
    'security' => [
        'cors' => [
            'enabled' => true,
            'allowed_origins' => explode(',', $_ENV['CORS_ALLOWED_ORIGINS'] ?? '*'),
            'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
            'allowed_headers' => ['Content-Type', 'Authorization', 'X-Requested-With'],
            'max_age' => 86400
        ],
        'csrf' => [
            'enabled' => true,
            'token_name' => 'csrf_token',
            'header_name' => 'X-CSRF-Token'
        ],
        'encryption' => [
            'key' => $_ENV['APP_KEY'] ?? 'base64:' . base64_encode(random_bytes(32)),
            'cipher' => 'AES-256-CBC'
        ]
    ],
    
    'uploads' => [
        'path' => 'public/uploads/',
        'max_size' => 5242880,
        'allowed_types' => ['jpg', 'jpeg', 'png', 'gif'],
        'candidates' => [
            'path' => 'public/uploads/candidates/',
            'max_width' => 800,
            'max_height' => 600
        ]
    ],
    
    'logging' => [
        'enabled' => true,
        'level' => $_ENV['LOG_LEVEL'] ?? 'info',
        'files' => [
            'api' => 'logs/api.log',
            'auth' => 'logs/auth.log',
            'audit' => 'logs/audit.log',
            'error' => 'logs/error.log'
        ],
        'max_file_size' => 10485760,
        'max_files' => 10
    ],
    
    'session' => [
        'lifetime' => 120,
        'expire_on_close' => true,
        'encrypt' => true,
        'files' => 'storage/sessions',
        'connection' => null,
        'table' => 'sessions',
        'store' => null,
        'lottery' => [2, 100],
        'cookie' => 'election_session',
        'path' => '/',
        'domain' => $_ENV['SESSION_DOMAIN'] ?? null,
        'secure' => $_ENV['SESSION_SECURE_COOKIE'] ?? false,
        'http_only' => true,
        'same_site' => 'lax'
    ]
];