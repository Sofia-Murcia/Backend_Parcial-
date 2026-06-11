<?php
use Slim\App;
use App\Productos\Presentation\Repositories\ProductosRepository;

return function (App $app) {
    $app->get('/api/categorias',         [ProductosRepository::class, 'allCategorias']);
    $app->get('/api/productos',          [ProductosRepository::class, 'all']);
    $app->get('/api/productos/{id}',     [ProductosRepository::class, 'detail']);
    $app->post('/api/productos',         [ProductosRepository::class, 'create']);
    $app->put('/api/productos/{id}',     [ProductosRepository::class, 'update']);
    $app->delete('/api/productos/{id}',  [ProductosRepository::class, 'delete']);

    $app->get('/api/health', function ($request, $response) {
        $response->getBody()->write(json_encode(['success' => true, 'service' => 'ms_productos', 'status' => 'running']));
        return $response->withHeader('Content-Type', 'application/json');
    });
};
