<?php

use app\controller\CommandController;
use Webman\Route;

// Define a route for the health check endpoint
Route::any('/healthcheck', [app\controller\HealthcheckController::class, 'index']);

Route::any("/command", [CommandController::class, 'executeCommand']);
