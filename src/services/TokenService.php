<?php
// src/services/TokenService.php

require_once __DIR__ . '/../utils/JWT.php';

class TokenService
{
    private $jwtConfig;
    private $blacklistedTokens = [];

    public function __construct()
    {
        $this->jwtConfig = require __DIR__ . '/../../config/jwt.php';
    }

    public function generateToken($userId, $role, $permissions = [])
    {
        $now = time();
        $expiry = $role === 'admin' ? 
            $this->jwtConfig['tokens']['admin']['expiry'] : 
            $this->jwtConfig['tokens']['voter']['expiry'];

        $payload = [
            'iss' => $this->jwtConfig['issuer'],
            'aud' => $this->jwtConfig['audience'],
            'iat' => $now,
            'nbf' => $now,
            'exp' => $now + $expiry,
            'jti' => uniqid(),
            'user_id' => $userId,
            'role' => $role,
            'permissions' => $permissions
        ];

        return JWT::encode($payload, $this->jwtConfig['secret'], $this->jwtConfig['algorithm']);
    }

    public function validateToken($token)
    {
        try {
            if ($this->isBlacklisted($token)) {
                return false;
            }

            $payload = JWT::decode($token, $this->jwtConfig['secret'], [$this->jwtConfig['algorithm']]);
            
            if ($payload->exp < time()) {
                return false;
            }

            return (array) $payload;
        } catch (Exception $e) {
            return false;
        }
    }

    public function refreshToken($token)
    {
        $payload = $this->validateToken($token);
        
        if (!$payload) {
            throw new Exception('Invalid token');
        }

        $this->blacklistToken($token);

        return $this->generateToken(
            $payload['user_id'], 
            $payload['role'], 
            $payload['permissions'] ?? []
        );
    }

    public function blacklistToken($token)
    {
        $this->blacklistedTokens[] = $token;
        
        $cacheFile = __DIR__ . '/../../storage/blacklisted_tokens.json';
        $blacklist = [];
        
        if (file_exists($cacheFile)) {
            $blacklist = json_decode(file_get_contents($cacheFile), true) ?? [];
        }
        
        $blacklist[] = [
            'token' => hash('sha256', $token),
            'blacklisted_at' => time()
        ];
        
        $blacklist = array_filter($blacklist, function($item) {
            return ($item['blacklisted_at'] + 86400) > time();
        });
        
        file_put_contents($cacheFile, json_encode($blacklist));
    }

    public function isBlacklisted($token)
    {
        if (in_array($token, $this->blacklistedTokens)) {
            return true;
        }
        
        $cacheFile = __DIR__ . '/../../storage/blacklisted_tokens.json';
        
        if (!file_exists($cacheFile)) {
            return false;
        }
        
        $blacklist = json_decode(file_get_contents($cacheFile), true) ?? [];
        $tokenHash = hash('sha256', $token);
        
        foreach ($blacklist as $item) {
            if ($item['token'] === $tokenHash) {
                return true;
            }
        }
        
        return false;
    }

    public function getTokenPayload($token)
    {
        return $this->validateToken($token);
    }

    public function isTokenExpired($token)
    {
        try {
            $payload = JWT::decode($token, $this->jwtConfig['secret'], [$this->jwtConfig['algorithm']]);
            return $payload->exp < time();
        } catch (Exception $e) {
            return true;
        }
    }

    public function getTokenExpiration($token)
    {
        try {
            $payload = JWT::decode($token, $this->jwtConfig['secret'], [$this->jwtConfig['algorithm']]);
            return $payload->exp;
        } catch (Exception $e) {
            return null;
        }
    }

    public function cleanupBlacklist()
    {
        $cacheFile = __DIR__ . '/../../storage/blacklisted_tokens.json';
        
        if (!file_exists($cacheFile)) {
            return;
        }
        
        $blacklist = json_decode(file_get_contents($cacheFile), true) ?? [];
        
        $blacklist = array_filter($blacklist, function($item) {
            return ($item['blacklisted_at'] + 86400) > time();
        });
        
        file_put_contents($cacheFile, json_encode(array_values($blacklist)));
    }
}