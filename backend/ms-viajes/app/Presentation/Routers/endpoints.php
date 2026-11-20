<?php

use App\Presentation\Repositories\SeguimientosRepository;
use Slim\App;

return function (App $app) {
    // Día 1: listado inicial para verificar la conexión con la base de datos.
    $app->get('/seguimientos', [SeguimientosRepository::class, 'all']);
};
