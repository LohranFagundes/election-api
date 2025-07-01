<?php
// src/utils/helpers.php

if (!function_exists('env')) {
    function env($key, $default = null) {
        return $_ENV[$key] ?? $default;
    }
}

if (!function_exists('config')) {
    function config($key, $default = null) {
        static $configs = [];
        
        $parts = explode('.', $key);
        $file = array_shift($parts);
        
        if (!isset($configs[$file])) {
            $configFile = __DIR__ . "/../../config/{$file}.php";
            if (file_exists($configFile)) {
                $configs[$file] = require $configFile;
            } else {
                return $default;
            }
        }
        
        $value = $configs[$file];
        foreach ($parts as $part) {
            if (isset($value[$part])) {
                $value = $value[$part];
            } else {
                return $default;
            }
        }
        
        return $value;
    }
}

if (!function_exists('base_path')) {
    function base_path($path = '') {
        return __DIR__ . '/../../' . ltrim($path, '/');
    }
}

if (!function_exists('storage_path')) {
    function storage_path($path = '') {
        return base_path('storage/' . ltrim($path, '/'));
    }
}

if (!function_exists('public_path')) {
    function public_path($path = '') {
        return base_path('public/' . ltrim($path, '/'));
    }
}

if (!function_exists('dd')) {
    function dd(...$args) {
        foreach ($args as $arg) {
            var_dump($arg);
        }
        die();
    }
}

if (!function_exists('response')) {
    function response() {
        return new Response();
    }
}

if (!function_exists('request')) {
    function request() {
        return new Request();
    }
}

if (!function_exists('now')) {
    function now() {
        return date('Y-m-d H:i:s');
    }
}

if (!function_exists('today')) {
    function today() {
        return date('Y-m-d');
    }
}

if (!function_exists('array_get')) {
    function array_get($array, $key, $default = null) {
        if (is_null($key)) {
            return $array;
        }
        
        if (isset($array[$key])) {
            return $array[$key];
        }
        
        foreach (explode('.', $key) as $segment) {
            if (is_array($array) && array_key_exists($segment, $array)) {
                $array = $array[$segment];
            } else {
                return $default;
            }
        }
        
        return $array;
    }
}

if (!function_exists('str_random')) {
    function str_random($length = 16) {
        return bin2hex(random_bytes($length / 2));
    }
}

if (!function_exists('bcrypt')) {
    function bcrypt($value, $options = []) {
        return password_hash($value, PASSWORD_DEFAULT, $options);
    }
}

if (!function_exists('verify_password')) {
    function verify_password($value, $hashedValue) {
        return password_verify($value, $hashedValue);
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['_token'])) {
            $_SESSION['_token'] = str_random(40);
        }
        
        return $_SESSION['_token'];
    }
}

if (!function_exists('old')) {
    function old($key, $default = null) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        return $_SESSION['_old_input'][$key] ?? $default;
    }
}

if (!function_exists('flash')) {
    function flash($key, $value = null) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if ($value === null) {
            return $_SESSION['_flash'][$key] ?? null;
        }
        
        $_SESSION['_flash'][$key] = $value;
    }
}

if (!function_exists('abort')) {
    function abort($code, $message = '') {
        http_response_code($code);
        echo $message;
        exit;
    }
}

if (!function_exists('redirect')) {
    function redirect($url, $code = 302) {
        http_response_code($code);
        header("Location: $url");
        exit;
    }
}

if (!function_exists('asset')) {
    function asset($path) {
        $baseUrl = env('APP_URL', 'http://localhost:8000');
        return rtrim($baseUrl, '/') . '/' . ltrim($path, '/');
    }
}

if (!function_exists('url')) {
    function url($path = '') {
        $baseUrl = env('APP_URL', 'http://localhost:8000');
        return rtrim($baseUrl, '/') . '/' . ltrim($path, '/');
    }
}

if (!function_exists('route')) {
    function route($name, $params = []) {
        // Simple route helper - can be expanded
        return url($name);
    }
}

if (!function_exists('validate_cpf')) {
    function validate_cpf($cpf) {
        $cpf = preg_replace('/[^0-9]/', '', $cpf);
        
        if (strlen($cpf) != 11) {
            return false;
        }
        
        if (preg_match('/(\d)\1{10}/', $cpf)) {
            return false;
        }
        
        for ($t = 9; $t < 11; $t++) {
            for ($d = 0, $c = 0; $c < $t; $c++) {
                $d += $cpf[$c] * (($t + 1) - $c);
            }
            $d = ((10 * $d) % 11) % 10;
            if ($cpf[$c] != $d) {
                return false;
            }
        }
        
        return true;
    }
}

if (!function_exists('format_cpf')) {
    function format_cpf($cpf) {
        $cpf = preg_replace('/[^0-9]/', '', $cpf);
        if (strlen($cpf) === 11) {
            return substr($cpf, 0, 3) . '.' . substr($cpf, 3, 3) . '.' . substr($cpf, 6, 3) . '-' . substr($cpf, 9, 2);
        }
        return $cpf;
    }
}

if (!function_exists('mask_email')) {
    function mask_email($email) {
        $parts = explode('@', $email);
        if (count($parts) !== 2) {
            return $email;
        }
        
        $username = $parts[0];
        $domain = $parts[1];
        
        if (strlen($username) <= 2) {
            return $email;
        }
        
        $maskedUsername = substr($username, 0, 2) . str_repeat('*', strlen($username) - 2);
        return $maskedUsername . '@' . $domain;
    }
}

if (!function_exists('format_bytes')) {
    function format_bytes($size, $precision = 2) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
            $size /= 1024;
        }
        
        return round($size, $precision) . ' ' . $units[$i];
    }
}

if (!function_exists('now_brasilia')) {
    function now_brasilia($format = 'Y-m-d H:i:s') {
        return DateHelper::nowBrasilia($format);
    }
}

if (!function_exists('format_brasilia')) {
    function format_brasilia($date, $format = 'Y-m-d H:i:s') {
        return DateHelper::formatBrasilia($date, $format);
    }
}

if (!function_exists('to_utc')) {
    function to_utc($date) {
        return DateHelper::toUTC($date);
    }
}

if (!function_exists('from_utc')) {
    function from_utc($date) {
        return DateHelper::fromUTC($date);
    }
}

if (!function_exists('brasilia_offset')) {
    function brasilia_offset() {
        return DateHelper::getBrasiliaOffset();
    }
}
