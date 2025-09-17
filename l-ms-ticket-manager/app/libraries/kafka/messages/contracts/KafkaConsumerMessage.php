<?php

namespace app\libraries\kafka\messages\contracts;

interface KafkaConsumerMessage extends KafkaMessage
{
    public function getOffset(): ?int;

    public function getTimestamp(): ?int;

    public function getTopicName(): ?string;

    public function getPartition(): ?int;
}
