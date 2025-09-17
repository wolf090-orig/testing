<?php

namespace tests\helpers\Healthcheck;

use PHPUnit\Framework\TestCase;
use app\helpers\Healthcheck\ShellCommand;

/**
 * Class ShellCommandTest
 *
 * Unit tests for the ShellCommand class.
 */
class ShellCommandTest extends TestCase
{
    /**
     * Test the exec method of ShellCommand.
     *
     * This method tests the functionality of the exec method in the ShellCommand class.
     */
    public function testExec()
    {
        $command = "echo 'Hello, World!'";
        $expectedOutput = ["Hello, World!"];
        $output = [];

        $shellCommand = new ShellCommand();
        $shellCommand->exec($command, $output);

        // Verify that the output array contains the expected result
        $this->assertEquals($expectedOutput, $output);
    }

    /**
     * Test the exec method with a more complex command.
     *
     * This method tests the functionality of the exec method with a more complex command.
     */
    public function testExecWithComplexCommand()
    {
        $command = "ls -la";
        $output = [];

        $shellCommand = new ShellCommand();
        $shellCommand->exec($command, $output);

        // Verify that the output array is not empty
        $this->assertNotEmpty($output);

        // Verify that the output contains the expected header for a directory listing
        $this->assertStringContainsString('total', $output[0]);
    }

    /**
     * Test the exec method with an invalid command.
     *
     * This method tests the functionality of the exec method with an invalid command.
     */
    public function testExecWithInvalidCommand()
    {
        $command = "invalidcommand";
        $output = [];

        $shellCommand = new ShellCommand();

        ob_start();
        $shellCommand->exec($command, $output);
        ob_end_clean();

        // Verify that the output array contains the error message
        $this->assertNotEmpty($output);
        $this->assertStringContainsString('not found', implode("\n", $output));
    }
}
