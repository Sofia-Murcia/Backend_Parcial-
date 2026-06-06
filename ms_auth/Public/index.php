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

require __DIR__ . '/../app/Routers/endpoints.php';

$app->run();
