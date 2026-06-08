<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

App\Config\Database::initialize();

$app = Slim\Factory\AppFactory::create();

$app->addBodyParsingMiddleware();

$app->addErrorMiddleware(
    displayErrorDetails: ($_ENV['APP_ENV'] ?? 'production') === 'development',
    logErrors: true,
    logErrorDetails: true
);

$app->options('/{routes:.+}', fn($req, $res) => $res);

$app->add(function (\Psr\Http\Message\ServerRequestInterface $request, $handler) {
    $origin   = $request->getHeaderLine('Origin') ?: '*';
    $response = $handler->handle($request);
    $response = $response
        ->withHeader('Access-Control-Allow-Origin',      $origin)
        ->withHeader('Access-Control-Allow-Headers',     'X-Requested-With, Content-Type, Accept, Origin, Authorization')
        ->withHeader('Access-Control-Allow-Methods',     'GET, POST, PUT, PATCH, DELETE, OPTIONS')
        ->withHeader('Access-Control-Allow-Credentials', 'true');

    if ($request->getMethod() === 'OPTIONS') {
        return $response->withStatus(200);
    }

    return $response;
});


require __DIR__ . '/../app/Routes/endpoints.php';

$app->run();
