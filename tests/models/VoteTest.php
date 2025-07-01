<?php

use PHPUnit\Framework\TestCase;

class VoteTest extends TestCase
{
    private $voteModel;
    
    protected function setUp(): void
    {
        require_once __DIR__ . '/../../src/models/Vote.php';
        $this->voteModel = new Vote();
    }
    
    public function testCreateVote()
    {
        $voteData = [
            'election_id' => 1,
            'position_id' => 1,
            'voter_id' => 1,
            'candidate_hash' => hash('sha256', 'candidate_1_test'),
            'vote_hash' => hash('sha256', 'vote_hash_test'),
            'vote_weight' => 1.0,
            'vote_type' => 'candidate',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit Test'
        ];
        
        $voteId = $this->voteModel->create($voteData);
        
        $this->assertIsNumeric($voteId);
        $this->assertGreaterThan(0, $voteId);
    }
    
    public function testHasVoted()
    {
        $hasVoted = $this->voteModel->hasVoted(1, 1, 1);
        
        $this->assertIsBool($hasVoted);
    }
    
    public function testGetVoterHistory()
    {
        $history = $this->voteModel->getVoterHistory(1);
        
        $this->assertIsArray($history);
    }
    
    public function testGetElectionResults()
    {
        $results = $this->voteModel->getElectionResults(1);
        
        $this->assertIsArray($results);
    }
    
    public function testGetTotalVotesByElection()
    {
        $totalVotes = $this->voteModel->getTotalVotesByElection(1);
        
        $this->assertIsNumeric($totalVotes);
        $this->assertGreaterThanOrEqual(0, $totalVotes);
    }
    
    public function testGetVoterTurnout()
    {
        $turnout = $this->voteModel->getVoterTurnout(1);
        
        $this->assertIsArray($turnout);
        $this->assertArrayHasKey('eligible', $turnout);
        $this->assertArrayHasKey('voted', $turnout);
        $this->assertArrayHasKey('percentage', $turnout);
    }
    
    public function testGetVotesByHour()
    {
        $votesByHour = $this->voteModel->getVotesByHour(1);
        
        $this->assertIsArray($votesByHour);
    }
}
