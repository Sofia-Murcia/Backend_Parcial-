<?php

use App\Controllers\MesaController;
use App\Controllers\ReservaController;
use App\Middleware\AuthMiddleware;

$app->group('/api', function ($group) {

    $group->get('/mesas',                   [MesaController::class, 'index']);
    $group->get('/mesas/{id}',              [MesaController::class, 'show']);
    $group->post('/mesas',                  [MesaController::class, 'store']);
    $group->put('/mesas/{id}',              [MesaController::class, 'update']);
    $group->patch('/mesas/{id}/estado',     [MesaController::class, 'cambiarEstado']);
    $group->delete('/mesas/{id}',           [MesaController::class, 'destroy']);

    $group->get('/reservas',                    [ReservaController::class, 'index']);
    $group->get('/reservas/{id}',               [ReservaController::class, 'show']);
    $group->post('/reservas',                   [ReservaController::class, 'store']);
    $group->put('/reservas/{id}',               [ReservaController::class, 'update']);
    $group->patch('/reservas/{id}/estado',      [ReservaController::class, 'cambiarEstado']);
    $group->patch('/reservas/{id}/cancelar',    [ReservaController::class, 'cancelar']);

})->add(new AuthMiddleware());

$app->get('/api/health', function ($request, $response) {
    $response->getBody()->write(json_encode([
        'success' => true,
        'service' => 'ms-reservas',
        'status'  => 'running',
    ]));
    return $response->withHeader('Content-Type', 'application/json');
});
