<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeguimientoViaje extends Model
{
    protected $table = 'seguimientos_viajes';

    public $timestamps = true;

    protected $fillable = [
        'programacion_viaje_id',
        'fecha',
        'hora',
        'estado',
        'novedad',
    ];

    protected $casts = [
        'programacion_viaje_id' => 'integer',
        'fecha' => 'date:Y-m-d',
    ];
}
