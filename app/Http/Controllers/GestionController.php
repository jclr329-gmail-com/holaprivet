<?php

namespace App\Http\Controllers;

use App\Models\Exercise;
use App\Models\Piece;
use App\Models\Progress;
use App\Models\Resource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * El area de gestion (/gestion). Protegida por el middleware EsAdmin;
 * sin un solo enlace desde la web publica.
 */
class GestionController extends Controller
{
    // -------------------------------------------------------------- panel

    public function panel()
    {
        $semana = now()->subDays(7);

        return view('gestion.panel', [
            'alumnos'       => User::count(),
            'verificados'   => User::whereNotNull('email_verified_at')->count(),
            'nuevosSemana'  => User::where('created_at', '>=', $semana)->count(),
            'activosSemana' => Progress::where('updated_at', '>=', $semana)
                                   ->distinct('user_id')->count('user_id'),
            'completadas'   => Progress::where('status', 'completada')->count(),
            'respuestas'    => (int) Progress::whereNotNull('answers_json')->count(),
        ]);
    }

    // ------------------------------------------------------------ alumnos

    public function alumnos()
    {
        $alumnos = User::query()
            ->leftJoin('progress', 'progress.user_id', '=', 'users.id')
            ->groupBy('users.id', 'users.name', 'users.email',
                      'users.email_verified_at', 'users.google_id', 'users.created_at')
            ->orderByDesc('users.created_at')
            ->get([
                'users.id', 'users.name', 'users.email', 'users.email_verified_at',
                'users.google_id', 'users.created_at',
                DB::raw("sum(case when progress.status = 'completada' then 1 else 0 end) as hechas"),
                DB::raw('max(progress.updated_at) as ultima_actividad'),
            ]);

        return view('gestion.alumnos', compact('alumnos'));
    }

    // ------------------------------------------------------------- embudo

    public function embudo()
    {
        // El camino en orden (modulos + cuentos tras su ancla), y cuantos
        // alumnos tienen CADA pieza como su ultima completada: la
        // distribucion dice exactamente donde se deja el curso.
        $camino = app(CursoController::class)->caminoPublico();

        $ultimas = DB::table('progress as p')
            ->join('pieces', 'pieces.id', '=', 'p.piece_id')
            ->where('p.status', 'completada')
            ->whereRaw('p.completed_at = (select max(p2.completed_at) from progress p2
                        where p2.user_id = p.user_id and p2.status = "completada")')
            ->groupBy('pieces.slug')
            ->pluck(DB::raw('count(*) as n'), 'pieces.slug');

        $hechasPorPieza = DB::table('progress')
            ->join('pieces', 'pieces.id', '=', 'progress.piece_id')
            ->where('status', 'completada')
            ->groupBy('pieces.slug')
            ->pluck(DB::raw('count(*) as n'), 'pieces.slug');

        $filas = [];
        foreach ($camino as $paso) {
            $filas[] = [
                'slug'     => $paso['slug'],
                'etiqueta' => $paso['etiqueta'],
                'titulo'   => $paso['titulo'],
                'hechas'   => (int) ($hechasPorPieza[$paso['slug']] ?? 0),
                'abandono' => (int) ($ultimas[$paso['slug']] ?? 0),
            ];
        }

        $maximo = max(1, ...array_column($filas, 'hechas'));

        return view('gestion.embudo', compact('filas', 'maximo'));
    }

    // ------------------------------------------------------------- fallos

    public function fallos()
    {
        // Tasa de fallo por ejercicio, agregando answers_json de todos los
        // alumnos. «El ejercicio 14 de n2-m13 lo falla el 70%» es una senal
        // directa de que el ejercicio o su explicacion necesitan retoque.
        $correctas = [];   // piece_id => [position => letra]
        foreach (Exercise::with('options')->get() as $ej) {
            $ok = $ej->options->firstWhere('is_correct', true);
            if ($ok) {
                $correctas[$ej->piece_id][$ej->position] = $ok->letter;
            }
        }

        $conteo = [];      // "piece_id:pos" => [malas, total]
        Progress::whereNotNull('answers_json')
            ->get(['piece_id', 'answers_json'])
            ->each(function ($fila) use (&$conteo, $correctas) {
                $respuestas = json_decode($fila->answers_json, true) ?: [];
                foreach ($respuestas as $pos => $letra) {
                    $buena = $correctas[$fila->piece_id][(int) $pos] ?? null;
                    if ($buena === null) {
                        continue;
                    }
                    $clave = $fila->piece_id . ':' . (int) $pos;
                    $conteo[$clave] ??= [0, 0];
                    $conteo[$clave][1]++;
                    if ($letra !== $buena) {
                        $conteo[$clave][0]++;
                    }
                }
            });

        $piezas = Piece::pluck('slug', 'id');
        $textos = Exercise::get(['piece_id', 'position', 'prompt'])
            ->keyBy(fn ($e) => $e->piece_id . ':' . $e->position);

        $filas = collect($conteo)
            ->map(function ($c, $clave) use ($piezas, $textos) {
                [$pid, $pos] = explode(':', $clave);

                return [
                    'pieza'      => $piezas[$pid] ?? '?',
                    'n'          => (int) $pos,
                    'enunciado'  => mb_substr($textos[$clave]->prompt ?? '', 0, 90),
                    'intentos'   => $c[1],
                    'fallos'     => $c[0],
                    'porcentaje' => $c[1] ? round($c[0] * 100 / $c[1]) : 0,
                ];
            })
            ->filter(fn ($f) => $f['intentos'] >= 3)   // sin muestra no hay senal
            ->sortByDesc('porcentaje')
            ->take(40)
            ->values();

        return view('gestion.fallos', ['filas' => $filas]);
    }

    // -------------------------------------------------------- donaciones

    public function donaciones()
    {
        return view('gestion.donaciones', [
            'totalCents' => (int) \App\Models\Order::where('status', 'pagado')->sum('total_cents'),
            'ocupadas'   => \App\Models\WallWord::where('status', 'ocupada')->count(),
            'pendientesModeracion' => \App\Models\WallOwnership::where('moderation', 'pendiente')->count(),
            'moderacion' => \App\Models\WallOwnership::with(['palabra', 'usuario'])
                                ->where('moderation', 'pendiente')->get(),
            'pedidos'    => \App\Models\Order::with(['usuario', 'items.palabra'])
                                ->orderByDesc('id')->limit(100)->get(),
        ]);
    }

    public function moderar(Request $peticion)
    {
        $datos = $peticion->validate([
            'id'       => ['required', 'integer', 'exists:wall_ownerships,id'],
            'decision' => ['required', 'in:aprobada,rechazada'],
        ]);

        \App\Models\WallOwnership::findOrFail($datos['id'])->update([
            'moderation'   => $datos['decision'],
            'moderated_at' => now(),
        ]);

        return back()->with('estado', 'Moderado.');
    }

    // -------------------------------------------------------- materiales

    public function materiales()
    {
        $raiz = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/') ?: public_path();

        $recursos = Resource::orderBy('categoria')->orderBy('orden')->get()
            ->map(function ($r) use ($raiz) {
                $r->existe = $r->categoria !== 'descarga'
                    || is_file($raiz . '/descargas/' . $r->archivo);

                return $r;
            });

        return view('gestion.materiales', compact('recursos'));
    }

    public function materialGuardar(Request $peticion)
    {
        $datos = $peticion->validate([
            'id'        => ['nullable', 'integer', 'exists:resources,id'],
            'categoria' => ['required', 'in:descarga,enlace'],
            'titulo'    => ['required', 'string', 'max:190'],
            'nota'      => ['nullable', 'string', 'max:500'],
            'url'       => ['nullable', 'string', 'max:300'],
            'orden'     => ['required', 'integer', 'min:0', 'max:999'],
            'visible'   => ['nullable'],
            'pdf'       => ['nullable', 'file', 'mimes:pdf', 'max:20480'],
        ]);

        $recurso = isset($datos['id'])
            ? Resource::findOrFail($datos['id'])
            : new Resource(['tipo' => $datos['categoria'] === 'enlace' ? 'enlace' : 'pdf']);

        $recurso->fill([
            'categoria' => $datos['categoria'],
            'titulo'    => $datos['titulo'],
            'nota'      => $datos['nota'] ?? null,
            'url'       => $datos['url'] ?? null,
            'orden'     => $datos['orden'],
            'visible'   => $peticion->boolean('visible'),
        ]);
        $recurso->tipo = $recurso->categoria === 'enlace' ? 'enlace' : 'pdf';

        // El PDF se guarda directamente en descargas/, en la raiz servida.
        if ($peticion->hasFile('pdf')) {
            $raiz    = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/') ?: public_path();
            $carpeta = $raiz . '/descargas';
            if (! is_dir($carpeta)) {
                mkdir($carpeta, 0755, true);
            }
            $nombre = preg_replace('/[^a-z0-9\-_.]/', '-',
                mb_strtolower($peticion->file('pdf')->getClientOriginalName()));
            $peticion->file('pdf')->move($carpeta, $nombre);
            $recurso->archivo = $nombre;
        }

        $recurso->save();

        return back()->with('estado', 'Guardado.');
    }

    public function materialBorrar(Request $peticion)
    {
        $datos = $peticion->validate(['id' => ['required', 'integer', 'exists:resources,id']]);

        // Solo la ficha: el archivo fisico se queda (borrar bytes desde una
        // pantalla web es de las cosas que se lamentan).
        Resource::findOrFail($datos['id'])->delete();

        return back()->with('estado', 'Quitado de la lista (el archivo sigue en descargas/).');
    }
}
