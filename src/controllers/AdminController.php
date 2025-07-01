<?php 
// src/controllers/AdminController.php

require_once __DIR__ . '/../models/Admin.php';
require_once __DIR__ . '/../services/ValidationService.php';
require_once __DIR__ . '/../services/AuditService.php';
require_once __DIR__ . '/../utils/Response.php';

class AdminController
{
    private $adminModel;
    private $validationService;
    private $auditService;

    public function __construct()
    {
        $this->adminModel = new Admin();
        $this->validationService = new ValidationService();
        $this->auditService = new AuditService();
    }

    public function index()
    {
        try {
            $page = $_GET['page'] ?? 1;
            $limit = $_GET['limit'] ?? 10;
            $search = $_GET['search'] ?? '';

            $admins = $this->adminModel->paginate($page, $limit, $search);
            
            $this->auditService->log($this->getCurrentUserId(), 'admin', 'list', 'admins');

            return Response::success($admins);

        } catch (Exception $e) {
            return Response::error(['message' => 'Failed to fetch admins'], 500);
        }
    }

    public function show($id)
    {
        try {
            $admin = $this->adminModel->findById($id);
            
            if (!$admin) {
                return Response::error(['message' => 'Admin not found'], 404);
            }

            unset($admin['password']);
            
            $this->auditService->log($this->getCurrentUserId(), 'admin', 'view', 'admins', $id);

            return Response::success($admin);

        } catch (Exception $e) {
            return Response::error(['message' => 'Failed to fetch admin'], 500);
        }
    }

    public function create()
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            $validation = $this->validationService->validateAdmin($data);
            if (!$validation['valid']) {
                return Response::error($validation['errors'], 422);
            }

            if ($this->adminModel->emailExists($data['email'])) {
                return Response::error(['message' => 'Email already exists'], 409);
            }

            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
            $data['permissions'] = json_encode($data['permissions'] ?? []);

            $adminId = $this->adminModel->create($data);
            
            $this->auditService->log($this->getCurrentUserId(), 'admin', 'create', 'admins', $adminId);

            return Response::success(['id' => $adminId, 'message' => 'Admin created successfully'], 201);

        } catch (Exception $e) {
            return Response::error(['message' => 'Failed to create admin'], 500);
        }
    }

    public function update($id)
    {
        try {
            $admin = $this->adminModel->findById($id);
            if (!$admin) {
                return Response::error(['message' => 'Admin not found'], 404);
            }

            $data = json_decode(file_get_contents('php://input'), true);
            
            $validation = $this->validationService->validateAdmin($data, true);
            if (!$validation['valid']) {
                return Response::error($validation['errors'], 422);
            }

            if (isset($data['email']) && $data['email'] !== $admin['email']) {
                if ($this->adminModel->emailExists($data['email'])) {
                    return Response::error(['message' => 'Email already exists'], 409);
                }
            }

            if (isset($data['password'])) {
                $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
            }

            if (isset($data['permissions'])) {
                $data['permissions'] = json_encode($data['permissions']);
            }

            $this->adminModel->update($id, $data);
            
            $this->auditService->log($this->getCurrentUserId(), 'admin', 'update', 'admins', $id);

            return Response::success(['message' => 'Admin updated successfully']);

        } catch (Exception $e) {
            return Response::error(['message' => 'Failed to update admin'], 500);
        }
    }

    public function delete($id)
    {
        try {
            $admin = $this->adminModel->findById($id);
            if (!$admin) {
                return Response::error(['message' => 'Admin not found'], 404);
            }

            if ($id == $this->getCurrentUserId()) {
                return Response::error(['message' => 'Cannot delete your own account'], 403);
            }

            $this->adminModel->delete($id);
            
            $this->auditService->log($this->getCurrentUserId(), 'admin', 'delete', 'admins', $id);

            return Response::success(['message' => 'Admin deleted successfully']);

        } catch (Exception $e) {
            return Response::error(['message' => 'Failed to delete admin'], 500);
        }
    }

    public function toggleStatus($id)
    {
        try {
            $admin = $this->adminModel->findById($id);
            if (!$admin) {
                return Response::error(['message' => 'Admin not found'], 404);
            }

            $newStatus = !$admin['is_active'];
            $this->adminModel->update($id, ['is_active' => $newStatus]);
            
            $action = $newStatus ? 'activate' : 'deactivate';
            $this->auditService->log($this->getCurrentUserId(), 'admin', $action, 'admins', $id);

            return Response::success(['message' => 'Admin status updated successfully']);

        } catch (Exception $e) {
            return Response::error(['message' => 'Failed to update admin status'], 500);
        }
    }

    private function getCurrentUserId()
    {
        return $_SESSION['user_id'] ?? null;
    }
}