<?php

namespace app\libraries\kafka\messages;

class KafkaConsumerMessage extends KafkaMessage implements contracts\KafkaConsumerMessage
{
    protected ?int $offset;
    protected ?int $timestamp;
    protected ?string $topicName;
    protected int $partition;

    public function __construct(
        ?string $topicName,
        $body,
        ?string $key,
        ?array $headers,
        ?int $offset,
        ?int $timestamp,
        int $partition
    ) {
        $this->timestamp = $timestamp;
        $this->offset = $offset;
        $this->key = $key;
        $this->body = $body;
        $this->headers = $headers;
        $this->topicName = $topicName;
        $this->partition = $partition;
    }

    public function getOffset(): ?int
    {
        return $this->offset;
    }

    public function getTimestamp(): ?int
    {
        return $this->timestamp;
    }

    /**
     * @return string|null
     */
    public function getTopicName(): ?string
    {
        return $this->topicName;
    }

    /**
     * @return int
     */
    public function getPartition(): int
    {
        return $this->partition;
    }
}
