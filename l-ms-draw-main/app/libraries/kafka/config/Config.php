<?php

namespace app\libraries\kafka\config;

class Config
{
    private ?string $groupId = null;
    private bool $autoCommit = true;
    private string $brokers;

    private string $bootstrapServers;

    private ?Sasl $sasl = null;

    // Все возможные опции для Producer
    private const PRODUCER_ONLY_CONFIG_OPTIONS = [
        'transactional.id',
        'transaction.timeout.ms',
        'enable.idempotence',
        'enable.gapless.guarantee',
        'queue.buffering.max.messages',
        'queue.buffering.max.kbytes',
        'queue.buffering.max.ms',
        'linger.ms',
        'message.send.max.retries',
        'retries',
        'retry.backoff.ms',
        'queue.buffering.backpressure.threshold',
        'compression.codec',
        'compression.type',
        'batch.num.messages',
        'batch.size',
        'delivery.report.only.error',
        'dr_cb',
        'dr_msg_cb',
        'sticky.partitioning.linger.ms',
    ];
    // Все возможные опции для Consumer
    private const CONSUMER_ONLY_CONFIG_OPTIONS = [
        'partition.assignment.strategy',
        'session.timeout.ms',
        'heartbeat.interval.ms',
        'group.protocol.type',
        'coordinator.query.interval.ms',
        'max.poll.interval.ms',
        'enable.auto.commit',
        'auto.commit.interval.ms',
        'enable.auto.offset.store',
        'queued.min.messages',
        'queued.max.messages.kbytes',
        'fetch.wait.max.ms',
        'fetch.message.max.bytes',
        'max.partition.fetch.bytes',
        'fetch.max.bytes',
        'fetch.min.bytes',
        'fetch.error.backoff.ms',
        'offset.store.method',
        'isolation.level',
        'consume_cb',
        'rebalance_cb',
        'offset_commit_cb',
        'enable.partition.eof',
        'check.crcs',
        'allow.auto.create.topics',
        'auto.offset.reset',
    ];
    private array $customOptions = [];

    public function __construct(
        string $brokers,
        string $bootstrapServers,
        ?string $groupId = null,
        bool $autoCommit = true,
        array $customOptions = [],
        ?Sasl $sasl = null
    ) {
        $this->autoCommit = $autoCommit;
        $this->groupId = $groupId;
        $this->brokers = $brokers;
        $this->bootstrapServers = $bootstrapServers;
        $this->customOptions = $customOptions;
        $this->sasl = $sasl;
    }

    public function getAutoCommit(): bool
    {
        return $this->autoCommit;
    }

    /**
     * Включить SASL авторизацию
     */
    public function withSasl(Sasl $sasl): self
    {
        $this->sasl = $sasl;

        return $this;
    }

    /**
     * Simple Authentication and Security Layer (SASL)
     * Настройки для подключения к кафке с авторизацией
     */
    private function getSaslOptions(): array
    {
        if ($this->sasl !== null) {
            return [
                'sasl.username' => $this->sasl->getUsername(),
                'sasl.password' => $this->sasl->getPassword(),
                'sasl.mechanisms' => $this->sasl->getMechanisms(),
                'security.protocol' => $this->sasl->getSecurityProtocol(),
            ];
        }

        return [];
    }

    public function setCustomOptions(array $customOptions): self
    {
        $this->customOptions = $customOptions;

        return $this;
    }

    /**
     * Получение опций для Producer
     */
    public function getProducerOptions(): array
    {
        $config = [
            'bootstrap.servers' => $this->bootstrapServers,
            'metadata.broker.list' => $this->brokers,
        ];

        return collect(array_merge($config, $this->customOptions, $this->getSaslOptions()))
            ->reject(fn(string $option, string $key) => in_array($key, self::CONSUMER_ONLY_CONFIG_OPTIONS))
            ->toArray();
    }

    /**
     * Получение опций для Consumer
     */
    public function getConsumerOptions(): array
    {
        $options = [
            'metadata.broker.list' => $this->brokers,
            'bootstrap.servers' => $this->bootstrapServers,
            'auto.offset.reset' => config('kafka.offset_reset', 'earliest'),
            'enable.auto.commit' => $this->autoCommit ? 'true' : 'false',
            'group.id' => $this->groupId,
        ];

        return collect(array_merge($options, $this->customOptions, $this->getSaslOptions()))
            ->reject(fn(string $option, string $key) => in_array($key, self::PRODUCER_ONLY_CONFIG_OPTIONS))
            ->toArray();
    }
}
