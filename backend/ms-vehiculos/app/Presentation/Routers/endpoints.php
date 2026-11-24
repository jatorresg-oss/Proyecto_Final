<?php

use App\Presentation\Repositories\VehiculosRepository;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return function (App $app) {
    $auth = require __DIR__ . '/../../Middlewares/auth.php';

    // Todos los recursos de vehículos requieren token Bearer.
    $app->group('', function (RouteCollectorProxy $group) {
        $group->get('/vehiculos', [VehiculosRepository::class, 'all']);
        $group->get('/vehiculos/buscar', [VehiculosRepository::class, 'buscar']);
        $group->get('/vehiculos/estado/{estado}', [VehiculosRepository::class, 'porEstado']);
        $group->get('/vehiculos/tipo/{tipo}', [VehiculosRepository::class, 'porTipo']);
        $group->get('/vehiculos/{id}', [VehiculosRepository::class, 'show']);
        $group->post('/vehiculos', [VehiculosRepository::class, 'store']);
        $group->put('/vehiculos/{id}', [VehiculosRepository::class, 'update']);
        $group->patch('/vehiculos/{id}', [VehiculosRepository::class, 'update']);
        $group->patch('/vehiculos/{id}/estado', [VehiculosRepository::class, 'cambiarEstado']);
        $group->delete('/vehiculos/{id}', [VehiculosRepository::class, 'destroy']);
    })->add($auth);
};
