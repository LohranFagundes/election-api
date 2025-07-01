<?php
// src/routes/admin.php

require_once __DIR__ . '/../controllers/AdminController.php';
require_once __DIR__ . '/../controllers/VoterController.php';
require_once __DIR__ . '/../controllers/ElectionController.php';
require_once __DIR__ . '/../controllers/PositionController.php';
require_once __DIR__ . '/../controllers/CandidateController.php';
require_once __DIR__ . '/../controllers/ReportController.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../middleware/AdminMiddleware.php';

$router->group(['prefix' => 'admin', 'middleware' => [AuthMiddleware::class, AdminMiddleware::class]], function($router) {
    
    $router->get('/dashboard', function() {
        $electionModel = new Election();
        $voterModel = new Voter();
        $voteModel = new Vote();
        
        $stats = [
            'total_elections' => count($electionModel->findAll()),
            'active_elections' => count($electionModel->getActive()),
            'total_voters' => $voterModel->getEligibleCount(),
            'total_votes' => 0,
            'recent_elections' => $electionModel->paginate(1, 5)['data'],
            'recent_activity' => []
        ];
        
        return Response::success($stats);
    });

    $router->get('/admins', function() {
        $controller = new AdminController();
        return $controller->index();
    });

    $router->get('/admins/{id}', function($id) {
        $controller = new AdminController();
        return $controller->show($id);
    });

    $router->post('/admins', function() {
        $controller = new AdminController();
        return $controller->create();
    });

    $router->put('/admins/{id}', function($id) {
        $controller = new AdminController();
        return $controller->update($id);
    });

    $router->delete('/admins/{id}', function($id) {
        $controller = new AdminController();
        return $controller->delete($id);
    });

    $router->post('/admins/{id}/toggle-status', function($id) {
        $controller = new AdminController();
        return $controller->toggleStatus($id);
    });

    $router->get('/voters', function() {
        $controller = new VoterController();
        return $controller->index();
    });

    $router->get('/voters/{id}', function($id) {
        $controller = new VoterController();
        return $controller->show($id);
    });

    $router->post('/voters', function() {
        $controller = new VoterController();
        return $controller->create();
    });

    $router->put('/voters/{id}', function($id) {
        $controller = new VoterController();
        return $controller->update($id);
    });

    $router->delete('/voters/{id}', function($id) {
        $controller = new VoterController();
        return $controller->delete($id);
    });

    $router->post('/voters/{id}/toggle-status', function($id) {
        $controller = new VoterController();
        return $controller->toggleStatus($id);
    });

    $router->post('/voters/{id}/verify', function($id) {
        $controller = new VoterController();
        return $controller->verify($id);
    });

    $router->post('/voters/bulk-import', function() {
        $controller = new VoterController();
        return $controller->bulkImport();
    });

    $router->get('/elections', function() {
        $controller = new ElectionController();
        return $controller->index();
    });

    $router->get('/elections/{id}', function($id) {
        $controller = new ElectionController();
        return $controller->show($id);
    });

    $router->post('/elections', function() {
        $controller = new ElectionController();
        return $controller->create();
    });

    $router->put('/elections/{id}', function($id) {
        $controller = new ElectionController();
        return $controller->update($id);
    });

    $router->delete('/elections/{id}', function($id) {
        $controller = new ElectionController();
        return $controller->delete($id);
    });

    $router->post('/elections/{id}/status', function($id) {
        $controller = new ElectionController();
        return $controller->updateStatus($id);
    });

    $router->get('/positions', function() {
        $controller = new PositionController();
        return $controller->index();
    });

    $router->get('/positions/{id}', function($id) {
        $controller = new PositionController();
        return $controller->show($id);
    });

    $router->post('/positions', function() {
        $controller = new PositionController();
        return $controller->create();
    });

    $router->put('/positions/{id}', function($id) {
        $controller = new PositionController();
        return $controller->update($id);
    });

    $router->delete('/positions/{id}', function($id) {
        $controller = new PositionController();
        return $controller->delete($id);
    });

    $router->post('/positions/reorder', function() {
        $controller = new PositionController();
        return $controller->reorder();
    });

    $router->get('/candidates', function() {
        $controller = new CandidateController();
        return $controller->index();
    });

    $router->get('/candidates/{id}', function($id) {
        $controller = new CandidateController();
        return $controller->show($id);
    });

    $router->post('/candidates', function() {
        $controller = new CandidateController();
        return $controller->create();
    });

    $router->put('/candidates/{id}', function($id) {
        $controller = new CandidateController();
        return $controller->update($id);
    });

    $router->delete('/candidates/{id}', function($id) {
        $controller = new CandidateController();
        return $controller->delete($id);
    });

    $router->post('/candidates/{id}/photo', function($id) {
        $controller = new CandidateController();
        return $controller->uploadPhoto($id);
    });

    $router->get('/reports/zeresima/{electionId}', function($electionId) {
        $controller = new ReportController();
        return $controller->generateZeresima($electionId);
    });

    $router->get('/reports/final-results/{electionId}', function($electionId) {
        $controller = new ReportController();
        return $controller->generateFinalResults($electionId);
    });

    $router->get('/reports/partial-results/{electionId}', function($electionId) {
        $controller = new ReportController();
        return $controller->getPartialResults($electionId);
    });

    $router->get('/reports/audit', function() {
        $controller = new ReportController();
        return $controller->getAuditReport();
    });

    $router->get('/reports/statistics/{electionId}', function($electionId) {
        $controller = new ReportController();
        return $controller->getVotingStatistics($electionId);
    });

    $router->get('/reports/export/{electionId}', function($electionId) {
        $controller = new ReportController();
        return $controller->exportResults($electionId);
    });
});