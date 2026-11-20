<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Conductor extends Model
{
    protected $table = 'conductores';

    public $timestamps = true;

    protected $fillable = [
        'nombres',
        'apellidos',
        'documento',
        'telefono',
        'correo',
        'numero_licencia',
        'categoria_licencia',
        'fecha_vencimiento_licencia',
        'estado',
    ];

    protected $casts = [
        'fecha_vencimiento_licencia' => 'date:Y-m-d',
    ];
}
