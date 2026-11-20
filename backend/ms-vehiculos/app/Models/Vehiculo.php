<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vehiculo extends Model
{
    protected $table = 'vehiculos';

    public $timestamps = true;

    protected $fillable = [
        'placa',
        'tipo_vehiculo',
        'capacidad_carga',
        'modelo',
        'marca',
        'estado',
    ];

    protected $casts = [
        'capacidad_carga' => 'float',
    ];
}
