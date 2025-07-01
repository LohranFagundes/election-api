<?php

use PHPUnit\Framework\TestCase;

class VoterTest extends TestCase
{
    private $voterModel;
    
    protected function setUp(): void
    {
        require_once __DIR__ . '/../../src/models/Voter.php';
        $this->voterModel = new Voter();
    }
    
    public function testCreateVoter()
    {
        $voterData = [
            'name' => 'Test Voter',
            'email' => 'test@voter.com',
            'password' => password_hash('password123', PASSWORD_DEFAULT),
            'cpf' => '12345678901',
            'birth_date' => '1990-01-01',
            'phone' => '(11) 99999-9999',
            'vote_weight' => 1.0
        ];
        
        $voterId = $this->voterModel->create($voterData);
        
        $this->assertIsNumeric($voterId);
        $this->assertGreaterThan(0, $voterId);
    }
    
    public function testFindVoterByEmail()
    {
        $voter = $this->voterModel->findByEmail('test@voter.com');
        
        $this->assertIsArray($voter);
        $this->assertArrayHasKey('email', $voter);
    }
    
    public function testFindVoterByCpf()
    {
        $voter = $this->voterModel->findByCpf('12345678901');
        
        $this->assertIsArray($voter);
        $this->assertArrayHasKey('cpf', $voter);
    }
    
    public function testEmailExists()
    {
        $exists = $this->voterModel->emailExists('test@voter.com');
        
        $this->assertIsBool($exists);
    }
    
    public function testCpfExists()
    {
        $exists = $this->voterModel->cpfExists('12345678901');
        
        $this->assertIsBool($exists);
    }
    
    public function testGetEligibleVoters()
    {
        $eligibleVoters = $this->voterModel->getEligibleVoters();
        
        $this->assertIsArray($eligibleVoters);
    }
    
    public function testVerifyVoter()
    {
        $result = $this->voterModel->verify(1);
        
        $this->assertIsBool($result);
    }
}