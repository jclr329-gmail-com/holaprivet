<?php

namespace App\Services;

use App\Models\ContentVersion;
use App\Models\CrossLink;
use App\Models\ImportLog;
use App\Models\Piece;
use App\Models\Tag;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Recorre la carpeta de contenido, analiza cada Markdown y lo vuelca a la
 * base de datos. Cada ejecucion queda registrada como una version.
 */
class Importador
{
    protected ContentVersion $version;

    /** Identificadores ya vistos, para detectar duplicados. */
    protected array $vistos = [];

    protected array $resumen = [
        'archivos'   => 0,
        'piezas'     => 0,
        'ejercicios' => 0,
        'errores'    => 0,
        'avisos'     => 0,
    ];

    public function __construct(
        protected string $ruta,
        protected bool $simulacro = false,
    ) {}

    public function ejecutar(): array
    {
        $archivos = $this->archivos();

        if (empty($archivos)) {
            throw new \RuntimeException("No se han encontrado archivos .md en {$this->ruta}");
        }

        $this->version = ContentVersion::create([
            'imported_at' => now(),
            'status'      => 'en_curso',
            'notes'       => $this->simulacro ? 'SIMULACRO: no se guarda nada' : null,
        ]);

        foreach ($archivos as $archivo) {
            $this->procesar($archivo);
        }

        if (! $this->simulacro) {
            $this->resolverEnlaces();
        }

        $this->version->update([
            'file_count'     => $this->resumen['archivos'],
            'piece_count'    => $this->resumen['piezas'],
            'exercise_count' => $this->resumen['ejercicios'],
            'error_count'    => $this->resumen['errores'],
            'status'         => $this->resumen['errores'] > 0 ? 'con_errores' : 'correcta',
        ]);

        return $this->resumen + ['version' => $this->version->id];
    }

    /**
     * Busca todos los .md de forma recursiva.
     *
     * Se recorre el arbol entero en vez de usar rutas fijas por dos motivos:
     * Linux distingue mayusculas —«Modulos» no es «modulos»— y asi da igual
     * como se llamen las carpetas o cuantos niveles tengan.
     *
     * @return array<int,string>
     */
    protected function archivos(): array
    {
        $salida = [];

        $iterador = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                rtrim($this->ruta, '/'),
                \FilesystemIterator::SKIP_DOTS
            )
        );

        foreach ($iterador as $archivo) {
            if (! $archivo->isFile() || strtolower($archivo->getExtension()) !== 'md') {
                continue;
            }
            $salida[] = $archivo->getPathname();
        }

        sort($salida);

        // Documentos de trabajo del proyecto: no son contenido del curso.
        $descartar = ['PLAN', 'ESTADO', 'FORMATO', 'README', 'PERSONAJES',
                      'PROMPTS-IMAGEN', 'ESPECIFICACIONES', 'INSTALACION', 'ESQUEMA-BD'];

        return array_values(array_filter($salida, function ($f) use ($descartar) {
            return ! in_array(strtoupper(pathinfo($f, PATHINFO_FILENAME)), $descartar, true);
        }));
    }

    protected function procesar(string $archivo): void
    {
        $this->resumen['archivos']++;
        $nombre = basename($archivo);

        $parser = new ParserPieza;
        $datos  = $parser->analizar($archivo);

        foreach ($parser->incidencias as $i) {
            $this->apuntar($nombre, $i['nivel'], $i['mensaje']);
            $this->resumen[$i['nivel'] === 'error' ? 'errores' : 'avisos']++;
        }

        if (! $datos) {
            return;
        }

        // Dos archivos con el mismo id: normalmente una copia antigua que
        // sobrevivio a un cambio de nombre. Se avisa y se omite el segundo.
        $slug = $datos['pieza']['slug'];
        if (isset($this->vistos[$slug])) {
            $this->apuntar($nombre, 'error',
                "id repetido «{$slug}»: ya lo trae {$this->vistos[$slug]}. Borra el archivo antiguo.");
            $this->resumen['errores']++;
            return;
        }
        $this->vistos[$slug] = $nombre;

        $tieneErrores = collect($parser->incidencias)->contains(fn ($i) => $i['nivel'] === 'error');

        if ($this->simulacro) {
            if (! $tieneErrores) {
                $this->resumen['piezas']++;
                $this->resumen['ejercicios'] += count($datos['ejercicios']);
            }
            return;
        }

        if ($tieneErrores) {
            $this->apuntar($nombre, 'aviso', 'no se importa por los errores anteriores');
            return;
        }

        DB::transaction(fn () => $this->guardar($datos));

        $this->resumen['piezas']++;
        $this->resumen['ejercicios'] += count($datos['ejercicios']);
    }

    protected function guardar(array $d): void
    {
        $pieza = Piece::updateOrCreate(
            ['slug' => $d['pieza']['slug']],
            $d['pieza'] + ['published_at' => now()]
        );

        // Se borra lo anterior y se reescribe: la fuente de verdad es el .md
        $pieza->sections()->delete();
        $pieza->vocabulary()->delete();
        $pieza->lines()->delete();
        $pieza->phrases()->delete();
        $pieza->links()->delete();
        foreach ($pieza->exercises as $ej) {
            $ej->options()->delete();
        }
        $pieza->exercises()->delete();

        foreach ($d['secciones'] as $s) {
            $pieza->sections()->create([
                'number'   => $s['number'],
                'kind'     => $s['kind'],
                'title_es' => $s['title_es'],
                'title_ru' => $s['title_ru'],
                'body_md'  => $s['body_md'],
            ]);
        }

        foreach ($d['vocabulario'] as $v) { $pieza->vocabulary()->create($v); }
        foreach ($d['lineas']      as $l) { $pieza->lines()->create($l); }
        foreach ($d['frases']      as $f) { $pieza->phrases()->create($f); }

        foreach ($d['ejercicios'] as $e) {
            $opciones = $e['opciones'];
            unset($e['opciones']);
            $ejercicio = $pieza->exercises()->create($e);
            foreach ($opciones as $o) {
                $ejercicio->options()->create($o);
            }
        }

        foreach ($d['enlaces'] as $en) {
            $pieza->links()->create($en);
        }

        $this->etiquetar($pieza, $d['etiquetas']);
    }

    protected function etiquetar(Piece $pieza, array $etiquetas): void
    {
        DB::table('taggables')
            ->where('taggable_type', 'piece')
            ->where('taggable_id', $pieza->id)
            ->delete();

        foreach ($etiquetas as $nombre) {
            $slug = Str::slug($nombre);
            if ($slug === '') {
                continue;
            }

            $tag = Tag::firstOrCreate(
                ['slug' => $slug],
                ['name_es' => $nombre, 'name_ru' => $nombre, 'kind' => 'gramatical']
            );

            DB::table('taggables')->insertOrIgnore([
                'tag_id'        => $tag->id,
                'taggable_id'   => $pieza->id,
                'taggable_type' => 'piece',
            ]);
        }
    }

    /**
     * Convierte los destinos de los enlaces en identificadores reales.
     *
     * En el texto los enlaces se escriben para leerse, no para el ordenador:
     * «N2-13», «Ojo #9 — llorar · reír», «Ficha — números». Hay que traducirlos.
     * Con las reglas de abajo se resuelve el 98 % de los 438 enlaces del curso.
     */
    protected function resolverEnlaces(): void
    {
        $mapa  = Piece::pluck('id', 'slug');
        $rotos = 0;

        foreach (CrossLink::whereNull('to_piece_id')->cursor() as $enlace) {
            $destino = null;

            foreach ($this->candidatos($enlace->to_slug) as $c) {
                if (isset($mapa[$c])) {
                    $destino = $mapa[$c];
                    break;
                }

                $parecido = $mapa->keys()->first(
                    fn ($s) => str_starts_with($s, $c) || str_starts_with($c, $s)
                );

                if ($parecido) {
                    $destino = $mapa[$parecido];
                    break;
                }
            }

            if ($destino) {
                $enlace->update(['to_piece_id' => $destino]);
            } else {
                $rotos++;
            }
        }

        if ($rotos > 0) {
            $this->apuntar(null, 'aviso', "{$rotos} enlaces sin destino: quedaran como texto");
            $this->resumen['avisos'] += $rotos;
        }
    }

    /** @return array<int,string> posibles slugs, del mas probable al menos */
    protected function candidatos(string $bruto): array
    {
        $b = trim(explode('/', $bruto)[0]);

        $partes = preg_split('/\s*[—–]\s*/u', $b, 2);
        $izq = trim($partes[0] ?? '');
        $der = trim($partes[1] ?? '');

        $salida = [$this->codigo($izq), $this->slug($izq)];

        if ($der !== '') {
            $salida[] = 'ficha-' . $this->slug($der);
            $salida[] = $this->slug($der);
            $salida[] = 'cuento-' . $this->slug($der);
        }

        return array_values(array_filter(array_unique($salida)));
    }

    /** «N2-13» -> n2-m13 · «Ojo #9» -> ojo-09 */
    protected function codigo(string $s): string
    {
        $s = str_replace([' ', '#'], '', mb_strtolower($s));
        $s = preg_replace('/^n([123])-(\d{1,2})$/', 'n$1-m$2', $s);
        $s = preg_replace_callback('/^n([123])-m(\d{1,2})$/',
            fn ($m) => 'n' . $m[1] . '-m' . str_pad($m[2], 2, '0', STR_PAD_LEFT), $s);
        $s = preg_replace_callback('/^ojo-?(\d{1,2})$/',
            fn ($m) => 'ojo-' . str_pad($m[1], 2, '0', STR_PAD_LEFT), $s);

        return $s;
    }

    protected function slug(string $s): string
    {
        return Str::slug($s);
    }

    protected function apuntar(?string $archivo, string $nivel, string $mensaje): void
    {
        ImportLog::create([
            'version_id' => $this->version->id,
            'file'       => $archivo,
            'level'      => $nivel,
            'message'    => $mensaje,
        ]);
    }
}
