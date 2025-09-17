<?php

namespace app\tests\functional;

use app\models\LoanRequest;
use app\tests\TestCase;
use Yii;
use yii\web\Application;

class ProcessorControllerTest extends TestCase
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

    protected function mockApplication($config = [], $appClass = '\yii\web\Application')
    {
        parent::mockApplication(array_merge([
            'components' => [
                'urlManager' => [
                    'enablePrettyUrl' => true,
                    'showScriptName' => false,
                    'rules' => [
                        'POST requests' => 'requests/create',
                        'GET processor' => 'processor/process',
                    ],
                ],
            ],
        ], $config), $appClass);
    }

    public function testProcessLoanRequestsEmpty(): void
    {
        $this->mockGetRequest('/processor', ['delay' => 1]);
        $response = Yii::$app->runAction('processor/process');

        $this->assertEquals(200, Yii::$app->response->statusCode);
        $this->assertTrue($response['result']);
    }

    public function testProcessLoanRequestsWithPendingRequests(): void
    {
        $request1 = new LoanRequest(['user_id' => 1, 'amount' => 1000, 'term' => 10]);
        $request1->save();

        $request2 = new LoanRequest(['user_id' => 2, 'amount' => 2000, 'term' => 20]);
        $request2->save();

        $request3 = new LoanRequest(['user_id' => 3, 'amount' => 3000, 'term' => 30]);
        $request3->save();

        $this->mockGetRequest('/processor', ['delay' => 0]);
        $response = Yii::$app->runAction('processor/process');

        $this->assertEquals(200, Yii::$app->response->statusCode);
        $this->assertTrue($response['result']);

        $request1->refresh();
        $request2->refresh();
        $request3->refresh();

        $this->assertContains($request1->status, [LoanRequest::STATUS_APPROVED, LoanRequest::STATUS_DECLINED]);
        $this->assertContains($request2->status, [LoanRequest::STATUS_APPROVED, LoanRequest::STATUS_DECLINED]);
        $this->assertContains($request3->status, [LoanRequest::STATUS_APPROVED, LoanRequest::STATUS_DECLINED]);
    }

    public function testProcessLoanRequestsWithDelay(): void
    {
        $request = new LoanRequest(['user_id' => 1, 'amount' => 1000, 'term' => 10]);
        $request->save();

        $startTime = microtime(true);
        $this->mockGetRequest('/processor', ['delay' => 1]);
        $response = Yii::$app->runAction('processor/process');
        $endTime = microtime(true);

        $this->assertEquals(200, Yii::$app->response->statusCode);
        $this->assertTrue($response['result']);
        $this->assertGreaterThanOrEqual(1.0, $endTime - $startTime);

        $request->refresh();
        $this->assertContains($request->status, [LoanRequest::STATUS_APPROVED, LoanRequest::STATUS_DECLINED]);
    }

    public function testProcessLoanRequestsWithNegativeDelay(): void
    {
        $request = new LoanRequest(['user_id' => 1, 'amount' => 1000, 'term' => 10]);
        $request->save();

        $this->mockGetRequest('/processor', ['delay' => -5]);
        $response = Yii::$app->runAction('processor/process');

        $this->assertEquals(200, Yii::$app->response->statusCode);
        $this->assertTrue($response['result']);

        $request->refresh();
        $this->assertContains($request->status, [LoanRequest::STATUS_APPROVED, LoanRequest::STATUS_DECLINED]);
    }

    public function testProcessLoanRequestsWithoutDelayParameter(): void
    {
        $request = new LoanRequest(['user_id' => 1, 'amount' => 1000, 'term' => 10]);
        $request->save();

        $this->mockGetRequest('/processor', []);
        $response = Yii::$app->runAction('processor/process');

        $this->assertEquals(200, Yii::$app->response->statusCode);
        $this->assertTrue($response['result']);

        $request->refresh();
        $this->assertContains($request->status, [LoanRequest::STATUS_APPROVED, LoanRequest::STATUS_DECLINED]);
    }

    public function testProcessLoanRequestsSkipsAlreadyProcessed(): void
    {
        $pendingRequest = new LoanRequest(['user_id' => 1, 'amount' => 1000, 'term' => 10]);
        $pendingRequest->save();

        $approvedRequest = new LoanRequest(['user_id' => 2, 'amount' => 2000, 'term' => 20]);
        $approvedRequest->save();
        $approvedRequest->approve();

        $declinedRequest = new LoanRequest(['user_id' => 3, 'amount' => 3000, 'term' => 30]);
        $declinedRequest->save();
        $declinedRequest->decline();

        $this->mockGetRequest('/processor', ['delay' => 0]);
        $response = Yii::$app->runAction('processor/process');

        $this->assertEquals(200, Yii::$app->response->statusCode);
        $this->assertTrue($response['result']);

        $pendingRequest->refresh();
        $approvedRequest->refresh();
        $declinedRequest->refresh();

        $this->assertContains($pendingRequest->status, [LoanRequest::STATUS_APPROVED, LoanRequest::STATUS_DECLINED]);
        $this->assertEquals(LoanRequest::STATUS_APPROVED, $approvedRequest->status);
        $this->assertEquals(LoanRequest::STATUS_DECLINED, $declinedRequest->status);
    }

    public function testProcessLoanRequestsPreventMultipleApprovals(): void
    {
        $existingApproved = new LoanRequest(['user_id' => 1, 'amount' => 1000, 'term' => 10]);
        $existingApproved->save();
        $existingApproved->approve();

        $newRequest = new LoanRequest(['user_id' => 1, 'amount' => 2000, 'term' => 20]);
        $newRequest->save();

        $this->mockGetRequest('/processor', ['delay' => 0]);
        $response = Yii::$app->runAction('processor/process');

        $this->assertEquals(200, Yii::$app->response->statusCode);
        $this->assertTrue($response['result']);

        $existingApproved->refresh();
        $newRequest->refresh();

        $this->assertEquals(LoanRequest::STATUS_APPROVED, $existingApproved->status);
        $this->assertEquals(LoanRequest::STATUS_DECLINED, $newRequest->status);
    }

    public function testProcessLoanRequestsMultipleUsers(): void
    {
        for ($i = 1; $i <= 10; $i++) {
            $request = new LoanRequest([
                'user_id' => $i,
                'amount' => 1000 * $i,
                'term' => 10 * $i,
            ]);
            $request->save();
        }

        $this->mockGetRequest('/processor', ['delay' => 0]);
        $response = Yii::$app->runAction('processor/process');

        $this->assertEquals(200, Yii::$app->response->statusCode);
        $this->assertTrue($response['result']);

        $approvedCount = LoanRequest::find()->where(['status' => LoanRequest::STATUS_APPROVED])->count();
        $declinedCount = LoanRequest::find()->where(['status' => LoanRequest::STATUS_DECLINED])->count();
        $totalProcessed = $approvedCount + $declinedCount;

        $this->assertEquals(10, $totalProcessed);
        $this->assertGreaterThanOrEqual(0, $approvedCount);
        $this->assertLessThanOrEqual(10, $approvedCount);
    }

    public function testProcessLoanRequestsStringDelayParameter(): void
    {
        $request = new LoanRequest(['user_id' => 1, 'amount' => 1000, 'term' => 10]);
        $request->save();

        $this->mockGetRequest('/processor', ['delay' => '2']);
        $response = Yii::$app->runAction('processor/process');

        $this->assertEquals(200, Yii::$app->response->statusCode);
        $this->assertTrue($response['result']);

        $request->refresh();
        $this->assertContains($request->status, [LoanRequest::STATUS_APPROVED, LoanRequest::STATUS_DECLINED]);
    }

    private function mockGetRequest(string $url, array $params): void
    {
        $_GET = $params;
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = $url;
        $_SERVER['QUERY_STRING'] = http_build_query($params);
        
        Yii::$app->response->statusCode = 200;
    }
}