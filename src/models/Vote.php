<?php
// src/models/Vote.php

require_once __DIR__ . '/BaseModel.php';

class Vote extends BaseModel
{
    protected $table = 'votes';
    protected $fillable = ['election_id', 'position_id', 'voter_id', 'candidate_hash', 'vote_hash', 'vote_weight', 'vote_type', 'ip_address', 'user_agent', 'session_id'];

    public function hasVoted($voterId, $electionId, $positionId)
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM {$this->table} WHERE voter_id = ? AND election_id = ? AND position_id = ?");
        $stmt->execute([$voterId, $electionId, $positionId]);
        return $stmt->fetch()['count'] > 0;
    }

    public function getVoterHistory($voterId)
    {
        $sql = "SELECT v.*, e.title as election_title, p.title as position_title 
                FROM {$this->table} v 
                JOIN elections e ON v.election_id = e.id 
                JOIN positions p ON v.position_id = p.id 
                WHERE v.voter_id = ? 
                ORDER BY v.created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$voterId]);
        return $stmt->fetchAll();
    }

    public function getVotingStatus($voterId, $electionId)
    {
        $sql = "SELECT p.id, p.title, 
                CASE WHEN v.id IS NOT NULL THEN 1 ELSE 0 END as has_voted 
                FROM positions p 
                LEFT JOIN {$this->table} v ON p.id = v.position_id AND v.voter_id = ? AND v.election_id = ?
                WHERE p.election_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$voterId, $electionId, $electionId]);
        return $stmt->fetchAll();
    }

    public function getElectionResults($electionId)
    {
        $sql = "SELECT p.id as position_id, p.title as position_title,
                COUNT(v.id) as total_votes,
                SUM(CASE WHEN v.vote_type = 'blank' THEN 1 ELSE 0 END) as blank_votes,
                SUM(CASE WHEN v.vote_type = 'null' THEN 1 ELSE 0 END) as null_votes
                FROM positions p
                LEFT JOIN {$this->table} v ON p.id = v.position_id
                WHERE p.election_id = ?
                GROUP BY p.id, p.title
                ORDER BY p.order_position";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$electionId]);
        return $stmt->fetchAll();
    }

    public function getTotalVotesByElection($electionId)
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM {$this->table} WHERE election_id = ?");
        $stmt->execute([$electionId]);
        return $stmt->fetch()['count'];
    }

    public function getVoterTurnout($electionId)
    {
        $votersStmt = $this->db->prepare("SELECT COUNT(*) as count FROM voters WHERE is_active = 1 AND is_verified = 1");
        $votersStmt->execute();
        $totalVoters = $votersStmt->fetch()['count'];

        $votedStmt = $this->db->prepare("SELECT COUNT(DISTINCT voter_id) as count FROM {$this->table} WHERE election_id = ?");
        $votedStmt->execute([$electionId]);
        $votedCount = $votedStmt->fetch()['count'];

        return [
            'eligible' => $totalVoters,
            'voted' => $votedCount,
            'percentage' => $totalVoters > 0 ? round(($votedCount / $totalVoters) * 100, 2) : 0
        ];
    }

    public function getBlankVotesByElection($electionId)
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM {$this->table} WHERE election_id = ? AND vote_type = 'blank'");
        $stmt->execute([$electionId]);
        return $stmt->fetch()['count'];
    }

    public function getNullVotesByElection($electionId)
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM {$this->table} WHERE election_id = ? AND vote_type = 'null'");
        $stmt->execute([$electionId]);
        return $stmt->fetch()['count'];
    }

    public function getVotesByPosition($positionId)
    {
        $sql = "SELECT candidate_hash, COUNT(*) as vote_count, SUM(vote_weight) as weighted_votes
                FROM {$this->table} 
                WHERE position_id = ? AND vote_type = 'candidate'
                GROUP BY candidate_hash
                ORDER BY weighted_votes DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$positionId]);
        return $stmt->fetchAll();
    }

    public function getBlankVotesByPosition($positionId)
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM {$this->table} WHERE position_id = ? AND vote_type = 'blank'");
        $stmt->execute([$positionId]);
        return $stmt->fetch()['count'];
    }

    public function getNullVotesByPosition($positionId)
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM {$this->table} WHERE position_id = ? AND vote_type = 'null'");
        $stmt->execute([$positionId]);
        return $stmt->fetch()['count'];
    }

    public function getCandidateVotes($candidateId)
    {
        $candidateHash = $this->generateCandidateHash($candidateId);
        $stmt = $this->db->prepare("SELECT COUNT(*) as vote_count, SUM(vote_weight) as weighted_votes FROM {$this->table} WHERE candidate_hash = ?");
        $stmt->execute([$candidateHash]);
        return $stmt->fetch();
    }

    public function getDetailedResults($electionId)
    {
        $positions = [];
        $positionsStmt = $this->db->prepare("SELECT * FROM positions WHERE election_id = ? ORDER BY order_position");
        $positionsStmt->execute([$electionId]);
        
        while ($position = $positionsStmt->fetch()) {
            $candidates = [];
            $candidatesStmt = $this->db->prepare("SELECT * FROM candidates WHERE position_id = ? ORDER BY order_position");
            $candidatesStmt->execute([$position['id']]);
            
            while ($candidate = $candidatesStmt->fetch()) {
                $votes = $this->getCandidateVotes($candidate['id']);
                $candidates[] = array_merge($candidate, $votes);
            }
            
            $position['candidates'] = $candidates;
            $position['blank_votes'] = $this->getBlankVotesByPosition($position['id']);
            $position['null_votes'] = $this->getNullVotesByPosition($position['id']);
            $positions[] = $position;
        }
        
        return $positions;
    }

    public function getElectionStatistics($electionId)
    {
        $totalVotes = $this->getTotalVotesByElection($electionId);
        $turnout = $this->getVoterTurnout($electionId);
        $blankVotes = $this->getBlankVotesByElection($electionId);
        $nullVotes = $this->getNullVotesByElection($electionId);
        
        return [
            'total_votes' => $totalVotes,
            'voter_turnout' => $turnout,
            'blank_votes' => $blankVotes,
            'null_votes' => $nullVotes,
            'valid_votes' => $totalVotes - $blankVotes - $nullVotes
        ];
    }

    public function getUniqueVotersByElection($electionId)
    {
        $stmt = $this->db->prepare("SELECT COUNT(DISTINCT voter_id) as count FROM {$this->table} WHERE election_id = ?");
        $stmt->execute([$electionId]);
        return $stmt->fetch()['count'];
    }

    public function getVotesByHour($electionId)
    {
        $sql = "SELECT HOUR(created_at) as hour, COUNT(*) as count 
                FROM {$this->table} 
                WHERE election_id = ? 
                GROUP BY HOUR(created_at) 
                ORDER BY hour";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$electionId]);
        return $stmt->fetchAll();
    }

    public function getVotesByAllPositions($electionId)
    {
        $sql = "SELECT p.title, COUNT(v.id) as vote_count
                FROM positions p
                LEFT JOIN {$this->table} v ON p.id = v.position_id
                WHERE p.election_id = ?
                GROUP BY p.id, p.title
                ORDER BY p.order_position";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$electionId]);
        return $stmt->fetchAll();
    }

    public function getDeviceStatistics($electionId)
    {
        $sql = "SELECT 
                CASE 
                    WHEN user_agent LIKE '%Mobile%' OR user_agent LIKE '%Android%' OR user_agent LIKE '%iPhone%' THEN 'Mobile'
                    WHEN user_agent LIKE '%Tablet%' OR user_agent LIKE '%iPad%' THEN 'Tablet'
                    ELSE 'Desktop'
                END as device_type,
                COUNT(*) as count
                FROM {$this->table}
                WHERE election_id = ?
                GROUP BY device_type";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$electionId]);
        return $stmt->fetchAll();
    }

    public function getIPStatistics($electionId)
    {
        $sql = "SELECT ip_address, COUNT(*) as count
                FROM {$this->table}
                WHERE election_id = ?
                GROUP BY ip_address
                ORDER BY count DESC
                LIMIT 10";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$electionId]);
        return $stmt->fetchAll();
    }

    public function createSession($data)
    {
        $columns = implode(',', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        
        $sql = "INSERT INTO vote_sessions ({$columns}) VALUES ({$placeholders})";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($data);
        
        return $this->db->lastInsertId();
    }

    private function generateCandidateHash($candidateId)
    {
        return hash('sha256', 'candidate_' . $candidateId . '_' . date('Y-m-d'));
    }

    protected function getSearchableColumns()
    {
        return [];
    }
}