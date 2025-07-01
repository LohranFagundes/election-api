<?php
// src/middleware/AdminMiddleware.php

require_once __DIR__ . '/../models/Admin.php';
require_once __DIR__ . '/../services/AuditService.php';
require_once __DIR__ . '/../utils/Response.php';

class AdminMiddleware
{
    private $adminModel;
    private $auditService;

    public function __construct()
    {
        $this->adminModel = new Admin();
        $this->auditService = new AuditService();
    }

    public function handle($request, $next)
    {
        try {
            if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
                return Response::error(['message' => 'Authentication required'], 401);
            }

            if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'super_admin' && $_SESSION['role'] !== 'moderator') {
                $this->auditService->log($_SESSION['user_id'], $_SESSION['role'], 'unauthorized_admin_access', 'auth');
                return Response::error(['message' => 'Admin access required'], 403);
            }

            $admin = $this->adminModel->findById($_SESSION['user_id']);
            
            if (!$admin) {
                return Response::error(['message' => 'Admin account not found'], 404);
            }

            if (!$admin['is_active']) {
                $this->auditService->log($_SESSION['user_id'], 'admin', 'inactive_admin_access', 'auth');
                return Response::error(['message' => 'Admin account is inactive'], 403);
            }

            $_SESSION['admin_permissions'] = json_decode($admin['permissions'], true) ?? [];

            return $next($request);

        } catch (Exception $e) {
            $this->auditService->log($_SESSION['user_id'] ?? null, 'admin', 'admin_middleware_error', 'auth', null, $e->getMessage());
            return Response::error(['message' => 'Admin verification failed'], 500);
        }
    }

    public function checkPermission($permission)
    {
        if (!isset($_SESSION['admin_permissions'])) {
            return false;
        }

        $permissions = $_SESSION['admin_permissions'];
        
        if (in_array('*', $permissions)) {
            return true;
        }

        if (in_array($permission, $permissions)) {
            return true;
        }

        $permissionParts = explode('.', $permission);
        $wildcardPermission = $permissionParts[0] . '.*';
        
        return in_array($wildcardPermission, $permissions);
    }

    public function requirePermission($permission)
    {
        if (!$this->checkPermission($permission)) {
            $this->auditService->log($_SESSION['user_id'], 'admin', 'permission_denied', 'auth', null, $permission);
            return Response::error(['message' => 'Insufficient permissions'], 403);
        }
        
        return null;
    }
}