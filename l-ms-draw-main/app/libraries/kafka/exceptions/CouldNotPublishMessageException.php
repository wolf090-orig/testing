<?php

namespace app\libraries\kafka\exceptions;

class CouldNotPublishMessageException extends LaravelKafkaException
{
    public static function withMessage(string $message, int $code): self
    {
        return new static("Your message could not be published. Flush returned with error code $code: '$message'");
    }
}
