<?php

use App\Controllers\CategoriaController;
use App\Controllers\ProductoController;
use App\Middleware\AuthMiddleware;

$app->group('/api', function ($group) {

    $group->get('/categorias',           [CategoriaController::class, 'index']);
    $group->get('/categorias/{id}',      [CategoriaController::class, 'show']);
    $group->post('/categorias',          [CategoriaController::class, 'store']);
    $group->put('/categorias/{id}',      [CategoriaController::class, 'update']);
    $group->delete('/categorias/{id}',   [CategoriaController::class, 'destroy']);

    $group->get('/productos',                        [ProductoController::class, 'index']);
    $group->get('/productos/{id}',                   [ProductoController::class, 'show']);
    $group->post('/productos',                       [ProductoController::class, 'store']);
    $group->put('/productos/{id}',                   [ProductoController::class, 'update']);
    $group->delete('/productos/{id}',                [ProductoController::class, 'destroy']);
    $group->patch('/productos/{id}/disponibilidad',  [ProductoController::class, 'cambiarDisponibilidad']);

})->add(new AuthMiddleware());

$app->get('/api/health', function ($request, $response) {
    $response->getBody()->write(json_encode([
        'success' => true,
        'service' => 'ms-productos',
        'status'  => 'running',
    ]));
    return $response->withHeader('Content-Type', 'application/json');
});
