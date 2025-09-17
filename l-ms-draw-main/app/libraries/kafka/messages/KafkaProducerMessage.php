<?php

namespace app\libraries\kafka\messages;

class KafkaProducerMessage extends KafkaMessage implements contracts\KafkaProducerMessage
{
    /**
     * @param array|null $headers
     * @param mixed $body
     * @param string|null $key
     */
    public function __construct(
        $body = [],
        ?string $key = null,
        ?array $headers = []
    ) {
        $this->key = $key;
        $this->body = $body;
        $this->headers = $headers;
    }

    /**
     * Set a key in the message array.
     *
     * @param string $key
     * @param mixed $message
     * @return $this
     */
    public function withBodyKey(string $key, $message): KafkaProducerMessage
    {
        $this->body[$key] = $message;

        return $this;
    }

    /**
     * Unset a key in the message array.
     *
     * @param string $key
     * @return $this
     */
    public function forgetBodyKey(string $key): KafkaProducerMessage
    {
        unset($this->body[$key]);

        return $this;
    }

    /**
     * Set the message headers.
     *
     * @param array $headers
     * @return $this
     */
    public function withHeaders(array $headers = []): KafkaProducerMessage
    {
        $this->headers = $headers;

        return $this;
    }

    /**
     * Set the kafka message key.
     *
     * @param string|null $key
     * @return $this
     */
    public function withKey(?string $key): KafkaProducerMessage
    {
        $this->key = $key;

        return $this;
    }

    public function toArray(): array
    {
        return [
            'payload' => $this->body,
            'key' => $this->key,
            'headers' => $this->headers,
        ];
    }

    public function withBody($body): KafkaProducerMessage
    {
        $this->body = $body;

        return $this;
    }

    public function withHeader(string $key, $value): KafkaProducerMessage
    {
        $this->headers[$key] = $value;

        return $this;
    }
}
