<?php
// src/models/AuditLog.php

require_once __DIR__ . '/BaseModel.php';

class AuditLog extends BaseModel
{
    protected $table = 'audit_logs';
    protected $fillable = ['user_id', 'user_type', 'action', 'resource', 'resource_id', 'route', 'method', 'ip_address', 'user_agent', 'request_data', 'response_data', 'status_code', 'execution_time', 'memory_usage', 'session_id', 'correlation_id', 'level', 'message', 'context'];
    protected $timestamps = false;

    public function log($userId, $userType, $action, $resource, $resourceId = null, $message = '', $context = [])
    {
        $data = [
            'user_id' => $userId,
            'user_type' => $userType,
            'action' => $action,
            'resource' => $resource,
            'resource_id' => $resourceId,
            'route' => $_SERVER['REQUEST_URI'] ?? '',
            'method' => $_SERVER['REQUEST_METHOD'] ?? '',
            'ip_address' => $this->getClientIP(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'session_id' => session_id(),
            'correlation_id' => $this->generateCorrelationId(),
            'level' => $this->determineLogLevel($action),
            'message' => $message,
            'context' => json_encode($context),
            'created_at' => date('Y-m-d H:i:s')
        ];

        return $this->create($data);
    }

    public function getFilteredLogs($filters, $page = 1, $limit = 100)
    {
        $offset = ($page - 1) * $limit;
        $whereConditions = [];
        $params = [];

        if (!empty($filters['date_from'])) {
            $whereConditions[] = "created_at >= :date_from";
            $params['date_from'] = $filters['date_from'] . ' 00:00:00';
        }

        if (!empty($filters['date_to'])) {
            $whereConditions[] = "created_at <= :date_to";
            $params['date_to'] = $filters['date_to'] . ' 23:59:59';
        }

        if (!empty($filters['user_type'])) {
            $whereConditions[] = "user_type = :user_type";
            $params['user_type'] = $filters['user_type'];
        }

        if (!empty($filters['action'])) {
            $whereConditions[] = "action = :action";
            $params['action'] = $filters['action'];
        }

        if (!empty($filters['resource'])) {
            $whereConditions[] = "resource = :resource";
            $params['resource'] = $filters['resource'];
        }

        if (!empty($filters['user_id'])) {
            $whereConditions[] = "user_id = :user_id";
            $params['user_id'] = $filters['user_id'];
        }

        $whereClause = empty($whereConditions) ? '' : 'WHERE ' . implode(' AND ', $whereConditions);

        $countSql = "SELECT COUNT(*) as total FROM {$this->table} {$whereClause}";
        $stmt = $this->db->prepare($countSql);
        $stmt->execute($params);
        $total = $stmt->fetch()['total'];

        $sql = "SELECT * FROM {$this->table} {$whereClause} ORDER BY created_at DESC LIMIT {$limit} OFFSET {$offset}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $data = $stmt->fetchAll();

        return [
            'data' => $data,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'pages' => ceil($total / $limit)
        ];
    }

    public function getAuditSummary($filters = [])
    {
        $whereConditions = [];
        $params = [];

        if (!empty($filters['date_from'])) {
            $whereConditions[] = "created_at >= :date_from";
            $params['date_from'] = $filters['date_from'] . ' 00:00:00';
        }

        if (!empty($filters['date_to'])) {
            $whereConditions[] = "created_at <= :date_to";
            $params['date_to'] = $filters['date_to'] . ' 23:59:59';
        }

        $whereClause = empty($whereConditions) ? '' : 'WHERE ' . implode(' AND ', $whereConditions);

        $totalSql = "SELECT COUNT(*) as total FROM {$this->table} {$whereClause}";
        $stmt = $this->db->prepare($totalSql);
        $stmt->execute($params);
        $total = $stmt->fetch()['total'];

        $userTypesSql = "SELECT user_type, COUNT(*) as count FROM {$this->table} {$whereClause} GROUP BY user_type";
        $stmt = $this->db->prepare($userTypesSql);
        $stmt->execute($params);
        $userTypes = $stmt->fetchAll();

        $actionsSql = "SELECT action, COUNT(*) as count FROM {$this->table} {$whereClause} GROUP BY action ORDER BY count DESC LIMIT 10";
        $stmt = $this->db->prepare($actionsSql);
        $stmt->execute($params);
        $topActions = $stmt->fetchAll();

        $resourcesSql = "SELECT resource, COUNT(*) as count FROM {$this->table} {$whereClause} GROUP BY resource ORDER BY count DESC LIMIT 10";
        $stmt = $this->db->prepare($resourcesSql);
        $stmt->execute($params);
        $topResources = $stmt->fetchAll();

        return [
            'total_logs' => $total,
            'user_types' => $userTypes,
            'top_actions' => $topActions,
            'top_resources' => $topResources
        ];
    }

    public function getLogsByUser($userId, $limit = 50)
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE user_id = ? ORDER BY created_at DESC LIMIT ?");
        $stmt->execute([$userId, $limit]);
        return $stmt->fetchAll();
    }

    public function getLogsByAction($action, $limit = 100)
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE action = ? ORDER BY created_at DESC LIMIT ?");
        $stmt->execute([$action, $limit]);
        return $stmt->fetchAll();
    }

    public function getLogsByResource($resource, $limit = 100)
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE resource = ? ORDER BY created_at DESC LIMIT ?");
        $stmt->execute([$resource, $limit]);
        return $stmt->fetchAll();
    }

    public function getErrorLogs($limit = 100)
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE level IN ('error', 'critical', 'emergency') ORDER BY created_at DESC LIMIT ?");
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }

    public function cleanupOldLogs($days = 90)
    {
        $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE created_at < ?");
        return $stmt->execute([$cutoffDate]);
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
        return uniqid('', true);
    }

    private function determineLogLevel($action)
    {
        $criticalActions = ['delete', 'login_failed', 'unauthorized_access', 'invalid_token'];
        $warningActions = ['login_success', 'logout', 'permission_denied'];
        $errorActions = ['error', 'exception', 'failed'];

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

    protected function getSearchableColumns()
    {
        return ['action', 'resource', 'message', 'ip_address'];
    }
}