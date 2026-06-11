<?php
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../app/Config/database.php';

$cors      = require __DIR__ . '/../app/Middlewares/CorsMiddleware.php';
$endpoints = require __DIR__ . '/../app/Auth/Presentation/Routers/endpoints.php';

$app = AppFactory::create();

$app->addBodyParsingMiddleware();

$endpoints($app);   // rutas primero

$cors($app);        // CORS al final → se ejecuta PRIMERO por LIFO

$app->run();
