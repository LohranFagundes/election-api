<?php

use PHPUnit\Framework\TestCase;

class ElectionTest extends TestCase
{
    private $electionModel;
    
    protected function setUp(): void
    {
        require_once __DIR__ . '/../../src/models/Election.php';
        $this->electionModel = new Election();
    }
    
    public function testCreateElection()
    {
        $electionData = [
            'title' => 'Test Election',
            'description' => 'Test Description',
            'election_type' => 'internal',
            'status' => 'draft',
            'start_date' => '2025-02-01 08:00:00',
            'end_date' => '2025-02-01 18:00:00',
            'created_by' => 1
        ];
        
        $electionId = $this->electionModel->create($electionData);
        
        $this->assertIsNumeric($electionId);
        $this->assertGreaterThan(0, $electionId);
    }
    
    public function testFindElectionById()
    {
        $election = $this->electionModel->findById(1);
        
        $this->assertIsArray($election);
        $this->assertArrayHasKey('id', $election);
        $this->assertArrayHasKey('title', $election);
    }
    
    public function testGetActiveElections()
    {
        $activeElections = $this->electionModel->getActive();
        
        $this->assertIsArray($activeElections);
    }
    
    public function testGetElectionsByType()
    {
        $internalElections = $this->electionModel->getByType('internal');
        
        $this->assertIsArray($internalElections);
    }
    
    public function testUpdateElection()
    {
        $updateData = [
            'title' => 'Updated Election Title'
        ];
        
        $result = $this->electionModel->update(1, $updateData);
        
        $this->assertTrue($result);
    }
    
    public function testDeleteElection()
    {
        $result = $this->electionModel->delete(999);
        
        $this->assertIsBool($result);
    }
    
    public function testPaginateElections()
    {
        $result = $this->electionModel->paginate(1, 10);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('page', $result);
        $this->assertArrayHasKey('pages', $result);
    }
}
