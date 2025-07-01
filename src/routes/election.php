<?php
// src/routes/election.php

require_once __DIR__ . '/../controllers/ElectionController.php';
require_once __DIR__ . '/../controllers/PositionController.php';
require_once __DIR__ . '/../controllers/CandidateController.php';
require_once __DIR__ . '/../controllers/VoteController.php';
require_once __DIR__ . '/../middleware/CorsMiddleware.php';

$router->group(['prefix' => 'elections'], function($router) {
    
    $router->get('/', function() {
        $electionModel = new Election();
        $elections = $electionModel->getActive();
        
        foreach ($elections as &$election) {
            $positionModel = new Position();
            $election['positions_count'] = count($positionModel->getByElection($election['id']));
        }
        
        return Response::success($elections);
    });

    $router->get('/{id}', function($id) {
        $electionModel = new Election();
        $election = $electionModel->findById($id);
        
        if (!$election) {
            return Response::error(['message' => 'Election not found'], 404);
        }
        
        if ($election['status'] !== 'active' && $election['status'] !== 'completed') {
            return Response::error(['message' => 'Election not available'], 403);
        }
        
        return Response::success($election);
    });

    $router->get('/{id}/positions', function($id) {
        $electionModel = new Election();
        $positionModel = new Position();
        
        $election = $electionModel->findById($id);
        if (!$election) {
            return Response::error(['message' => 'Election not found'], 404);
        }
        
        $positions = $positionModel->getByElection($id);
        return Response::success($positions);
    });

    $router->get('/{id}/candidates', function($id) {
        $electionModel = new Election();
        $candidateModel = new Candidate();
        
        $election = $electionModel->findById($id);
        if (!$election) {
            return Response::error(['message' => 'Election not found'], 404);
        }
        
        $candidates = $electionModel->getAllCandidates($id);
        return Response::success($candidates);
    });

    $router->get('/{id}/results', function($id) {
        $controller = new VoteController();
        return $controller->getElectionResults($id);
    });

    $router->get('/positions/{id}', function($id) {
        $positionModel = new Position();
        $candidateModel = new Candidate();
        
        $position = $positionModel->findById($id);
        if (!$position) {
            return Response::error(['message' => 'Position not found'], 404);
        }
        
        $position['candidates'] = $candidateModel->getActiveByPosition($id);
        return Response::success($position);
    });

    $router->get('/candidates/{id}', function($id) {
        $candidateModel = new Candidate();
        $candidate = $candidateModel->findById($id);
        
        if (!$candidate) {
            return Response::error(['message' => 'Candidate not found'], 404);
        }
        
        unset($candidate['photo']);
        return Response::success($candidate);
    });

    $router->get('/candidates/{id}/photo', function($id) {
        $controller = new CandidateController();
        return $controller->getPhoto($id);
    });

    $router->get('/{id}/statistics', function($id) {
        $electionModel = new Election();
        $voteModel = new Vote();
        
        $election = $electionModel->findById($id);
        if (!$election) {
            return Response::error(['message' => 'Election not found'], 404);
        }
        
        if ($election['results_visibility'] === 'private') {
            return Response::error(['message' => 'Statistics not available'], 403);
        }
        
        $stats = [
            'total_votes' => $voteModel->getTotalVotesByElection($id),
            'voter_turnout' => $voteModel->getVoterTurnout($id),
            'blank_votes' => $voteModel->getBlankVotesByElection($id),
            'null_votes' => $voteModel->getNullVotesByElection($id),
            'votes_by_hour' => $voteModel->getVotesByHour($id)
        ];
        
        return Response::success($stats);
    });
});
