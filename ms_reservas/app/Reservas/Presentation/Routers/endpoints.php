<?php
use Slim\App;
use App\Reservas\Presentation\Repositories\MesasRepository;
use App\Reservas\Presentation\Repositories\ReservasRepository;

return function (App $app) {
    // Mesas
    $app->get('/api/mesas',                  [MesasRepository::class, 'all']);
    $app->get('/api/mesas/{id}',             [MesasRepository::class, 'detail']);
    $app->post('/api/mesas',                 [MesasRepository::class, 'create']);
    $app->put('/api/mesas/{id}',             [MesasRepository::class, 'update']);
    $app->patch('/api/mesas/{id}/estado',    [MesasRepository::class, 'cambiarEstado']);

    // Reservas
    $app->get('/api/reservas',               [ReservasRepository::class, 'all']);
    $app->get('/api/reservas/{id}',          [ReservasRepository::class, 'detail']);
    $app->post('/api/reservas',              [ReservasRepository::class, 'create']);
    $app->put('/api/reservas/{id}',          [ReservasRepository::class, 'update']);
    $app->patch('/api/reservas/{id}/cancelar', [ReservasRepository::class, 'cancelar']);

    $app->get('/api/health', function ($request, $response) {
        $response->getBody()->write(json_encode(['success' => true, 'service' => 'ms_reservas', 'status' => 'running']));
        return $response->withHeader('Content-Type', 'application/json');
    });
};
