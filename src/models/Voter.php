<?php
// src/models/Voter.php

require_once __DIR__ . '/BaseModel.php';

class Voter extends BaseModel
{
    protected $table = 'voters';
    protected $fillable = ['name', 'email', 'password', 'cpf', 'birth_date', 'phone', 'vote_weight', 'is_active', 'is_verified'];

    public function findByEmail($email)
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch();
    }

    public function findByCpf($cpf)
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE cpf = ?");
        $stmt->execute([$cpf]);
        return $stmt->fetch();
    }

    public function emailExists($email, $excludeId = null)
    {
        return $this->exists('email', $email, $excludeId);
    }

    public function cpfExists($cpf, $excludeId = null)
    {
        return $this->exists('cpf', $cpf, $excludeId);
    }

    public function updateLastLogin($id)
    {
        $sql = "UPDATE {$this->table} SET last_login_at = ?, last_login_ip = ? WHERE {$this->primaryKey} = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            date('Y-m-d H:i:s'),
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $id
        ]);
    }

    public function getEligibleVoters()
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE is_active = 1 AND is_verified = 1");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getEligibleCount()
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM {$this->table} WHERE is_active = 1 AND is_verified = 1");
        $stmt->execute();
        return $stmt->fetch()['count'];
    }

    public function getElectionEligibleCount($electionId)
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM {$this->table} WHERE is_active = 1 AND is_verified = 1");
        $stmt->execute();
        return $stmt->fetch()['count'];
    }

    public function hasVotedInElection($voterId, $electionId)
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM votes WHERE voter_id = ? AND election_id = ?");
        $stmt->execute([$voterId, $electionId]);
        return $stmt->fetch()['count'] > 0;
    }

    public function verify($id)
    {
        return $this->update($id, [
            'is_verified' => 1,
            'email_verified_at' => date('Y-m-d H:i:s'),
            'verification_token' => null
        ]);
    }

    public function activate($id)
    {
        return $this->update($id, ['is_active' => 1]);
    }

    public function deactivate($id)
    {
        return $this->update($id, ['is_active' => 0]);
    }

    protected function getSearchableColumns()
    {
        return ['name', 'email', 'cpf'];
    }
}
