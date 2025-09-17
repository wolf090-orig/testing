<?php

namespace app\controller;

use app\model\LotteryNumber;
use app\repository\ticket\TicketRepositoryDB;
use app\services\HealthCheckService;
use app\services\LotteryGeneratorService;
use support\Request;

class IndexController
{
    /**
     * Handle the incoming request.
     *
     * @param Request $request The incoming request.
     * @return mixed The response to the request.
     */
    public function index(Request $request, HealthCheckService $healthCheckService)
    {
        if (config('app.debug') == 'true') {
            return $this->readme($request);
        }
        $data = $healthCheckService->performHealthCheck($request);
        return success($data);
    }

    /**
     * Return the contents of the README.md file.
     *
     * @param Request $request The incoming request.
     * @return string The contents of the README.md file.
     */
    protected function readme(Request $request)
    {
        static $readme;
        if (!$readme) {
            $readme = file_get_contents(base_path('README.md'));
        }
        return $readme;
    }
}
