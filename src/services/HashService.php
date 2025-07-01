<?php
// src/services/HashService.php

class HashService
{
    private $salt;

    public function __construct()
    {
        $this->salt = $_ENV['HASH_SALT'] ?? 'election_system_salt_2024';
    }

    public function generateCandidateHash($candidateId)
    {
        $data = 'candidate_' . $candidateId . '_' . $this->salt . '_' . date('Y-m-d');
        return hash('sha256', $data);
    }

    public function generateVoteHash($voterId, $electionId, $positionId, $candidateHash)
    {
        $data = $voterId . '_' . $electionId . '_' . $positionId . '_' . $candidateHash . '_' . $this->salt . '_' . time();
        return hash('sha256', $data);
    }

    public function generateBlankVoteHash()
    {
        $data = 'blank_vote_' . $this->salt . '_' . date('Y-m-d');
        return hash('sha256', $data);
    }

    public function generateNullVoteHash()
    {
        $data = 'null_vote_' . $this->salt . '_' . date('Y-m-d');
        return hash('sha256', $data);
    }

    public function generateSessionHash($voterId, $electionId, $ipAddress, $userAgent)
    {
        $data = $voterId . '_' . $electionId . '_' . $ipAddress . '_' . $userAgent . '_' . $this->salt . '_' . time();
        return hash('sha256', $data);
    }

    public function hashPassword($password)
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    public function verifyPassword($password, $hash)
    {
        return password_verify($password, $hash);
    }

    public function generateSecureToken($length = 32)
    {
        return bin2hex(random_bytes($length));
    }

    public function generateOTP($length = 6)
    {
        $digits = '0123456789';
        $otp = '';
        for ($i = 0; $i < $length; $i++) {
            $otp .= $digits[random_int(0, strlen($digits) - 1)];
        }
        return $otp;
    }

    public function hashSensitiveData($data)
    {
        return hash('sha256', $data . $this->salt);
    }

    public function generateFileHash($filePath)
    {
        if (!file_exists($filePath)) {
            return null;
        }
        return hash_file('sha256', $filePath);
    }

    public function generateDataIntegrityHash($data)
    {
        if (is_array($data)) {
            $data = json_encode($data);
        }
        return hash('sha256', $data . $this->salt . date('Y-m-d'));
    }

    public function verifyDataIntegrity($data, $hash)
    {
        return $this->generateDataIntegrityHash($data) === $hash;
    }
}