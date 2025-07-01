<?php
// src/services/AuthService.php

require_once __DIR__ . '/../models/Admin.php';
require_once __DIR__ . '/../models/Voter.php';

class AuthService
{
    private $adminModel;
    private $voterModel;

    public function __construct()
    {
        $this->adminModel = new Admin();
        $this->voterModel = new Voter();
    }

    public function validateAdminCredentials($email, $password)
    {
        $admin = $this->adminModel->findByEmail($email);
        
        if (!$admin) {
            return false;
        }

        if (!password_verify($password, $admin['password'])) {
            return false;
        }

        if (!$admin['is_active']) {
            return false;
        }

        return $admin;
    }

    public function validateVoterCredentials($email, $password)
    {
        $voter = $this->voterModel->findByEmail($email);
        
        if (!$voter) {
            return false;
        }

        if (!password_verify($password, $voter['password'])) {
            return false;
        }

        return $voter;
    }

    public function updateLastLogin($userId, $userType)
    {
        if ($userType === 'admin') {
            return $this->adminModel->updateLastLogin($userId);
        } else {
            return $this->voterModel->updateLastLogin($userId);
        }
    }

    public function getUserById($userId, $userType)
    {
        if ($userType === 'admin') {
            return $this->adminModel->findById($userId);
        } else {
            return $this->voterModel->findById($userId);
        }
    }

    public function isUserActive($userId, $userType)
    {
        $user = $this->getUserById($userId, $userType);
        
        if (!$user) {
            return false;
        }

        if ($userType === 'voter') {
            return $user['is_active'] && $user['is_verified'];
        }

        return $user['is_active'];
    }

    public function changePassword($userId, $userType, $currentPassword, $newPassword)
    {
        $user = $this->getUserById($userId, $userType);
        
        if (!$user) {
            return ['success' => false, 'message' => 'User not found'];
        }

        if (!password_verify($currentPassword, $user['password'])) {
            return ['success' => false, 'message' => 'Current password is incorrect'];
        }

        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        
        if ($userType === 'admin') {
            $result = $this->adminModel->update($userId, ['password' => $hashedPassword]);
        } else {
            $result = $this->voterModel->update($userId, ['password' => $hashedPassword]);
        }

        return ['success' => $result, 'message' => $result ? 'Password changed successfully' : 'Failed to change password'];
    }

    public function generatePasswordResetToken($email, $userType)
    {
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        if ($userType === 'admin') {
            $user = $this->adminModel->findByEmail($email);
            if ($user) {
                $this->adminModel->update($user['id'], [
                    'remember_token' => $token,
                    'remember_token_expires' => $expires
                ]);
            }
        } else {
            $user = $this->voterModel->findByEmail($email);
            if ($user) {
                $this->voterModel->update($user['id'], [
                    'remember_token' => $token,
                    'remember_token_expires' => $expires
                ]);
            }
        }
        
        return $user ? $token : null;
    }

    public function resetPassword($token, $newPassword, $userType)
    {
        if ($userType === 'admin') {
            $admin = $this->adminModel->findByToken($token);
            if ($admin && strtotime($admin['remember_token_expires']) > time()) {
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                return $this->adminModel->update($admin['id'], [
                    'password' => $hashedPassword,
                    'remember_token' => null,
                    'remember_token_expires' => null
                ]);
            }
        } else {
            $voter = $this->voterModel->findByToken($token);
            if ($voter && strtotime($voter['remember_token_expires']) > time()) {
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                return $this->voterModel->update($voter['id'], [
                    'password' => $hashedPassword,
                    'remember_token' => null,
                    'remember_token_expires' => null
                ]);
            }
        }
        
        return false;
    }
}