<?php

namespace app\classes\Debug;

use Carbon\Carbon;

class DebugLog
{
    public string $service;
    public Carbon $dateTime;
    public string $request;
    public mixed $params = null;
    public mixed $response = null;

    /**
     * @param string $service
     * @param string $request
     * @param Carbon $dateTime
     * @param array $params
     * @param mixed|null $response
     */
    public function __construct(string $service, string $request, Carbon $dateTime, mixed $params = null, mixed $response = null)
    {
        $this->service = $service;
        $this->request = $request;
        $this->dateTime = $dateTime;
        $this->params = $params;
        $this->response = $response;
    }


}
