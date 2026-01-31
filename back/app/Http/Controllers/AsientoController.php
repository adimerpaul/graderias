<?php

namespace App\Http\Controllers;

use App\Models\Graderia;
use App\Models\Asiento;
use Illuminate\Http\Request;

class AsientoController extends Controller
{
    // GET /mis-graderias/{graderia}/asientos?search=&estado=&per_page=50
    public function indexByGraderia(Request $request, Graderia $graderia)
    {
        if ((int)$graderia->user_id !== (int)$request->user()->id) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $search  = trim((string)$request->query('search', ''));
        $estado  = strtoupper(trim((string)$request->query('estado', '')));
        $perPage = (int)$request->query('per_page', 50);
        $perPage = max(10, min($perPage, 200));

        $q = Asiento::query()
            ->where('graderia_id', $graderia->id)
            ->orderBy('fila')
            ->orderBy('columna');

        if ($search !== '') {
            $q->where(function ($qq) use ($search) {
                $qq->where('codigo', 'like', "%{$search}%")
                    ->orWhere('cliente_nombre', 'like', "%{$search}%")
                    ->orWhere('cliente_celular', 'like', "%{$search}%");
            });
        }

        if (in_array($estado, ['LIBRE', 'RESERVADO', 'PAGADO', 'BLOQUEADO'], true)) {
            $q->where('estado', $estado);
        }

        return $q->paginate($perPage);
    }
}
