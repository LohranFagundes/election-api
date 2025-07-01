<?php
// src/services/AuditService.php

require_once __DIR__ . '/../models/AuditLog.php';

class AuditService
{
    private $auditLogModel;
    private $config;

    public function __construct()
    {
        $this->auditLogModel = new AuditLog();
        $this->config = require __DIR__ . '/../../config/app.php';
    }

    public function log($userId, $userType, $action, $resource, $resourceId = null, $message = '', $context = [])
    {
        if (!$this->config['logging']['enabled']) {
            return;
        }

        try {
            $logData = [
                'user_id' => $userId,
                'user_type' => $userType,
                'action' => $action,
                'resource' => $resource,
                'resource_id' => $resourceId,
                'route' => $_SERVER['REQUEST_URI'] ?? '',
                'method' => $_SERVER['REQUEST_METHOD'] ?? '',
                'ip_address' => $this->getClientIP(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'session_id' => session_id() ?: '',
                'correlation_id' => $this->generateCorrelationId(),
                'level' => $this->determineLogLevel($action),
                'message' => $message,
                'context' => json_encode($context),
                'execution_time' => $this->getExecutionTime(),
                'memory_usage' => memory_get_usage(true),
                'created_at' => date('Y-m-d H:i:s')
            ];

            $this->auditLogModel->create($logData);
            $this->writeToFile($logData);

        } catch (Exception $e) {
            error_log("Audit logging failed: " . $e->getMessage());
        }
    }

    public function logRequest($request = [])
    {
        $requestData = [
            'method' => $_SERVER['REQUEST_METHOD'],
            'uri' => $_SERVER['REQUEST_URI'],
            'query_params' => $_GET,
            'headers' => $this->getHeaders(),
            'body_size' => $_SERVER['CONTENT_LENGTH'] ?? 0
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

        $this->log(
            $_SESSION['user_id'] ?? null,
            $_SESSION['role'] ?? 'guest',
            'api_request',
            'request',
            null,
            'API Request',
            $requestData
        );
    }

    public function logResponse($response, $statusCode = 200)
    {
        $responseData = [
            'status_code' => $statusCode,
            'response_size' => strlen(json_encode($response)),
            'execution_time' => $this->getExecutionTime(),
            'memory_usage' => memory_get_usage(true),
            'peak_memory' => memory_get_peak_usage(true)
        ];

        $this->log(
            $_SESSION['user_id'] ?? null,
            $_SESSION['role'] ?? 'guest',
            'api_response',
            'response',
            null,
            'API Response',
            $responseData
        );
    }

    public function logError($error, $context = [])
    {
        $errorData = array_merge($context, [
            'error_message' => $error,
            'file' => debug_backtrace()[1]['file'] ?? 'unknown',
            'line' => debug_backtrace()[1]['line'] ?? 'unknown',
            'trace' => debug_backtrace()
        ]);

        $this->log(
            $_SESSION['user_id'] ?? null,
            $_SESSION['role'] ?? 'system',
            'error',
            'system',
            null,
            $error,
            $errorData
        );
    }

    public function logSecurityEvent($event, $severity = 'warning', $context = [])
    {
        $securityData = array_merge($context, [
            'severity' => $severity,
            'event_type' => $event,
            'ip_address' => $this->getClientIP(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'timestamp' => date('Y-m-d H:i:s')
        ]);

        $this->log(
            $_SESSION['user_id'] ?? null,
            $_SESSION['role'] ?? 'system',
            'security_event',
            'security',
            null,
            "Security Event: $event",
            $securityData
        );
    }

    public function logPerformance($action, $resource, $executionTime, $context = [])
    {
        $performanceData = array_merge($context, [
            'execution_time' => $executionTime,
            'memory_usage' => memory_get_usage(true),
            'peak_memory' => memory_get_peak_usage(true),
            'queries_count' => $context['queries_count'] ?? 0
        ]);

        $this->log(
            $_SESSION['user_id'] ?? null,
            $_SESSION['role'] ?? 'system',
            'performance',
            $resource,
            null,
            "Performance: $action",
            $performanceData
        );
    }

    private function writeToFile($logData)
    {
        $logFile = $this->getLogFile($logData['level']);
        $logLine = $this->formatLogLine($logData);
        
        file_put_contents($logFile, $logLine . PHP_EOL, FILE_APPEND | LOCK_EX);
        
        $this->rotateLogFile($logFile);
    }

    private function getLogFile($level)
    {
        $logFiles = $this->config['logging']['files'];
        
        switch ($level) {
            case 'error':
            case 'critical':
            case 'emergency':
                return $logFiles['error'];
            case 'warning':
                return $logFiles['auth'];
            default:
                return $logFiles['api'];
        }
    }

    private function formatLogLine($logData)
    {
        return sprintf(
            "[%s] %s.%s: %s %s %s - %s (User: %s, IP: %s)",
            $logData['created_at'],
            strtoupper($logData['level']),
            strtoupper($logData['user_type']),
            $logData['method'],
            $logData['route'],
            $logData['action'],
            $logData['message'],
            $logData['user_id'] ?? 'guest',
            $logData['ip_address']
        );
    }

    private function rotateLogFile($logFile)
    {
        if (!file_exists($logFile)) {
            return;
        }

        $maxSize = $this->config['logging']['max_file_size'];
        $maxFiles = $this->config['logging']['max_files'];

        if (filesize($logFile) > $maxSize) {
            for ($i = $maxFiles - 1; $i > 0; $i--) {
                $oldFile = $logFile . '.' . $i;
                $newFile = $logFile . '.' . ($i + 1);
                
                if (file_exists($oldFile)) {
                    rename($oldFile, $newFile);
                }
            }
            
            rename($logFile, $logFile . '.1');
        }
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

    private function generateCorrelationId()
    {
        return uniqid('audit_', true);
    }

    private function determineLogLevel($action)
    {
        $criticalActions = ['delete', 'login_failed', 'unauthorized_access', 'invalid_token', 'security_breach'];
        $warningActions = ['login_success', 'logout', 'permission_denied', 'failed_validation'];
        $errorActions = ['error', 'exception', 'database_error', 'file_error'];

        if (in_array($action, $criticalActions)) {
            return 'critical';
        } elseif (in_array($action, $warningActions)) {
            return 'warning';
        } elseif (strpos($action, 'error') !== false || in_array($action, $errorActions)) {
            return 'error';
        } else {
            return 'info';
        }
    }

    private function getExecutionTime()
    {
        if (defined('REQUEST_START_TIME')) {
            return round((microtime(true) - REQUEST_START_TIME) * 1000, 2);
        }
        return 0;
    }

    private function getHeaders()
    {
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $header = str_replace('HTTP_', '', $key);
                $header = str_replace('_', '-', $header);
                $headers[ucwords(strtolower($header), '-')] = $value;
            }
        }
        return $headers;
    }

    private function isJson($string)
    {
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }
}
