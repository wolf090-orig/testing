<?php

namespace app\libraries\kafka\consumers;

use app\libraries\kafka\config\Config;
use app\libraries\kafka\config\Sasl;
use app\libraries\kafka\exceptions\KafkaConsumerException;
use app\libraries\kafka\MessageCounter;
use app\libraries\kafka\messages\contracts\KafkaConsumerMessage;
use Closure;
use \support\Log;
use Psr\Log\LoggerInterface;
use RdKafka\Conf;
use RdKafka\Exception;
use RdKafka\KafkaConsumer;
use RdKafka\Message as KafkaMessage;
use Throwable;

class Consumer
{
    private Config $config;
    private array $topics;

    protected Closure $handler;
    protected ?bool $autoCommit = null;

    private KafkaConsumer $consumer;

    private MessageCounter $messageCounter;

    protected ?string $groupId;

    private bool $stopRequested = false;

    private bool $stopAfterFailCommit = false;

    private ?LoggerInterface $logger;

    private bool $enableDebug;

    private const IGNORABLE_CONSUMER_ERRORS = [
        RD_KAFKA_RESP_ERR__PARTITION_EOF,
        RD_KAFKA_RESP_ERR__TRANSPORT,
        RD_KAFKA_RESP_ERR_REQUEST_TIMED_OUT,
        RD_KAFKA_RESP_ERR__TIMED_OUT,
    ];

    private const CONSUME_STOP_EOF_ERRORS = [
        RD_KAFKA_RESP_ERR__PARTITION_EOF,
        RD_KAFKA_RESP_ERR__TIMED_OUT,
    ];

    private const IGNORABLE_COMMIT_ERRORS = [
        RD_KAFKA_RESP_ERR__NO_OFFSET,
    ];

    public function __construct(
        Config $config,
        array $topics = [],
        ?string $groupId = null,
        ?LoggerInterface $logger = null
    ) {
        $this->config = $config;
        $this->topics = $topics;
        $this->groupId = $groupId;
        $this->autoCommit = $config->getAutoCommit();
        $this->logger = $logger ?? Log::channel();
        $this->enableDebug = config('kafka.enable_debug', false);
    }

    /**
     * Создание экземпляра класса из конфига config('kafka.$connection);
     * @param string $connection
     * @param array $topics
     * @return self
     */
    public static function createFromConfigKey(string $connection, array $topics, ?LoggerInterface $logger = null): self
    {
        $defaultOptions = config('kafka.' . $connection);

        $config = new Config(
            $defaultOptions['metadata_broker_list'],
            $defaultOptions['bootstrap_servers'],
            $defaultOptions['consumer_group_id'] ?? 'group',
            $defaultOptions['auto_commit'] ?? true,
        );
        if ($defaultOptions['with_sassl']) {
            $saslOptions = $defaultOptions['sasl_options'];
            $sasl = new Sasl(
                $saslOptions['sasl_username'],
                $saslOptions['sasl_password'],
                $saslOptions['sasl_mechanisms'],
                $saslOptions['sasl_protocol'] ?? 'SASL_PLAINTEXT'
            );
            $config->withSasl($sasl);
        }

        return new self(
            $config,
            $topics,
            $defaultOptions['consumer_group_id'] ?? null,
            $logger
        );
    }

    /**
     * Добавление топиков для подписки
     * @param ...$topics
     * @return $this
     */
    public function subscribe(...$topics): self
    {
        if (is_array($topics[0])) {
            $topics = $topics[0];
        }

        foreach ($topics as $topic) {
            if (!in_array($topic, $this->topics)) {
                $this->topics[] = $topic;
            }
        }

        return $this;
    }

    public function withConsumerGroupId(?string $groupId): self
    {
        $this->groupId = $groupId;

        return $this;
    }

    /**
     * Обработчик сообщений из топиков
     * @param callable $handler
     * @return $this
     */
    public function withHandler(callable $handler): self
    {
        $this->handler = Closure::fromCallable($handler);

        return $this;
    }

    /**
     * Управление настройкой авто коммита, если отключить - после прочтения сообщения из топика автоматически оффсет изменяться не будет
     * @param bool $autoCommit
     * @return $this
     */
    public function withAutoCommit(bool $autoCommit = true): self
    {
        $this->autoCommit = $autoCommit;

        return $this;
    }

    /**
     * Метод для определения достигнут ли лимит сообщений
     *
     * @return bool
     */
    private function maxMessagesLimitReached(): bool
    {
        return $this->messageCounter->maxMessagesLimitReached();
    }

    /**
     * Метод для запуска консьюмера
     * @param int $maxMessages
     * @return void
     * @throws KafkaConsumerException
     * @throws Throwable
     * @throws Exception
     */
    public function consume(int $maxMessages = -1, ?int $timeoutMs = null): void
    {
        if (is_null($timeoutMs)) {
            $timeoutMs = config('kafka.default_consumer_timeout');
        }

        $this->messageCounter = new MessageCounter($maxMessages);
        $this->consumer = new KafkaConsumer(
            $this->setConf(
                $this->config->getConsumerOptions(),
            )
        );
        $this->consumer->subscribe($this->topics);

        do {
            $this->doConsume($timeoutMs);
        } while (!$this->maxMessagesLimitReached() && !$this->stopRequested);
    }

    private function setDebugOptions(Conf $conf): Conf
    {
        $conf->set('log_level', (string)LOG_DEBUG);
        $conf->set('debug', 'all');
        $conf->setLogCb(
            function ($rdkafka, int $level, string $facility, string $message): void {
                echo sprintf("log: %d %s %s", $level, $facility, $message) . PHP_EOL;
            }
        );

        return $conf;
    }

    private function setConf(array $options): Conf
    {
        $conf = new Conf();

        foreach ($options as $key => $value) {
            $conf->set($key, $value);
        }

        if (!is_null($this->autoCommit)) {
            $conf->set('enable.auto.commit', $this->autoCommit ? 'true' : 'false');
        }

        if ($this->enableDebug) {
            $conf = $this->setDebugOptions($conf);
        }

        return $conf;
    }

    /**
     * @throws KafkaConsumerException
     * @throws Throwable
     */
    private function doConsume(int $timeoutMs): void
    {
        $message = $this->consumer->consume($timeoutMs);
        $this->handleMessage($message);
    }

    private function stopConsuming(): void
    {
        $this->stopRequested = true;
    }

    /**
     * Остановки обработки сообщений консьмера после фейла коммита
     * @return $this
     */
    public function stopAfterFailCommit(): self
    {
        $this->stopAfterFailCommit = true;
        return $this;
    }

    /**
     * Обработка сообщения из топика
     * @throws KafkaConsumerException
     * @throws Throwable
     */
    private function handleMessage(KafkaMessage $message): void
    {
        if (RD_KAFKA_RESP_ERR_NO_ERROR === $message->err) {
            $this->messageCounter->add();

            $this->executeMessage($message);

            return;
        }

        if (in_array($message->err, self::CONSUME_STOP_EOF_ERRORS, true)) {
            $this->stopConsuming();
        }

        if (!in_array($message->err, self::IGNORABLE_CONSUMER_ERRORS, true)) {
            $this->logger->error("Error to consume message", [
                'message' => $message,
            ]);

            throw new KafkaConsumerException($message->errstr(), $message->err);
        }
    }

    /**
     * @throws Throwable
     */
    private function executeMessage(KafkaMessage $message): void
    {
        try {
            $consumedMessage = $this->getConsumerMessage($message);

            $this->handler->call($this, $consumedMessage);

            $success = true;
        } catch (Throwable $throwable) {
            $this->logger->error("Error to handle consume message", [
                'message' => $message,
                'exception' => $throwable->getMessage(),
                'file' => $throwable->getFile(),
                'line' => $throwable->getLine(),
                'trace' => $throwable->getTraceAsString(),
            ]);
            $success = false;
        }

        $this->commit($message, $success);
    }

    /**
     * @throws Throwable
     */
    private function commit(KafkaMessage $message, bool $success): void
    {
        try {
            if (!$success) {
                if ($this->stopAfterFailCommit) {
                    $this->stopConsuming();
                }
                return;
            }
            $this->consumer->commit($message);
        } catch (Throwable $throwable) {
            if ($throwable->getCode() !== self::IGNORABLE_COMMIT_ERRORS) {
                $this->logger->error("Error to commit consume message", [
                    'message' => $message,
                    'exception' => $throwable->getMessage(),
                ]);

                throw $throwable;
            }
        }
    }

    /**
     * Преобразование сообщения из Кафки, в сообщение для обработчика
     */
    private function getConsumerMessage(KafkaMessage $message): KafkaConsumerMessage
    {
        return new \app\libraries\kafka\messages\KafkaConsumerMessage(
            $message->topic_name,
            json_decode($message->payload, true), //todo: $this->desirializer->deserialize($message->payload) ...
            $message->key,
            $message->headers,
            $message->offset,
            $message->timestamp,
            $message->partition,
        );
    }

    /**
     * Получение последнего оффсета для топика
     */
    public function getLastOffset(string $topic, int $partition = 0, int $timeoutMs = 1000): int
    {
        $low = null;
        $high = null;

        $this->consumer->queryWatermarkOffsets($topic, $partition, $low, $high, $timeoutMs);

        return $high - 1;
    }
}
