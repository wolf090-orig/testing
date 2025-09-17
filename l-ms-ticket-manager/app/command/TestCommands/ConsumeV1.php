<?php

namespace app\command\TestCommands;

use app\libraries\kafka\consumers\Consumer;
use app\libraries\kafka\messages\contracts\KafkaConsumerMessage;
use support\Log;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConsumeV1 extends Command
{
    protected static $defaultName = 'consume:run';
    protected static $defaultDescription = 'consume test';

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
        $logger = Log::channel('command_test_log');
        $logger->info("тест запуска команды", ['test' => 'test1']);

        $topicName = config('kafka.daily_tickets_topic');
        while (true) {
            $consumer = Consumer::createFromConfigKey(
                'tickets',
                [$topicName]
            )
                ->withHandler(function (KafkaConsumerMessage $message) use ($logger) {
                    /** @var Consumer $this */
                    $lastOffset = $this->getLastOffset($message->getTopicName(), $message->getPartition());
                    $currentOffset = $message->getOffset();
                    $cntMessagesInTopicPartition = $lastOffset + 1;

                    $logger
                        ->info(
                            "Обработка сообщения с офсетом $currentOffset.\n" .
                            "Всего сообщений в топике - $cntMessagesInTopicPartition;" .
                            "Партиция - {$message->getPartition()}.\n" .
                            "Последнее сообщение в топике - $lastOffset"
                        );

                    $body = $message->getBody();
                    $headers = $message->getHeaders();

                    $logger->info('Прочитано сообщение', [
                        'body' => $body,
                        'headers' => $headers
                    ]);
                })
                ->stopAfterFailCommit()
                ->withAutoCommit();

            $consumerTimeout = 15000;
            $messagesBatchSize = 1000;
            $msg = "Подписка на топик $topicName с таймаутом $consumerTimeout ms и лимитом $messagesBatchSize сообщений.";
            $logger->info($msg);

            $consumer->consume($messagesBatchSize, $consumerTimeout);


            $output->writeln('Таймаут 5 сек');
            sleep(5);
        }

        return self::SUCCESS;
    }
}
