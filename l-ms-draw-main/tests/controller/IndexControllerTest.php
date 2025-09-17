<?php

namespace tests\controller;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use app\controller\IndexController;
use app\services\HealthCheckService;
use support\Request;
use support\Response;
use phpmock\MockBuilder;

/**
 * Class IndexControllerTest
 *
 * Unit tests for the IndexController class.
 */
class IndexControllerTest extends TestCase
{
    /**
     * @var \phpmock\Mock
     */
    private $configMock;

    /**
     * Clean up the test environment.
     *
     * This method is called after each test to perform any necessary cleanup.
     */
    protected function tearDown(): void
    {
        m::close();
        if ($this->configMock !== null) {
            $this->configMock->disable();
        }
    }

    /**
     * Mock the config function.
     *
     * This method sets up a mock for the global config function.
     *
     * @param string $returnValue The value to return when the config function is called.
     */
    private function mockConfigFunction(string $returnValue): void
    {
        $this->configMock = (new MockBuilder())
            ->setNamespace('app\controller')
            ->setName('config')
            ->setFunction(
                function ($key) use ($returnValue) {
                    if ($key == 'app.debug') {
                        return $returnValue;
                    }
                    return null;
                }
            )
            ->build();
        $this->configMock->enable();
    }

    /**
     * Test the index method in debug mode.
     *
     * This test verifies that the index method returns the README content when the application is in debug mode.
     */
    public function testIndexInDebugMode()
    {
        /** @var Request|m\MockInterface $request */
        $request = m::mock(Request::class);
        $healthCheckService = $this->createMock(HealthCheckService::class);

        // Mock the config function to return 'true' for 'app.debug'
        $this->mockConfigFunction('true');

        $controller = new IndexController();
        $response = $controller->index($request, $healthCheckService);

        // Assert that the response contains 'readme' text
        $this->assertStringContainsString('readme', mb_strtolower($response));
    }

    /**
     * Test the index method in normal mode.
     *
     * This test verifies that the index method returns the health check data when the application is not in debug mode.
     */
    public function testIndexInNormalMode()
    {
        /** @var Request|m\MockInterface $request */
        $request = m::mock(Request::class);
        $healthCheckService = $this->createMock(HealthCheckService::class);
        $healthCheckService->method('performHealthCheck')->with($request)->willReturn([]);

        // Mock the config function to return 'false' for 'app.debug'
        $this->mockConfigFunction('false');

        $controller = new IndexController();
        $response = $controller->index($request, $healthCheckService);

        // Assert that the response is a valid success response
        $this->assertInstanceOf(Response::class, $response);
        $expectedResponse = json_encode(["status" => "success", "data" => [], "errors" => null]);
        $this->assertEquals($expectedResponse, $response->rawBody());
    }

    /**
     * Test the readme method.
     *
     * This test verifies that the readme method returns the content of the README.md file.
     */
    public function testReadme()
    {
        /** @var Request|m\MockInterface $request */
        $request = m::mock(Request::class);

        $controller = new IndexController();

        // Use reflection to access the protected readme method
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('readme');
        $method->setAccessible(true);
        $response = $method->invoke($controller, $request);

        // Assert that the response contains 'Readme' text
        $this->assertStringContainsString('Readme', $response);
    }
}
