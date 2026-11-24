<?php

namespace App\Presentation\Repositories;

use App\Controllers\VehiculosController;
use Illuminate\Database\QueryException;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Throwable;

/** Adaptadores HTTP del módulo de vehículos. */
class VehiculosRepository
{
    /** GET /vehiculos?placa=&tipo=&estado= */
    public function all(Request $request, Response $response): Response
    {
        $filters = $request->getQueryParams();

        return $this->run($response, function () use ($filters) {
            return (new VehiculosController())->all($filters);
        });
    }

    /** GET /vehiculos/buscar?placa=&tipo=&estado= (alias de all). */
    public function buscar(Request $request, Response $response): Response
    {
        return $this->all($request, $response);
    }

    /** GET /vehiculos/estado/{estado} */
    public function porEstado(Request $request, Response $response, array $args): Response
    {
        return $this->run($response, function () use ($args) {
            return (new VehiculosController())->all([
                'estado' => (string) ($args['estado'] ?? ''),
            ]);
        });
    }

    /** GET /vehiculos/tipo/{tipo} */
    public function porTipo(Request $request, Response $response, array $args): Response
    {
        return $this->run($response, function () use ($args) {
            return (new VehiculosController())->all([
                'tipo' => (string) ($args['tipo'] ?? ''),
            ]);
        });
    }

    /** GET /vehiculos/{id} */
    public function show(Request $request, Response $response, array $args): Response
    {
        return $this->run($response, function () use ($args) {
            return (new VehiculosController())->find((int) ($args['id'] ?? 0));
        });
    }

    /** POST /vehiculos */
    public function store(Request $request, Response $response): Response
    {
        $body = $request->getParsedBody();

        if (!is_array($body)) {
            return $this->json($response, ['message' => 'Cuerpo JSON inválido.'], 400);
        }

        return $this->run($response, function () use ($body) {
            return (new VehiculosController())->create($body);
        }, 201);
    }

    /** PUT / PATCH /vehiculos/{id} */
    public function update(Request $request, Response $response, array $args): Response
    {
        $body = $request->getParsedBody();

        if (!is_array($body)) {
            return $this->json($response, ['message' => 'Cuerpo JSON inválido.'], 400);
        }

        return $this->run($response, function () use ($body, $args) {
            return (new VehiculosController())->update((int) ($args['id'] ?? 0), $body);
        });
    }

    /** PATCH /vehiculos/{id}/estado */
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
            return (new VehiculosController())->changeEstado(
                (int) ($args['id'] ?? 0),
                $estado
            );
        });
    }

    /** DELETE /vehiculos/{id} */
    public function destroy(Request $request, Response $response, array $args): Response
    {
        return $this->run($response, function () use ($args) {
            (new VehiculosController())->delete((int) ($args['id'] ?? 0));

            return ['message' => 'Vehículo eliminado correctamente.'];
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
                        ? 'La placa ya está registrada.'
                        : 'No fue posible consultar la base de datos logitrans.',
                ],
                $duplicado ? 409 : 500
            );
        } catch (Throwable $ex) {
            return $this->json(
                $response,
                ['message' => 'Ocurrió un error inesperado en el servicio de vehículos.'],
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
