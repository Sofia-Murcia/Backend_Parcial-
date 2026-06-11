<?php
use Slim\App;
use App\Auth\Presentation\Repositories\AuthRepository;

return function (App $app) {
    $app->post('/api/login',   [AuthRepository::class, 'login']);
    $app->post('/api/logout',  [AuthRepository::class, 'logout']);
    $app->get('/api/validate', [AuthRepository::class, 'validate']);

    $app->get('/api/health', function ($request, $response) {
        $response->getBody()->write(json_encode([
            'success' => true,
            'service' => 'ms_auth',
            'status'  => 'running',
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    });
};
