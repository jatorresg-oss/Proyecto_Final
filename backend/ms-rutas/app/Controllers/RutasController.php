<?php

namespace App\Controllers;

use App\Models\Ruta;
use Illuminate\Database\Eloquent\Collection;

class RutasController
{
    public function all(): Collection
    {
        return Ruta::all();
    }
}