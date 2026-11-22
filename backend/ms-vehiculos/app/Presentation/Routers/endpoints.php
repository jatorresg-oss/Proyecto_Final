<?php

use App\Presentation\Repositories\VehiculosRepository;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return function (App $app) {
    $auth = require __DIR__ . '/../../Middlewares/auth.php';

    // Todos los recursos requieren token Bearer; /health queda público.
    $app->group('', function (RouteCollectorProxy $group) {
        $group->get('/vehiculos', [VehiculosRepository::class, 'all']);
    })->add($auth);
};
