<?php

namespace app\tests\unit;

use app\models\LoanRequest;
use app\tests\TestCase;
use Yii;

class LoanRequestTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->cleanDatabase();
    }

    protected function tearDown(): void
    {
        $this->cleanDatabase();
        parent::tearDown();
    }

    private function cleanDatabase(): void
    {
        Yii::$app->db->createCommand('TRUNCATE TABLE loan_requests RESTART IDENTITY CASCADE')->execute();
    }

    public function testCreateLoanRequest(): void
    {
        $loanRequest = new LoanRequest([
            'user_id' => 1,
            'amount' => 5000,
            'term' => 30,
        ]);

        $this->assertTrue($loanRequest->save());
        $this->assertEquals(LoanRequest::STATUS_PENDING, $loanRequest->status);
        $this->assertNotNull($loanRequest->created_at);
        $this->assertNotNull($loanRequest->updated_at);
    }

    public function testValidationRules(): void
    {
        $loanRequest = new LoanRequest();
        $this->assertFalse($loanRequest->validate());
        
        $this->assertArrayHasKey('user_id', $loanRequest->errors);
        $this->assertArrayHasKey('amount', $loanRequest->errors);
        $this->assertArrayHasKey('term', $loanRequest->errors);
    }

    public function testNegativeAmountValidation(): void
    {
        $loanRequest = new LoanRequest([
            'user_id' => 1,
            'amount' => -1000,
            'term' => 30,
        ]);

        $this->assertFalse($loanRequest->validate());
        $this->assertArrayHasKey('amount', $loanRequest->errors);
    }

    public function testNegativeTermValidation(): void
    {
        $loanRequest = new LoanRequest([
            'user_id' => 1,
            'amount' => 5000,
            'term' => -10,
        ]);

        $this->assertFalse($loanRequest->validate());
        $this->assertArrayHasKey('term', $loanRequest->errors);
    }

    public function testStatusMethods(): void
    {
        $loanRequest = new LoanRequest([
            'user_id' => 1,
            'amount' => 5000,
            'term' => 30,
        ]);
        $loanRequest->save();

        $this->assertTrue($loanRequest->isPending());
        $this->assertFalse($loanRequest->isApproved());
        $this->assertFalse($loanRequest->isDeclined());

        $loanRequest->approve();
        $this->assertFalse($loanRequest->isPending());
        $this->assertTrue($loanRequest->isApproved());
        $this->assertFalse($loanRequest->isDeclined());

        $loanRequest->decline();
        $this->assertFalse($loanRequest->isPending());
        $this->assertFalse($loanRequest->isApproved());
        $this->assertTrue($loanRequest->isDeclined());
    }

    public function testFindPendingRequests(): void
    {
        $request1 = new LoanRequest(['user_id' => 1, 'amount' => 1000, 'term' => 10]);
        $request1->save();

        $request2 = new LoanRequest(['user_id' => 2, 'amount' => 2000, 'term' => 20]);
        $request2->save();
        $request2->approve();

        $request3 = new LoanRequest(['user_id' => 3, 'amount' => 3000, 'term' => 30]);
        $request3->save();

        $pendingRequests = LoanRequest::findPendingRequests();
        $this->assertCount(2, $pendingRequests);
        $this->assertEquals(1, $pendingRequests[0]->user_id);
        $this->assertEquals(3, $pendingRequests[1]->user_id);
    }

    public function testHasApprovedLoan(): void
    {
        $this->assertFalse(LoanRequest::hasApprovedLoan(1));

        $request = new LoanRequest(['user_id' => 1, 'amount' => 1000, 'term' => 10]);
        $request->save();
        $this->assertFalse(LoanRequest::hasApprovedLoan(1));

        $request->approve();
        $this->assertTrue(LoanRequest::hasApprovedLoan(1));
    }

    public function testPreventMultipleApprovedLoans(): void
    {
        $request1 = new LoanRequest(['user_id' => 1, 'amount' => 1000, 'term' => 10]);
        $request1->save();
        $request1->approve();

        $request2 = new LoanRequest([
            'user_id' => 1,
            'amount' => 2000,
            'term' => 20,
            'status' => LoanRequest::STATUS_APPROVED
        ]);

        $this->assertFalse($request2->save());
        $this->assertArrayHasKey('user_id', $request2->errors);
    }

    public function testGetStatusList(): void
    {
        $statusList = LoanRequest::getStatusList();
        $this->assertIsArray($statusList);
        $this->assertArrayHasKey(LoanRequest::STATUS_PENDING, $statusList);
        $this->assertArrayHasKey(LoanRequest::STATUS_APPROVED, $statusList);
        $this->assertArrayHasKey(LoanRequest::STATUS_DECLINED, $statusList);
    }
}