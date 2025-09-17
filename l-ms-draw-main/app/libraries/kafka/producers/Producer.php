<?php

namespace app\libraries\kafka\producers;

use app\libraries\kafka\config\Config;
use app\libraries\kafka\config\Sasl;
use app\libraries\kafka\exceptions\CouldNotPublishMessageException;
use app\libraries\kafka\messages\contracts\KafkaProducerMessage;
use app\libraries\kafka\messages\serializers\JsonSerializer;
use app\libraries\kafka\messages\serializers\MessageSerializer;
use RdKafka\Conf;
use RdKafka\Exception;
use RdKafka\Producer as KafkaProducer;
use RdKafka\ProducerTopic;

class Producer
{
    private Config $config;
    private string $topic;

    private MessageSerializer $serializer;

    private KafkaProducer $kafkaProducer;

    public function __construct(
        string $topic,
        Config $config
    ) {
        $this->config = $config;
        $this->topic = $topic;

        $this->serializer = new JsonSerializer();
    }

    /**
     * Использование сериализатора сообщений отличного от JsonSerializer
     *
     * @param MessageSerializer $serializer
     * @return Producer
     */
    public function usingSerializer(MessageSerializer $serializer): self
    {
        $this->serializer = $serializer;

        return $this;
    }

    /**
     * Создание экземпляра класса из конфига config('kafka.$connection);
     * @param string $connection
     * @param string $topic
     * @return self
     */
    public static function createFromConfigKey(string $connection, string $topic): self
    {
        $defaultOptions = config('kafka.' . $connection);
        $config = new Config(
            $defaultOptions['metadata_broker_list'],
            $defaultOptions['bootstrap_servers'],
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
            $topic,
            $config
        );
    }

    private function setConf(array $options): Conf
    {
        $conf = new Conf();

        foreach ($options as $key => $value) {
            $conf->set($key, $value);
        }

        return $conf;
    }

    /**
     * Сериализация сообщения, отправка сообщения в топик
     * @throws Exception
     * @throws CouldNotPublishMessageException
     */
    private function produce(KafkaProducerMessage $message, int $timeoutMs, int $partition, int $msFlags): void
    {
        $topic = $this->kafkaProducer->newTopic($this->topic);

        if (!is_string($message->getBody())) {
            $message = clone $message;
            $message = $this->serializer->serialize($message);
        }

        $this->produceMessage($topic, $message, $partition, $msFlags);

        $this->kafkaProducer->poll(0);
        // Ждём пока все сообщение в продюсере были отправлены
        $res = $this->kafkaProducer->flush($timeoutMs);

        if (RD_KAFKA_RESP_ERR_NO_ERROR === $res) {
            return;
        }

        $message = rd_kafka_err2str($res);
        throw CouldNotPublishMessageException::withMessage($message, $res);
    }

    /**
     * Отправка одного сообщения в топик
     * @throws Exception
     */
    private function produceMessage(
        ProducerTopic $topic,
        KafkaProducerMessage $message,
        int $partition,
        int $msFlags
    ): void {
        $topic->producev(
            $partition,
            $msFlags,
            $message->getBody(),
            $message->getKey(),
            $message->getHeaders()
        );
    }

    /**
     * Отправка сообщения в топик
     * @param KafkaProducerMessage $message
     * @param int|null $timeoutMs
     * @param int $partition - номер партиции, по-умолчанию = -1, позволяет librdkafka выбрать партицию автоматически.
     * @param int $msgflags - флаг сообщения, по-умолчанию = 0, либо RD_KAFKA_MSG_F_BLOCK (блокирующий режим)
     * @return void
     * @throws CouldNotPublishMessageException
     * @throws Exception
     */
    public function sendMessage(
        KafkaProducerMessage $message,
        ?int $timeoutMs = null,
        int $partition = RD_KAFKA_PARTITION_UA,
        int $msgflags = RD_KAFKA_MSG_F_BLOCK
    ): void {
        $this->kafkaProducer = new KafkaProducer(
            $this->setConf($this->config->getProducerOptions())
        );

        $timeout = $timeoutMs ?? config('kafka.default_produce_timeout_ms');

        $this->produce($message, $timeout, $partition, $msgflags);
    }
}
