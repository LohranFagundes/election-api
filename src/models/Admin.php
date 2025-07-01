<?php
// src/models/Admin.php

require_once __DIR__ . '/BaseModel.php';

class Admin extends BaseModel
{
    protected $table = 'admins';
    protected $fillable = ['name', 'email', 'password', 'role', 'permissions', 'is_active'];

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

    public function getByRole($role)
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE role = ? AND is_active = 1");
        $stmt->execute([$role]);
        return $stmt->fetchAll();
    }

    public function getSuperAdmins()
    {
        return $this->getByRole('super_admin');
    }

    public function getAdmins()
    {
        return $this->getByRole('admin');
    }

    public function getModerators()
    {
        return $this->getByRole('moderator');
    }

    public function hasPermission($adminId, $permission)
    {
        $admin = $this->findById($adminId);
        if (!$admin) return false;
        
        $permissions = json_decode($admin['permissions'], true) ?? [];
        
        if (in_array('*', $permissions)) return true;
        if (in_array($permission, $permissions)) return true;
        
        $permissionParts = explode('.', $permission);
        $wildcardPermission = $permissionParts[0] . '.*';
        
        return in_array($wildcardPermission, $permissions);
    }
}