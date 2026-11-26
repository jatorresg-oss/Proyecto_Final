<?php

namespace App\Controllers;

use App\Models\Ruta;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Collection;
use InvalidArgumentException;

/** Lógica de negocio del módulo de rutas. */
class RutasController
{
    private const CAMPOS_OBLIGATORIOS = [
        'ciudad_origen',
        'ciudad_destino',
        'distancia',
        'tiempo_estimado',
    ];

    /** Listado general o filtrado por ciudad, origen, destino y/o tiempo. */
    public function all(array $filters = []): Collection
    {
        $query = Ruta::query();

        $ciudad = trim((string) ($filters['ciudad'] ?? ''));
        if ($ciudad !== '') {
            // "Buscar por ciudad" incluye tanto el origen como el destino.
            $query->where(function ($subQuery) use ($ciudad) {
                $subQuery->where('ciudad_origen', 'LIKE', '%' . $ciudad . '%')
                    ->orWhere('ciudad_destino', 'LIKE', '%' . $ciudad . '%');
            });
        }

        $origen = trim((string) ($filters['origen'] ?? $filters['ciudad_origen'] ?? ''));
        if ($origen !== '') {
            $query->where('ciudad_origen', 'LIKE', '%' . $origen . '%');
        }

        $destino = trim((string) ($filters['destino'] ?? $filters['ciudad_destino'] ?? ''));
        if ($destino !== '') {
            $query->where('ciudad_destino', 'LIKE', '%' . $destino . '%');
        }

        $tiempo = trim((string) ($filters['tiempo'] ?? $filters['tiempo_estimado'] ?? ''));
        if ($tiempo !== '') {
            $query->where('tiempo_estimado', 'LIKE', '%' . $tiempo . '%');
        }

        return $query->orderBy('id')->get();
    }

    /** Consulta de tiempos estimados: solo los campos necesarios. */
    public function tiempos(): Collection
    {
        return Ruta::query()
            ->select('id', 'ciudad_origen', 'ciudad_destino', 'tiempo_estimado')
            ->orderBy('id')
            ->get();
    }

    /** Obtiene una ruta o responde 404 si no existe. */
    public function find(int $id): Ruta
    {
        $ruta = Ruta::find($id);

        if (!$ruta) {
            throw new InvalidArgumentException('Ruta no encontrada.', 404);
        }

        return $ruta;
    }

    public function create(array $data): Ruta
    {
        $clean = $this->validate($data);
        $this->assertDuplicada($clean, null);

        return Ruta::create($clean);
    }

    /**
     * Edita una ruta. Acepta todos los campos (PUT) o solo algunos (PATCH):
     * los valores omitidos se toman del registro actual.
     */
    public function update(int $id, array $data): Ruta
    {
        $ruta = $this->find($id);
        $merged = array_merge($ruta->toArray(), $data);
        $clean = $this->validate($merged);
        $this->assertDuplicada($clean, $id);

        $ruta->fill($clean);
        $ruta->save();

        return $ruta->fresh() ?? $ruta;
    }

    /**
     * Elimina una ruta. Se bloquea si ya está en programaciones_viajes para no
     * dejar referencias lógicas huérfanas (la tabla no tiene FOREIGN KEY).
     */
    public function delete(int $id): void
    {
        $ruta = $this->find($id);

        $asignada = Capsule::table('programaciones_viajes')
            ->where('ruta_id', $id)
            ->exists();

        if ($asignada) {
            throw new InvalidArgumentException(
                'No se puede eliminar la ruta porque tiene programaciones asignadas.',
                409
            );
        }

        $ruta->delete();
    }

    /** Valida los datos y devuelve solo los campos de la tabla rutas. */
    private function validate(array $data): array
    {
        $errors = [];
        $clean = [];

        foreach (self::CAMPOS_OBLIGATORIOS as $field) {
            $value = trim((string) ($data[$field] ?? ''));

            if ($value === '') {
                $errors[] = ucfirst(str_replace('_', ' ', $field)) . ' es obligatorio.';
                continue;
            }

            $clean[$field] = $value;
        }

        foreach (['ciudad_origen', 'ciudad_destino'] as $field) {
            if (isset($clean[$field]) && (mb_strlen($clean[$field]) < 2 || mb_strlen($clean[$field]) > 100)) {
                $errors[] = ucfirst(str_replace('_', ' ', $field)) . ' debe tener entre 2 y 100 caracteres.';
            }
        }

        if (isset($clean['distancia'])) {
            $distancia = $clean['distancia'];

            if (
                !is_numeric($distancia)
                || !preg_match('/^\d+(\.\d{1,2})?$/', (string) $distancia)
                || (float) $distancia <= 0
                || (float) $distancia > 99999999.99
            ) {
                $errors[] = 'Distancia no válida: debe ser mayor que 0 y tener máximo 2 decimales.';
            } else {
                $clean['distancia'] = (float) $distancia;
            }
        }

        if (isset($clean['tiempo_estimado']) && mb_strlen($clean['tiempo_estimado']) > 50) {
            $errors[] = 'Tiempo estimado no puede superar los 50 caracteres.';
        }

        if (array_key_exists('observaciones', $data)) {
            $observaciones = trim((string) $data['observaciones']);
            $clean['observaciones'] = $observaciones === '' ? null : $observaciones;
        }

        if ($errors) {
            throw new InvalidArgumentException(implode(' ', $errors), 422);
        }

        return $clean;
    }

    /**
     * La tabla rutas no tiene UNIQUE (origen + destino), así que la regla se
     * comprueba en PHP. La comparación ignora mayúsculas para evitar duplicados
     * como "Bogotá / Medellín" y "bogotá / medellín".
     */
    private function assertDuplicada(array $clean, ?int $ignoreId): void
    {
        if (!isset($clean['ciudad_origen'], $clean['ciudad_destino'])) {
            return;
        }

        $query = Ruta::whereRaw(
            'LOWER(ciudad_origen) = ? AND LOWER(ciudad_destino) = ?',
            [
                mb_strtolower($clean['ciudad_origen'], 'UTF-8'),
                mb_strtolower($clean['ciudad_destino'], 'UTF-8'),
            ]
        );

        if ($ignoreId !== null) {
            $query->where('id', '!=', $ignoreId);
        }

        if ($query->exists()) {
            throw new InvalidArgumentException('Ya existe una ruta con ese origen y destino.', 409);
        }
    }
}
