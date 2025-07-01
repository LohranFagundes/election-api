<?php

use PHPUnit\Framework\TestCase;

class VoteControllerTest extends TestCase
{
    private $voteController;
    
    protected function setUp(): void
    {
        require_once __DIR__ . '/../../src/controllers/VoteController.php';
        $this->voteController = new VoteController();
        
        $_SESSION['user_id'] = 5;
        $_SESSION['role'] = 'voter';
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['HTTP_USER_AGENT'] = 'PHPUnit Test';
    }
    
    public function testCastVoteSuccess()
    {
        $voteData = [
            'election_id' => 1,
            'position_id' => 1,
            'candidate_id' => 1,
            'vote_type' => 'candidate'
        ];
        
        file_put_contents('php://input', json_encode($voteData));
        
        $result = $this->voteController->castVote();
        
        $this->assertIsArray($result);
    }
    
    public function testCastBlankVote()
    {
        $voteData = [
            'election_id' => 1,
            'position_id' => 1,
            'vote_type' => 'blank'
        ];
        
        file_put_contents('php://input', json_encode($voteData));
        
        $result = $this->voteController->castVote();
        
        $this->assertIsArray($result);
    }
    
    public function testGetVotingHistory()
    {
        $result = $this->voteController->getVotingHistory();
        
        $this->assertIsArray($result);
        $this->assertTrue($result['success']);
    }
    
    public function testCheckVotingStatus()
    {
        $result = $this->voteController->checkVotingStatus(1);
        
        $this->assertIsArray($result);
    }
    
    public function testGetElectionResults()
    {
        $result = $this->voteController->getElectionResults(1);
        
        $this->assertIsArray($result);
    }
    
    protected function tearDown(): void
    {
        $_SESSION = [];
        $_SERVER = [];
    }
}
