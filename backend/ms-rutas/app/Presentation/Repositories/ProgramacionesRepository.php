<?php

namespace App\Presentation\Repositories;

use App\Controllers\ProgramacionesController;
use Illuminate\Database\QueryException;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Throwable;

/** Adaptadores HTTP del módulo de programación de viajes. */
class ProgramacionesRepository
{
    /** GET /programaciones?conductor_id=&vehiculo_id=&ruta_id=&estado=&fecha= */
    public function all(Request $request, Response $response): Response
    {
        $filters = $request->getQueryParams();

        return $this->run($response, function () use ($filters) {
            return (new ProgramacionesController())->all($filters);
        });
    }

    /** GET /programaciones/{id} */
    public function show(Request $request, Response $response, array $args): Response
    {
        return $this->run($response, function () use ($args) {
            return (new ProgramacionesController())->find((int) ($args['id'] ?? 0));
        });
    }

    /** POST /programaciones */
    public function store(Request $request, Response $response): Response
    {
        $body = $request->getParsedBody();

        if (!is_array($body)) {
            return $this->json($response, ['message' => 'Cuerpo JSON inválido.'], 400);
        }

        return $this->run($response, function () use ($body) {
            return (new ProgramacionesController())->create($body);
        }, 201);
    }

    /** PUT / PATCH /programaciones/{id} */
    public function update(Request $request, Response $response, array $args): Response
    {
        $body = $request->getParsedBody();

        if (!is_array($body)) {
            return $this->json($response, ['message' => 'Cuerpo JSON inválido.'], 400);
        }

        return $this->run($response, function () use ($body, $args) {
            return (new ProgramacionesController())->update((int) ($args['id'] ?? 0), $body);
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
                ['message' => 'Ocurrió un error inesperado en el servicio de programación.'],
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
