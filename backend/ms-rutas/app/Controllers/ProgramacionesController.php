<?php

namespace App\Controllers;

use App\Models\ProgramacionViaje;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Collection;
use InvalidArgumentException;

/**
 * Lógica de negocio de la programación de viajes (Día 10).
 *
 * Los datos de conductores y vehículos se leen directamente de la base
 * compartida `logitrans`: no hay comunicación HTTP con ms-conductores ni con
 * ms-vehiculos. El frontend pre-validara, pero aquí se revalidan todas las
 * reglas antes de guardar.
 */
class ProgramacionesController
{
    private const CAMPOS_OBLIGATORIOS = [
        'conductor_id',
        'vehiculo_id',
        'ruta_id',
        'fecha_salida',
        'hora_salida',
        'fecha_estimada_llegada',
    ];

    private const ESTADOS = ['programado', 'en_transito', 'retrasado', 'finalizado', 'cancelado'];

    /** Estados que ocupan a un conductor/vehículo en una fecha. */
    private const ESTADOS_ACTIVOS = ['programado', 'en_transito', 'retrasado'];

    /** Solo estos estados pueden reprogramarse o cancelarse desde ms-rutas. */
    private const ESTADOS_EDITABLES = ['programado', 'retrasado'];

    /** Etiquetas legibles para los mensajes de validación. */
    private const ETIQUETAS = [
        'conductor_id' => 'Conductor',
        'vehiculo_id' => 'Vehículo',
        'ruta_id' => 'Ruta',
        'fecha_salida' => 'Fecha de salida',
        'hora_salida' => 'Hora de salida',
        'fecha_estimada_llegada' => 'Fecha estimada de llegada',
    ];

    /** Mensajes de campo obligatorio con el género correcto. */
    private const OBLIGATORIOS = [
        'conductor_id' => 'Conductor es obligatorio.',
        'vehiculo_id' => 'Vehículo es obligatorio.',
        'ruta_id' => 'Ruta es obligatoria.',
        'fecha_salida' => 'Fecha de salida es obligatoria.',
        'hora_salida' => 'Hora de salida es obligatoria.',
        'fecha_estimada_llegada' => 'Fecha estimada de llegada es obligatoria.',
    ];

    /** Mensajes de formato inválido con el género correcto. */
    private const NO_VALIDO = [
        'conductor_id' => 'Conductor no válido.',
        'vehiculo_id' => 'Vehículo no válido.',
        'ruta_id' => 'Ruta no válida.',
    ];

    /** Listado general o filtrado por conductor, vehículo, ruta, estado y fecha. */
    public function all(array $filters = []): Collection
    {
        $query = ProgramacionViaje::query();

        foreach (['conductor_id', 'vehiculo_id', 'ruta_id'] as $field) {
            $value = trim((string) ($filters[$field] ?? ''));
            if ($value !== '') {
                $query->where($field, (int) $value);
            }
        }

        $estado = trim((string) ($filters['estado'] ?? ''));
        if ($estado !== '') {
            $query->where('estado', $estado);
        }

        $fecha = trim((string) ($filters['fecha'] ?? ''));
        if ($fecha !== '') {
            $query->whereDate('fecha_salida', $fecha);
        }

        return $query->orderByDesc('fecha_salida')->orderBy('id')->get();
    }

    /** Obtiene una programación o responde 404 si no existe. */
    public function find(int $id): ProgramacionViaje
    {
        $programacion = ProgramacionViaje::find($id);

        if (!$programacion) {
            throw new InvalidArgumentException('Programación no encontrada.', 404);
        }

        return $programacion;
    }

    /** Programa un viaje: siempre nace con estado `programado`. */
    public function create(array $data): ProgramacionViaje
    {
        $clean = $this->validate($data, true);
        $this->assertFechas($clean, true);
        $this->assertReferences($clean);
        $this->assertDisponibilidad($clean, null);

        // El estado lo gestiona ms-viajes durante el viaje; aquí solo se crea.
        $clean['estado'] = 'programado';

        return ProgramacionViaje::create($clean);
    }

    /**
     * Reprograma (PUT) o aplica cambios parciales (PATCH).
     *
     * Los campos omitidos se toman del registro actual. Solo se pueden editar
     * programaciones `programado` o `retrasado`; `en_transito`, `finalizado` y
     * `cancelado` son gestionadas por ms-viajes o son definitivas.
     */
    public function update(int $id, array $data): ProgramacionViaje
    {
        $programacion = $this->find($id);

        if (!in_array($programacion->estado, self::ESTADOS_EDITABLES, true)) {
            throw new InvalidArgumentException('Solo se puede reprogramar un viaje programado o retrasado.', 409);
        }

        $clean = $this->validate($data, false);
        $merged = array_merge($programacion->toArray(), $clean);
        $this->assertFechas($merged, array_key_exists('fecha_salida', $clean));
        $this->assertReferences($merged);

        $nuevoEstado = $clean['estado'] ?? $programacion->estado;

        if ($nuevoEstado !== $programacion->estado) {
            if (in_array($nuevoEstado, ['en_transito', 'finalizado'], true)) {
                throw new InvalidArgumentException(
                    'Los estados en_transito y finalizado se gestionan desde ms-viajes.',
                    409
                );
            }
            // `cancelado` y el paso entre `programado`/`retrasado` son válidos
            // siempre que la programación estuviera en un estado editable.
        }

        // La disponibilidad solo se revalida si cambian los recursos o la
        // fecha; cancelar o editar observaciones no requiere estar disponible.
        $cambiaRecursos = array_intersect(
            array_keys($clean),
            ['conductor_id', 'vehiculo_id', 'fecha_salida']
        ) !== [];

        if ($cambiaRecursos && $nuevoEstado !== 'cancelado') {
            $this->assertDisponibilidad($merged, $id);
        }

        $programacion->fill($clean);
        $programacion->save();

        return $programacion->fresh() ?? $programacion;
    }

    /**
     * Valida campos y normaliza valores. En alta se exigen todos los
     * obligatorios; en edición solo se validan los campos presentes.
     */
    private function validate(array $data, bool $esAlta): array
    {
        $errors = [];
        $clean = [];

        foreach (self::CAMPOS_OBLIGATORIOS as $field) {
            $presente = array_key_exists($field, $data) && trim((string) $data[$field]) !== '';

            if ($esAlta && !$presente) {
                $errors[] = self::OBLIGATORIOS[$field];
            }
        }

        foreach (['conductor_id', 'vehiculo_id', 'ruta_id'] as $field) {
            if (!array_key_exists($field, $data) || trim((string) $data[$field]) === '') {
                continue;
            }

            $value = $data[$field];

            if (filter_var($value, FILTER_VALIDATE_INT) === false || (int) $value <= 0) {
                $errors[] = self::NO_VALIDO[$field];
                continue;
            }

            $clean[$field] = (int) $value;
        }

        foreach (['fecha_salida', 'fecha_estimada_llegada'] as $field) {
            if (!array_key_exists($field, $data) || trim((string) $data[$field]) === '') {
                continue;
            }

            $fecha = $this->parsearFecha((string) $data[$field]);

            if ($fecha === null) {
                $errors[] = self::ETIQUETAS[$field] . ' no válida: use YYYY-MM-DD.';
                continue;
            }

            $clean[$field] = $fecha;
        }

        if (array_key_exists('hora_salida', $data) && trim((string) $data['hora_salida']) !== '') {
            $hora = $this->parsearHora((string) $data['hora_salida']);

            if ($hora === null) {
                $errors[] = 'Hora de salida no válida: use HH:MM o HH:MM:SS.';
            } else {
                $clean['hora_salida'] = $hora;
            }
        }

        if (array_key_exists('observaciones', $data)) {
            $observaciones = trim((string) $data['observaciones']);
            $clean['observaciones'] = $observaciones === '' ? null : $observaciones;
        }

        // En alta se ignora cualquier `estado` recibido: siempre se programa.
        if (!$esAlta && array_key_exists('estado', $data)) {
            $estado = trim((string) $data['estado']);

            if (!in_array($estado, self::ESTADOS, true)) {
                $errors[] = 'Estado no válido.';
            } else {
                $clean['estado'] = $estado;
            }
        }

        if ($errors) {
            throw new InvalidArgumentException(implode(' ', $errors), 422);
        }

        return $clean;
    }

    /** Reglas de fechas sobre los valores ya combinados (alta o edición). */
    private function assertFechas(array $values, bool $validarPasado): void
    {
        if ($validarPasado && $values['fecha_salida'] < date('Y-m-d')) {
            throw new InvalidArgumentException('La fecha de salida no puede ser anterior a hoy.', 422);
        }

        if ($values['fecha_estimada_llegada'] < $values['fecha_salida']) {
            throw new InvalidArgumentException('La llegada estimada no puede ser anterior a la salida.', 422);
        }
    }

    /** Comprueba que existan las referencias lógicas (no hay FOREIGN KEY). */
    private function assertReferences(array $values): void
    {
        $referencias = [
            'conductor_id' => ['conductores', 'El conductor indicado no existe.'],
            'vehiculo_id' => ['vehiculos', 'El vehículo indicado no existe.'],
            'ruta_id' => ['rutas', 'La ruta indicada no existe.'],
        ];

        foreach ($referencias as $field => [$tabla, $mensaje]) {
            if (!isset($values[$field])) {
                continue;
            }

            if (!Capsule::table($tabla)->where('id', $values[$field])->exists()) {
                throw new InvalidArgumentException($mensaje, 422);
            }
        }
    }

    /**
     * Revalida en backend la disponibilidad que el frontend ya filtró:
     * conductor no inactivo, vehículo sin mantenimiento/inactivo y sin otra
     * programación activa en la misma fecha con el mismo conductor/vehículo.
     */
    private function assertDisponibilidad(array $values, ?int $ignoreId): void
    {
        $conductor = Capsule::table('conductores')
            ->where('id', $values['conductor_id'])
            ->first(['estado']);

        if ($conductor && $conductor->estado === 'inactivo') {
            throw new InvalidArgumentException('El conductor no está disponible.', 409);
        }

        $vehiculo = Capsule::table('vehiculos')
            ->where('id', $values['vehiculo_id'])
            ->first(['estado']);

        if ($vehiculo && in_array($vehiculo->estado, ['mantenimiento', 'inactivo'], true)) {
            throw new InvalidArgumentException('El vehículo no está disponible.', 409);
        }

        $solapada = ProgramacionViaje::query()
            ->where('fecha_salida', $values['fecha_salida'])
            ->whereIn('estado', self::ESTADOS_ACTIVOS)
            ->where(function ($query) use ($values) {
                $query->where('conductor_id', $values['conductor_id'])
                    ->orWhere('vehiculo_id', $values['vehiculo_id']);
            });

        if ($ignoreId !== null) {
            $solapada->where('id', '!=', $ignoreId);
        }

        if ($solapada->exists()) {
            throw new InvalidArgumentException(
                'El conductor o el vehículo ya tiene un viaje en esa fecha.',
                409
            );
        }
    }

    private function parsearFecha(string $value): ?string
    {
        $value = trim($value);

        if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $partes)) {
            return null;
        }

        if (!checkdate((int) $partes[2], (int) $partes[3], (int) $partes[1])) {
            return null;
        }

        return $value;
    }

    /** Normaliza `HH:MM` a `HH:MM:SS` para el campo TIME de MySQL. */
    private function parsearHora(string $value): ?string
    {
        $value = trim($value);

        if (!preg_match('/^(\d{1,2}):(\d{2})(?::(\d{2}))?$/', $value, $partes)) {
            return null;
        }

        $hora = (int) $partes[1];
        $minuto = (int) $partes[2];
        $segundo = isset($partes[3]) ? (int) $partes[3] : 0;

        if ($hora > 23 || $minuto > 59 || $segundo > 59) {
            return null;
        }

        return sprintf('%02d:%02d:%02d', $hora, $minuto, $segundo);
    }
}
