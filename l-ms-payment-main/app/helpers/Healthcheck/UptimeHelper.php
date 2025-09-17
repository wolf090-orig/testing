<?php

namespace app\helpers\Healthcheck;

/**
 * Class UptimeHelper
 *
 * Helper for tracking server uptime.
 */
class UptimeHelper
{
    /**
     * @var int Server start time in Unix timestamp format.
     */
    private static int $startTime;

    /**
     * Initialization of the server start time.
     *
     * @return void
     */
    public static function init(): void
    {
        self::$startTime = time();
    }

    /**
     * Get the server uptime in seconds.
     *
     * @return int Server uptime in seconds.
     */
    public static function getUptime(): int
    {
        return time() - self::$startTime;
    }
}
