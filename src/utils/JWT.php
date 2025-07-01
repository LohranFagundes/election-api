<?php
// src/utils/JWT.php

class JWT
{
    public static function encode($payload, $key, $algorithm = 'HS256')
    {
        $header = json_encode(['typ' => 'JWT', 'alg' => $algorithm]);
        $payload = json_encode($payload);
        
        $base64Header = self::base64UrlEncode($header);
        $base64Payload = self::base64UrlEncode($payload);
        
        $signature = hash_hmac('sha256', $base64Header . '.' . $base64Payload, $key, true);
        $base64Signature = self::base64UrlEncode($signature);
        
        return $base64Header . '.' . $base64Payload . '.' . $base64Signature;
    }
    
    public static function decode($jwt, $key, $algorithms = ['HS256'])
    {
        $parts = explode('.', $jwt);
        
        if (count($parts) !== 3) {
            throw new Exception('Invalid JWT format');
        }
        
        list($base64Header, $base64Payload, $base64Signature) = $parts;
        
        $header = json_decode(self::base64UrlDecode($base64Header), true);
        $payload = json_decode(self::base64UrlDecode($base64Payload), true);
        
        if (!$header || !$payload) {
            throw new Exception('Invalid JWT encoding');
        }
        
        if (!in_array($header['alg'], $algorithms)) {
            throw new Exception('Algorithm not allowed');
        }
        
        $signature = self::base64UrlDecode($base64Signature);
        $expectedSignature = hash_hmac('sha256', $base64Header . '.' . $base64Payload, $key, true);
        
        if (!hash_equals($signature, $expectedSignature)) {
            throw new Exception('Invalid signature');
        }
        
        if (isset($payload['exp']) && time() >= $payload['exp']) {
            throw new Exception('Token has expired');
        }
        
        if (isset($payload['nbf']) && time() < $payload['nbf']) {
            throw new Exception('Token not yet valid');
        }
        
        return (object) $payload;
    }
    
    public static function verify($jwt, $key, $algorithm = 'HS256')
    {
        try {
            self::decode($jwt, $key, [$algorithm]);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    public static function getPayload($jwt)
    {
        $parts = explode('.', $jwt);
        
        if (count($parts) !== 3) {
            throw new Exception('Invalid JWT format');
        }
        
        return json_decode(self::base64UrlDecode($parts[1]), true);
    }
    
    public static function getHeader($jwt)
    {
        $parts = explode('.', $jwt);
        
        if (count($parts) !== 3) {
            throw new Exception('Invalid JWT format');
        }
        
        return json_decode(self::base64UrlDecode($parts[0]), true);
    }
    
    public static function isExpired($jwt)
    {
        try {
            $payload = self::getPayload($jwt);
            return isset($payload['exp']) && time() >= $payload['exp'];
        } catch (Exception $e) {
            return true;
        }
    }
    
    public static function getTimeUntilExpiry($jwt)
    {
        try {
            $payload = self::getPayload($jwt);
            if (isset($payload['exp'])) {
                return max(0, $payload['exp'] - time());
            }
            return 0;
        } catch (Exception $e) {
            return 0;
        }
    }
    
    private static function base64UrlEncode($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
    
    private static function base64UrlDecode($data)
    {
        return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
    }
    
    public static function refresh($jwt, $key, $newExpiry = 3600)
    {
        $payload = self::getPayload($jwt);
        $payload['exp'] = time() + $newExpiry;
        $payload['iat'] = time();
        
        return self::encode($payload, $key);
    }
    
    public static function createToken($userId, $role, $expiry = 3600, $key = null)
    {
        $key = $key ?: $_ENV['JWT_SECRET'] ?? 'default-secret-key';
        
        $payload = [
            'iss' => $_ENV['JWT_ISSUER'] ?? 'election-api',
            'aud' => $_ENV['JWT_AUDIENCE'] ?? 'election-system',
            'iat' => time(),
            'nbf' => time(),
            'exp' => time() + $expiry,
            'jti' => uniqid(),
            'user_id' => $userId,
            'role' => $role
        ];
        
        return self::encode($payload, $key);
    }
}