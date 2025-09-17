<?php

namespace app\tests\functional;

use app\models\LoanRequest;
use app\tests\TestCase;
use Yii;
use yii\web\Application;
use yii\web\Request;
use yii\web\Response;

class RequestsControllerTest extends TestCase
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

    public function testCreateLoanRequestSuccess(): void
    {
        $data = [
            'user_id' => 1,
            'amount' => 5000,
            'term' => 30,
        ];

        $this->mockPostRequest('/requests', $data);
        $response = Yii::$app->runAction('requests/create');

        $this->assertEquals(201, Yii::$app->response->statusCode);
        $this->assertTrue($response['result']);
        $this->assertArrayHasKey('id', $response);
        $this->assertIsInt($response['id']);

        $loanRequest = LoanRequest::findOne($response['id']);
        $this->assertNotNull($loanRequest);
        $this->assertEquals(1, $loanRequest->user_id);
        $this->assertEquals(5000, $loanRequest->amount);
        $this->assertEquals(30, $loanRequest->term);
        $this->assertEquals(LoanRequest::STATUS_PENDING, $loanRequest->status);
    }

    public function testCreateLoanRequestMissingFields(): void
    {
        $data = [
            'user_id' => 1,
        ];

        $this->mockPostRequest('/requests', $data);
        $response = Yii::$app->runAction('requests/create');

        $this->assertEquals(400, Yii::$app->response->statusCode);
        $this->assertFalse($response['result']);
    }

    public function testCreateLoanRequestInvalidData(): void
    {
        $data = [
            'user_id' => -1,
            'amount' => -5000,
            'term' => -30,
        ];

        $this->mockPostRequest('/requests', $data);
        $response = Yii::$app->runAction('requests/create');

        $this->assertEquals(400, Yii::$app->response->statusCode);
        $this->assertFalse($response['result']);
    }

    public function testCreateLoanRequestZeroValues(): void
    {
        $data = [
            'user_id' => 0,
            'amount' => 0,
            'term' => 0,
        ];

        $this->mockPostRequest('/requests', $data);
        $response = Yii::$app->runAction('requests/create');

        $this->assertEquals(400, Yii::$app->response->statusCode);
        $this->assertFalse($response['result']);
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

        $this->mockPostRequest('/requests', $data);
        $response = Yii::$app->runAction('requests/create');

        $this->assertEquals(400, Yii::$app->response->statusCode);
        $this->assertFalse($response['result']);
    }

    public function testCreateLoanRequestInvalidJson(): void
    {
        $this->mockPostRequestWithRawBody('/requests', 'invalid json');
        $response = Yii::$app->runAction('requests/create');

        $this->assertEquals(400, Yii::$app->response->statusCode);
        $this->assertFalse($response['result']);
    }

    public function testCreateLoanRequestEmptyBody(): void
    {
        $this->mockPostRequestWithRawBody('/requests', '');
        $response = Yii::$app->runAction('requests/create');

        $this->assertEquals(400, Yii::$app->response->statusCode);
        $this->assertFalse($response['result']);
    }

    public function testCreateLoanRequestValidJsonInRawBody(): void
    {
        $data = [
            'user_id' => 2,
            'amount' => 7500,
            'term' => 45,
        ];

        $this->mockPostRequestWithRawBody('/requests', json_encode($data));
        $response = Yii::$app->runAction('requests/create');

        $this->assertEquals(201, Yii::$app->response->statusCode);
        $this->assertTrue($response['result']);
        $this->assertArrayHasKey('id', $response);
    }

    public function testCreateMultipleLoanRequestsForDifferentUsers(): void
    {
        $data1 = ['user_id' => 1, 'amount' => 1000, 'term' => 10];
        $data2 = ['user_id' => 2, 'amount' => 2000, 'term' => 20];
        $data3 = ['user_id' => 3, 'amount' => 3000, 'term' => 30];

        $this->mockPostRequest('/requests', $data1);
        $response1 = Yii::$app->runAction('requests/create');
        $this->assertEquals(201, Yii::$app->response->statusCode);
        $this->assertTrue($response1['result']);

        $this->mockPostRequest('/requests', $data2);
        $response2 = Yii::$app->runAction('requests/create');
        $this->assertEquals(201, Yii::$app->response->statusCode);
        $this->assertTrue($response2['result']);

        $this->mockPostRequest('/requests', $data3);
        $response3 = Yii::$app->runAction('requests/create');
        $this->assertEquals(201, Yii::$app->response->statusCode);
        $this->assertTrue($response3['result']);

        $this->assertNotEquals($response1['id'], $response2['id']);
        $this->assertNotEquals($response2['id'], $response3['id']);
        $this->assertNotEquals($response1['id'], $response3['id']);
    }

    private function mockPostRequest(string $url, array $data): void
    {
        $_POST = $data;
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = $url;
        
        Yii::$app->request->setBodyParams($data);
        Yii::$app->response->statusCode = 200;
    }

    private function mockPostRequestWithRawBody(string $url, string $rawBody): void
    {
        $_POST = [];
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = $url;
        
        $request = $this->getMockBuilder(Request::class)
            ->onlyMethods(['getRawBody', 'getBodyParams'])
            ->getMock();
        
        $request->method('getRawBody')->willReturn($rawBody);
        $request->method('getBodyParams')->willReturn([]);
        
        Yii::$app->set('request', $request);
        Yii::$app->response->statusCode = 200;
    }
}