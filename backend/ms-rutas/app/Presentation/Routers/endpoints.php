<?php

use App\Presentation\Repositories\ProgramacionesRepository;
use App\Presentation\Repositories\RutasRepository;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return function (App $app) {
    $auth = require __DIR__ . '/../../Middlewares/auth.php';

    // Todos los recursos requieren token Bearer; /health queda público.
    $app->group('', function (RouteCollectorProxy $group) {
        $group->get('/rutas', [RutasRepository::class, 'all']);
        $group->get('/programaciones', [ProgramacionesRepository::class, 'all']);
    })->add($auth);
};
