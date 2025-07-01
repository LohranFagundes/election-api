<?php
// src/models/Election.php

require_once __DIR__ . '/BaseModel.php';

class Election extends BaseModel
{
    protected $table = 'elections';
    protected $fillable = ['title', 'description', 'election_type', 'status', 'start_date', 'end_date', 'timezone', 'allow_blank_votes', 'allow_null_votes', 'require_justification', 'max_votes_per_voter', 'voting_method', 'results_visibility', 'created_by', 'updated_by'];

    public function getActive()
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE status = 'active' AND start_date <= NOW() AND end_date >= NOW()");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getScheduled()
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE status = 'scheduled' AND start_date > NOW()");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getCompleted()
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE status = 'completed'");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getByType($type)
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE election_type = ?");
        $stmt->execute([$type]);
        return $stmt->fetchAll();
    }

    public function getByStatus($status)
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE status = ?");
        $stmt->execute([$status]);
        return $stmt->fetchAll();
    }

    public function getPositions($electionId)
    {
        $stmt = $this->db->prepare("SELECT * FROM positions WHERE election_id = ? ORDER BY order_position ASC");
        $stmt->execute([$electionId]);
        return $stmt->fetchAll();
    }

    public function getAllCandidates($electionId)
    {
        $sql = "SELECT c.*, p.title as position_title FROM candidates c 
                JOIN positions p ON c.position_id = p.id 
                WHERE p.election_id = ? 
                ORDER BY p.order_position ASC, c.order_position ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$electionId]);
        return $stmt->fetchAll();
    }

    public function getEligibleVotersCount($electionId)
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM voters WHERE is_active = 1 AND is_verified = 1");
        $stmt->execute();
        return $stmt->fetch()['count'];
    }

    public function updateStatus($id, $status)
    {
        return $this->update($id, ['status' => $status]);
    }

    public function isActive($id)
    {
        $election = $this->findById($id);
        return $election && $election['status'] === 'active' && 
               strtotime($election['start_date']) <= time() && 
               strtotime($election['end_date']) >= time();
    }

    public function canVote($id)
    {
        return $this->isActive($id);
    }

    public function paginate($page = 1, $limit = 10, $status = '', $type = '')
    {
        $offset = ($page - 1) * $limit;
        
        $whereConditions = [];
        $params = [];
        
        if ($status) {
            $whereConditions[] = "status = :status";
            $params['status'] = $status;
        }
        
        if ($type) {
            $whereConditions[] = "election_type = :type";
            $params['type'] = $type;
        }
        
        $whereClause = empty($whereConditions) ? '' : 'WHERE ' . implode(' AND ', $whereConditions);
        
        $countSql = "SELECT COUNT(*) as total FROM {$this->table} {$whereClause}";
        $stmt = $this->db->prepare($countSql);
        $stmt->execute($params);
        $total = $stmt->fetch()['total'];
        
        $sql = "SELECT * FROM {$this->table} {$whereClause} ORDER BY created_at DESC LIMIT {$limit} OFFSET {$offset}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $data = $stmt->fetchAll();
        
        return [
            'data' => $data,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'pages' => ceil($total / $limit)
        ];
    }

    protected function getSearchableColumns()
    {
        return ['title', 'description'];
    }
}