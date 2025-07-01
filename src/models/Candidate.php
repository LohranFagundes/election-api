<?php
// src/models/Candidate.php

require_once __DIR__ . '/BaseModel.php';

class Candidate extends BaseModel
{
    protected $table = 'candidates';
    protected $fillable = ['position_id', 'name', 'nickname', 'description', 'photo', 'photo_filename', 'photo_mime_type', 'number', 'party', 'coalition', 'is_active', 'order_position'];

    public function getByPosition($positionId)
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE position_id = ? ORDER BY order_position ASC");
        $stmt->execute([$positionId]);
        return $stmt->fetchAll();
    }

    public function getActiveByPosition($positionId)
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE position_id = ? AND is_active = 1 ORDER BY order_position ASC");
        $stmt->execute([$positionId]);
        return $stmt->fetchAll();
    }

    public function getByNumber($number, $positionId)
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE number = ? AND position_id = ?");
        $stmt->execute([$number, $positionId]);
        return $stmt->fetch();
    }

    public function numberExists($number, $positionId, $excludeId = null)
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE number = ? AND position_id = ?";
        $params = [$number, $positionId];
        
        if ($excludeId) {
            $sql .= " AND {$this->primaryKey} != ?";
            $params[] = $excludeId;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetch()['count'] > 0;
    }

    public function getVoteCount($candidateId)
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM votes WHERE candidate_hash = ?");
        $candidateHash = $this->generateCandidateHash($candidateId);
        $stmt->execute([$candidateHash]);
        return $stmt->fetch()['count'];
    }

    public function getWeightedVoteCount($candidateId)
    {
        $stmt = $this->db->prepare("SELECT SUM(vote_weight) as total FROM votes WHERE candidate_hash = ?");
        $candidateHash = $this->generateCandidateHash($candidateId);
        $stmt->execute([$candidateHash]);
        return $stmt->fetch()['total'] ?? 0;
    }

    public function updatePhoto($id, $photoData, $filename, $mimeType)
    {
        return $this->update($id, [
            'photo' => $photoData,
            'photo_filename' => $filename,
            'photo_mime_type' => $mimeType
        ]);
    }

    public function removePhoto($id)
    {
        return $this->update($id, [
            'photo' => null,
            'photo_filename' => null,
            'photo_mime_type' => null
        ]);
    }

    public function reorder($candidates)
    {
        $this->db->beginTransaction();
        
        try {
            foreach ($candidates as $candidate) {
                $this->update($candidate['id'], ['order_position' => $candidate['order_position']]);
            }
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    public function getNextOrderPosition($positionId)
    {
        $stmt = $this->db->prepare("SELECT MAX(order_position) as max_order FROM {$this->table} WHERE position_id = ?");
        $stmt->execute([$positionId]);
        $result = $stmt->fetch();
        return ($result['max_order'] ?? 0) + 1;
    }

    private function generateCandidateHash($candidateId)
    {
        return hash('sha256', 'candidate_' . $candidateId . '_' . date('Y-m-d'));
    }

    protected function getSearchableColumns()
    {
        return ['name', 'nickname', 'number', 'party'];
    }
}
