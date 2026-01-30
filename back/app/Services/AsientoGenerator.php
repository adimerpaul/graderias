<?php

namespace App\Services;

use App\Models\Graderia;
use App\Models\Asiento;
use Illuminate\Support\Facades\DB;

class AsientoGenerator
{
    public static function generateFor(Graderia $g, bool $wipeExisting = false): void
    {
        DB::transaction(function () use ($g, $wipeExisting) {

            if ($wipeExisting) {
                Asiento::where('graderia_id', $g->id)->delete();
            }

            $rows = (int)$g->filas;
            $cols = (int)$g->columnas;

            $total = $rows * $cols;
            $g->capacidad_total = $total;
            $g->save();

            if ($rows <= 0 || $cols <= 0) return;

            $startTop  = (bool)$g->start_top;
            $startLeft = (bool)$g->start_left;

            // orden real de filas/cols según start_top / start_left
            $rowOrder = $startTop ? range(1, $rows) : array_reverse(range(1, $rows));
            $colOrder = $startLeft ? range(1, $cols) : array_reverse(range(1, $cols));

            $data = [];

            foreach ($rowOrder as $r) {
                foreach ($colOrder as $c) {

                    // etiqueta_modo:
                    // fila => letra = fila, numero = col
                    // columna => letra = col, numero = fila
                    if ($g->etiqueta_modo === 'fila') {
                        $letter = self::numToLetters($r); // A, B, ... AA...
                        $number = $c;
                    } else {
                        $letter = self::numToLetters($c);
                        $number = $r;
                    }

                    $codigo = $letter . $number;

                    $data[] = [
                        'graderia_id' => $g->id,
                        'fila' => $r,
                        'columna' => $c,
                        'codigo' => $codigo,
                        'estado' => 'LIBRE',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }

            // Insert por lotes (más rápido)
            foreach (array_chunk($data, 1000) as $chunk) {
                Asiento::insert($chunk);
            }
        });
    }

    // 1->A, 2->B, ... 26->Z, 27->AA...
    private static function numToLetters(int $n): string
    {
        $s = '';
        while ($n > 0) {
            $n--;
            $s = chr(65 + ($n % 26)) . $s;
            $n = intdiv($n, 26);
        }
        return $s;
    }
}
