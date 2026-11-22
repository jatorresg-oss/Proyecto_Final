<?php

use App\Presentation\Repositories\ConductoresRepository;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return function (App $app) {
    $auth = require __DIR__ . '/../../Middlewares/auth.php';

    // Todos los recursos de conductores requieren token Bearer.
    $app->group('', function (RouteCollectorProxy $group) {
        $group->get('/conductores', [ConductoresRepository::class, 'all']);
        $group->get('/conductores/buscar', [ConductoresRepository::class, 'buscar']);
        $group->get('/conductores/estado/{estado}', [ConductoresRepository::class, 'porEstado']);
        $group->get('/conductores/{id}', [ConductoresRepository::class, 'show']);
        $group->post('/conductores', [ConductoresRepository::class, 'store']);
        $group->put('/conductores/{id}', [ConductoresRepository::class, 'update']);
        $group->patch('/conductores/{id}', [ConductoresRepository::class, 'update']);
        $group->patch('/conductores/{id}/estado', [ConductoresRepository::class, 'cambiarEstado']);
        $group->delete('/conductores/{id}', [ConductoresRepository::class, 'destroy']);
    })->add($auth);
};
