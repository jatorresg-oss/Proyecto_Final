<?php

namespace App\Presentation\Repositories;

use App\Controllers\AuthController;
use App\Models\Usuario;
use Illuminate\Database\QueryException;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Throwable;

/** Adaptadores HTTP de los endpoints de autenticación. */
class AuthRepository
{
    public function login(Request $request, Response $response): Response
    {
        $body = $request->getParsedBody();

        if (!is_array($body)) {
            return $this->json($response, ['message' => 'Cuerpo JSON inválido.'], 400);
        }

        // El contrato usa "usuario", pero también se acepta "correo" como clave.
        $identifier = $body['usuario'] ?? $body['correo'] ?? '';
        $password = $body['password'] ?? '';

        if (!is_string($identifier) || !is_string($password)) {
            return $this->json(
                $response,
                ['message' => 'Usuario o correo y contraseña deben ser texto.'],
                400
            );
        }

        return $this->run($response, function () use ($identifier, $password) {
            return (new AuthController())->login($identifier, $password);
        });
    }

    /** El middleware deja el usuario validado en el atributo authUser. */
    public function logout(Request $request, Response $response): Response
    {
        $user = $request->getAttribute('authUser');

        if (!$user instanceof Usuario) {
            return $this->json($response, ['message' => 'Sesión inválida o expirada.'], 401);
        }

        return $this->run($response, function () use ($user) {
            (new AuthController())->logout((int) $user->id);

            return ['message' => 'Sesión cerrada correctamente.'];
        });
    }

    public function validate(Request $request, Response $response): Response
    {
        $user = $request->getAttribute('authUser');

        if (!$user instanceof Usuario) {
            return $this->json($response, ['message' => 'Sesión inválida o expirada.'], 401);
        }

        return $this->run($response, function () use ($user) {
            return [
                'valid' => true,
                'user' => (new AuthController())->validate($user),
            ];
        });
    }

    /** Ejecuta la acción y convierte las excepciones en respuestas JSON. */
    private function run(Response $response, callable $action, int $okStatus = 200): Response
    {
        try {
            return $this->json($response, $action(), $okStatus);
        } catch (InvalidArgumentException $ex) {
            $status = $ex->getCode();
            $allowed = [400, 401, 403, 404, 409, 422];

            return $this->json(
                $response,
                ['message' => $ex->getMessage()],
                in_array($status, $allowed, true) ? $status : 400
            );
        } catch (QueryException $ex) {
            return $this->json(
                $response,
                ['message' => 'No fue posible consultar la base de datos logitrans.'],
                500
            );
        } catch (Throwable $ex) {
            return $this->json(
                $response,
                ['message' => 'Ocurrió un error inesperado en el servicio de autenticación.'],
                500
            );
        }
    }

    private function json(Response $response, $data, int $status = 200): Response
    {
        $payload = json_encode($data, JSON_UNESCAPED_UNICODE);
        $response->getBody()->write($payload === false ? '{}' : $payload);

        return $response->withStatus($status)->withHeader('Content-Type', 'application/json');
    }
}
