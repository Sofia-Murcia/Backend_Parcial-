<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$capsule = new Illuminate\Database\Capsule\Manager();

$capsule->addConnection([
    'driver'    => 'mysql',
    'host'      => $_ENV['DB_HOST']     ?? '127.0.0.1',
    'port'      => $_ENV['DB_PORT']     ?? '3306',
    'database'  => $_ENV['DB_DATABASE'] ?? 'db_pedidos',
    'username'  => $_ENV['DB_USERNAME'] ?? 'root',
    'password'  => $_ENV['DB_PASSWORD'] ?? '',
    'charset'   => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix'    => '',
], 'default');

$capsule->addConnection([
    'driver'    => 'mysql',
    'host'      => $_ENV['DB_HOST']          ?? '127.0.0.1',
    'port'      => $_ENV['DB_PORT']          ?? '3306',
    'database'  => $_ENV['DB_AUTH_DATABASE'] ?? 'db_auth',
    'username'  => $_ENV['DB_USERNAME']      ?? 'root',
    'password'  => $_ENV['DB_PASSWORD']      ?? '',
    'charset'   => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix'    => '',
], 'auth');

$capsule->setAsGlobal();
$capsule->bootEloquent();

$app = Slim\Factory\AppFactory::create();

$app->addBodyParsingMiddleware();

$app->addErrorMiddleware(
    displayErrorDetails: ($_ENV['APP_ENV'] ?? 'production') === 'development',
    logErrors: true,
    logErrorDetails: true
);

$app->add(new App\Middleware\CorsMiddleware());

require __DIR__ . '/../app/Routes/endpoints.php';

$app->run();
