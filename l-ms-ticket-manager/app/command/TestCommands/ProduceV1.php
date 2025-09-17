<?php

namespace app\command\TestCommands;

use app\libraries\kafka\Messages\KafkaProducerMessage;
use app\libraries\kafka\Producers\Producer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class ProduceV1 extends Command
{
    protected static $defaultName = 'produce:run';
    protected static $defaultDescription = 'produce test';

    /**
     * @return void
     */
    protected function configure()
    {
        $this->addArgument('name', InputArgument::OPTIONAL, 'Name description');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $producer = Producer::createFromConfigKey(
            'tickets',
            config('kafka.lottery_draw_results_topic')
        );

        $message =  new KafkaProducerMessage([
            'lottery_id' => 2,
            'lottery_name' => 'RU Daily Dynamic Test 30.06.2025',
            'draw_date' => '2025-06-30T18:00:00Z',
            'tickets' => [
                [
                    'ticket_number' => 'RU0000037_L2',
                    'winner_position' => 1
                ],
                [
                    'ticket_number' => 'RU0000038_L2',
                    'winner_position' => 2
                ],
            ]
        ]);
        $message->withHeaders([
            'lottery_id' => 2,
        ]);

        $producer->sendMessage($message);

        echo ("сообщение отправлено\n");

        return self::SUCCESS;
    }
}
