<?php

namespace app\helpers\Healthcheck;

/**
 * Interface ShellCommandInterface
 *
 * Represents an interface for executing shell commands.
 */
interface ShellCommandInterface
{
    /**
     * Executes the given shell command.
     *
     * @param string $command The shell command to execute.
     * @param array &$output An array reference to store the output of the command.
     * @return void
     */
    public function exec(string $command, array &$output): void;
}
