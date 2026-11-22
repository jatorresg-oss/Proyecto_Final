<?php

namespace App\Controllers;

use App\Models\Conductor;
use DateTimeImmutable;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Collection;
use InvalidArgumentException;

/** Lógica de negocio del módulo de conductores. */
class ConductoresController
{
    public const ESTADOS = ['disponible', 'en_ruta', 'inactivo'];

    private const CAMPOS_OBLIGATORIOS = [
        'nombres',
        'apellidos',
        'documento',
        'telefono',
        'correo',
        'numero_licencia',
        'categoria_licencia',
        'fecha_vencimiento_licencia',
    ];

    /** Listado general o filtrado por documento, licencia y/o estado. */
    public function all(array $filters = []): Collection
    {
        $query = Conductor::query();

        $documento = trim((string) ($filters['documento'] ?? ''));
        if ($documento !== '') {
            $query->where('documento', 'LIKE', '%' . $documento . '%');
        }

        $licencia = trim((string) ($filters['licencia'] ?? $filters['numero_licencia'] ?? ''));
        if ($licencia !== '') {
            $query->where('numero_licencia', 'LIKE', '%' . $licencia . '%');
        }

        $estado = trim((string) ($filters['estado'] ?? ''));
        if ($estado !== '') {
            $estado = strtolower($estado);
            $this->assertEstado($estado);
            $query->where('estado', $estado);
        }

        return $query->orderBy('id')->get();
    }

    /** Obtiene un conductor o responde 404 si no existe. */
    public function find(int $id): Conductor
    {
        $conductor = Conductor::find($id);

        if (!$conductor) {
            throw new InvalidArgumentException('Conductor no encontrado.', 404);
        }

        return $conductor;
    }

    public function create(array $data): Conductor
    {
        $clean = $this->validate($data);
        $this->assertUnique($clean, null);

        $clean['estado'] = $clean['estado'] ?? 'disponible';

        return Conductor::create($clean);
    }

    /**
     * Edita un conductor. Acepta todos los campos (PUT) o solo algunos (PATCH):
     * los valores omitidos se toman del registro actual.
     */
    public function update(int $id, array $data): Conductor
    {
        $conductor = $this->find($id);
        $merged = array_merge($conductor->toArray(), $data);
        $clean = $this->validate($merged);
        $this->assertUnique($clean, $id);

        $conductor->fill($clean);
        $conductor->save();

        return $conductor->fresh() ?? $conductor;
    }

    public function changeEstado(int $id, string $estado): Conductor
    {
        $estado = strtolower(trim($estado));
        $this->assertEstado($estado);

        $conductor = $this->find($id);
        $conductor->estado = $estado;
        $conductor->save();

        return $conductor;
    }

    /**
     * Elimina un conductor. Se bloquea si ya tiene programaciones asignadas
     * para no dejar referencias lógicas huérfanas en programaciones_viajes.
     */
    public function delete(int $id): void
    {
        $conductor = $this->find($id);

        $asignado = Capsule::table('programaciones_viajes')
            ->where('conductor_id', $id)
            ->exists();

        if ($asignado) {
            throw new InvalidArgumentException(
                'No se puede eliminar el conductor porque tiene programaciones asignadas.',
                409
            );
        }

        $conductor->delete();
    }

    /** Valida los datos y devuelve solo los campos de la tabla conductores. */
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

        if (isset($clean['nombres']) && (mb_strlen($clean['nombres']) < 2 || mb_strlen($clean['nombres']) > 120)) {
            $errors[] = 'Nombres debe tener entre 2 y 120 caracteres.';
        }

        if (isset($clean['apellidos']) && (mb_strlen($clean['apellidos']) < 2 || mb_strlen($clean['apellidos']) > 120)) {
            $errors[] = 'Apellidos debe tener entre 2 y 120 caracteres.';
        }

        if (isset($clean['documento']) && !preg_match('/^[A-Za-z0-9.\-]{5,30}$/', $clean['documento'])) {
            $errors[] = 'Documento no válido (5 a 30 caracteres alfanuméricos, punto o guion).';
        }

        if (isset($clean['telefono']) && !preg_match('/^[0-9+()\s\-]{7,30}$/', $clean['telefono'])) {
            $errors[] = 'Teléfono no válido (7 a 30 caracteres).';
        }

        if (isset($clean['correo']) && (filter_var($clean['correo'], FILTER_VALIDATE_EMAIL) === false || mb_strlen($clean['correo']) > 150)) {
            $errors[] = 'Correo electrónico no válido.';
        }

        if (isset($clean['numero_licencia']) && !preg_match('/^[A-Za-z0-9\-]{4,50}$/', $clean['numero_licencia'])) {
            $errors[] = 'Número de licencia no válido (4 a 50 caracteres alfanuméricos o guion).';
        }

        if (isset($clean['categoria_licencia']) && !preg_match('/^[A-Za-z0-9\-]{1,10}$/', $clean['categoria_licencia'])) {
            $errors[] = 'Categoría de licencia no válida (máximo 10 caracteres).';
        }

        if (isset($clean['fecha_vencimiento_licencia'])) {
            $fecha = DateTimeImmutable::createFromFormat('!Y-m-d', $clean['fecha_vencimiento_licencia']);

            if (!$fecha || $fecha->format('Y-m-d') !== $clean['fecha_vencimiento_licencia']) {
                $errors[] = 'Fecha de vencimiento de la licencia debe tener formato YYYY-MM-DD.';
            } elseif ($fecha < new DateTimeImmutable('today')) {
                $errors[] = 'La licencia del conductor debe estar vigente (fecha no vencida).';
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

    /** Documento, correo y licencia son únicos (UNIQUE en la tabla). */
    private function assertUnique(array $clean, ?int $ignoreId): void
    {
        $reglas = [
            'documento' => 'documento',
            'correo' => 'correo electrónico',
            'numero_licencia' => 'número de licencia',
        ];

        foreach ($reglas as $campo => $etiqueta) {
            if (!isset($clean[$campo])) {
                continue;
            }

            $query = Conductor::where($campo, $clean[$campo]);

            if ($ignoreId !== null) {
                $query->where('id', '!=', $ignoreId);
            }

            if ($query->exists()) {
                throw new InvalidArgumentException('El ' . $etiqueta . ' ya está registrado.', 409);
            }
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
