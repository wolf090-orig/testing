<?php

namespace app\helpers\Healthcheck;

/**
 * Class ShellCommand
 *
 * Represents a class for executing shell commands.
 */
class ShellCommand implements ShellCommandInterface
{
    /**
     * Executes the given shell command.
     *
     * @param string $command The shell command to execute.
     * @param array &$output An array reference to store the output of the command.
     * @return void
     */
    public function exec(string $command, array &$output): void
    {
        exec($command . ' 2>&1', $output);
    }
}
