<?php
// src/controllers/VoteController.php

require_once __DIR__ . '/../models/Vote.php';
require_once __DIR__ . '/../models/Election.php';
require_once __DIR__ . '/../models/Voter.php';
require_once __DIR__ . '/../services/ValidationService.php';
require_once __DIR__ . '/../services/AuditService.php';
require_once __DIR__ . '/../services/HashService.php';
require_once __DIR__ . '/../utils/Response.php';

class VoteController
{
    private $voteModel;
    private $electionModel;
    private $voterModel;
    private $validationService;
    private $auditService;
    private $hashService;

    public function __construct()
    {
        $this->voteModel = new Vote();
        $this->electionModel = new Election();
        $this->voterModel = new Voter();
        $this->validationService = new ValidationService();
        $this->auditService = new AuditService();
        $this->hashService = new HashService();
    }

    public function castVote()
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            
            $validation = $this->validationService->validateVote($data);
            if (!$validation['valid']) {
                return Response::error($validation['errors'], 422);
            }

            $voterId = $this->getCurrentUserId();
            $electionId = $data['election_id'];
            $positionId = $data['position_id'];

            $election = $this->electionModel->findById($electionId);
            if (!$election || $election['status'] !== 'active') {
                return Response::error(['message' => 'Election not available for voting'], 403);
            }

            if ($this->voteModel->hasVoted($voterId, $electionId, $positionId)) {
                return Response::error(['message' => 'Vote already cast for this position'], 409);
            }

            $voter = $this->voterModel->findById($voterId);
            if (!$voter['is_active'] || !$voter['is_verified']) {
                return Response::error(['message' => 'Voter not eligible'], 403);
            }

            $candidateHash = '';
            $voteType = 'candidate';

            if ($data['vote_type'] === 'blank') {
                $candidateHash = $this->hashService->generateBlankVoteHash();
                $voteType = 'blank';
            } elseif ($data['vote_type'] === 'null') {
                $candidateHash = $this->hashService->generateNullVoteHash();
                $voteType = 'null';
            } else {
                $candidateHash = $this->hashService->generateCandidateHash($data['candidate_id']);
            }

            $voteHash = $this->hashService->generateVoteHash($voterId, $electionId, $positionId, $candidateHash);

            $voteData = [
                'election_id' => $electionId,
                'position_id' => $positionId,
                'voter_id' => $voterId,
                'candidate_hash' => $candidateHash,
                'vote_hash' => $voteHash,
                'vote_weight' => $voter['vote_weight'],
                'vote_type' => $voteType,
                'ip_address' => $_SERVER['REMOTE_ADDR'],
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'session_id' => session_id()
            ];

            $voteId = $this->voteModel->create($voteData);

            $sessionData = [
                'voter_id' => $voterId,
                'election_id' => $electionId,
                'session_token' => bin2hex(random_bytes(32)),
                'ip_address' => $_SERVER['REMOTE_ADDR'],
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'status' => 1,
                'vote_completed' => 1,
                'vote_timestamp' => date('Y-m-d H:i:s'),
                'completed_at' => date('Y-m-d H:i:s'),
                'expires_at' => date('Y-m-d H:i:s', strtotime('+1 hour'))
            ];

            $this->voteModel->createSession($sessionData);
            
            $this->auditService->log($voterId, 'voter', 'cast_vote', 'votes', $voteId, 
                                   "Election: $electionId, Position: $positionId");

            return Response::success([
                'message' => 'Vote cast successfully',
                'vote_id' => $voteId,
                'vote_hash' => substr($voteHash, 0, 8) . '...'
            ]);

        } catch (Exception $e) {
            $this->auditService->log($this->getCurrentUserId(), 'voter', 'vote_error', 'votes', null, $e->getMessage());
            return Response::error(['message' => 'Failed to cast vote'], 500);
        }
    }

    public function getVotingHistory()
    {
        try {
            $voterId = $this->getCurrentUserId();
            $history = $this->voteModel->getVoterHistory($voterId);
            
            $this->auditService->log($voterId, 'voter', 'view_history', 'votes');

            return Response::success($history);

        } catch (Exception $e) {
            return Response::error(['message' => 'Failed to fetch voting history'], 500);
        }
    }

    public function checkVotingStatus($electionId)
    {
        try {
            $voterId = $this->getCurrentUserId();
            $status = $this->voteModel->getVotingStatus($voterId, $electionId);
            
            return Response::success($status);

        } catch (Exception $e) {
            return Response::error(['message' => 'Failed to check voting status'], 500);
        }
    }

    public function getElectionResults($electionId)
    {
        try {
            $election = $this->electionModel->findById($electionId);
            
            if (!$election) {
                return Response::error(['message' => 'Election not found'], 404);
            }

            if ($election['results_visibility'] === 'private') {
                return Response::error(['message' => 'Results are private'], 403);
            }

            if ($election['results_visibility'] === 'after_end' && $election['status'] !== 'completed') {
                return Response::error(['message' => 'Results not available yet'], 403);
            }

            $results = $this->voteModel->getElectionResults($electionId);
            
            $this->auditService->log($this->getCurrentUserId(), 'voter', 'view_results', 'elections', $electionId);

            return Response::success($results);

        } catch (Exception $e) {
            return Response::error(['message' => 'Failed to fetch results'], 500);
        }
    }

    private function getCurrentUserId()
    {
        return $_SESSION['user_id'] ?? null;
    }
}
