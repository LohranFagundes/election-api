<?php
// src/controllers/PositionController.php

require_once __DIR__ . '/../models/Position.php';
require_once __DIR__ . '/../services/ValidationService.php';
require_once __DIR__ . '/../services/AuditService.php';
require_once __DIR__ . '/../utils/Response.php';

class PositionController
{
    private $positionModel;
    private $validationService;
    private $auditService;

    public function __construct()
    {
        $this->positionModel = new Position();
        $this->validationService = new ValidationService();
        $this->auditService = new AuditService();
    }

    public function index()
    {
        try {
            $electionId = $_GET['election_id'] ?? null;
            $page = $_GET['page'] ?? 1;
            $limit = $_GET['limit'] ?? 10;

            if ($electionId) {
                $positions = $this->positionModel->getByElection($electionId);
            } else {
                $positions = $this->positionModel->paginate($page, $limit);
            }
            
            $this->auditService->log($this->getCurrentUserId(), 'admin', 'list', 'positions');

            return Response::success($positions);

        } catch (Exception $e) {
            return Response::error(['message' => 'Failed to fetch positions'], 500);
        }
    }

    public function show($id)
    {
        try {
            $position = $this->positionModel->findById($id);
            
            if (!$position) {
                return Response::error(['message' => 'Position not found'], 404);
            }

            $position['candidates'] = $this->positionModel->getCandidates($id);
            
            $this->auditService->log($this->getCurrentUserId(), 'admin', 'view', 'positions', $id);

            return Response::success($position);

        } catch (Exception $e) {
            return Response::error(['message' => 'Failed to fetch position'], 500);
        }
    }

    public function create()
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            $validation = $this->validationService->validatePosition($data);
            if (!$validation['valid']) {
                return Response::error($validation['errors'], 422);
            }

            $positionId = $this->positionModel->create($data);
            
            $this->auditService->log($this->getCurrentUserId(), 'admin', 'create', 'positions', $positionId);

            return Response::success(['id' => $positionId, 'message' => 'Position created successfully'], 201);

        } catch (Exception $e) {
            return Response::error(['message' => 'Failed to create position'], 500);
        }
    }

    public function update($id)
    {
        try {
            $position = $this->positionModel->findById($id);
            if (!$position) {
                return Response::error(['message' => 'Position not found'], 404);
            }

            $data = json_decode(file_get_contents('php://input'), true);
            
            $validation = $this->validationService->validatePosition($data, true);
            if (!$validation['valid']) {
                return Response::error($validation['errors'], 422);
            }

            $this->positionModel->update($id, $data);
            
            $this->auditService->log($this->getCurrentUserId(), 'admin', 'update', 'positions', $id);

            return Response::success(['message' => 'Position updated successfully']);

        } catch (Exception $e) {
            return Response::error(['message' => 'Failed to update position'], 500);
        }
    }

    public function delete($id)
    {
        try {
            $position = $this->positionModel->findById($id);
            if (!$position) {
                return Response::error(['message' => 'Position not found'], 404);
            }

            $this->positionModel->delete($id);
            
            $this->auditService->log($this->getCurrentUserId(), 'admin', 'delete', 'positions', $id);

            return Response::success(['message' => 'Position deleted successfully']);

        } catch (Exception $e) {
            return Response::error(['message' => 'Failed to delete position'], 500);
        }
    }

    public function reorder()
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['positions']) || !is_array($data['positions'])) {
                return Response::error(['message' => 'Invalid data format'], 422);
            }

            foreach ($data['positions'] as $item) {
                if (isset($item['id']) && isset($item['order_position'])) {
                    $this->positionModel->update($item['id'], ['order_position' => $item['order_position']]);
                }
            }
            
            $this->auditService->log($this->getCurrentUserId(), 'admin', 'reorder', 'positions');

            return Response::success(['message' => 'Positions reordered successfully']);

        } catch (Exception $e) {
            return Response::error(['message' => 'Failed to reorder positions'], 500);
        }
    }

    private function getCurrentUserId()
    {
        return $_SESSION['user_id'] ?? null;
    }
}
