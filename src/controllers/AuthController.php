<?php
// src/controllers/AuthController.php

require_once __DIR__ . '/../services/AuthService.php';
require_once __DIR__ . '/../services/TokenService.php';
require_once __DIR__ . '/../services/ValidationService.php';
require_once __DIR__ . '/../services/AuditService.php';
require_once __DIR__ . '/../utils/Response.php';

class AuthController
{
    private $authService;
    private $tokenService;
    private $validationService;
    private $auditService;

    public function __construct()
    {
        $this->authService = new AuthService();
        $this->tokenService = new TokenService();
        $this->validationService = new ValidationService();
        $this->auditService = new AuditService();
    }

    public function adminLogin()
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            $validation = $this->validationService->validateLogin($data);
            if (!$validation['valid']) {
                return Response::error($validation['errors'], 422);
            }

            $admin = $this->authService->validateAdminCredentials($data['email'], $data['password']);
            if (!$admin) {
                $this->auditService->log('admin', 'login_failed', 'auth', null, $data['email']);
                return Response::error(['message' => 'Invalid credentials'], 401);
            }

            $token = $this->tokenService->generateToken($admin['id'], 'admin');
            
            $this->authService->updateLastLogin($admin['id'], 'admin');
            $this->auditService->log($admin['id'], 'admin', 'login_success', 'auth', $admin['id']);

            return Response::success([
                'token' => $token,
                'user' => [
                    'id' => $admin['id'],
                    'name' => $admin['name'],
                    'email' => $admin['email'],
                    'role' => $admin['role'],
                    'permissions' => json_decode($admin['permissions'], true)
                ],
                'expires_in' => 3540
            ]);

        } catch (Exception $e) {
            $this->auditService->log(null, 'system', 'login_error', 'auth', null, $e->getMessage());
            return Response::error(['message' => 'Login failed'], 500);
        }
    }

    public function voterLogin()
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            $validation = $this->validationService->validateLogin($data);
            if (!$validation['valid']) {
                return Response::error($validation['errors'], 422);
            }

            $voter = $this->authService->validateVoterCredentials($data['email'], $data['password']);
            if (!$voter) {
                $this->auditService->log('voter', 'login_failed', 'auth', null, $data['email']);
                return Response::error(['message' => 'Invalid credentials'], 401);
            }

            if (!$voter['is_active'] || !$voter['is_verified']) {
                return Response::error(['message' => 'Account not active or verified'], 403);
            }

            $token = $this->tokenService->generateToken($voter['id'], 'voter');
            
            $this->authService->updateLastLogin($voter['id'], 'voter');
            $this->auditService->log($voter['id'], 'voter', 'login_success', 'auth', $voter['id']);

            return Response::success([
                'token' => $token,
                'user' => [
                    'id' => $voter['id'],
                    'name' => $voter['name'],
                    'email' => $voter['email'],
                    'cpf' => $voter['cpf'],
                    'vote_weight' => $voter['vote_weight']
                ],
                'expires_in' => 300
            ]);

        } catch (Exception $e) {
            $this->auditService->log(null, 'system', 'login_error', 'auth', null, $e->getMessage());
            return Response::error(['message' => 'Login failed'], 500);
        }
    }

    public function logout()
    {
        try {
            $token = $this->getTokenFromHeader();
            if ($token) {
                $this->tokenService->blacklistToken($token);
            }

            $payload = $this->tokenService->validateToken($token);
            if ($payload) {
                $this->auditService->log($payload['user_id'], $payload['role'], 'logout', 'auth', $payload['user_id']);
            }

            return Response::success(['message' => 'Logged out successfully']);

        } catch (Exception $e) {
            return Response::error(['message' => 'Logout failed'], 500);
        }
    }

    public function refreshToken()
    {
        try {
            $token = $this->getTokenFromHeader();
            $payload = $this->tokenService->validateToken($token);
            
            if (!$payload) {
                return Response::error(['message' => 'Invalid token'], 401);
            }

            $newToken = $this->tokenService->refreshToken($token);
            
            $this->auditService->log($payload['user_id'], $payload['role'], 'token_refresh', 'auth', $payload['user_id']);

            return Response::success([
                'token' => $newToken,
                'expires_in' => $payload['role'] === 'admin' ? 3540 : 300
            ]);

        } catch (Exception $e) {
            return Response::error(['message' => 'Token refresh failed'], 500);
        }
    }

    public function validateToken()
    {
        try {
            $token = $this->getTokenFromHeader();
            $payload = $this->tokenService->validateToken($token);
            
            if (!$payload) {
                return Response::error(['message' => 'Invalid token'], 401);
            }

            return Response::success([
                'valid' => true,
                'user_id' => $payload['user_id'],
                'role' => $payload['role'],
                'expires_at' => $payload['exp']
            ]);

        } catch (Exception $e) {
            return Response::error(['message' => 'Token validation failed'], 500);
        }
    }

    private function getTokenFromHeader()
    {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? '';
        
        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return $matches[1];
        }
        
        return null;
    }
}