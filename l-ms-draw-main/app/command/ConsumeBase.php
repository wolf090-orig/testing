<?php

namespace app\command;

use app\libraries\kafka\consumers\Consumer;
use app\libraries\kafka\messages\contracts\KafkaConsumerMessage;
use app\services\DlqService;
use Closure;
use Psr\Log\LoggerInterface;
use support\Log;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class ConsumeBase extends Command
{
    protected string $topic;

    /**
     * Настройка топика для консьюмера
     */
    abstract public function setUp(): void;

    /**
     * Логика обработки сообщений
     */
    abstract public function consumerLogic(): Closure;

    /**
     * Получить канал логирования для команды
     */
    abstract protected function getLogChannel(): string;

    protected function configure(): void
    {
        $this->addArgument('topic', InputArgument::OPTIONAL, 'Topic name override');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->setUp();

        $logChannel = $this->getLogChannel();
        $logger = Log::channel($logChannel);

        $logger->info("Запуск консьюмера для топика: {$this->topic}");

        try {
            while (true) {
                $consumerTimeout = 15000;
                $messagesBatchSize = 1000;

                $msg = "Подписка на топик {$this->topic} с таймаутом {$consumerTimeout}ms и лимитом {$messagesBatchSize} сообщений";
                $logger->info($msg);

                $consumer = $this->createConsumer($logger);
                $consumer->consume($messagesBatchSize, $consumerTimeout);

                $output->writeln('Таймаут 5 сек');
                sleep(5);
            }
        } catch (\Exception $e) {
            $logger->error('Критическая ошибка консьюмера', ['error' => $e->getMessage()]);
            return self::FAILURE;
        }
    }

    protected function createConsumer(LoggerInterface $logger): Consumer
    {
        $logic = $this->consumerLogic();
        $dlqService = new DlqService();

        return Consumer::createFromConfigKey('tickets', [$this->topic])
            ->withHandler(function (KafkaConsumerMessage $message) use ($logic, $dlqService, $logger) {

                $body = (array)$message->getBody();
                $headers = (array)$message->getHeaders();

                $success = $dlqService->processWithDlq(
                    $message->getTopicName(),
                    $body,
                    $headers,
                    function ($messageBody, $messageHeaders) use ($logic, $logger) {
                        $logic($messageBody, $messageHeaders, $logger);
                    }
                );

                if ($success) {
                    $logger->info('Сообщение обработано успешно', [
                        'topic' => $message->getTopicName(),
                        'partition' => $message->getPartition(),
                        'offset' => $message->getOffset(),
                        'lottery_id' => $headers['lottery_id'] ?? $body['lottery_id'] ?? null,
                    ]);
                }

                // Статистика обработки отключена для упрощения
            })
            ->stopAfterFailCommit()
            ->withAutoCommit();
    }

    private function logProcessingStats(KafkaConsumerMessage $message, LoggerInterface $logger): void
    {
        try {
            $lastOffset = $this->getLastOffset($message->getTopicName(), $message->getPartition());
            $currentOffset = $message->getOffset();
            $totalMessages = $lastOffset + 1;

            $logger->info("Статистика обработки", [
                'current_offset' => $currentOffset,
                'total_messages' => $totalMessages,
                'partition' => $message->getPartition(),
                'last_offset' => $lastOffset,
            ]);
        } catch (\Exception $e) {
            // Игнорируем ошибки получения статистики
            $logger->debug('Не удалось получить статистику: ' . $e->getMessage());
        }
    }

    /**
     * Получение последнего оффсета для топика
     * TODO: вынести в отдельный сервис
     */
    private function getLastOffset(string $topic, int $partition = 0, int $timeoutMs = 1000): int
    {
        // Временная заглушка, так как метод должен быть в Consumer
        return 0;
    }

    public function run(InputInterface $input, OutputInterface $output): int
    {
        return $this->execute($input, $output);
    }
}
