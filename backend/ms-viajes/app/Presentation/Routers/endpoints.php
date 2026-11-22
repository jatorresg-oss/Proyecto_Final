<?php

use App\Presentation\Repositories\SeguimientosRepository;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return function (App $app) {
    $auth = require __DIR__ . '/../../Middlewares/auth.php';

    // Todos los recursos requieren token Bearer; /health queda público.
    $app->group('', function (RouteCollectorProxy $group) {
        $group->get('/seguimientos', [SeguimientosRepository::class, 'all']);
    })->add($auth);
};
