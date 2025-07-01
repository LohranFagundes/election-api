<?php

use PHPUnit\Framework\TestCase;

class HashServiceTest extends TestCase
{
    private $hashService;
    
    protected function setUp(): void
    {
        require_once __DIR__ . '/../../src/services/HashService.php';
        $this->hashService = new HashService();
    }
    
    public function testGenerateCandidateHash()
    {
        $hash = $this->hashService->generateCandidateHash(1);
        
        $this->assertIsString($hash);
        $this->assertEquals(64, strlen($hash));
    }
    
    public function testGenerateVoteHash()
    {
        $hash = $this->hashService->generateVoteHash(1, 1, 1, 'test_candidate_hash');
        
        $this->assertIsString($hash);
        $this->assertEquals(64, strlen($hash));
    }
    
    public function testGenerateBlankVoteHash()
    {
        $hash = $this->hashService->generateBlankVoteHash();
        
        $this->assertIsString($hash);
        $this->assertEquals(64, strlen($hash));
    }
    
    public function testGenerateNullVoteHash()
    {
        $hash = $this->hashService->generateNullVoteHash();
        
        $this->assertIsString($hash);
        $this->assertEquals(64, strlen($hash));
    }
    
    public function testHashPassword()
    {
        $password = 'password123';
        $hash = $this->hashService->hashPassword($password);
        
        $this->assertIsString($hash);
        $this->assertTrue(password_verify($password, $hash));
    }
    
    public function testVerifyPassword()
    {
        $password = 'password123';
        $hash = password_hash($password, PASSWORD_DEFAULT);
        
        $result = $this->hashService->verifyPassword($password, $hash);
        
        $this->assertTrue($result);
    }
    
    public function testGenerateSecureToken()
    {
        $token = $this->hashService->generateSecureToken();
        
        $this->assertIsString($token);
        $this->assertEquals(64, strlen($token));
    }
    
    public function testGenerateOTP()
    {
        $otp = $this->hashService->generateOTP();
        
        $this->assertIsString($otp);
        $this->assertEquals(6, strlen($otp));
        $this->assertTrue(is_numeric($otp));
    }
}