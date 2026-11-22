<?php

use App\Models\Usuario;
use Illuminate\Database\QueryException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Psr7\Response as HttpResponse;

/**
 * Middleware de token para los endpoints protegidos.
 *
 * Valida la cabecera Authorization: Bearer <token> contra la tabla usuarios de
 * la base compartida logitrans. No consulta a ms-auth por HTTP: todos los
 * microservicios acceden directamente a la misma base de datos.
 */

$json = function (Response $response, string $message, int $status): Response {
    $payload = json_encode(['message' => $message], JSON_UNESCAPED_UNICODE);
    $response->getBody()->write($payload === false ? '{}' : $payload);

    return $response->withStatus($status)->withHeader('Content-Type', 'application/json');
};

return function (Request $request, $handler) use ($json): Response {
    $path = $request->getUri()->getPath();

    // /health y /login son públicos; las demás rutas se agrupan y protegen.
    if ($path === '/health' || $path === '/login') {
        return $handler->handle($request);
    }

    $header = trim($request->getHeaderLine('Authorization'));

    if ($header === '' || !preg_match('/^Bearer\s+(.+)$/i', $header, $matches)) {
        return $json(new HttpResponse(), 'Token no proporcionado.', 401);
    }

    $token = trim($matches[1]);

    try {
        $user = Usuario::where('token', $token)
            ->where('sesion_activa', 1)
            ->where('estado', 'activo')
            ->first();
    } catch (QueryException $ex) {
        return $json(new HttpResponse(), 'No fue posible validar la sesión.', 500);
    }

    if (!$user) {
        return $json(new HttpResponse(), 'Sesión inválida o expirada. Inicia sesión nuevamente.', 401);
    }

    // Deja el usuario autenticado disponible para los repositorios.
    $request = $request->withAttribute('authUser', $user);

    return $handler->handle($request);
};
