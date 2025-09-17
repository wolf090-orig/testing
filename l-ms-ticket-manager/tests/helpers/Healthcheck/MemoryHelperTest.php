<?php

namespace tests\helpers\Healthcheck;

use PHPUnit\Framework\TestCase;
use Mockery as m;
use app\helpers\Healthcheck\MemoryHelper;
use app\helpers\Healthcheck\ShellCommandInterface;

/**
 * Class MemoryHelperTest
 *
 * Unit tests for the MemoryHelper class.
 */
class MemoryHelperTest extends TestCase
{
    /**
     * Clean up the test environment.
     */
    protected function tearDown(): void
    {
        m::close();
    }

    /**
     * Test the getMemoryUsage method of MemoryHelper.
     *
     * This method tests the functionality of the getMemoryUsage method in the MemoryHelper class.
     */
    public function testGetMemoryUsage()
    {
        /** @var ShellCommandInterface|m\MockInterface $shellCommandMock */
        $shellCommandMock = m::mock(ShellCommandInterface::class);
        $shellCommandMock->shouldReceive('exec')
            ->withArgs(function ($cmd, &$output) {
                if ($cmd === "ps aux | grep '[w]ebman'") {
                    $output = [
                        'user  12345  0.0  0.1  5678  9876 ?  Ss   12:34   0:00 webman worker',
                        'user  12346  0.0  0.1  1234  4567 ?  Ss   12:34   0:00 webman worker'
                    ];
                    return true;
                }
                return false;
            })
            ->andReturn(0);

        // Creating an instance of MemoryHelper with the mocked ShellCommandInterface
        $memoryHelper = new MemoryHelper($shellCommandMock);

        // Calling the method being tested
        $result = $memoryHelper->getMemoryUsage();

        // Assertions
        $this->assertIsArray($result);
        $this->assertArrayHasKey('bytes', $result);
        $this->assertArrayHasKey('kilobytes', $result);
        $this->assertArrayHasKey('megabytes', $result);

        // Calculating expected values
        $expectedKilobytes = 9876 + 4567;
        $expectedBytes = $expectedKilobytes * 1024;
        $expectedMegabytes = round($expectedKilobytes / 1024, 2);

        // More assertions
        $this->assertEquals($expectedBytes, $result['bytes']);
        $this->assertEquals($expectedKilobytes, $result['kilobytes']);
        $this->assertEquals($expectedMegabytes, $result['megabytes']);
    }
}
