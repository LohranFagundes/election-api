<?php
// src/middleware/LoggingMiddleware.php

require_once __DIR__ . '/../services/AuditService.php';

class LoggingMiddleware
{
    private $auditService;
    private $startTime;
    private $startMemory;

    public function __construct()
    {
        $this->auditService = new AuditService();
        $this->startTime = microtime(true);
        $this->startMemory = memory_get_usage(true);
    }

    public function handle($request, $next)
    {
        $this->logRequest();
        
        $response = $next($request);
        
        $this->logResponse($response);
        
        return $response;
    }

    private function logRequest()
    {
        $requestData = [
            'method' => $_SERVER['REQUEST_METHOD'],
            'uri' => $_SERVER['REQUEST_URI'],
            'query_params' => $_GET,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'ip_address' => $this->getClientIP(),
            'referer' => $_SERVER['HTTP_REFERER'] ?? '',
            'content_type' => $_SERVER['CONTENT_TYPE'] ?? '',
            'content_length' => $_SERVER['CONTENT_LENGTH'] ?? 0
        ];

        if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'PUT') {
            $input = file_get_contents('php://input');
            if ($input && $this->isJson($input)) {
                $decodedInput = json_decode($input, true);
                if (isset($decodedInput['password'])) {
                    $decodedInput['password'] = '[REDACTED]';
                }
                $requestData['body'] = $decodedInput;
            }
        }

        $this->auditService->log(
            $_SESSION['user_id'] ?? null,
            $_SESSION['role'] ?? 'guest',
            'request',
            'api',
            null,
            'API Request',
            $requestData
        );
    }

    private function logResponse($response)
    {
        $executionTime = microtime(true) - $this->startTime;
        $memoryUsage = memory_get_usage(true) - $this->startMemory;
        
        $responseData = [
            'execution_time' => round($executionTime * 1000, 2),
            'memory_usage' => $memoryUsage,
            'status_code' => http_response_code(),
            'response_size' => strlen(json_encode($response))
        ];

        $this->auditService->log(
            $_SESSION['user_id'] ?? null,
            $_SESSION['role'] ?? 'guest',
            'response',
            'api',
            null,
            'API Response',
            $responseData
        );
    }

    private function getClientIP()
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

    private function isJson($string)
    {
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }

    public function logError($error, $context = [])
    {
        $this->auditService->log(
            $_SESSION['user_id'] ?? null,
            $_SESSION['role'] ?? 'system',
            'error',
            'api',
            null,
            $error,
            array_merge($context, [
                'file' => debug_backtrace()[0]['file'] ?? 'unknown',
                'line' => debug_backtrace()[0]['line'] ?? 'unknown',
                'execution_time' => microtime(true) - $this->startTime
            ])
        );
    }

    public function logPerformance($action, $resource = null, $context = [])
    {
        $executionTime = microtime(true) - $this->startTime;
        $memoryUsage = memory_get_usage(true) - $this->startMemory;
        
        $performanceData = array_merge($context, [
            'execution_time' => round($executionTime * 1000, 2),
            'memory_usage' => $memoryUsage,
            'peak_memory' => memory_get_peak_usage(true)
        ]);

        $this->auditService->log(
            $_SESSION['user_id'] ?? null,
            $_SESSION['role'] ?? 'system',
            $action,
            $resource ?? 'performance',
            null,
            'Performance Log',
            $performanceData
        );
    }
}