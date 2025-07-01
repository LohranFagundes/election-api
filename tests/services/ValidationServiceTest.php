<?php

use PHPUnit\Framework\TestCase;

class ValidationServiceTest extends TestCase
{
    private $validationService;
    
    protected function setUp(): void
    {
        require_once __DIR__ . '/../../src/services/ValidationService.php';
        $this->validationService = new ValidationService();
    }
    
    public function testValidateLoginSuccess()
    {
        $data = [
            'email' => 'test@example.com',
            'password' => 'password123'
        ];
        
        $result = $this->validationService->validateLogin($data);
        
        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }
    
    public function testValidateLoginFailure()
    {
        $data = [
            'email' => 'invalid-email',
            'password' => ''
        ];
        
        $result = $this->validationService->validateLogin($data);
        
        $this->assertFalse($result['valid']);
        $this->assertNotEmpty($result['errors']);
    }
    
    public function testValidateAdminSuccess()
    {
        $data = [
            'name' => 'Test Admin',
            'email' => 'admin@test.com',
            'password' => 'password123',
            'role' => 'admin'
        ];
        
        $result = $this->validationService->validateAdmin($data);
        
        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }
    
    public function testValidateVoterSuccess()
    {
        $data = [
            'name' => 'Test Voter',
            'email' => 'voter@test.com',
            'password' => 'password123',
            'cpf' => '12345678901',
            'birth_date' => '1990-01-01'
        ];
        
        $result = $this->validationService->validateVoter($data);
        
        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }
    
    public function testValidateElectionSuccess()
    {
        $data = [
            'title' => 'Test Election',
            'start_date' => '2025-02-01 08:00:00',
            'end_date' => '2025-02-01 18:00:00'
        ];
        
        $result = $this->validationService->validateElection($data);
        
        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }
    
    public function testSanitizeInput()
    {
        $input = '<script>alert("xss")</script>Test';
        $sanitized = $this->validationService->sanitizeInput($input);
        
        $this->assertStringNotContainsString('<script>', $sanitized);
    }
}