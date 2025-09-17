<?php

namespace app\services;

use support\Request;
use app\helpers\Healthcheck\MemoryHelper;
use app\helpers\Healthcheck\ShellCommand;

/**
 * Class HealthCheckService
 *
 * A service class to handle the health check logic.
 */
class HealthCheckService
{
    private MemoryHelper $memoryHelper;

    public function __construct()
    {
        $this->memoryHelper = new MemoryHelper(new ShellCommand());
    }

    /**
     * Perform the health check and return the result.
     *
     * @param Request $request The incoming request.
     * @return array The health check data.
     */
    public function performHealthCheck(Request $request): array
    {
        return [
            'microservice_name' => config('server.name'),
            'hostname' => gethostname(),
            'server_ip' => $request->getLocalIp(),
            'port' => getenv('SERVER_PORT') ?: '8085',
            'client_ip' => $request->getRealIp(),
            'php_version' => PHP_VERSION,
            'server_os' => $this->getServerOS(),
            'memory_usage' => $this->getMemoryUsage(),
            'disk_free_space' => $this->getDiskSpaceInfo('free'),
            'disk_total_space' => $this->getDiskSpaceInfo('total'),
            'cpu_load' => $this->getCPULoad(),
            'webman_memory_usage' => $this->memoryHelper->getMemoryUsage(),
            'uptime' => @file_get_contents('/proc/uptime'),
            'timezone' => config('app.default_timezone'),
            'timestamp' => date('Y-m-d H:i:s'),
        ];
    }

    /**
     * Get server OS information.
     *
     * @return array The server OS information.
     */
    private function getServerOS(): array
    {
        return [
            'name' => php_uname('s'),
            'release' => php_uname('r'),
            'version' => php_uname('v'),
            'machine' => php_uname('m'),
        ];
    }

    /**
     * Get memory usage information.
     *
     * @return array The memory usage information.
     */
    private function getMemoryUsage(): array
    {
        $memoryUsageBytes = memory_get_usage();
        $memoryUsageMB = round($memoryUsageBytes / 1024 / 1024, 2);

        return [
            'bytes' => $memoryUsageBytes,
            'megabytes' => $memoryUsageMB,
        ];
    }

    /**
     * Get disk space information.
     *
     * @param string $type The type of disk space information ('free' or 'total').
     * @return array The disk space information.
     */
    private function getDiskSpaceInfo(string $type): array
    {
        $diskSpaceBytes = ($type === 'free') ? disk_free_space('/') : disk_total_space('/');
        $diskSpaceMB = round($diskSpaceBytes / 1024 / 1024);
        $diskSpaceGB = round($diskSpaceBytes / 1024 / 1024 / 1024, 2);

        $data = [
            'bytes' => $diskSpaceBytes,
            'megabytes' => $diskSpaceMB,
            'gigabytes' => $diskSpaceGB,
        ];

        if ($type === 'free') {
            $totalSpaceBytes = disk_total_space('/');
            $data['percentage'] = round(($diskSpaceBytes / $totalSpaceBytes) * 100, 2);
        }

        return $data;
    }

    /**
     * Get CPU load averages.
     *
     * @return array The CPU load averages.
     */
    private function getCPULoad(): array
    {
        $cpuLoad = sys_getloadavg();

        return [
            '1_min' => $cpuLoad[0],
            '5_min' => $cpuLoad[1],
            '15_min' => $cpuLoad[2],
        ];
    }
}
