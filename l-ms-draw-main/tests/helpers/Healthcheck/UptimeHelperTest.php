<?php

namespace tests\helpers\Healthcheck;

use PHPUnit\Framework\TestCase;
use app\helpers\Healthcheck\UptimeHelper;

/**
 * Class UptimeHelperTest
 *
 * Unit tests for the UptimeHelper class.
 */
class UptimeHelperTest extends TestCase
{
    /**
     * Set up the environment before each test.
     *
     * This method initializes the UptimeHelper class to set the start time.
     */
    protected function setUp(): void
    {
        UptimeHelper::init();
    }

    /**
     * Test that the init method sets the start time correctly.
     *
     * This test uses reflection to access the private static property startTime
     * and checks that it is set to a valid integer value.
     */
    public function testInitSetsStartTime()
    {
        $reflection = new \ReflectionClass(UptimeHelper::class);
        $property = $reflection->getProperty('startTime');
        $property->setAccessible(true);

        $startTime = $property->getValue();

        // Assert that the start time is set and is a valid integer
        $this->assertNotEmpty($startTime, 'Start time should be set');
        $this->assertIsInt($startTime, 'Start time should be an integer');
        $this->assertGreaterThan(0, $startTime, 'Start time should be greater than zero');
    }

    /**
     * Test that the getUptime method returns the correct uptime value.
     *
     * This test waits for a short period before checking that the uptime is at least 1 second.
     */
    public function testGetUptimeReturnsCorrectValue()
    {
        // Wait for a short period before checking the uptime
        sleep(1);
        $uptime = UptimeHelper::getUptime();

        // Assert that the uptime is a valid integer and at least 1 second
        $this->assertIsInt($uptime, 'Uptime should be an integer');
        $this->assertGreaterThanOrEqual(1, $uptime, 'Uptime should be at least 1 second');
    }
}
