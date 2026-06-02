<?php

use App\Controllers\AuthController;
use App\Middleware\AuthMiddleware;

$app->post('/api/login', [AuthController::class, 'login']);

$app->group('/api', function ($group) {
    $group->post('/logout',   [AuthController::class, 'logout']);
    $group->get('/validate',  [AuthController::class, 'validate']);
})->add(new AuthMiddleware());

$app->get('/api/health', function ($request, $response) {
    $response->getBody()->write(json_encode([
        'success' => true,
        'service' => 'ms-auth',
        'status'  => 'running',
    ]));
    return $response->withHeader('Content-Type', 'application/json');
});
