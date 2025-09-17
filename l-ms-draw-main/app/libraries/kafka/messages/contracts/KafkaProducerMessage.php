<?php

namespace app\libraries\kafka\messages\contracts;

interface KafkaProducerMessage extends KafkaMessage
{
    public function withKey(?string $key): KafkaProducerMessage;

    public function withBody($body): KafkaProducerMessage;

    public function withHeaders(array $headers = []): KafkaProducerMessage;

    public function withHeader(string $key, $value): KafkaProducerMessage;
}
