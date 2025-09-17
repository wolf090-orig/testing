<?php

namespace app\tests\unit;

use app\models\LoanRequest;
use app\services\LoanService;
use app\tests\TestCase;
use Yii;

class LoanServiceTest extends TestCase
{
    private LoanService $loanService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->loanService = new LoanService();
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

    public function testCreateLoanRequestSuccess(): void
    {
        $data = [
            'user_id' => 1,
            'amount' => 5000,
            'term' => 30,
        ];

        $result = $this->loanService->createLoanRequest($data);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('id', $result);
        $this->assertIsInt($result['id']);
    }

    public function testCreateLoanRequestValidationFailure(): void
    {
        $data = [
            'user_id' => -1,
            'amount' => -5000,
            'term' => -30,
        ];

        $result = $this->loanService->createLoanRequest($data);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('errors', $result);
    }

    public function testCreateLoanRequestForUserWithApprovedLoan(): void
    {
        $existingRequest = new LoanRequest([
            'user_id' => 1,
            'amount' => 3000,
            'term' => 20,
        ]);
        $existingRequest->save();
        $existingRequest->approve();

        $data = [
            'user_id' => 1,
            'amount' => 5000,
            'term' => 30,
        ];

        $result = $this->loanService->createLoanRequest($data);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('message', $result);
    }

    public function testValidateLoanRequestDataValid(): void
    {
        $data = [
            'user_id' => 1,
            'amount' => 5000,
            'term' => 30,
        ];

        $errors = $this->loanService->validateLoanRequestData($data);
        $this->assertEmpty($errors);
    }

    public function testValidateLoanRequestDataInvalid(): void
    {
        $data = [
            'user_id' => -1,
            'amount' => 0,
            'term' => -10,
        ];

        $errors = $this->loanService->validateLoanRequestData($data);
        $this->assertArrayHasKey('user_id', $errors);
        $this->assertArrayHasKey('amount', $errors);
        $this->assertArrayHasKey('term', $errors);
    }

    public function testValidateLoanRequestDataMissingFields(): void
    {
        $data = [];

        $errors = $this->loanService->validateLoanRequestData($data);
        $this->assertArrayHasKey('user_id', $errors);
        $this->assertArrayHasKey('amount', $errors);
        $this->assertArrayHasKey('term', $errors);
    }

    public function testProcessLoanRequestsEmpty(): void
    {
        $result = $this->loanService->processLoanRequests(0);

        $this->assertTrue($result['success']);
        $this->assertEquals(0, $result['processed_count']);
        $this->assertEmpty($result['errors']);
    }

    public function testProcessLoanRequestsWithPendingRequests(): void
    {
        $request1 = new LoanRequest(['user_id' => 1, 'amount' => 1000, 'term' => 10]);
        $request1->save();

        $request2 = new LoanRequest(['user_id' => 2, 'amount' => 2000, 'term' => 20]);
        $request2->save();

        $result = $this->loanService->processLoanRequests(0);

        $this->assertTrue($result['success']);
        $this->assertEquals(2, $result['processed_count']);
        $this->assertEmpty($result['errors']);

        $request1->refresh();
        $request2->refresh();

        $this->assertContains($request1->status, [LoanRequest::STATUS_APPROVED, LoanRequest::STATUS_DECLINED]);
        $this->assertContains($request2->status, [LoanRequest::STATUS_APPROVED, LoanRequest::STATUS_DECLINED]);
    }

    public function testGetApprovalProbability(): void
    {
        $probability = $this->loanService->getApprovalProbability();
        $this->assertIsFloat($probability);
        $this->assertGreaterThanOrEqual(0.0, $probability);
        $this->assertLessThanOrEqual(1.0, $probability);
    }

    public function testSetApprovalProbability(): void
    {
        $this->loanService->setApprovalProbability(0.5);
        $this->assertEquals(0.5, $this->loanService->getApprovalProbability());

        $this->loanService->setApprovalProbability(-0.1);
        $this->assertEquals(0.0, $this->loanService->getApprovalProbability());

        $this->loanService->setApprovalProbability(1.5);
        $this->assertEquals(1.0, $this->loanService->getApprovalProbability());
    }

    public function testProcessLoanRequestsWithDelay(): void
    {
        $request = new LoanRequest(['user_id' => 1, 'amount' => 1000, 'term' => 10]);
        $request->save();

        $startTime = microtime(true);
        $result = $this->loanService->processLoanRequests(1);
        $endTime = microtime(true);

        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['processed_count']);
        $this->assertGreaterThanOrEqual(1.0, $endTime - $startTime);
    }

    public function testProcessLoanRequestsPreventMultipleApprovals(): void
    {
        $this->loanService->setApprovalProbability(1.0);

        $request1 = new LoanRequest(['user_id' => 1, 'amount' => 1000, 'term' => 10]);
        $request1->save();

        $request2 = new LoanRequest(['user_id' => 1, 'amount' => 2000, 'term' => 20]);
        $request2->save();

        $result = $this->loanService->processLoanRequests(0);

        $this->assertTrue($result['success']);
        $this->assertEquals(2, $result['processed_count']);

        $request1->refresh();
        $request2->refresh();

        $approvedCount = 0;
        if ($request1->isApproved()) $approvedCount++;
        if ($request2->isApproved()) $approvedCount++;

        $this->assertLessThanOrEqual(1, $approvedCount, 'Пользователь не может иметь более одного одобренного займа');
    }
}