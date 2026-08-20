<?php

namespace App\Http\Controllers;

use App\Models\Exercise;
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
            'modulos'    => Piece::where('type', 'modulo')->count(),
            'fichas'     => Piece::whereIn('type', ['ficha_ojo', 'ficha_practica'])->count(),
            'cuentos'    => Piece::where('type', 'cuento')->count(),
            // Se cuentan los ejercicios reales: el campo declarado deja fuera
            // las cinco preguntas de cada cuento.
            'ejercicios' => Exercise::count(),
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

        $pieza->load(['sections', 'vocabulary', 'lines', 'phrases', 'links',
                      'exercises.options']);

        // Con ejercicios en la base de datos, la seccion se vuelve
        // interactiva: cada solucion aparece al responder, asi que el
        // solucionario como seccion sobra (y destriparia las respuestas).
        $interactivo = $pieza->exercises->isNotEmpty();

        $md = new Markdown;

        $secciones = $pieza->sections;

        if ($interactivo) {
            $secciones = $secciones->reject(fn ($s) => $s->kind === 'soluciones')->values();

            // Sin el solucionario quedaria un hueco en la numeracion del
            // indice; se renumera solo para pintar, la base no se toca.
            $n = 0;
            foreach ($secciones as $s) {
                $s->number = ++$n;
            }
        }

        // Cada seccion se prepara segun su tipo
        $propias = ['apoyo', 'escena', 'cuento', 'frases', 'enlaces'];
        if ($interactivo) {
            $propias[] = 'ejercicios';
            $propias[] = 'preguntas';
        }

        $secciones = $secciones->map(function ($s) use ($md, $propias) {
            $s->html = in_array($s->kind, $propias, true)
                ? null                       // estas se pintan con sus propios datos
                : $md->aHtml($s->body_md);

            return $s;
        });

        $siguiente = $this->siguiente($pieza);
        $repasos   = $interactivo ? $this->repasos($pieza) : collect();

        return view('pieza', compact('pieza', 'secciones', 'siguiente', 'interactivo', 'repasos'));
    }

    /**
     * Piezas que conviene repasar segun las soluciones de esta.
     *
     * Algunas explicaciones traen una marca de repaso —«(повторение N2-04)»—
     * que el importador guardo como codigo corto. Aqui se traduce a piezas
     * reales para ofrecerlas en el resumen final de los ejercicios.
     */
    protected function repasos(Piece $pieza)
    {
        return $pieza->exercises
            ->pluck('review_of_slug')
            ->filter()
            ->unique()
            ->map(function ($codigo) {
                $c = str_replace('#', '-', mb_strtolower($codigo));
                $c = preg_replace_callback('/^ojo-?(\d{1,2})$/',
                    fn ($m) => 'ojo-' . str_pad($m[1], 2, '0', STR_PAD_LEFT), $c);

                return Piece::where('slug', $c)
                    ->orWhere('slug', 'like', $c . '-%')
                    ->first();
            })
            ->filter()
            ->unique('id')
            ->values();
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
