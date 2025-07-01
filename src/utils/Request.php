<?php
// src/utils/Request.php

class Request
{
    private $data;
    private $files;
    private $headers;
    
    public function __construct()
    {
        $this->data = $this->parseInput();
        $this->files = $_FILES;
        $this->headers = $this->parseHeaders();
    }
    
    public function all()
    {
        return array_merge($this->data, $_GET);
    }
    
    public function get($key, $default = null)
    {
        return $this->data[$key] ?? $_GET[$key] ?? $default;
    }
    
    public function post($key, $default = null)
    {
        return $this->data[$key] ?? $default;
    }
    
    public function query($key, $default = null)
    {
        return $_GET[$key] ?? $default;
    }
    
    public function file($key)
    {
        return $this->files[$key] ?? null;
    }
    
    public function header($key, $default = null)
    {
        $key = strtolower(str_replace('_', '-', $key));
        return $this->headers[$key] ?? $default;
    }
    
    public function method()
    {
        return $_SERVER['REQUEST_METHOD'];
    }
    
    public function uri()
    {
        return parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    }
    
    public function fullUrl()
    {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        return $protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    }
    
    public function ip()
    {
        $ipKeys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ips = explode(',', $_SERVER[$key]);
                return trim($ips[0]);
            }
        }
        
        return 'unknown';
    }
    
    public function userAgent()
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? '';
    }
    
    public function isJson()
    {
        return strpos($this->header('content-type', ''), 'application/json') !== false;
    }
    
    public function isXml()
    {
        $contentType = $this->header('content-type', '');
        return strpos($contentType, 'application/xml') !== false || strpos($contentType, 'text/xml') !== false;
    }
    
    public function isAjax()
    {
        return $this->header('x-requested-with') === 'XMLHttpRequest';
    }
    
    public function isSecure()
    {
        return isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
    }
    
    public function validate($rules)
    {
        $errors = [];
        
        foreach ($rules as $field => $rule) {
            $value = $this->get($field);
            $fieldRules = explode('|', $rule);
            
            foreach ($fieldRules as $fieldRule) {
                if ($fieldRule === 'required' && empty($value)) {
                    $errors[$field][] = "$field is required";
                }
                
                if (strpos($fieldRule, 'min:') === 0) {
                    $min = (int) substr($fieldRule, 4);
                    if (strlen($value) < $min) {
                        $errors[$field][] = "$field must be at least $min characters";
                    }
                }
                
                if (strpos($fieldRule, 'max:') === 0) {
                    $max = (int) substr($fieldRule, 4);
                    if (strlen($value) > $max) {
                        $errors[$field][] = "$field must not exceed $max characters";
                    }
                }
                
                if ($fieldRule === 'email' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $errors[$field][] = "$field must be a valid email";
                }
                
                if ($fieldRule === 'numeric' && !is_numeric($value)) {
                    $errors[$field][] = "$field must be numeric";
                }
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    public function only($keys)
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->get($key);
        }
        return $result;
    }
    
    public function except($keys)
    {
        $data = $this->all();
        foreach ($keys as $key) {
            unset($data[$key]);
        }
        return $data;
    }
    
    public function filled($key)
    {
        return !empty($this->get($key));
    }
    
    public function missing($key)
    {
        return !isset($this->data[$key]) && !isset($_GET[$key]);
    }
    
    public function hasFile($key)
    {
        return isset($this->files[$key]) && $this->files[$key]['error'] === UPLOAD_ERR_OK;
    }
    
    public function bearerToken()
    {
        $header = $this->header('authorization');
        if (preg_match('/Bearer\s+(.*)$/i', $header, $matches)) {
            return $matches[1];
        }
        return null;
    }
    
    private function parseInput()
    {
        $input = file_get_contents('php://input');
        
        if ($this->isJson()) {
            return json_decode($input, true) ?? [];
        }
        
        if ($this->method() === 'POST') {
            return $_POST;
        }
        
        parse_str($input, $data);
        return $data ?? [];
    }
    
    private function parseHeaders()
    {
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $header = str_replace('HTTP_', '', $key);
                $header = str_replace('_', '-', strtolower($header));
                $headers[$header] = $value;
            }
        }
        return $headers;
    }
    
    public function session($key = null, $default = null)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if ($key === null) {
            return $_SESSION;
        }
        
        return $_SESSION[$key] ?? $default;
    }
    
    public function cookie($key, $default = null)
    {
        return $_COOKIE[$key] ?? $default;
    }
    
    public function server($key, $default = null)
    {
        return $_SERVER[$key] ?? $default;
    }
}