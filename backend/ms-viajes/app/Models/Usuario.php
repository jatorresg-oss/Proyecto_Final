<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo de la tabla de usuarios, usado por el middleware de autenticación.
 *
 * Cada microservicio valida el token contra la base compartida logitrans;
 * no hay comunicación HTTP entre servicios.
 */
class Usuario extends Model
{
    protected $table = 'usuarios';

    public $timestamps = true;

    protected $fillable = [
        'nombre',
        'correo',
        'usuario',
        'contrasena',
        'rol',
        'token',
        'sesion_activa',
        'estado',
    ];

    protected $hidden = ['contrasena', 'token'];

    protected $casts = [
        'sesion_activa' => 'boolean',
    ];
}
