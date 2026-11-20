<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ruta extends Model
{
    protected $table = 'rutas';

    public $timestamps = true;

    protected $fillable = [
        'ciudad_origen',
        'ciudad_destino',
        'distancia',
        'tiempo_estimado',
        'observaciones',
    ];

    protected $casts = [
        'distancia' => 'float',
    ];
}
