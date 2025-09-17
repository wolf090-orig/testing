<?php

namespace app\helpers\Healthcheck;

/**
 * Class MemoryHelper
 *
 * Helper to calculate memory usage by Webman processes.
 */
class MemoryHelper
{
    private ShellCommandInterface $shellCommand;

    public function __construct(ShellCommandInterface $shellCommand)
    {
        $this->shellCommand = $shellCommand;
    }

    /**
     * Calculate the total memory usage of Webman processes.
     *
     * @return array The memory usage in bytes, kilobytes, and megabytes.
     */
    public function getMemoryUsage(): array
    {
        // Execute the shell command to get the list of Webman processes and their memory usage
        $output = [];
        $this->shellCommand->exec("ps aux | grep '[w]ebman'", $output);

        $totalMemoryBytes = 0.0;

        // Parse the output to extract memory usage
        foreach ($output as $line) {
            // Split the line into columns
            $columns = preg_split('/\s+/', $line);

            // Memory usage is typically in the 6th column (this might vary depending on the system)
            if (isset($columns[5])) {
                $memoryUsageKB = floatval($columns[5]); // Memory usage in KB
                $totalMemoryBytes += $memoryUsageKB * 1024; // Convert to bytes and add to total
            }
        }

        $totalMemoryKB = $totalMemoryBytes / 1024; // Convert to KB
        $totalMemoryMB = $totalMemoryKB / 1024; // Convert to MB

        return [
            'bytes' => round($totalMemoryBytes),
            'kilobytes' => round($totalMemoryKB, 2),
            'megabytes' => round($totalMemoryMB, 2),
        ];
    }
}
