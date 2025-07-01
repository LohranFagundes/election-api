<?php
// src/routes/public.php

require_once __DIR__ . '/../controllers/ElectionController.php';
require_once __DIR__ . '/../controllers/CandidateController.php';
require_once __DIR__ . '/../middleware/CorsMiddleware.php';

$router->group(['prefix' => 'public'], function($router) {
    
    $router->get('/elections', function() {
        $electionModel = new Election();
        $elections = $electionModel->getActive();
        
        $publicElections = [];
        foreach ($elections as $election) {
            if ($election['results_visibility'] === 'public') {
                $publicElections[] = [
                    'id' => $election['id'],
                    'title' => $election['title'],
                    'description' => $election['description'],
                    'start_date' => $election['start_date'],
                    'end_date' => $election['end_date'],
                    'status' => $election['status']
                ];
            }
        }
        
        return Response::success($publicElections);
    });

    $router->get('/elections/{id}/results', function($id) {
        $electionModel = new Election();
        $voteModel = new Vote();
        
        $election = $electionModel->findById($id);
        if (!$election) {
            return Response::error(['message' => 'Election not found'], 404);
        }
        
        if ($election['results_visibility'] !== 'public') {
            return Response::error(['message' => 'Results not public'], 403);
        }
        
        $results = $voteModel->getElectionResults($id);
        return Response::success($results);
    });

    $router->get('/candidates/{id}/photo', function($id) {
        $candidateModel = new Candidate();
        $candidate = $candidateModel->findById($id);
        
        if (!$candidate || !$candidate['photo']) {
            return Response::error(['message' => 'Photo not found'], 404);
        }
        
        header('Content-Type: ' . $candidate['photo_mime_type']);
        header('Content-Length: ' . strlen($candidate['photo']));
        header('Cache-Control: public, max-age=3600');
        
        echo $candidate['photo'];
        exit;
    });

    $router->get('/health', function() {
        return Response::success([
            'status' => 'healthy',
            'timestamp' => date('Y-m-d H:i:s'),
            'version' => '1.0.0'
        ]);
    });
});