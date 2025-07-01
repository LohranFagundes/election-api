<?php

use PHPUnit\Framework\TestCase;

class ElectionControllerTest extends TestCase
{
    private $electionController;
    
    protected function setUp(): void
    {
        require_once __DIR__ . '/../../src/controllers/ElectionController.php';
        $this->electionController = new ElectionController();
        
        $_SESSION['user_id'] = 1;
        $_SESSION['role'] = 'admin';
    }
    
    public function testIndexReturnsElections()
    {
        $result = $this->electionController->index();
        
        $this->assertIsArray($result);
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
    }
    
    public function testCreateElection()
    {
        $electionData = [
            'title' => 'Test Election',
            'description' => 'Test Description',
            'election_type' => 'internal',
            'start_date' => '2025-02-01 08:00:00',
            'end_date' => '2025-02-01 18:00:00'
        ];
        
        file_put_contents('php://input', json_encode($electionData));
        
        $result = $this->electionController->create();
        
        $this->assertIsArray($result);
        $this->assertTrue($result['success']);
    }
    
    public function testShowElection()
    {
        $result = $this->electionController->show(1);
        
        $this->assertIsArray($result);
    }
    
    public function testUpdateElection()
    {
        $updateData = [
            'title' => 'Updated Election Title'
        ];
        
        file_put_contents('php://input', json_encode($updateData));
        
        $result = $this->electionController->update(1);
        
        $this->assertIsArray($result);
    }
    
    public function testDeleteElection()
    {
        $result = $this->electionController->delete(999);
        
        $this->assertIsArray($result);
    }
    
    protected function tearDown(): void
    {
        $_SESSION = [];
    }
}