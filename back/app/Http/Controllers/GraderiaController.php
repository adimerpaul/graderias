<?php

namespace App\Http\Controllers;

use App\Models\Graderia;
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

    // GET /mis-graderias/{graderia}?preview_rows=30&preview_cols=30
    public function show(Request $request, Graderia $graderia)
    {
        // Seguridad básica (si tienes policies, cámbialo por authorize)
        if ((int)$graderia->user_id !== (int)$request->user()->id) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        // Para no traer 250k asientos, solo preview
        $previewRows = (int) $request->query('preview_rows', 30);
        $previewCols = (int) $request->query('preview_cols', 30);

        $previewRows = max(1, min($previewRows, 60)); // puedes subir si quieres
        $previewCols = max(1, min($previewCols, 60));

        $asientosPreview = $graderia->asientos()
            ->select([
                'id',
                'graderia_id',
                'fila',
                'columna',
                'codigo',
                'estado',
                'descripcion',
                'cliente_nombre',
                'cliente_celular',
                'monto',
                'reservado_at',
                'pagado_at',
            ])
            ->where('fila', '<=', $previewRows)
            ->where('columna', '<=', $previewCols)
            ->orderBy('fila')
            ->orderBy('columna')
            ->get();

        return response()->json([
            'graderia'        => $graderia,
            'asientos_count'  => $graderia->asientos()->count(),
            'asientos_preview'=> $asientosPreview,
            'preview'         => [
                'rows' => $previewRows,
                'cols' => $previewCols,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'nombre' => 'nullable|string|max:120',
            'direccion' => 'required|string|max:255',
            'filas' => 'required|integer|min:1|max:500',
            'columnas' => 'required|integer|min:1|max:500',
            'etiqueta_modo' => 'required|in:fila,columna',
            'start_top' => 'required|boolean',
            'start_left' => 'required|boolean',
            'activo' => 'required|boolean',
        ]);

        $g = new Graderia();
        $g->fill($data);
        $g->user_id = $user->id;
        $g->capacidad_total = ((int)$data['filas']) * ((int)$data['columnas']);
        $g->save();

        \App\Services\AsientoGenerator::generateFor($g);

        return response()->json($g, 201);
    }

    public function update(Request $request, Graderia $graderia)
    {
        if ((int)$graderia->user_id !== (int)$request->user()->id) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $data = $request->validate([
            'nombre' => 'nullable|string|max:120',
            'direccion' => 'required|string|max:255',
            'filas' => 'required|integer|min:1|max:500',
            'columnas' => 'required|integer|min:1|max:500',
            'etiqueta_modo' => 'required|in:fila,columna',
            'start_top' => 'required|boolean',
            'start_left' => 'required|boolean',
            'activo' => 'required|boolean',
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

        if (($data['regenerar_asientos'] ?? false) && $changedLayout) {
            \App\Services\AsientoGenerator::generateFor($graderia, true);
        }

        return $graderia;
    }

    public function destroy(Request $request, Graderia $graderia)
    {
        if ((int)$graderia->user_id !== (int)$request->user()->id) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $graderia->delete();
        return response()->json(['ok' => true]);
    }
}
