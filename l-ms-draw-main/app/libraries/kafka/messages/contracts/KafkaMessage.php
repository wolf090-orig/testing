<?php

namespace app\libraries\kafka\messages\contracts;

interface KafkaMessage
{
    /**
     * @return string|null
     */
    public function getKey(): ?string;

    /**
     * @return array|null
     */
    public function getHeaders(): ?array;

    /**
     * @return array|mixed|string
     */
    public function getBody();
}
