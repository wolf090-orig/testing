<?php

namespace app\controller;

use app\command\ConsumeSchedulesV1;
use app\command\ConsumeTicketsDaily;
use app\command\ConsumeTicketsMonthly;
use app\command\ConsumeTicketsWeekly;
use app\command\ConsumeTicketsYearly;
use app\command\DrawLotteries;
use Exception;
use Phinx\Console\PhinxApplication;
use support\Request;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class CommandController
{
    public array $AVAILABLE_COMMANDS = [
        'draw:lotteries' => DrawLotteries::class,
        'consume-schedules:run' => ConsumeSchedulesV1::class,
        'consume-tickets:daily' => ConsumeTicketsDaily::class,
        'consume-tickets:weekly' => ConsumeTicketsWeekly::class,
        'consume-tickets:monthly' => ConsumeTicketsMonthly::class,
        'consume-tickets:yearly' => ConsumeTicketsYearly::class,
    ];

    /**
     * @throws Exception
     */
    public function executeCommand(Request $request)
    {
        $auth = $request->header("Authorization", "123");
        if ($auth != "internal_test") {
            throw new Exception("Not found", 404);
        }
        $command = $request->input("command_name", '');

        if ($command == "seeds") {
            return $this->runSeeds();
        }
        if ($command == "refresh") {
            return $this->refresh();
        }
        if (in_array($command, ['seeds', 'refresh'])) {
            return $this->runSeeds();
        }
        if (!in_array($command, array_keys($this->AVAILABLE_COMMANDS))) {
            throw new Exception("Command not found", 404);
        }
        $c = new $this->AVAILABLE_COMMANDS[$command]();
        $input = new ArrayInput(array(
            'command' => $command,
            'start' => "",
            'end' => ""
        ));
        $output = new BufferedOutput();
        $c->run($input, $output);
        $content = $output->fetch();

        return $content;
    }

    /**
     * @throws Exception
     */
    private function runSeeds()
    {
        $app = new PhinxApplication();
        $app->setAutoExit(false); // Prevents Phinx from exiting the script

        $input = new ArrayInput([
            'command' => 'seed:run',
            '--configuration' => base_path('phinx.php'),
            '--environment' => 'development',
        ]);

        $output = new BufferedOutput();
        $app->run($input, $output);

        return response($output->fetch());
    }

    private function refresh()
    {
        $app = new PhinxApplication();
        $app->setAutoExit(false); // Prevents Phinx from exiting the script

        $input = new ArrayInput([
            'command' => 'rollback',
            '--configuration' => base_path('phinx.php'),
            '--environment' => 'development',
            '--target' => 0,
        ]);

        $output = new BufferedOutput();
        $app->run($input, $output);

        $input = new ArrayInput([
            'command' => 'migrate',
            '--configuration' => base_path('phinx.php'),
            '--environment' => 'development',
        ]);

        $output = new BufferedOutput();
        $app->run($input, $output);

        return response($output->fetch());
    }
}
