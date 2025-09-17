<?php

namespace app\controller;

use app\services\HealthCheckService;
use support\Request;

class HealthcheckController
{
    /**
     * Handle the incoming request.
     *
     * @param Request $request The incoming request.
     * @return mixed The response to the request.
     */
    public function index(Request $request, HealthCheckService $healthCheckService)
    {
        $data = $healthCheckService->performHealthCheck($request);
        return success($data);
    }
}
