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

        // Programación de viajes (Día 10). No se expone DELETE: se cancela con PATCH.
        $group->get('/programaciones', [ProgramacionesRepository::class, 'all']);
        $group->get('/programaciones/{id}', [ProgramacionesRepository::class, 'show']);
        $group->post('/programaciones', [ProgramacionesRepository::class, 'store']);
        $group->put('/programaciones/{id}', [ProgramacionesRepository::class, 'update']);
        $group->patch('/programaciones/{id}', [ProgramacionesRepository::class, 'update']);
    })->add($auth);
};
