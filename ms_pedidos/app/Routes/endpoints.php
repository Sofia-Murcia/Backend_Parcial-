<?php

use App\Controllers\PedidoController;
use App\Controllers\DetallesController;
use App\Middleware\AuthMiddleware;

$app->group('/api', function ($group) {

    // ── Pedidos ────────────────────────────────────────────────
    $group->get('/pedidos',                    [PedidoController::class, 'index']);
    $group->get('/pedidos/{id}',               [PedidoController::class, 'show']);
    $group->post('/pedidos',                   [PedidoController::class, 'store']);
    $group->patch('/pedidos/{id}/estado',      [PedidoController::class, 'cambiarEstado']);

    $group->post('/pedidos/{id}/detalles',                        [DetallesController::class, 'store']);
    $group->put('/pedidos/{id}/detalles/{detalleId}',             [DetallesController::class, 'update']);
    $group->delete('/pedidos/{id}/detalles/{detalleId}',          [DetallesController::class, 'destroy']);

})->add(new AuthMiddleware());

$app->get('/api/health', function ($request, $response) {
    $response->getBody()->write(json_encode([
        'success' => true,
        'service' => 'ms-pedidos',
        'status'  => 'running',
    ]));
    return $response->withHeader('Content-Type', 'application/json');
});
