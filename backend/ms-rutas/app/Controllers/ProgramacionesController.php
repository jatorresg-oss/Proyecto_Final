<?php

namespace App\Controllers;

use App\Models\ProgramacionViaje;
use Illuminate\Database\Eloquent\Collection;

class ProgramacionesController
{
    public function all(): Collection
    {
        return ProgramacionViaje::orderByDesc('fecha_salida')->get();
    }
}
