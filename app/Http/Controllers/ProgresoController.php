<?php

namespace App\Http\Controllers;

use App\Models\Piece;
use App\Models\Progress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * El progreso de la cuenta.
 *
 * El navegador sigue siendo quien pinta (localStorage manda en la pagina);
 * el servidor es la copia que sobrevive al cambio de movil. cuenta.js baja
 * este estado al entrar y sube cada pieza al responder.
 */
class ProgresoController extends Controller
{
    /** Todo el progreso del usuario, listo para volcar en localStorage. */
    public function estado()
    {
        $filas = Progress::where('user_id', Auth::id())
            ->join('pieces', 'pieces.id', '=', 'progress.piece_id')
            ->get(['pieces.slug', 'progress.status', 'progress.answers_json',
                   'progress.score_num', 'progress.score_den']);

        $piezas = [];
        foreach ($filas as $f) {
            $piezas[$f->slug] = [
                'respuestas' => $f->answers_json ? json_decode($f->answers_json, true) : null,
                'hecha'      => $f->status === 'completada',
                'nota'       => $f->score_num !== null
                    ? $f->score_num . ' из ' . $f->score_den
                    : null,
            ];
        }

        return response()->json(['piezas' => $piezas]);
    }

    /** Guarda una pieza: respuestas y, si esta completa, la nota. */
    public function guardar(Request $peticion)
    {
        $datos = $peticion->validate([
            'pieza'        => ['required', 'string', 'exists:pieces,slug'],
            'respuestas'   => ['nullable', 'array'],
            'respuestas.*' => ['string', 'max:2'],
            'hecha'        => ['required', 'boolean'],
            'nota'         => ['nullable', 'string', 'max:20'],
        ]);

        $pieza = Piece::where('slug', $datos['pieza'])->first();

        [$num, $den] = [null, null];
        if ($datos['hecha'] && preg_match('/^(\d+)\D+(\d+)$/u', $datos['nota'] ?? '', $m)) {
            [$num, $den] = [(int) $m[1], (int) $m[2]];
        }

        $fila = Progress::firstOrNew(['user_id' => Auth::id(), 'piece_id' => $pieza->id]);
        $fila->first_opened_at ??= now();
        $fila->status       = $datos['hecha'] ? 'completada' : 'abierta';
        $fila->completed_at = $datos['hecha'] ? ($fila->completed_at ?? now()) : null;
        $fila->answers_json = $datos['respuestas'] ? json_encode($datos['respuestas']) : null;
        $fila->score_num    = $num;
        $fila->score_den    = $den;
        $fila->save();

        return response()->json(['ok' => true]);
    }
}
