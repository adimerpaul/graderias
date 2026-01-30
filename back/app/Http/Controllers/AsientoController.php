<?php

namespace App\Http\Controllers;

use App\Models\Graderia;
use App\Models\Asiento;
use Illuminate\Http\Request;

class AsientoController extends Controller
{
    public function indexByGraderia(Request $request, Graderia $graderia)
    {
//        $this->authorize('view', $graderia);

        return Asiento::query()
            ->where('graderia_id', $graderia->id)
            ->orderBy('fila')
            ->orderBy('columna')
            ->get();
    }
}
