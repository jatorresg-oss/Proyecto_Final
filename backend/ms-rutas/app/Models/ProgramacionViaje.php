<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
