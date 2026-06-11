<?php
use Slim\App;
use App\Pedidos\Presentation\Repositories\PedidosRepository;

return function (App $app) {
    $app->get('/api/pedidos',                              [PedidosRepository::class, 'all']);
    $app->get('/api/pedidos/{id}',                         [PedidosRepository::class, 'detail']);
    $app->post('/api/pedidos',                             [PedidosRepository::class, 'create']);
    $app->patch('/api/pedidos/{id}/estado',                [PedidosRepository::class, 'cambiarEstado']);
    $app->post('/api/pedidos/{id}/detalles',               [PedidosRepository::class, 'addDetalle']);
    $app->put('/api/pedidos/{id}/detalles/{detalleId}',    [PedidosRepository::class, 'updateDetalle']);
    $app->delete('/api/pedidos/{id}/detalles/{detalleId}', [PedidosRepository::class, 'deleteDetalle']);

    $app->get('/api/health', function ($request, $response) {
        $response->getBody()->write(json_encode(['success' => true, 'service' => 'ms_pedidos', 'status' => 'running']));
        return $response->withHeader('Content-Type', 'application/json');
    });
};
