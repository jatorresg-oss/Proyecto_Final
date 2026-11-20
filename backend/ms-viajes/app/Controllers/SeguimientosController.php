<?php

namespace App\Controllers;

use App\Models\SeguimientoViaje;
use Illuminate\Database\Eloquent\Collection;

class SeguimientosController
{
    public function all(): Collection
    {
        return SeguimientoViaje::orderBy('fecha')->orderBy('hora')->get();
    }
}
