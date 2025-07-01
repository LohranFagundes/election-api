<?php
// src/routes/api.php

$router->group(['prefix' => 'api/v1'], function($router) {
    
    $router->get('/', function() {
        return Response::success([
            'message' => 'Election API v1.0',
            'version' => '1.0.0',
            'timestamp' => date('Y-m-d H:i:s'),
            'endpoints' => [
                'auth' => '/api/v1/auth',
                'admin' => '/api/v1/admin',
                'voter' => '/api/v1/voter',
                'elections' => '/api/v1/elections',
                'positions' => '/api/v1/positions',
                'candidates' => '/api/v1/candidates',
                'votes' => '/api/v1/votes',
                'reports' => '/api/v1/reports'
            ]
        ]);
    });

    $router->get('/status', function() {
        try {
            $db = Database::getInstance();
            $dbStatus = $db->isConnected() ? 'connected' : 'disconnected';
        } catch (Exception $e) {
            $dbStatus = 'error: ' . $e->getMessage();
        }
        
        return Response::success([
            'status' => 'healthy',
            'database' => $dbStatus,
            'timestamp' => date('Y-m-d H:i:s'),
            'memory_usage' => memory_get_usage(true),
            'peak_memory' => memory_get_peak_usage(true)
        ]);
    });

    // Auth routes
    $router->group(['prefix' => 'auth'], function($router) {
        require_once __DIR__ . '/../controllers/AuthController.php';
        
        $router->post('/admin/login', function() {
            $controller = new AuthController();
            return $controller->adminLogin();
        });

        $router->post('/voter/login', function() {
            $controller = new AuthController();
            return $controller->voterLogin();
        });
    });

    // Test route
    $router->get('/test', function() {
        return Response::success([
            'message' => 'API is working!',
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    });
});
