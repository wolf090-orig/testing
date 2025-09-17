<?php

namespace app\libraries\kafka\messages\serializers;

use app\libraries\kafka\messages\contracts\KafkaProducerMessage;
use JsonException;

class JsonSerializer implements MessageSerializer
{
    /**
     * @throws JsonException
     */
    public function serialize(KafkaProducerMessage $message): KafkaProducerMessage
    {
        $body = json_encode($message->getBody(), JSON_THROW_ON_ERROR);

        return $message->withBody($body);
    }
}
