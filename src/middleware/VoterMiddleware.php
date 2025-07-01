<?php
// src/middleware/VoterMiddleware.php

require_once __DIR__ . '/../models/Voter.php';
require_once __DIR__ . '/../services/AuditService.php';
require_once __DIR__ . '/../utils/Response.php';

class VoterMiddleware
{
    private $voterModel;
    private $auditService;

    public function __construct()
    {
        $this->voterModel = new Voter();
        $this->auditService = new AuditService();
    }

    public function handle($request, $next)
    {
        try {
            if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
                return Response::error(['message' => 'Authentication required'], 401);
            }

            if ($_SESSION['role'] !== 'voter') {
                $this->auditService->log($_SESSION['user_id'], $_SESSION['role'], 'unauthorized_voter_access', 'auth');
                return Response::error(['message' => 'Voter access required'], 403);
            }

            $voter = $this->voterModel->findById($_SESSION['user_id']);
            
            if (!$voter) {
                return Response::error(['message' => 'Voter account not found'], 404);
            }

            if (!$voter['is_active']) {
                $this->auditService->log($_SESSION['user_id'], 'voter', 'inactive_voter_access', 'auth');
                return Response::error(['message' => 'Voter account is inactive'], 403);
            }

            if (!$voter['is_verified']) {
                $this->auditService->log($_SESSION['user_id'], 'voter', 'unverified_voter_access', 'auth');
                return Response::error(['message' => 'Voter account is not verified'], 403);
            }

            $_SESSION['voter_data'] = [
                'cpf' => $voter['cpf'],
                'vote_weight' => $voter['vote_weight'],
                'verified_at' => $voter['email_verified_at']
            ];

            return $next($request);

        } catch (Exception $e) {
            $this->auditService->log($_SESSION['user_id'] ?? null, 'voter', 'voter_middleware_error', 'auth', null, $e->getMessage());
            return Response::error(['message' => 'Voter verification failed'], 500);
        }
    }

    public function checkVotingEligibility($electionId)
    {
        try {
            $voterId = $_SESSION['user_id'];
            
            $voter = $this->voterModel->findById($voterId);
            if (!$voter || !$voter['is_active'] || !$voter['is_verified']) {
                return ['eligible' => false, 'reason' => 'Voter not eligible'];
            }

            $hasVoted = $this->voterModel->hasVotedInElection($voterId, $electionId);
            if ($hasVoted) {
                return ['eligible' => false, 'reason' => 'Already voted in this election'];
            }

            return ['eligible' => true, 'vote_weight' => $voter['vote_weight']];

        } catch (Exception $e) {
            return ['eligible' => false, 'reason' => 'Eligibility check failed'];
        }
    }
}