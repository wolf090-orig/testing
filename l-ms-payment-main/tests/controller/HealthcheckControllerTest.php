<?php

namespace tests\controller;

use PHPUnit\Framework\TestCase;
use Mockery as m;
use app\controller\HealthcheckController;
use app\services\HealthCheckService;
use support\Request;

require_once 'app/functions.php';

/**
 * Class HealthcheckControllerTest
 *
 * Unit tests for the HealthcheckController class.
 */
class HealthcheckControllerTest extends TestCase
{
    /**
     * Clean up the test environment.
     */
    protected function tearDown(): void
    {
        m::close();
    }

    /**
     * Test index method of HealthcheckController.
     *
     * This method tests the functionality of the index method in the HealthcheckController class.
     */
    public function testIndex()
    {
        /** @var Request|m\MockInterface $request */
        $request = m::mock(Request::class);
        /** @var HealthCheckService|m\MockInterface $healthCheckService */
        $healthCheckService = m::mock(HealthCheckService::class);
        $healthCheckService->shouldReceive('performHealthCheck')
            ->withArgs([$request])
            ->once()
            ->andReturn([]);

        $controller = new HealthcheckController();
        $response = $controller->index($request, $healthCheckService);

        // Assert
        $data = ['status' => 'success', 'data' => [], 'errors' => null];
        $this->assertEquals($data, json_decode($response->rawBody(), true));
    }
}
