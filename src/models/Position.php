<?php
// src/models/Position.php

require_once __DIR__ . '/BaseModel.php';

class Position extends BaseModel
{
    protected $table = 'positions';
    protected $fillable = ['election_id', 'title', 'description', 'order_position', 'max_candidates', 'min_votes', 'max_votes', 'is_active'];

    public function getByElection($electionId)
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE election_id = ? ORDER BY order_position ASC");
        $stmt->execute([$electionId]);
        return $stmt->fetchAll();
    }

    public function getCandidates($positionId)
    {
        $stmt = $this->db->prepare("SELECT * FROM candidates WHERE position_id = ? AND is_active = 1 ORDER BY order_position ASC");
        $stmt->execute([$positionId]);
        return $stmt->fetchAll();
    }

    public function getActiveCandidates($positionId)
    {
        $stmt = $this->db->prepare("SELECT * FROM candidates WHERE position_id = ? AND is_active = 1 ORDER BY order_position ASC");
        $stmt->execute([$positionId]);
        return $stmt->fetchAll();
    }

    public function getCandidateCount($positionId)
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM candidates WHERE position_id = ? AND is_active = 1");
        $stmt->execute([$positionId]);
        return $stmt->fetch()['count'];
    }

    public function getVoteCount($positionId)
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM votes WHERE position_id = ?");
        $stmt->execute([$positionId]);
        return $stmt->fetch()['count'];
    }

    public function reorder($positions)
    {
        $this->db->beginTransaction();
        
        try {
            foreach ($positions as $position) {
                $this->update($position['id'], ['order_position' => $position['order_position']]);
            }
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    public function getNextOrderPosition($electionId)
    {
        $stmt = $this->db->prepare("SELECT MAX(order_position) as max_order FROM {$this->table} WHERE election_id = ?");
        $stmt->execute([$electionId]);
        $result = $stmt->fetch();
        return ($result['max_order'] ?? 0) + 1;
    }

    protected function getSearchableColumns()
    {
        return ['title', 'description'];
    }
}