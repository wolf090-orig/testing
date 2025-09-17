<?php

namespace tests\services;

use PHPUnit\Framework\TestCase;
use Mockery as m;
use app\services\HealthCheckService;
use support\Request;
use app\helpers\Healthcheck\MemoryHelper;

/**
 * Class HealthCheckServiceTest
 *
 * Unit tests for the HealthCheckService class.
 */
class HealthCheckServiceTest extends TestCase
{
    /**
     * Tear down the environment after each test.
     *
     * This method closes Mockery after each test.
     */
    protected function tearDown(): void
    {
        m::close();
    }

    /**
     * Test the performHealthCheck method returns correct health check data.
     *
     * This test mocks the dependencies and checks that the health check data
     * is returned correctly.
     */
    public function testPerformHealthCheck()
    {
        /** @var Request|m\MockInterface $requestMock */
        $requestMock = m::mock(Request::class);
        $requestMock->shouldReceive('getLocalIp')->andReturn('127.0.0.1');
        $requestMock->shouldReceive('getRealIp')->andReturn('192.168.1.1');

        /** @var MemoryHelper|m\MockInterface $memoryHelperMock */
        $memoryHelperMock = m::mock(MemoryHelper::class);
        $memoryHelperMock->shouldReceive('getMemoryUsage')->andReturn([
            'bytes' => 10485760,
            'kilobytes' => 10240,
            'megabytes' => 10
        ]);

        // Create the HealthCheckService instance
        $healthCheckService = new HealthCheckService();

        // Use reflection to set the private memoryHelper property
        $reflection = new \ReflectionClass($healthCheckService);
        $property = $reflection->getProperty('memoryHelper');
        $property->setAccessible(true);
        $property->setValue($healthCheckService, $memoryHelperMock);

        // Call the performHealthCheck method
        $result = $healthCheckService->performHealthCheck($requestMock);

        // Assert that the result contains the expected keys and values
        $this->assertIsArray($result);
        $this->assertArrayHasKey('microservice_name', $result);
        $this->assertArrayHasKey('hostname', $result);
        $this->assertArrayHasKey('server_ip', $result);
        $this->assertArrayHasKey('port', $result);
        $this->assertArrayHasKey('client_ip', $result);
        $this->assertArrayHasKey('php_version', $result);
        $this->assertArrayHasKey('server_os', $result);
        $this->assertArrayHasKey('memory_usage', $result);
        $this->assertArrayHasKey('disk_free_space', $result);
        $this->assertArrayHasKey('disk_total_space', $result);
        $this->assertArrayHasKey('cpu_load', $result);
        $this->assertArrayHasKey('webman_memory_usage', $result);
        $this->assertArrayHasKey('uptime', $result);
        $this->assertArrayHasKey('timezone', $result);
        $this->assertArrayHasKey('timestamp', $result);

        $this->assertEquals('127.0.0.1', $result['server_ip']);
        $this->assertEquals('192.168.1.1', $result['client_ip']);
        $this->assertEquals(8080, $result['port']);
        $this->assertEquals([
            'bytes' => 10485760,
            'kilobytes' => 10240,
            'megabytes' => 10
        ], $result['webman_memory_usage']);
    }
}
