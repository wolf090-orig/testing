<?php

namespace app\libraries\kafka\messages;

abstract class KafkaMessage
{
    protected ?array $headers = [];
    protected $body = '';
    protected ?string $key = null;

    public function getBody()
    {
        return $this->body;
    }

    public function getHeaders(): ?array
    {
        return $this->headers;
    }

    public function getKey(): ?string
    {
        return $this->key;
    }
}
