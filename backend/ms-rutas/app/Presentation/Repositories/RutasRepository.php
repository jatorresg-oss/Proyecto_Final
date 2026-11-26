<?php

namespace App\Presentation\Repositories;

use App\Controllers\RutasController;
use Illuminate\Database\QueryException;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Throwable;

/** Adaptadores HTTP del módulo de rutas. */
class RutasRepository
{
    /** GET /rutas?ciudad=&origen=&destino=&tiempo= */
    public function all(Request $request, Response $response): Response
    {
        $filters = $request->getQueryParams();

        return $this->run($response, function () use ($filters) {
            return (new RutasController())->all($filters);
        });
    }

    /** GET /rutas/buscar?ciudad=&origen=&destino=&tiempo= (alias de all). */
    public function buscar(Request $request, Response $response): Response
    {
        return $this->all($request, $response);
    }

    /** GET /rutas/ciudad/{ciudad}: coincide con origen o destino. */
    public function porCiudad(Request $request, Response $response, array $args): Response
    {
        return $this->run($response, function () use ($args) {
            return (new RutasController())->all([
                'ciudad' => (string) ($args['ciudad'] ?? ''),
            ]);
        });
    }

    /** GET /rutas/tiempos: consulta de tiempos estimados. */
    public function tiempos(Request $request, Response $response): Response
    {
        return $this->run($response, function () {
            return (new RutasController())->tiempos();
        });
    }

    /** GET /rutas/{id} */
    public function show(Request $request, Response $response, array $args): Response
    {
        return $this->run($response, function () use ($args) {
            return (new RutasController())->find((int) ($args['id'] ?? 0));
        });
    }

    /** POST /rutas */
    public function store(Request $request, Response $response): Response
    {
        $body = $request->getParsedBody();

        if (!is_array($body)) {
            return $this->json($response, ['message' => 'Cuerpo JSON inválido.'], 400);
        }

        return $this->run($response, function () use ($body) {
            return (new RutasController())->create($body);
        }, 201);
    }

    /** PUT / PATCH /rutas/{id} */
    public function update(Request $request, Response $response, array $args): Response
    {
        $body = $request->getParsedBody();

        if (!is_array($body)) {
            return $this->json($response, ['message' => 'Cuerpo JSON inválido.'], 400);
        }

        return $this->run($response, function () use ($body, $args) {
            return (new RutasController())->update((int) ($args['id'] ?? 0), $body);
        });
    }

    /** DELETE /rutas/{id} */
    public function destroy(Request $request, Response $response, array $args): Response
    {
        return $this->run($response, function () use ($args) {
            (new RutasController())->delete((int) ($args['id'] ?? 0));

            return ['message' => 'Ruta eliminada correctamente.'];
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
                        ? 'Ya existe una ruta con ese origen y destino.'
                        : 'No fue posible consultar la base de datos logitrans.',
                ],
                $duplicado ? 409 : 500
            );
        } catch (Throwable $ex) {
            return $this->json(
                $response,
                ['message' => 'Ocurrió un error inesperado en el servicio de rutas.'],
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
