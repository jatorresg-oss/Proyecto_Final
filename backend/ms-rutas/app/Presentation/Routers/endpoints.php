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
        $group->get('/rutas/buscar', [RutasRepository::class, 'buscar']);
        $group->get('/rutas/ciudad/{ciudad}', [RutasRepository::class, 'porCiudad']);
        $group->get('/rutas/tiempos', [RutasRepository::class, 'tiempos']);
        $group->get('/rutas/{id}', [RutasRepository::class, 'show']);
        $group->post('/rutas', [RutasRepository::class, 'store']);
        $group->put('/rutas/{id}', [RutasRepository::class, 'update']);
        $group->patch('/rutas/{id}', [RutasRepository::class, 'update']);
        $group->delete('/rutas/{id}', [RutasRepository::class, 'destroy']);

        // La programación se amplía en el Día 10; se mantiene el listado actual.
        $group->get('/programaciones', [ProgramacionesRepository::class, 'all']);
    })->add($auth);
};
