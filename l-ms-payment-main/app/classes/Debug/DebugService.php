<?php

namespace app\classes\Debug;

use Carbon\Carbon;
use Exception;

final class DebugService
{
    /**
     * Режим включения логирования запросов
     * 0 - выкл.
     * 1 - вкл. (запрос + параметры)
     * 2 - вкл.(запрос + параметры + ответ)
     */
//    private static ?DebugService $instance = null;
    private int $mode = 0;
    private array $logs = [];

    public function __construct()
    {
    }

//    public static function getInstance(): ?self
//    {
//        if (self::$instance === null) {
//            self::$instance = new self();
//        }
//
//        return self::$instance;
//    }
//
//    private function __clone()
//    {
//    }

//    public function __wakeup()
//    {
//        throw new Exception("Cannot unserialize singleton");
//    }

    private function addLog(
        string $serviceName,
        string $request,
        mixed  $params = [],
        mixed  $response = null,
    ): void
    {
        if (!$this->isEnable()) return;

        $this->logs[] = new DebugLog(
            $serviceName,
            $request,
            Carbon::now(),
            $params,
            $this->mode > 1 ? $response : null
        );
    }

    public function setMode(int $mode): self
    {
        $this->mode = $mode;
        return $this;
    }

    public function addRedisLog(string $request, mixed $params = [], mixed $response = null): void
    {
        $this->addLog('redis', $request, $params, $response);
    }

    public function addDbLog(string $request, mixed $params = [], mixed $response = null): void
    {
        $this->addLog('db', $request, $params, $response);
    }

    public function clearLogs(): self
    {
        $this->logs = [];
        return $this;
    }

    public function getLogs(): array
    {
        return $this->logs;
    }

    public function isEnable(): bool
    {
        return $this->mode > 0;
    }
}
