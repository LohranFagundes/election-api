<?php
// src/controllers/ReportController.php

require_once __DIR__ . '/../models/Vote.php';
require_once __DIR__ . '/../models/Election.php';
require_once __DIR__ . '/../models/AuditLog.php';
require_once __DIR__ . '/../models/Voter.php';
require_once __DIR__ . '/../models/Position.php';
require_once __DIR__ . '/../models/Candidate.php';
require_once __DIR__ . '/../services/AuditService.php';
require_once __DIR__ . '/../utils/Response.php';

class ReportController
{
    private $voteModel;
    private $electionModel;
    private $auditLogModel;
    private $voterModel;
    private $positionModel;
    private $candidateModel;
    private $auditService;

    public function __construct()
    {
        $this->voteModel = new Vote();
        $this->electionModel = new Election();
        $this->auditLogModel = new AuditLog();
        $this->voterModel = new Voter();
        $this->positionModel = new Position();
        $this->candidateModel = new Candidate();
        $this->auditService = new AuditService();
    }

    public function generateZeresima($electionId)
    {
        try {
            $election = $this->electionModel->findById($electionId);
            if (!$election) {
                return Response::error(['message' => 'Election not found'], 404);
            }

            $positions = $this->positionModel->getByElection($electionId);
            $totalVoters = $this->voterModel->getEligibleCount();
            $electionVoters = $this->voterModel->getElectionEligibleCount($electionId);

            $zeresima = [
                'report_type' => 'zeresima',
                'election' => [
                    'id' => $election['id'],
                    'title' => $election['title'],
                    'description' => $election['description'],
                    'start_date' => $election['start_date'],
                    'end_date' => $election['end_date'],
                    'status' => $election['status'],
                    'timezone' => $election['timezone']
                ],
                'statistics' => [
                    'total_registered_voters' => $totalVoters,
                    'eligible_voters_for_election' => $electionVoters,
                    'total_positions' => count($positions),
                    'total_candidates' => 0,
                    'voting_started' => false,
                    'votes_cast' => 0
                ],
                'positions' => [],
                'generated_at' => date('Y-m-d H:i:s'),
                'generated_by' => $this->getCurrentUserId(),
                'server_info' => [
                    'ip' => $_SERVER['SERVER_ADDR'] ?? 'unknown',
                    'timestamp' => time(),
                    'timezone' => date_default_timezone_get()
                ]
            ];

            foreach ($positions as $position) {
                $candidates = $this->candidateModel->getByPosition($position['id']);
                $zeresima['statistics']['total_candidates'] += count($candidates);

                $positionData = [
                    'id' => $position['id'],
                    'title' => $position['title'],
                    'description' => $position['description'],
                    'order_position' => $position['order_position'],
                    'max_votes' => $position['max_votes'],
                    'min_votes' => $position['min_votes'],
                    'candidates' => []
                ];

                foreach ($candidates as $candidate) {
                    $positionData['candidates'][] = [
                        'id' => $candidate['id'],
                        'name' => $candidate['name'],
                        'nickname' => $candidate['nickname'],
                        'number' => $candidate['number'],
                        'party' => $candidate['party'],
                        'coalition' => $candidate['coalition'],
                        'votes' => 0,
                        'percentage' => 0.0
                    ];
                }

                $zeresima['positions'][] = $positionData;
            }

            $this->auditService->log(
                $this->getCurrentUserId(), 
                'admin', 
                'generate_zeresima_report', 
                'elections', 
                $electionId,
                'Zerésima report generated'
            );

            return Response::success($zeresima);

        } catch (Exception $e) {
            $this->auditService->log(
                $this->getCurrentUserId(), 
                'admin', 
                'zeresima_error', 
                'elections', 
                $electionId, 
                $e->getMessage()
            );
            return Response::error(['message' => 'Failed to generate zerésima report'], 500);
        }
    }

    public function generateFinalResults($electionId)
    {
        try {
            $election = $this->electionModel->findById($electionId);
            if (!$election) {
                return Response::error(['message' => 'Election not found'], 404);
            }

            if ($election['status'] !== 'completed') {
                return Response::error(['message' => 'Election not completed yet'], 403);
            }

            $positions = $this->positionModel->getByElection($electionId);
            $totalVotes = $this->voteModel->getTotalVotesByElection($electionId);
            $voterTurnout = $this->voteModel->getVoterTurnout($electionId);

            $finalResults = [
                'report_type' => 'final_results',
                'election' => [
                    'id' => $election['id'],
                    'title' => $election['title'],
                    'description' => $election['description'],
                    'start_date' => $election['start_date'],
                    'end_date' => $election['end_date'],
                    'status' => $election['status'],
                    'completed_at' => $election['updated_at']
                ],
                'summary' => [
                    'total_votes_cast' => $totalVotes,
                    'total_eligible_voters' => $voterTurnout['eligible'],
                    'voter_turnout_count' => $voterTurnout['voted'],
                    'voter_turnout_percentage' => $voterTurnout['percentage'],
                    'blank_votes' => $this->voteModel->getBlankVotesByElection($electionId),
                    'null_votes' => $this->voteModel->getNullVotesByElection($electionId)
                ],
                'positions' => [],
                'generated_at' => date('Y-m-d H:i:s'),
                'generated_by' => $this->getCurrentUserId()
            ];

            foreach ($positions as $position) {
                $candidates = $this->candidateModel->getByPosition($position['id']);
                $positionVotes = $this->voteModel->getVotesByPosition($position['id']);
                $positionTotal = array_sum(array_column($positionVotes, 'vote_count'));

                $positionResults = [
                    'id' => $position['id'],
                    'title' => $position['title'],
                    'total_votes' => $positionTotal,
                    'candidates' => [],
                    'blank_votes' => $this->voteModel->getBlankVotesByPosition($position['id']),
                    'null_votes' => $this->voteModel->getNullVotesByPosition($position['id'])
                ];

                foreach ($candidates as $candidate) {
                    $candidateVotes = $this->voteModel->getCandidateVotes($candidate['id']);
                    $percentage = $positionTotal > 0 ? ($candidateVotes['vote_count'] / $positionTotal) * 100 : 0;

                    $positionResults['candidates'][] = [
                        'id' => $candidate['id'],
                        'name' => $candidate['name'],
                        'nickname' => $candidate['nickname'],
                        'number' => $candidate['number'],
                        'party' => $candidate['party'],
                        'coalition' => $candidate['coalition'],
                        'votes' => $candidateVotes['vote_count'],
                        'weighted_votes' => $candidateVotes['weighted_votes'],
                        'percentage' => round($percentage, 2)
                    ];
                }

                usort($positionResults['candidates'], function($a, $b) {
                    return $b['weighted_votes'] <=> $a['weighted_votes'];
                });

                $finalResults['positions'][] = $positionResults;
            }

            $this->auditService->log(
                $this->getCurrentUserId(), 
                'admin', 
                'generate_final_results', 
                'elections', 
                $electionId,
                'Final results generated'
            );

            return Response::success($finalResults);

        } catch (Exception $e) {
            $this->auditService->log(
                $this->getCurrentUserId(), 
                'admin', 
                'final_results_error', 
                'elections', 
                $electionId, 
                $e->getMessage()
            );
            return Response::error(['message' => 'Failed to generate final results'], 500);
        }
    }

    public function getPartialResults($electionId)
    {
        try {
            $election = $this->electionModel->findById($electionId);
            if (!$election) {
                return Response::error(['message' => 'Election not found'], 404);
            }

            if ($election['status'] !== 'active' && $election['status'] !== 'completed') {
                return Response::error(['message' => 'Results not available'], 403);
            }

            $positions = $this->positionModel->getByElection($electionId);
            $currentVotes = $this->voteModel->getTotalVotesByElection($electionId);

            $partialResults = [
                'report_type' => 'partial_results',
                'election_id' => $electionId,
                'election_title' => $election['title'],
                'election_status' => $election['status'],
                'total_votes_so_far' => $currentVotes,
                'last_updated' => date('Y-m-d H:i:s'),
                'positions' => []
            ];

            foreach ($positions as $position) {
                $candidates = $this->candidateModel->getByPosition($position['id']);
                $positionVotes = $this->voteModel->getVotesByPosition($position['id']);
                $positionTotal = array_sum(array_column($positionVotes, 'vote_count'));

                $positionData = [
                    'position_id' => $position['id'],
                    'position_title' => $position['title'],
                    'total_votes' => $positionTotal,
                    'candidates' => []
                ];

                foreach ($candidates as $candidate) {
                    $candidateVotes = $this->voteModel->getCandidateVotes($candidate['id']);
                    $percentage = $positionTotal > 0 ? ($candidateVotes['vote_count'] / $positionTotal) * 100 : 0;

                    $positionData['candidates'][] = [
                        'candidate_id' => $candidate['id'],
                        'name' => $candidate['name'],
                        'nickname' => $candidate['nickname'],
                        'number' => $candidate['number'],
                        'votes' => $candidateVotes['vote_count'],
                        'percentage' => round($percentage, 2)
                    ];
                }

                $partialResults['positions'][] = $positionData;
            }

            $this->auditService->log(
                $this->getCurrentUserId(), 
                'admin', 
                'view_partial_results', 
                'elections', 
                $electionId
            );

            return Response::success($partialResults);

        } catch (Exception $e) {
            return Response::error(['message' => 'Failed to get partial results'], 500);
        }
    }

    public function getAuditReport()
    {
        try {
            $filters = [
                'date_from' => $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days')),
                'date_to' => $_GET['date_to'] ?? date('Y-m-d'),
                'user_type' => $_GET['user_type'] ?? '',
                'action' => $_GET['action'] ?? '',
                'resource' => $_GET['resource'] ?? '',
                'user_id' => $_GET['user_id'] ?? ''
            ];

            $page = $_GET['page'] ?? 1;
            $limit = $_GET['limit'] ?? 100;

            $auditLogs = $this->auditLogModel->getFilteredLogs($filters, $page, $limit);
            $summary = $this->auditLogModel->getAuditSummary($filters);

            $auditReport = [
                'report_type' => 'audit_log',
                'filters' => $filters,
                'summary' => $summary,
                'logs' => $auditLogs,
                'generated_at' => date('Y-m-d H:i:s'),
                'generated_by' => $this->getCurrentUserId()
            ];

            $this->auditService->log(
                $this->getCurrentUserId(), 
                'admin', 
                'generate_audit_report', 
                'audit_logs'
            );

            return Response::success($auditReport);

        } catch (Exception $e) {
            return Response::error(['message' => 'Failed to generate audit report'], 500);
        }
    }

    public function exportResults($electionId)
    {
        try {
            $format = $_GET['format'] ?? 'json';
            $reportType = $_GET['type'] ?? 'final';

            $election = $this->electionModel->findById($electionId);
            if (!$election) {
                return Response::error(['message' => 'Election not found'], 404);
            }

            switch ($reportType) {
                case 'zeresima':
                    $data = $this->generateZeresima($electionId);
                    break;
                case 'final':
                    $data = $this->generateFinalResults($electionId);
                    break;
                case 'partial':
                    $data = $this->getPartialResults($electionId);
                    break;
                default:
                    return Response::error(['message' => 'Invalid report type'], 422);
            }

            if (!$data || !isset($data['data'])) {
                return Response::error(['message' => 'Failed to generate report data'], 500);
            }

            $reportData = $data['data'];

            switch ($format) {
                case 'csv':
                    return $this->exportToCSV($reportData, $election, $reportType);
                case 'xml':
                    return $this->exportToXML($reportData, $election, $reportType);
                case 'txt':
                    return $this->exportToTXT($reportData, $election, $reportType);
                default:
                    return Response::success($reportData);
            }

        } catch (Exception $e) {
            return Response::error(['message' => 'Failed to export results'], 500);
        }
    }

    public function getVotingStatistics($electionId)
    {
        try {
            $election = $this->electionModel->findById($electionId);
            if (!$election) {
                return Response::error(['message' => 'Election not found'], 404);
            }

            $statistics = [
                'election_id' => $electionId,
                'election_title' => $election['title'],
                'total_registered_voters' => $this->voterModel->getEligibleCount(),
                'total_votes_cast' => $this->voteModel->getTotalVotesByElection($electionId),
                'unique_voters' => $this->voteModel->getUniqueVotersByElection($electionId),
                'votes_by_hour' => $this->voteModel->getVotesByHour($electionId),
                'votes_by_position' => $this->voteModel->getVotesByAllPositions($electionId),
                'device_statistics' => $this->voteModel->getDeviceStatistics($electionId),
                'ip_statistics' => $this->voteModel->getIPStatistics($electionId),
                'generated_at' => date('Y-m-d H:i:s')
            ];

            $this->auditService->log(
                $this->getCurrentUserId(), 
                'admin', 
                'view_voting_statistics', 
                'elections', 
                $electionId
            );

            return Response::success($statistics);

        } catch (Exception $e) {
            return Response::error(['message' => 'Failed to get voting statistics'], 500);
        }
    }

    private function exportToCSV($data, $election, $type)
    {
        $filename = "election_{$election['id']}_{$type}_" . date('Y-m-d_H-i-s') . ".csv";
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');
        
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        fputcsv($output, ['Election', $election['title']]);
        fputcsv($output, ['Report Type', ucfirst($type)]);
        fputcsv($output, ['Generated At', date('Y-m-d H:i:s')]);
        fputcsv($output, []);
        
        if (isset($data['positions'])) {
            fputcsv($output, ['Position', 'Candidate', 'Number', 'Party', 'Votes', 'Percentage']);
            
            foreach ($data['positions'] as $position) {
                if (isset($position['candidates'])) {
                    foreach ($position['candidates'] as $candidate) {
                        fputcsv($output, [
                            $position['title'] ?? $position['position_title'],
                            $candidate['name'],
                            $candidate['number'] ?? '',
                            $candidate['party'] ?? '',
                            $candidate['votes'],
                            ($candidate['percentage'] ?? 0) . '%'
                        ]);
                    }
                }
            }
        }
        
        fclose($output);
        exit;
    }

    private function exportToXML($data, $election, $type)
    {
        $filename = "election_{$election['id']}_{$type}_" . date('Y-m-d_H-i-s') . ".xml";
        
        header('Content-Type: application/xml; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $xml = new SimpleXMLElement('<election_report/>');
        $xml->addAttribute('type', $type);
        $xml->addChild('election_id', $election['id']);
        $xml->addChild('election_title', htmlspecialchars($election['title']));
        $xml->addChild('generated_at', date('Y-m-d H:i:s'));
        
        if (isset($data['positions'])) {
            $positions = $xml->addChild('positions');
            
            foreach ($data['positions'] as $positionData) {
                $position = $positions->addChild('position');
                $position->addChild('title', htmlspecialchars($positionData['title'] ?? $positionData['position_title']));
                
                if (isset($positionData['candidates'])) {
                    $candidates = $position->addChild('candidates');
                    
                    foreach ($positionData['candidates'] as $candidateData) {
                        $candidate = $candidates->addChild('candidate');
                        $candidate->addChild('name', htmlspecialchars($candidateData['name']));
                        $candidate->addChild('number', $candidateData['number'] ?? '');
                        $candidate->addChild('party', htmlspecialchars($candidateData['party'] ?? ''));
                        $candidate->addChild('votes', $candidateData['votes']);
                        $candidate->addChild('percentage', $candidateData['percentage'] ?? 0);
                    }
                }
            }
        }
        
        echo $xml->asXML();
        exit;
    }

    private function exportToTXT($data, $election, $type)
    {
        $filename = "election_{$election['id']}_{$type}_" . date('Y-m-d_H-i-s') . ".txt";
        
        header('Content-Type: text/plain; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = "ELECTION REPORT\n";
        $output .= "================\n\n";
        $output .= "Election: {$election['title']}\n";
        $output .= "Report Type: " . ucfirst($type) . "\n";
        $output .= "Generated: " . date('Y-m-d H:i:s') . "\n\n";
        
        if (isset($data['positions'])) {
            foreach ($data['positions'] as $position) {
                $output .= "POSITION: " . ($position['title'] ?? $position['position_title']) . "\n";
                $output .= str_repeat('-', 50) . "\n";
                
                if (isset($position['candidates'])) {
                    foreach ($position['candidates'] as $candidate) {
                        $output .= sprintf(
                            "%-30s | %s | %s | %d votes (%.2f%%)\n",
                            $candidate['name'],
                            $candidate['number'] ?? 'N/A',
                            $candidate['party'] ?? 'N/A',
                            $candidate['votes'],
                            $candidate['percentage'] ?? 0
                        );
                    }
                }
                $output .= "\n";
            }
        }
        
        echo $output;
        exit;
    }

    private function getCurrentUserId()
    {
        return $_SESSION['user_id'] ?? null;
    }
}