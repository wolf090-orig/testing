<?php

namespace app\libraries\kafka\messages\serializers;

use app\libraries\kafka\messages\contracts\KafkaProducerMessage;

interface MessageSerializer
{
    public function serialize(KafkaProducerMessage $message): KafkaProducerMessage;
}
