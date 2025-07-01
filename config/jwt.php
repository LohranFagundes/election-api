<?php
// config/jwt.php

return [
    'secret' => $_ENV['JWT_SECRET'] ?? 'your-super-secret-jwt-key-change-this-in-production',
    'algorithm' => 'HS256',
    
    'tokens' => [
        'admin' => [
            'expiry' => 3540,
            'refresh_expiry' => 86400
        ],
        'voter' => [
            'expiry' => 300,
            'refresh_expiry' => 1800
        ]
    ],
    
    'issuer' => $_ENV['JWT_ISSUER'] ?? 'election-api',
    'audience' => $_ENV['JWT_AUDIENCE'] ?? 'election-system',
    
    'blacklist' => [
        'enabled' => true,
        'grace_period' => 0
    ],
    
    'required_claims' => [
        'iss',
        'iat',
        'exp',
        'nbf',
        'sub',
        'jti'
    ],
    
    'persistent_claims' => [
        'user_id',
        'role',
        'permissions'
    ],
    
    'lock_subject' => true,
    'leeway' => 0,
    'blacklist_grace_period' => 0
];