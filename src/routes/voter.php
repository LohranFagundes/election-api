<?php
// src/routes/voter.php

require_once __DIR__ . '/../controllers/VoteController.php';
require_once __DIR__ . '/../controllers/ElectionController.php';
require_once __DIR__ . '/../controllers/CandidateController.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../middleware/VoterMiddleware.php';

$router->group(['prefix' => 'voter', 'middleware' => [AuthMiddleware::class, VoterMiddleware::class]], function($router) {
    
    $router->get('/dashboard', function() {
        $electionModel = new Election();
        $voteModel = new Vote();
        $voterId = $_SESSION['user_id'];
        
        $activeElections = $electionModel->getActive();
        $votingHistory = $voteModel->getVoterHistory($voterId);
        
        $dashboard = [
            'active_elections' => $activeElections,
            'voting_history' => $votingHistory,
            'total_votes_cast' => count($votingHistory),
            'available_elections' => count($activeElections)
        ];
        
        return Response::success($dashboard);
    });

    $router->get('/elections', function() {
        $controller = new ElectionController();
        return $controller->getActive();
    });

    $router->get('/elections/{id}', function($id) {
        $electionModel = new Election();
        $positionModel = new Position();
        $candidateModel = new Candidate();
        
        $election = $electionModel->findById($id);
        if (!$election || !$electionModel->canVote($id)) {
            return Response::error(['message' => 'Election not available'], 404);
        }
        
        $positions = $positionModel->getByElection($id);
        foreach ($positions as &$position) {
            $position['candidates'] = $candidateModel->getActiveByPosition($position['id']);
        }
        
        $election['positions'] = $positions;
        
        return Response::success($election);
    });

    $router->get('/elections/{id}/status', function($id) {
        $controller = new VoteController();
        return $controller->checkVotingStatus($id);
    });

    $router->post('/vote', function() {
        $controller = new VoteController();
        return $controller->castVote();
    });

    $router->get('/voting-history', function() {
        $controller = new VoteController();
        return $controller->getVotingHistory();
    });

    $router->get('/candidates/{id}/photo', function($id) {
        $controller = new CandidateController();
        return $controller->getPhoto($id);
    });

    $router->get('/profile', function() {
        $voterModel = new Voter();
        $voter = $voterModel->findById($_SESSION['user_id']);
        unset($voter['password']);
        return Response::success($voter);
    });

    $router->put('/profile', function() {
        $data = json_decode(file_get_contents('php://input'), true);
        $voterModel = new Voter();
        $validationService = new ValidationService();
        
        $allowedFields = ['name', 'email', 'phone'];
        $updateData = array_intersect_key($data, array_flip($allowedFields));
        
        if (isset($updateData['email'])) {
            $existingVoter = $voterModel->findByEmail($updateData['email']);
            if ($existingVoter && $existingVoter['id'] != $_SESSION['user_id']) {
                return Response::error(['message' => 'Email already exists'], 409);
            }
        }
        
        $voterModel->update($_SESSION['user_id'], $updateData);
        
        return Response::success(['message' => 'Profile updated successfully']);
    });
});