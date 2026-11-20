<?php

use App\Presentation\Repositories\ProgramacionesRepository;
use App\Presentation\Repositories\RutasRepository;
use Slim\App;

return function (App $app) {
    // Día 1: listados iniciales para verificar la conexión con la base de datos.
    $app->get('/rutas', [RutasRepository::class, 'all']);
    $app->get('/programaciones', [ProgramacionesRepository::class, 'all']);
};
