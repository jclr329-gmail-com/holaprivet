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

    /** Convierte los slugs de destino en identificadores reales. */
    protected function resolverEnlaces(): void
    {
        $mapa = Piece::pluck('id', 'slug');
        $rotos = 0;

        foreach (CrossLink::whereNull('to_piece_id')->cursor() as $enlace) {
            $destino = $mapa[$enlace->to_slug] ?? null;

            if (! $destino) {
                // Los enlaces cortos («n2-13») se completan con el prefijo largo
                $coincidencia = $mapa->keys()->first(
                    fn ($s) => str_starts_with($s, $enlace->to_slug)
                );
                $destino = $coincidencia ? $mapa[$coincidencia] : null;
            }

            if ($destino) {
                $enlace->update(['to_piece_id' => $destino]);
            } else {
                $rotos++;
            }
        }

        if ($rotos > 0) {
            $this->apuntar(null, 'aviso', "{$rotos} enlaces no apuntan a ninguna pieza existente");
            $this->resumen['avisos'] += $rotos;
        }
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
