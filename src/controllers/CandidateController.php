<?php
// src/controllers/CandidateController.php

require_once __DIR__ . '/../models/Candidate.php';
require_once __DIR__ . '/../services/ValidationService.php';
require_once __DIR__ . '/../services/AuditService.php';
require_once __DIR__ . '/../services/FileUploadService.php';
require_once __DIR__ . '/../utils/Response.php';

class CandidateController
{
    private $candidateModel;
    private $validationService;
    private $auditService;
    private $fileUploadService;

    public function __construct()
    {
        $this->candidateModel = new Candidate();
        $this->validationService = new ValidationService();
        $this->auditService = new AuditService();
        $this->fileUploadService = new FileUploadService();
    }

    public function index()
    {
        try {
            $positionId = $_GET['position_id'] ?? null;
            $page = $_GET['page'] ?? 1;
            $limit = $_GET['limit'] ?? 10;

            if ($positionId) {
                $candidates = $this->candidateModel->getByPosition($positionId);
            } else {
                $candidates = $this->candidateModel->paginate($page, $limit);
            }
            
            $this->auditService->log($this->getCurrentUserId(), 'admin', 'list', 'candidates');

            return Response::success($candidates);

        } catch (Exception $e) {
            return Response::error(['message' => 'Failed to fetch candidates'], 500);
        }
    }

    public function show($id)
    {
        try {
            $candidate = $this->candidateModel->findById($id);
            
            if (!$candidate) {
                return Response::error(['message' => 'Candidate not found'], 404);
            }
            
            $this->auditService->log($this->getCurrentUserId(), 'admin', 'view', 'candidates', $id);

            return Response::success($candidate);

        } catch (Exception $e) {
            return Response::error(['message' => 'Failed to fetch candidate'], 500);
        }
    }

    public function create()
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            $validation = $this->validationService->validateCandidate($data);
            if (!$validation['valid']) {
                return Response::error($validation['errors'], 422);
            }

            $candidateId = $this->candidateModel->create($data);
            
            $this->auditService->log($this->getCurrentUserId(), 'admin', 'create', 'candidates', $candidateId);

            return Response::success(['id' => $candidateId, 'message' => 'Candidate created successfully'], 201);

        } catch (Exception $e) {
            return Response::error(['message' => 'Failed to create candidate'], 500);
        }
    }

    public function update($id)
    {
        try {
            $candidate = $this->candidateModel->findById($id);
            if (!$candidate) {
                return Response::error(['message' => 'Candidate not found'], 404);
            }

            $data = json_decode(file_get_contents('php://input'), true);
            
            $validation = $this->validationService->validateCandidate($data, true);
            if (!$validation['valid']) {
                return Response::error($validation['errors'], 422);
            }

            $this->candidateModel->update($id, $data);
            
            $this->auditService->log($this->getCurrentUserId(), 'admin', 'update', 'candidates', $id);

            return Response::success(['message' => 'Candidate updated successfully']);

        } catch (Exception $e) {
            return Response::error(['message' => 'Failed to update candidate'], 500);
        }
    }

    public function delete($id)
    {
        try {
            $candidate = $this->candidateModel->findById($id);
            if (!$candidate) {
                return Response::error(['message' => 'Candidate not found'], 404);
            }

            $this->candidateModel->delete($id);
            
            $this->auditService->log($this->getCurrentUserId(), 'admin', 'delete', 'candidates', $id);

            return Response::success(['message' => 'Candidate deleted successfully']);

        } catch (Exception $e) {
            return Response::error(['message' => 'Failed to delete candidate'], 500);
        }
    }

    public function uploadPhoto($id)
    {
        try {
            $candidate = $this->candidateModel->findById($id);
            if (!$candidate) {
                return Response::error(['message' => 'Candidate not found'], 404);
            }

            if (!isset($_FILES['photo'])) {
                return Response::error(['message' => 'No photo uploaded'], 422);
            }

            $uploadResult = $this->fileUploadService->uploadCandidatePhoto($_FILES['photo']);
            
            if (!$uploadResult['success']) {
                return Response::error(['message' => $uploadResult['message']], 422);
            }

            $photoData = [
                'photo' => $uploadResult['photo_data'],
                'photo_filename' => $uploadResult['filename'],
                'photo_mime_type' => $uploadResult['mime_type']
            ];

            $this->candidateModel->update($id, $photoData);
            
            $this->auditService->log($this->getCurrentUserId(), 'admin', 'upload_photo', 'candidates', $id);

            return Response::success(['message' => 'Photo uploaded successfully']);

        } catch (Exception $e) {
            return Response::error(['message' => 'Failed to upload photo'], 500);
        }
    }

    public function getPhoto($id)
    {
        try {
            $candidate = $this->candidateModel->findById($id);
            
            if (!$candidate || !$candidate['photo']) {
                return Response::error(['message' => 'Photo not found'], 404);
            }

            header('Content-Type: ' . $candidate['photo_mime_type']);
            header('Content-Length: ' . strlen($candidate['photo']));
            header('Cache-Control: public, max-age=3600');
            
            echo $candidate['photo'];
            exit;

        } catch (Exception $e) {
            return Response::error(['message' => 'Failed to retrieve photo'], 500);
        }
    }

    private function getCurrentUserId()
    {
        return $_SESSION['user_id'] ?? null;
    }
}