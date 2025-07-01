<?php
// src/middleware/AuthMiddleware.php

require_once __DIR__ . '/../services/TokenService.php';
require_once __DIR__ . '/../services/AuditService.php';
require_once __DIR__ . '/../utils/Response.php';

class AuthMiddleware
{
    private $tokenService;
    private $auditService;

    public function __construct()
    {
        $this->tokenService = new TokenService();
        $this->auditService = new AuditService();
    }

    public function handle($request, $next)
    {
        try {
            $token = $this->extractToken();
            
            if (!$token) {
                $this->auditService->log(null, 'system', 'unauthorized_access', 'auth', null, 'Missing token');
                return Response::error(['message' => 'Authorization token required'], 401);
            }

            $payload = $this->tokenService->validateToken($token);
            
            if (!$payload) {
                $this->auditService->log(null, 'system', 'invalid_token', 'auth', null, 'Invalid token');
                return Response::error(['message' => 'Invalid or expired token'], 401);
            }

            if ($this->tokenService->isBlacklisted($token)) {
                $this->auditService->log($payload['user_id'], $payload['role'], 'blacklisted_token', 'auth', null);
                return Response::error(['message' => 'Token has been revoked'], 401);
            }

            $_SESSION['user_id'] = $payload['user_id'];
            $_SESSION['role'] = $payload['role'];
            $_SESSION['token'] = $token;
            $_SESSION['permissions'] = $payload['permissions'] ?? [];

            return $next($request);

        } catch (Exception $e) {
            $this->auditService->log(null, 'system', 'auth_error', 'auth', null, $e->getMessage());
            return Response::error(['message' => 'Authentication failed'], 500);
        }
    }

    private function extractToken()
    {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? '';
        
        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return $matches[1];
        }
        
        return $_GET['token'] ?? $_POST['token'] ?? null;
    }
}