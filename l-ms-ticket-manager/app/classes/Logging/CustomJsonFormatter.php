<?php

namespace app\classes\Logging;

use Monolog\Formatter\JsonFormatter;

class CustomJsonFormatter extends JsonFormatter
{
    public function format(array $record): string
    {
        $data = [
            'date_time' => $record['datetime']->format('Y-m-d\TH:i:s.uP'),
            'level' => $record['level_name'],
            'channel' => $record['channel'],
            'msg' => $record['message'],
            'context' => $record['context'],
        ];

        return $this->toJson($data, true) . "\n";
    }
}
