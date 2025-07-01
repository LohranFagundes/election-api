<?php

use PHPUnit\Framework\TestCase;

class AuthServiceTest extends TestCase
{
    private $authService;
    
    protected function setUp(): void
    {
        require_once __DIR__ . '/../../src/services/AuthService.php';
        $this->authService = new AuthService();
    }
    
    public function testValidateAdminCredentials()
    {
        $result = $this->authService->validateAdminCredentials('admin@election.com', 'password123');
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('email', $result);
    }
    
    public function testValidateVoterCredentials()
    {
        $result = $this->authService->validateVoterCredentials('voter@election.com', 'password123');
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertArrayHasKey('email', $result);
    }
    
    public function testInvalidCredentials()
    {
        $result = $this->authService->validateAdminCredentials('admin@election.com', 'wrongpassword');
        
        $this->assertFalse($result);
    }
    
    public function testUpdateLastLogin()
    {
        $result = $this->authService->updateLastLogin(1, 'admin');
        
        $this->assertIsBool($result);
    }
    
    public function testIsUserActive()
    {
        $result = $this->authService->isUserActive(1, 'admin');
        
        $this->assertIsBool($result);
    }
    
    public function testChangePassword()
    {
        $result = $this->authService->changePassword(1, 'admin', 'password123', 'newpassword123');
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('message', $result);
    }
}
