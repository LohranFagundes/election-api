<?php
// src/controllers/VoterController.php

require_once __DIR__ . '/../models/Voter.php';
require_once __DIR__ . '/../services/ValidationService.php';
require_once __DIR__ . '/../services/AuditService.php';
require_once __DIR__ . '/../utils/Response.php';

class VoterController
{
    private $voterModel;
    private $validationService;
    private $auditService;

    public function __construct()
    {
        $this->voterModel = new Voter();
        $this->validationService = new ValidationService();
        $this->auditService = new AuditService();
    }

    public function index()
    {
        try {
            $page = $_GET['page'] ?? 1;
            $limit = $_GET['limit'] ?? 10;
            $search = $_GET['search'] ?? '';

            $voters = $this->voterModel->paginate($page, $limit, $search);
            
            foreach ($voters['data'] as &$voter) {
                unset($voter['password']);
            }
            
            $this->auditService->log($this->getCurrentUserId(), 'admin', 'list', 'voters');

            return Response::success($voters);

        } catch (Exception $e) {
            return Response::error(['message' => 'Failed to fetch voters'], 500);
        }
    }

    public function show($id)
    {
        try {
            $voter = $this->voterModel->findById($id);
            
            if (!$voter) {
                return Response::error(['message' => 'Voter not found'], 404);
            }

            unset($voter['password']);
            
            $this->auditService->log($this->getCurrentUserId(), 'admin', 'view', 'voters', $id);

            return Response::success($voter);

        } catch (Exception $e) {
            return Response::error(['message' => 'Failed to fetch voter'], 500);
        }
    }

    public function create()
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            $validation = $this->validationService->validateVoter($data);
            if (!$validation['valid']) {
                return Response::error($validation['errors'], 422);
            }

            if ($this->voterModel->emailExists($data['email'])) {
                return Response::error(['message' => 'Email already exists'], 409);
            }

            if ($this->voterModel->cpfExists($data['cpf'])) {
                return Response::error(['message' => 'CPF already exists'], 409);
            }

            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
            $data['verification_token'] = bin2hex(random_bytes(32));

            $voterId = $this->voterModel->create($data);
            
            $this->auditService->log($this->getCurrentUserId(), 'admin', 'create', 'voters', $voterId);

            return Response::success(['id' => $voterId, 'message' => 'Voter created successfully'], 201);

        } catch (Exception $e) {
            return Response::error(['message' => 'Failed to create voter'], 500);
        }
    }

    public function update($id)
    {
        try {
            $voter = $this->voterModel->findById($id);
            if (!$voter) {
                return Response::error(['message' => 'Voter not found'], 404);
            }

            $data = json_decode(file_get_contents('php://input'), true);
            
            $validation = $this->validationService->validateVoter($data, true);
            if (!$validation['valid']) {
                return Response::error($validation['errors'], 422);
            }

            if (isset($data['email']) && $data['email'] !== $voter['email']) {
                if ($this->voterModel->emailExists($data['email'])) {
                    return Response::error(['message' => 'Email already exists'], 409);
                }
            }

            if (isset($data['cpf']) && $data['cpf'] !== $voter['cpf']) {
                if ($this->voterModel->cpfExists($data['cpf'])) {
                    return Response::error(['message' => 'CPF already exists'], 409);
                }
            }

            if (isset($data['password'])) {
                $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
            }

            $this->voterModel->update($id, $data);
            
            $this->auditService->log($this->getCurrentUserId(), 'admin', 'update', 'voters', $id);

            return Response::success(['message' => 'Voter updated successfully']);

        } catch (Exception $e) {
            return Response::error(['message' => 'Failed to update voter'], 500);
        }
    }

    public function delete($id)
    {
        try {
            $voter = $this->voterModel->findById($id);
            if (!$voter) {
                return Response::error(['message' => 'Voter not found'], 404);
            }

            $this->voterModel->delete($id);
            
            $this->auditService->log($this->getCurrentUserId(), 'admin', 'delete', 'voters', $id);

            return Response::success(['message' => 'Voter deleted successfully']);

        } catch (Exception $e) {
            return Response::error(['message' => 'Failed to delete voter'], 500);
        }
    }

    public function toggleStatus($id)
    {
        try {
            $voter = $this->voterModel->findById($id);
            if (!$voter) {
                return Response::error(['message' => 'Voter not found'], 404);
            }

            $newStatus = !$voter['is_active'];
            $this->voterModel->update($id, ['is_active' => $newStatus]);
            
            $action = $newStatus ? 'activate' : 'deactivate';
            $this->auditService->log($this->getCurrentUserId(), 'admin', $action, 'voters', $id);

            return Response::success(['message' => 'Voter status updated successfully']);

        } catch (Exception $e) {
            return Response::error(['message' => 'Failed to update voter status'], 500);
        }
    }

    public function verify($id)
    {
        try {
            $voter = $this->voterModel->findById($id);
            if (!$voter) {
                return Response::error(['message' => 'Voter not found'], 404);
            }

            $this->voterModel->update($id, [
                'is_verified' => 1,
                'email_verified_at' => date('Y-m-d H:i:s'),
                'verification_token' => null
            ]);
            
            $this->auditService->log($this->getCurrentUserId(), 'admin', 'verify', 'voters', $id);

            return Response::success(['message' => 'Voter verified successfully']);

        } catch (Exception $e) {
            return Response::error(['message' => 'Failed to verify voter'], 500);
        }
    }

    public function bulkImport()
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['voters']) || !is_array($data['voters'])) {
                return Response::error(['message' => 'Invalid data format'], 422);
            }

            $created = 0;
            $errors = [];

            foreach ($data['voters'] as $index => $voterData) {
                $validation = $this->validationService->validateVoter($voterData);
                if (!$validation['valid']) {
                    $errors[] = "Row $index: " . implode(', ', $validation['errors']);
                    continue;
                }

                if ($this->voterModel->emailExists($voterData['email']) || 
                    $this->voterModel->cpfExists($voterData['cpf'])) {
                    $errors[] = "Row $index: Email or CPF already exists";
                    continue;
                }

                $voterData['password'] = password_hash($voterData['password'], PASSWORD_DEFAULT);
                $voterData['verification_token'] = bin2hex(random_bytes(32));

                $this->voterModel->create($voterData);
                $created++;
            }

            $this->auditService->log($this->getCurrentUserId(), 'admin', 'bulk_import', 'voters', null, "Created: $created");

            return Response::success([
                'message' => 'Bulk import completed',
                'created' => $created,
                'errors' => $errors
            ]);

        } catch (Exception $e) {
            return Response::error(['message' => 'Failed to import voters'], 500);
        }
    }

    private function getCurrentUserId()
    {
        return $_SESSION['user_id'] ?? null;
    }
}