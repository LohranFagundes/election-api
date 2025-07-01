<?php
// src/routes/auth.php

require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../middleware/CorsMiddleware.php';

$router->group(['prefix' => 'auth'], function($router) {
    
    $router->post('/admin/login', function() {
        $controller = new AuthController();
        return $controller->adminLogin();
    });

    $router->post('/voter/login', function() {
        $controller = new AuthController();
        return $controller->voterLogin();
    });

    $router->post('/logout', function() {
        $controller = new AuthController();
        return $controller->logout();
    }, ['middleware' => [AuthMiddleware::class]]);

    $router->post('/refresh', function() {
        $controller = new AuthController();
        return $controller->refreshToken();
    }, ['middleware' => [AuthMiddleware::class]]);

    $router->get('/validate', function() {
        $controller = new AuthController();
        return $controller->validateToken();
    }, ['middleware' => [AuthMiddleware::class]]);

    $router->get('/me', function() {
        $userId = $_SESSION['user_id'];
        $role = $_SESSION['role'];
        
        if ($role === 'admin') {
            $adminModel = new Admin();
            $user = $adminModel->findById($userId);
            unset($user['password']);
            $user['permissions'] = json_decode($user['permissions'], true);
        } else {
            $voterModel = new Voter();
            $user = $voterModel->findById($userId);
            unset($user['password']);
        }
        
        return Response::success($user);
    }, ['middleware' => [AuthMiddleware::class]]);

    $router->post('/change-password', function() {
        $data = json_decode(file_get_contents('php://input'), true);
        $userId = $_SESSION['user_id'];
        $role = $_SESSION['role'];
        
        if (!isset($data['current_password']) || !isset($data['new_password'])) {
            return Response::error(['message' => 'Current and new passwords required'], 422);
        }
        
        if ($role === 'admin') {
            $model = new Admin();
        } else {
            $model = new Voter();
        }
        
        $user = $model->findById($userId);
        if (!password_verify($data['current_password'], $user['password'])) {
            return Response::error(['message' => 'Current password is incorrect'], 401);
        }
        
        $model->update($userId, ['password' => password_hash($data['new_password'], PASSWORD_DEFAULT)]);
        
        return Response::success(['message' => 'Password changed successfully']);
    }, ['middleware' => [AuthMiddleware::class]]);
});