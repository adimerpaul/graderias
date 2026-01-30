<?php

namespace App\Http\Controllers;

use App\Models\Graderia;
use App\Services\AsientoGenerator;
use Illuminate\Http\Request;

class GraderiaController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        return Graderia::query()
            ->where('user_id', $user->id)
            ->orderByDesc('id')
            ->get();
    }

    public function show(Request $request, Graderia $graderia)
    {
//        $this->authorize('view', $graderia);
        return $graderia;
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'nombre' => 'nullable|string|max:120',
            'codigo' => 'nullable|string|max:40|unique:graderias,codigo',
            'direccion' => 'required|string|max:255',
            'ref_izquierda' => 'nullable|string|max:255',
            'ref_derecha' => 'nullable|string|max:255',
            'ref_frente' => 'nullable|string|max:255',
            'filas' => 'required|integer|min:1|max:500',
            'columnas' => 'required|integer|min:1|max:500',
            'etiqueta_modo' => 'required|in:fila,columna',
            'start_top' => 'required|boolean',
            'start_left' => 'required|boolean',
            'activo' => 'nullable|boolean',
        ]);

        $g = new Graderia();
        $g->fill($data);
        $g->user_id = $user->id;
        $g->capacidad_total = ((int)$data['filas']) * ((int)$data['columnas']);
        $g->save();

        // crea asientos
        AsientoGenerator::generateFor($g);

        return response()->json($g, 201);
    }

    public function update(Request $request, Graderia $graderia)
    {
//        $this->authorize('update', $graderia);

        $data = $request->validate([
            'nombre' => 'nullable|string|max:120',
            'codigo' => 'nullable|string|max:40|unique:graderias,codigo,' . $graderia->id,
            'direccion' => 'required|string|max:255',
            'ref_izquierda' => 'nullable|string|max:255',
            'ref_derecha' => 'nullable|string|max:255',
            'ref_frente' => 'nullable|string|max:255',
            'filas' => 'required|integer|min:1|max:500',
            'columnas' => 'required|integer|min:1|max:500',
            'etiqueta_modo' => 'required|in:fila,columna',
            'start_top' => 'required|boolean',
            'start_left' => 'required|boolean',
            'activo' => 'nullable|boolean',

            // bandera simple para regenerar asientos
            'regenerar_asientos' => 'nullable|boolean',
        ]);

        $changedLayout =
            (int)$graderia->filas !== (int)$data['filas'] ||
            (int)$graderia->columnas !== (int)$data['columnas'] ||
            (string)$graderia->etiqueta_modo !== (string)$data['etiqueta_modo'] ||
            (bool)$graderia->start_top !== (bool)$data['start_top'] ||
            (bool)$graderia->start_left !== (bool)$data['start_left'];

        $graderia->fill($data);
        $graderia->capacidad_total = ((int)$data['filas']) * ((int)$data['columnas']);
        $graderia->save();

        $regenerar = (bool)($data['regenerar_asientos'] ?? false);

        // Si cambió el layout, regeneras si tú quieres (sin validar, simple)
        if ($regenerar && $changedLayout) {
            AsientoGenerator::generateFor($graderia, true);
        }

        return $graderia;
    }

    public function destroy(Request $request, Graderia $graderia)
    {
//        $this->authorize('delete', $graderia);
        $graderia->delete();
        return response()->json(['ok' => true]);
    }
}
