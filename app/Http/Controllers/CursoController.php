<?php

namespace App\Http\Controllers;

use App\Models\Exercise;
use App\Models\Piece;
use App\Support\Markdown;

class CursoController extends Controller
{
    /** Portada: los tres niveles y la biblioteca. */
    /** Materiales descargables y enlaces externos. */
    public function recursos()
    {
        // La carpeta servida de verdad, no app/public (leccion del hito 8).
        $raiz = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/') ?: public_path();

        // Desde el hito 10, los materiales viven en la base de datos y se
        // administran en /gestion. La carpeta fisica sigue siendo
        // «descargas» (si se llamara «recursos», Apache la serviria antes
        // que esta pagina y daria un 403), y el peso se lee del archivo.
        $todos = \App\Models\Resource::where('visible', true)
            ->orderBy('orden')->get();

        $descargables = $todos->where('categoria', 'descarga')
            ->map(function ($r) use ($raiz) {
                $fisica = $raiz . '/descargas/' . $r->archivo;
                if (! $r->archivo || ! is_file($fisica)) {
                    return null;             // aun sin subir: no se pinta
                }

                return [
                    'titulo' => $r->titulo,
                    'nota'   => $r->nota,
                    'url'    => '/descargas/' . $r->archivo,
                    'peso'   => round(filesize($fisica) / 1024 / 1024, 1),
                ];
            })
            ->filter()
            ->values();

        $enlaces = $todos->where('categoria', 'enlace')
            ->map(fn ($r) => ['url' => $r->url, 'titulo' => $r->titulo, 'nota' => $r->nota])
            ->values();

        return view('recursos', compact('descargables', 'enlaces'));
    }

    /** «О нас»: la historia del curso. */
    public function nosotros()
    {
        $raiz = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/') ?: public_path();

        $foto = null;
        foreach (['img/nosotros.jpg', 'img/nosotros.png', 'img/nosotros.webp'] as $ruta) {
            if (is_file($raiz . '/' . $ruta)) {
                $foto = '/' . $ruta . '?v=' . (@filemtime($raiz . '/' . $ruta) ?: 1);
                break;
            }
        }

        return view('nosotros', ['foto' => $foto]);
    }

    /** La puerta: bienvenida para quien no ha entrado, el camino para quien si. */
    public function inicio()
    {
        return auth()->check() ? $this->portada() : $this->bienvenida();
    }

    /** El camino, accesible tambien sin cuenta («смотреть без регистрации»). */
    public function curso()
    {
        return $this->portada();
    }

    protected function bienvenida()
    {
        return view('bienvenida', [
            'modulos'    => Piece::where('type', 'modulo')->count(),
            'fichas'     => Piece::whereIn('type', ['ficha_ojo', 'ficha_practica'])->count(),
            'cuentos'    => Piece::where('type', 'cuento')->count(),
            'ejercicios' => Exercise::count(),
        ]);
    }

    protected function portada()
    {
        $niveles = [];

        foreach ([1, 2, 3] as $n) {
            $niveles[$n] = Piece::where('type', 'modulo')
                ->where('level', $n)
                ->orderBy('position')
                ->get(['id', 'slug', 'level', 'position', 'title_es', 'title_ru', 'duration_min', 'exercise_count']);
        }

        return view('portada', [
            'camino'    => $this->camino(),
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

        return view('nivel', compact('n', 'modulos', 'cuentos')
            + ['nombre' => $nombres[$n], 'camino' => $this->camino($n)]);
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

    /**
     * El camino del curso: la secuencia en la que se estudia.
     *
     * Modulos por nivel y posicion; cada cuento entra justo despues del
     * modulo que su «leer_despues_de» señala. Sin cuentas de usuario, el
     * servidor solo publica el ORDEN: quien sabe que hay hecho y que no es
     * el navegador (localStorage), y alli se decora.
     *
     * @return array<int,array{slug:string,url:string,etiqueta:string,titulo:string}>
     */
    /** El camino, para quien lo necesite desde fuera (el embudo de gestion). */
    public function caminoPublico(): array
    {
        return $this->camino();
    }

    protected function camino(?int $nivel = null): array
    {
        $de = function (string $tipo) use ($nivel) {
            return Piece::where('type', $tipo)
                ->when($nivel, fn ($q) => $q->where('level', $nivel))
                ->orderBy('level')->orderBy('position')
                ->get(['id', 'slug', 'position', 'title_es', 'read_after_slug']);
        };

        $modulos = $de('modulo');
        $cuentos = $de('cuento')->groupBy(fn ($c) => mb_strtolower($c->read_after_slug ?? ''));
        $usadas  = [];

        $camino = [];
        foreach ($modulos as $m) {
            $codigo = preg_match('/^(n\d-m\d{2})/', $m->slug, $x) ? $x[1] : $m->slug;
            $camino[] = $this->paso($m, 'Модуль');

            foreach ($cuentos->get($codigo, collect()) as $c) {
                $camino[]         = $this->paso($c, 'Рассказ');
                $usadas[$codigo]  = true;
            }
        }

        // Cuentos cuya ancla no casara con ningun modulo (no deberia pasar):
        // mejor al final del camino que perdidos.
        foreach ($cuentos as $codigo => $grupo) {
            if (! isset($usadas[$codigo])) {
                foreach ($grupo as $c) {
                    $camino[] = $this->paso($c, 'Рассказ');
                }
            }
        }

        return $camino;
    }

    /** @return array{slug:string,url:string,etiqueta:string,titulo:string} */
    protected function paso(Piece $p, string $tipo): array
    {
        return [
            'slug'     => $p->slug,
            'url'      => route('pieza', $p->slug),
            'etiqueta' => $tipo . ' ' . $p->position,
            'titulo'   => $p->title_es,
        ];
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
