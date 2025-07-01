<?php

use PHPUnit\Framework\TestCase;

class AuthControllerTest extends TestCase
{
    private $authController;
    
    protected function setUp(): void
    {
        require_once __DIR__ . '/../../src/controllers/AuthController.php';
        $this->authController = new AuthController();
        
        $_SESSION = [];
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['HTTP_USER_AGENT'] = 'PHPUnit Test';
    }
    
    public function testAdminLoginSuccess()
    {
        $_POST['email'] = 'admin@election.com';
        $_POST['password'] = 'password123';
        
        $result = $this->authController->adminLogin();
        
        $this->assertIsArray($result);
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('token', $result['data']);
    }
    
    public function testAdminLoginFailure()
    {
        $_POST['email'] = 'admin@election.com';
        $_POST['password'] = 'wrongpassword';
        
        $result = $this->authController->adminLogin();
        
        $this->assertIsArray($result);
        $this->assertFalse($result['success']);
        $this->assertEquals(401, http_response_code());
    }
    
    public function testVoterLoginSuccess()
    {
        $_POST['email'] = 'voter@election.com';
        $_POST['password'] = 'password123';
        
        $result = $this->authController->voterLogin();
        
        $this->assertIsArray($result);
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('token', $result['data']);
    }
    
    public function testTokenValidation()
    {
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer valid_test_token';
        
        $result = $this->authController->validateToken();
        
        $this->assertIsArray($result);
    }
    
    public function testLogout()
    {
        $_SESSION['user_id'] = 1;
        $_SESSION['role'] = 'admin';
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer test_token';
        
        $result = $this->authController->logout();
        
        $this->assertIsArray($result);
        $this->assertTrue($result['success']);
    }
    
    protected function tearDown(): void
    {
        $_POST = [];
        $_SERVER = [];
        $_SESSION = [];
    }
}