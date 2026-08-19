<?php

namespace App\Http\Controllers;

use App\Models\Piece;
use App\Support\Markdown;

class CursoController extends Controller
{
    /** Portada: los tres niveles y la biblioteca. */
    public function portada()
    {
        $niveles = [];

        foreach ([1, 2, 3] as $n) {
            $niveles[$n] = Piece::where('type', 'modulo')
                ->where('level', $n)
                ->orderBy('position')
                ->get(['id', 'slug', 'level', 'position', 'title_es', 'title_ru', 'duration_min', 'exercise_count']);
        }

        return view('portada', [
            'niveles'   => $niveles,
            'fichas'    => Piece::whereIn('type', ['ficha_ojo', 'ficha_practica'])->count(),
            'cuentos'   => Piece::where('type', 'cuento')->count(),
            'ejercicios'=> Piece::sum('exercise_count'),
        ]);
    }

    /** Listado de un nivel: modulos y cuentos intercalados. */
    public function nivel(int $n)
    {
        abort_unless(in_array($n, [1, 2, 3], true), 404);

        $modulos = Piece::where('type', 'modulo')->where('level', $n)
            ->orderBy('position')->get();

        $cuentos = Piece::where('type', 'cuento')->where('level', $n)
            ->orderBy('position')->get();

        $nombres = [
            1 => ['Ничего не знаю, но очень хочу', 'No sé nada, pero quiero'],
            2 => ['Уже строю свои фразы',          'Ya construyo mis frases'],
            3 => ['Решаю свои дела по-испански',   'Resuelvo mi vida en español'],
        ];

        return view('nivel', compact('n', 'modulos', 'cuentos') + ['nombre' => $nombres[$n]]);
    }

    /** La biblioteca de fichas. */
    public function fichas()
    {
        return view('fichas', [
            'ojo'       => Piece::where('type', 'ficha_ojo')->orderBy('position')->get(),
            'practicas' => Piece::where('type', 'ficha_practica')->orderBy('slug')->get(),
        ]);
    }

    /** Una pieza cualquiera: modulo, cuento o ficha. */
    public function pieza(string $slug)
    {
        $pieza = Piece::where('slug', $slug)->firstOrFail();

        $pieza->load(['sections', 'vocabulary', 'lines', 'phrases', 'links']);

        $md = new Markdown;

        // Cada seccion se prepara segun su tipo
        $secciones = $pieza->sections->map(function ($s) use ($md, $pieza) {
            $s->html = in_array($s->kind, ['apoyo', 'escena', 'cuento', 'frases', 'enlaces'], true)
                ? null                       // estas se pintan con sus propios datos
                : $md->aHtml($s->body_md);

            return $s;
        });

        $siguiente = $this->siguiente($pieza);

        return view('pieza', compact('pieza', 'secciones', 'siguiente'));
    }

    protected function siguiente(Piece $pieza): ?Piece
    {
        if ($pieza->type !== 'modulo') {
            return null;
        }

        return Piece::where('type', 'modulo')
            ->where('level', $pieza->level)
            ->where('position', '>', $pieza->position)
            ->orderBy('position')
            ->first();
    }
}
