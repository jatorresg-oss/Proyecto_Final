<?php

namespace App\Controllers;

use App\Models\Vehiculo;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Collection;
use InvalidArgumentException;

/** Lógica de negocio del módulo de vehículos. */
class VehiculosController
{
    public const ESTADOS = ['disponible', 'en_ruta', 'mantenimiento', 'inactivo'];

    private const CAMPOS_OBLIGATORIOS = [
        'placa',
        'tipo_vehiculo',
        'capacidad_carga',
        'modelo',
        'marca',
    ];

    /** Listado general o filtrado por placa, tipo y/o estado. */
    public function all(array $filters = []): Collection
    {
        $query = Vehiculo::query();

        $placa = trim((string) ($filters['placa'] ?? ''));
        if ($placa !== '') {
            $query->where('placa', 'LIKE', '%' . strtoupper($placa) . '%');
        }

        $tipo = trim((string) ($filters['tipo'] ?? $filters['tipo_vehiculo'] ?? ''));
        if ($tipo !== '') {
            $query->where('tipo_vehiculo', 'LIKE', '%' . $tipo . '%');
        }

        $estado = trim((string) ($filters['estado'] ?? ''));
        if ($estado !== '') {
            $estado = strtolower($estado);
            $this->assertEstado($estado);
            $query->where('estado', $estado);
        }

        return $query->orderBy('id')->get();
    }

    /** Obtiene un vehículo o responde 404 si no existe. */
    public function find(int $id): Vehiculo
    {
        $vehiculo = Vehiculo::find($id);

        if (!$vehiculo) {
            throw new InvalidArgumentException('Vehículo no encontrado.', 404);
        }

        return $vehiculo;
    }

    public function create(array $data): Vehiculo
    {
        $clean = $this->validate($data);
        $this->assertUnique($clean, null);

        $clean['estado'] = $clean['estado'] ?? 'disponible';

        return Vehiculo::create($clean);
    }

    /**
     * Edita un vehículo. Acepta todos los campos (PUT) o solo algunos (PATCH):
     * los valores omitidos se toman del registro actual.
     */
    public function update(int $id, array $data): Vehiculo
    {
        $vehiculo = $this->find($id);
        $merged = array_merge($vehiculo->toArray(), $data);
        $clean = $this->validate($merged);
        $this->assertUnique($clean, $id);

        $vehiculo->fill($clean);
        $vehiculo->save();

        return $vehiculo->fresh() ?? $vehiculo;
    }

    public function changeEstado(int $id, string $estado): Vehiculo
    {
        $estado = strtolower(trim($estado));
        $this->assertEstado($estado);

        $vehiculo = $this->find($id);
        $vehiculo->estado = $estado;
        $vehiculo->save();

        return $vehiculo;
    }

    /**
     * Elimina un vehículo. Se bloquea si ya tiene programaciones asignadas
     * para no dejar referencias lógicas huérfanas en programaciones_viajes.
     */
    public function delete(int $id): void
    {
        $vehiculo = $this->find($id);

        $asignado = Capsule::table('programaciones_viajes')
            ->where('vehiculo_id', $id)
            ->exists();

        if ($asignado) {
            throw new InvalidArgumentException(
                'No se puede eliminar el vehículo porque tiene programaciones asignadas.',
                409
            );
        }

        $vehiculo->delete();
    }

    /** Valida los datos y devuelve solo los campos de la tabla vehiculos. */
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

        if (isset($clean['placa'])) {
            // Se normaliza a mayúsculas para uniformizar la búsqueda y la unicidad.
            $clean['placa'] = strtoupper($clean['placa']);

            if (!preg_match('/^[A-Z0-9\-]{3,10}$/', $clean['placa'])) {
                $errors[] = 'Placa no válida (3 a 10 caracteres alfanuméricos o guion).';
            }
        }

        foreach (['tipo_vehiculo', 'modelo', 'marca'] as $field) {
            if (isset($clean[$field]) && mb_strlen($clean[$field]) > 60) {
                $errors[] = ucfirst(str_replace('_', ' ', $field)) . ' no puede superar los 60 caracteres.';
            }
        }

        if (isset($clean['capacidad_carga'])) {
            $capacidad = $clean['capacidad_carga'];

            if (
                !is_numeric($capacidad)
                || !preg_match('/^\d+(\.\d{1,2})?$/', (string) $capacidad)
                || (float) $capacidad <= 0
                || (float) $capacidad > 99999999.99
            ) {
                $errors[] = 'Capacidad de carga no válida: debe ser mayor que 0 y tener máximo 2 decimales.';
            } else {
                $clean['capacidad_carga'] = (float) $capacidad;
            }
        }

        if (array_key_exists('estado', $data) && trim((string) $data['estado']) !== '') {
            $estado = strtolower(trim((string) $data['estado']));

            if (!in_array($estado, self::ESTADOS, true)) {
                $errors[] = 'Estado no válido. Use: ' . implode(', ', self::ESTADOS) . '.';
            } else {
                $clean['estado'] = $estado;
            }
        }

        if ($errors) {
            throw new InvalidArgumentException(implode(' ', $errors), 422);
        }

        // La comprobación de unicidad se hace aparte para no repetir consultas
        // cuando hay errores de formato.
        return $clean;
    }

    /** La placa es única (UNIQUE en la tabla). */
    private function assertUnique(array $clean, ?int $ignoreId): void
    {
        if (!isset($clean['placa'])) {
            return;
        }

        $query = Vehiculo::where('placa', $clean['placa']);

        if ($ignoreId !== null) {
            $query->where('id', '!=', $ignoreId);
        }

        if ($query->exists()) {
            throw new InvalidArgumentException('La placa ya está registrada.', 409);
        }
    }

    private function assertEstado(string $estado): void
    {
        if (!in_array($estado, self::ESTADOS, true)) {
            throw new InvalidArgumentException(
                'Estado no válido. Use: ' . implode(', ', self::ESTADOS) . '.',
                422
            );
        }
    }
}
