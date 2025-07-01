<?php
// src/middleware/CorsMiddleware.php

class CorsMiddleware
{
    private $config;

    public function __construct()
    {
        $this->config = require __DIR__ . '/../../config/app.php';
    }

    public function handle($request, $next)
    {
        if (!$this->config['security']['cors']['enabled']) {
            return $next($request);
        }

        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        $allowedOrigins = $this->config['security']['cors']['allowed_origins'];
        
        if (in_array('*', $allowedOrigins) || in_array($origin, $allowedOrigins)) {
            header('Access-Control-Allow-Origin: ' . ($origin ?: '*'));
        }

        header('Access-Control-Allow-Methods: ' . implode(', ', $this->config['security']['cors']['allowed_methods']));
        header('Access-Control-Allow-Headers: ' . implode(', ', $this->config['security']['cors']['allowed_headers']));
        header('Access-Control-Max-Age: ' . $this->config['security']['cors']['max_age']);
        header('Access-Control-Allow-Credentials: true');

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }

        return $next($request);
    }

    public function setCorsHeaders($origin = null)
    {
        if (!$this->config['security']['cors']['enabled']) {
            return;
        }

        $allowedOrigins = $this->config['security']['cors']['allowed_origins'];
        
        if ($origin && (in_array('*', $allowedOrigins) || in_array($origin, $allowedOrigins))) {
            header('Access-Control-Allow-Origin: ' . $origin);
        } elseif (in_array('*', $allowedOrigins)) {
            header('Access-Control-Allow-Origin: *');
        }

        header('Access-Control-Allow-Methods: ' . implode(', ', $this->config['security']['cors']['allowed_methods']));
        header('Access-Control-Allow-Headers: ' . implode(', ', $this->config['security']['cors']['allowed_headers']));
        header('Access-Control-Allow-Credentials: true');
    }
}