<?php

namespace App\Presentation\Repositories;

use App\Controllers\ConductoresController;
use Illuminate\Database\QueryException;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Throwable;

/** Adaptadores HTTP del módulo de conductores. */
class ConductoresRepository
{
    /** GET /conductores?documento=&licencia=&estado= */
    public function all(Request $request, Response $response): Response
    {
        $filters = $request->getQueryParams();

        return $this->run($response, function () use ($filters) {
            return (new ConductoresController())->all($filters);
        });
    }

    /** GET /conductores/buscar?documento=&licencia=&estado= (alias de all). */
    public function buscar(Request $request, Response $response): Response
    {
        return $this->all($request, $response);
    }

    /** GET /conductores/estado/{estado} */
    public function porEstado(Request $request, Response $response, array $args): Response
    {
        return $this->run($response, function () use ($args) {
            return (new ConductoresController())->all([
                'estado' => (string) ($args['estado'] ?? ''),
            ]);
        });
    }

    /** GET /conductores/{id} */
    public function show(Request $request, Response $response, array $args): Response
    {
        return $this->run($response, function () use ($args) {
            return (new ConductoresController())->find((int) ($args['id'] ?? 0));
        });
    }

    /** POST /conductores */
    public function store(Request $request, Response $response): Response
    {
        $body = $request->getParsedBody();

        if (!is_array($body)) {
            return $this->json($response, ['message' => 'Cuerpo JSON inválido.'], 400);
        }

        return $this->run($response, function () use ($body) {
            return (new ConductoresController())->create($body);
        }, 201);
    }

    /** PUT / PATCH /conductores/{id} */
    public function update(Request $request, Response $response, array $args): Response
    {
        $body = $request->getParsedBody();

        if (!is_array($body)) {
            return $this->json($response, ['message' => 'Cuerpo JSON inválido.'], 400);
        }

        return $this->run($response, function () use ($body, $args) {
            return (new ConductoresController())->update((int) ($args['id'] ?? 0), $body);
        });
    }

    /** PATCH /conductores/{id}/estado */
    public function cambiarEstado(Request $request, Response $response, array $args): Response
    {
        $body = $request->getParsedBody();
        $estado = is_array($body) ? ($body['estado'] ?? '') : '';

        if (!is_string($estado) || trim($estado) === '') {
            return $this->json(
                $response,
                ['message' => 'El campo estado es obligatorio.'],
                422
            );
        }

        return $this->run($response, function () use ($estado, $args) {
            return (new ConductoresController())->changeEstado(
                (int) ($args['id'] ?? 0),
                $estado
            );
        });
    }

    /** DELETE /conductores/{id} */
    public function destroy(Request $request, Response $response, array $args): Response
    {
        return $this->run($response, function () use ($args) {
            (new ConductoresController())->delete((int) ($args['id'] ?? 0));

            return ['message' => 'Conductor eliminado correctamente.'];
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
            $duplicado = $ex->getCode() === '23000';

            return $this->json(
                $response,
                [
                    'message' => $duplicado
                        ? 'El documento, correo o número de licencia ya está registrado.'
                        : 'No fue posible consultar la base de datos logitrans.',
                ],
                $duplicado ? 409 : 500
            );
        } catch (Throwable $ex) {
            return $this->json(
                $response,
                ['message' => 'Ocurrió un error inesperado en el servicio de conductores.'],
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
