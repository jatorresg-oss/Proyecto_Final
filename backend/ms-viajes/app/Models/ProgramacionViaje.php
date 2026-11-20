<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * La programación se crea en ms-rutas, pero vive en la misma base `logitrans`.
 * ms-viajes la lee para conocer el viaje y actualiza su columna `estado`.
 */
class ProgramacionViaje extends Model
{
    protected $table = 'programaciones_viajes';

    public $timestamps = true;

    protected $fillable = [
        'conductor_id',
        'vehiculo_id',
        'ruta_id',
        'fecha_salida',
        'hora_salida',
        'fecha_estimada_llegada',
        'observaciones',
        'estado',
    ];

    protected $casts = [
        'conductor_id' => 'integer',
        'vehiculo_id' => 'integer',
        'ruta_id' => 'integer',
        'fecha_salida' => 'date:Y-m-d',
        'fecha_estimada_llegada' => 'date:Y-m-d',
    ];
}
