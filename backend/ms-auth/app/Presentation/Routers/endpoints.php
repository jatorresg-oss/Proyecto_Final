<?php

use App\Presentation\Repositories\AuthRepository;
use App\Presentation\Repositories\UsuariosRepository;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return function (App $app) {
    $auth = require __DIR__ . '/../../Middlewares/auth.php';

    // Endpoint público: solo /login y /health (este último en index.php).
    $app->post('/login', [AuthRepository::class, 'login']);

    // Endpoints protegidos con token Bearer.
    $app->group('', function (RouteCollectorProxy $group) {
        $group->get('/validate', [AuthRepository::class, 'validate']);
        $group->post('/logout', [AuthRepository::class, 'logout']);
        $group->get('/usuarios', [UsuariosRepository::class, 'all']);
    })->add($auth);
};
