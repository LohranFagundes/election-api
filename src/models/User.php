<?php
// src/models/User.php

require_once __DIR__ . '/BaseModel.php';

class User extends BaseModel
{
    protected $table = 'users';
    protected $fillable = ['name', 'email', 'password', 'role', 'is_active'];

    public function findByEmail($email)
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch();
    }

    public function emailExists($email, $excludeId = null)
    {
        return $this->exists('email', $email, $excludeId);
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

    public function getActiveUsers()
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE is_active = 1");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getUsersByRole($role)
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE role = ?");
        $stmt->execute([$role]);
        return $stmt->fetchAll();
    }
}
