<?php

namespace App\Services;

use App\Support\FrontMatter;

/**
 * Analiza un archivo Markdown del curso y lo convierte en estructuras planas
 * listas para guardar. No toca la base de datos: solo lee y devuelve.
 */
class ParserPieza
{
    /** Errores y avisos encontrados. @var array<int,array{nivel:string,mensaje:string}> */
    public array $incidencias = [];

    public function analizar(string $ruta): ?array
    {
        $bruto = file_get_contents($ruta);
        if ($bruto === false) {
            $this->error('no se pudo leer el archivo');
            return null;
        }

        [$cab, $cuerpo] = FrontMatter::separar($bruto);

        if (empty($cab)) {
            // Sin cabecera YAML no es una pieza del curso: se omite sin ruido.
            $this->aviso('no tiene cabecera: no parece contenido del curso, se omite');
            return null;
        }

        if (empty($cab['id'])) {
            $this->error('falta el campo id en la cabecera');
            return null;
        }

        $esperado = pathinfo($ruta, PATHINFO_FILENAME);
        if ($cab['id'] !== $esperado) {
            $this->error("el id ({$cab['id']}) no coincide con el nombre de archivo ({$esperado})");
        }

        $secciones = $this->secciones($cuerpo);

        $datos = [
            'pieza'      => $this->pieza($cab),
            'secciones'  => $secciones,
            'vocabulario'=> $this->vocabulario($secciones),
            'lineas'     => $this->lineas($secciones),
            'frases'     => $this->frases($secciones),
            'ejercicios' => $this->ejercicios($secciones),
            'enlaces'    => $this->enlaces($secciones),
            'etiquetas'  => $this->lista($cab, 'tags'),
        ];

        $this->validar($cab, $datos);

        return $datos;
    }

    // ---------------------------------------------------------------- pieza

    protected function pieza(array $c): array
    {
        $tipos = [
            'modulo'         => 'modulo',
            'cuento'         => 'cuento',
            'ficha-ojo'      => 'ficha_ojo',
            'ficha-practica' => 'ficha_practica',
        ];

        return [
            'slug'            => $c['id'],
            'type'            => $tipos[$c['tipo'] ?? ''] ?? 'ficha_practica',
            'level'           => isset($c['nivel']) ? (int) $c['nivel'] : null,
            'position'        => (int) ($c['orden'] ?? $c['numero'] ?? 0),
            'title_es'        => $c['titulo_es'] ?? '(sin titulo)',
            'title_ru'        => $c['titulo_ru'] ?? '',
            'duration_min'    => is_numeric($c['duracion_min'] ?? null) ? (int) $c['duracion_min'] : null,
            'word_count'      => isset($c['palabras']) ? (int) $c['palabras'] : null,
            'read_after_slug' => $c['leer_despues_de'] ?? null,
            'anchor_slug'     => $c['ancla'] ?? null,
            'in_campaign'     => (bool) ($c['campaña'] ?? $c['campana'] ?? true),
            'printable'       => (bool) ($c['imprimible'] ?? false),
            'audio_status'    => in_array($c['audio'] ?? '', ['imprescindible', 'listo'], true)
                                    ? $c['audio'] : 'pendiente',
            'image_prompt'    => $c['prompt_imagen'] ?? null,
            'characters'      => $this->lista($c, 'personajes'),
            'exercise_count'  => (int) ($c['ejercicios'] ?? 0),
        ];
    }

    protected function lista(array $c, string $clave): array
    {
        $v = $c[$clave] ?? [];
        return is_array($v) ? $v : array_filter([$v]);
    }

    // ------------------------------------------------------------ secciones

    /** Parte el cuerpo por los encabezados «## N. Titulo — Titulo» */
    protected function secciones(string $cuerpo): array
    {
        $lineas = explode("\n", $cuerpo);
        $secciones = [];
        $actual = null;

        foreach ($lineas as $l) {
            if (preg_match('/^##\s+(\d+)\.\s+(.+?)\s*$/u', $l, $m)) {
                if ($actual) {
                    $secciones[] = $actual;
                }
                [$es, $ru] = $this->partirTitulo($m[2]);
                $actual = [
                    'number'   => (int) $m[1],
                    'title_es' => $es,
                    'title_ru' => $ru,
                    'kind'     => $this->tipoSeccion($es),
                    'cuerpo'   => [],
                ];
                continue;
            }
            if ($actual) {
                $actual['cuerpo'][] = $l;
            }
        }

        if ($actual) {
            $secciones[] = $actual;
        }

        foreach ($secciones as &$s) {
            $s['body_md'] = trim(implode("\n", $s['cuerpo']));
        }

        return $secciones;
    }

    protected function partirTitulo(string $t): array
    {
        foreach ([' — ', ' – ', ' - '] as $sep) {
            if (str_contains($t, $sep)) {
                [$a, $b] = explode($sep, $t, 2);
                return [trim($a), trim($b)];
            }
        }
        return [trim($t), ''];
    }

    protected function tipoSeccion(string $titulo): string
    {
        $t = mb_strtolower($titulo);
        $mapa = [
            'objetivos'              => 'objetivos',
            'antes de empezar'       => 'apoyo',
            'antes de leer'          => 'apoyo',
            'la escena'              => 'escena',
            'el cuento'              => 'cuento',
            'traducción'             => 'traduccion',
            'lo que necesitas saber' => 'teoria',
            'frases para llevar'     => 'frases',
            'frases útiles'          => 'frases',
            'ojo con esto'           => 'ojo',
            'ejercicios'             => 'ejercicios',
            'solucionario'           => 'soluciones',
            'soluciones'             => 'soluciones',
            'has entendido'          => 'preguntas',
            'suelta el móvil'        => 'tarea',
            'enlaces'                => 'enlaces',
            'para hablar'            => 'conversacion',
            'para qué sirve'         => 'proposito',
            'el problema'            => 'problema',
            'la idea clave'          => 'idea',
            'contrastes'             => 'contrastes',
        ];

        foreach ($mapa as $aguja => $tipo) {
            if (str_contains($t, $aguja)) {
                return $tipo;
            }
        }

        return 'texto';
    }

    protected function seccion(array $secciones, string $tipo): ?array
    {
        foreach ($secciones as $s) {
            if ($s['kind'] === $tipo) {
                return $s;
            }
        }
        return null;
    }

    // ---------------------------------------------------------- vocabulario

    protected function vocabulario(array $secciones): array
    {
        $s = $this->seccion($secciones, 'apoyo');
        if (! $s) {
            return [];
        }

        $bloques = [
            'palabras nuevas' => 'nuevas',
            'ya lo conoces'   => 'conocidas',
            'frases clave'    => 'clave',
        ];

        $salida  = [];
        $bloque  = null;
        $posicion = 0;

        foreach (explode("\n", $s['body_md']) as $l) {
            if (preg_match('/^###\s+(.+)$/u', $l, $m)) {
                $bloque = null;
                $titulo = mb_strtolower($m[1]);
                foreach ($bloques as $aguja => $nombre) {
                    if (str_contains($titulo, $aguja)) {
                        $bloque = $nombre;
                    }
                }
                continue;
            }

            if (! $bloque || ! str_starts_with(trim($l), '|')) {
                continue;
            }

            $celdas = $this->celdas($l);
            if (! $celdas || $this->esSeparador($celdas) || $this->esCabecera($celdas)) {
                continue;
            }

            $es = $this->limpiarMd($celdas[0]);
            $ru = $this->limpiarMd($celdas[1] ?? '');
            if ($es === '' || $ru === '') {
                continue;
            }

            $nota = null;
            if (preg_match('/\*\((.+?)\)\*/u', $celdas[1] ?? '', $m)) {
                $nota = trim($m[1]);
            }

            $salida[] = [
                'block'        => $bloque,
                'position'     => $posicion++,
                'term_es'      => mb_substr($es, 0, 120),
                'term_ru'      => mb_substr($ru, 0, 120),
                'note'         => $nota ? mb_substr($nota, 0, 160) : null,
                'seen_in_slug' => isset($celdas[2]) ? $this->limpiarMd($celdas[2]) ?: null : null,
            ];
        }

        return $salida;
    }

    // --------------------------------------------------------------- lineas

    /**
     * Escenas y cuentos se leen distinto y no se pueden tratar igual.
     *
     * En una ESCENA cada linea es una replica: «> **Miguel**» seguido de lo que
     * dice, y las lineas en cursiva sueltas son acotaciones.
     *
     * En un CUENTO el texto es prosa: los parrafos ocupan varias lineas y la
     * negrita puede abrirse en una y cerrarse en la siguiente. Por eso hay que
     * juntar el parrafo ANTES de limpiar el Markdown; si no, salen asteriscos
     * sueltos en pantalla.
     */
    protected function lineas(array $secciones): array
    {
        if ($s = $this->seccion($secciones, 'escena')) {
            return $this->replicas($s['body_md']);
        }

        if ($s = $this->seccion($secciones, 'cuento')) {
            return $this->prosa($s['body_md']);
        }

        return [];
    }

    /** Escena: dialogo con personajes y acotaciones. */
    protected function replicas(string $md): array
    {
        $salida    = [];
        $posicion  = 0;
        $personaje = null;
        $acotacion = null;

        foreach (explode("\n", $md) as $l) {
            $t = trim($l);

            if ($t === '' || $t === '---') {
                continue;
            }

            // Acotacion suelta en cursiva: cambia de momento o de lugar
            if (! str_starts_with($t, '>') && preg_match('/^\*(.+)\*$/u', $t, $m)) {
                $salida[] = [
                    'position'      => $posicion++,
                    'character'     => null,
                    'stage_note_ru' => mb_substr(trim($m[1]), 0, 200),
                    'text_es'       => '',
                    'text_ru'       => null,
                    'is_break'      => true,
                ];
                continue;
            }

            if (! str_starts_with($t, '>')) {
                continue;
            }

            $c = trim(ltrim($t, '> '));
            if ($c === '') {
                continue;
            }

            // Cabecera de replica: «**Miguel** *(desde la cocina)*»
            if (preg_match('/^\*\*(.+?)\*\*\s*(?:\*\((.+?)\)\*)?$/u', $c, $m)) {
                $personaje = trim($m[1]);
                $acotacion = isset($m[2]) ? trim($m[2]) : null;
                continue;
            }

            $texto = $c;
            $nota  = $acotacion;
            if (preg_match('/^(.*?)\s*\*\((.+?)\)\*\s*$/u', $c, $m)) {
                $texto = trim($m[1]);
                $nota  = trim($m[2]);
            }

            $salida[] = [
                'position'      => $posicion++,
                'character'     => $personaje,
                'stage_note_ru' => $nota ? mb_substr($nota, 0, 200) : null,
                'text_es'       => $this->limpiarMd($texto),
                'text_ru'       => null,
                'is_break'      => false,
            ];
            $acotacion = null;
        }

        return array_values(array_filter($salida, fn ($x) => $x['text_es'] !== '' || $x['is_break']));
    }

    /** Cuento: prosa por parrafos. */
    protected function prosa(string $md): array
    {
        $salida   = [];
        $posicion = 0;

        // Se parte por lineas en blanco: cada bloque es un parrafo completo.
        foreach (preg_split('/\n\s*\n/u', $md) as $bloque) {
            $bloque = trim($bloque);

            if ($bloque === '' || $bloque === '---') {
                continue;
            }

            $esCita = str_starts_with($bloque, '>');

            // Se juntan las lineas del parrafo antes de tocar el Markdown:
            // asi la negrita partida entre dos lineas se limpia bien.
            $texto = implode(' ', array_map(
                fn ($l) => trim(ltrim(trim($l), '> ')),
                explode("\n", $bloque)
            ));

            $texto = $this->limpiarMd(trim($texto));

            if ($texto === '') {
                continue;
            }

            $salida[] = [
                'position'      => $posicion++,
                'character'     => null,
                // Marca interna: en los cuentos, los bloques citados son el
                // texto de una nota, una lista o un cartel. Se pintan aparte.
                'stage_note_ru' => $esCita ? 'cita' : null,
                'text_es'       => $texto,
                'text_ru'       => null,
                'is_break'      => false,
            ];
        }

        return $salida;
    }

    // --------------------------------------------------------------- frases

    protected function frases(array $secciones): array
    {
        $s = $this->seccion($secciones, 'frases');
        if (! $s) {
            return [];
        }

        $salida = [];
        foreach (explode("\n", $s['body_md']) as $l) {
            if (! preg_match('/^\s*(\d+)\.\s+(.+)$/u', trim($l), $m)) {
                continue;
            }

            [$es, $ru] = $this->partirBilingue($m[2]);
            if ($es === '') {
                continue;
            }

            $salida[] = [
                'position' => (int) $m[1],
                'text_es'  => mb_substr($es, 0, 255),
                'text_ru'  => mb_substr($ru, 0, 255),
            ];
        }

        return $salida;
    }

    protected function partirBilingue(string $t): array
    {
        foreach ([' — ', ' – '] as $sep) {
            if (str_contains($t, $sep)) {
                [$a, $b] = explode($sep, $t, 2);
                return [$this->limpiarMd($a), $this->limpiarMd($b)];
            }
        }
        return [$this->limpiarMd($t), ''];
    }


    // ----------------------------------------------------------- ejercicios

    /**
     * Los ejercicios y sus soluciones viven en secciones distintas.
     * Formato del enunciado:
     *     1. Texto ____ texto. — a) opcion · b) opcion · c) opcion
     * Formato de la solucion:
     *     **1. b) opcion** — explicacion en ruso.
     */
    protected function ejercicios(array $secciones): array
    {
        $sEj  = $this->seccion($secciones, 'ejercicios') ?? $this->seccion($secciones, 'preguntas');
        $sSol = $this->seccion($secciones, 'soluciones');

        if (! $sEj) {
            return [];
        }

        $enunciados = $this->enunciados($sEj['body_md']);
        $soluciones = $sSol ? $this->soluciones($sSol['body_md']) : [];

        $salida = [];

        foreach ($enunciados as $num => $ej) {
            $sol = $soluciones[$num] ?? null;

            if (! $sol) {
                $this->error("el ejercicio {$num} no tiene solucion");
                continue;
            }

            $correcta = $sol['letra'];
            $letras   = array_column($ej['opciones'], 'letter');

            if (! in_array($correcta, $letras, true)) {
                $this->error("el ejercicio {$num}: la solucion indica «{$correcta}» pero las opciones son "
                             . implode(', ', $letras));
                continue;
            }

            foreach ($ej['opciones'] as &$o) {
                $o['is_correct'] = ($o['letter'] === $correcta);
            }
            unset($o);

            $salida[] = [
                'position'        => $num,
                'kind'            => 'opcion_multiple',
                'prompt'          => $ej['enunciado'],
                'explanation_ru'  => $sol['explicacion'],
                'review_of_slug'  => $sol['repaso'],
                'is_calque_trap'  => false,
                'opciones'        => $ej['opciones'],
            ];
        }

        return $salida;
    }

    protected function enunciados(string $md): array
    {
        $salida = [];

        foreach (explode("\n", $md) as $l) {
            $t = trim($l);
            if (! preg_match('/^(\d+)\.\s+(.+)$/u', $t, $m)) {
                continue;
            }

            $num   = (int) $m[1];
            $resto = $m[2];

            // Separar enunciado de opciones.
            //
            // OJO: no vale buscar el ultimo guion largo. En ejercicios sobre el
            // articulo, una de las OPCIONES es un guion —«c) —» significa «sin
            // articulo»— y se confundiria con el separador.
            //
            // El separador correcto es el guion largo que va seguido de «a)».
            if (! preg_match('/^(.*?)\s+[—–]\s+(a\).+)$/u', $resto, $p)) {
                $this->aviso("el ejercicio {$num} no tiene opciones reconocibles");
                continue;
            }

            $enunciado = trim($p[1]);
            $bloque    = trim($p[2]);

            $opciones = [];
            foreach (preg_split('/\s+·\s+/u', $bloque) as $trozo) {
                if (! preg_match('/^([a-z])\)\s*(.*)$/u', trim($trozo), $o)) {
                    continue;
                }

                // «c) —» significa «sin articulo»: se guarda con un texto legible.
                $texto = $this->limpiarMd(trim($o[2]));
                if ($texto === '' || $texto === '—' || $texto === '–' || $texto === '-') {
                    $texto = '—';
                }

                $opciones[] = [
                    'letter'     => $o[1],
                    'text'       => mb_substr($texto, 0, 255),
                    'is_correct' => false,
                ];
            }

            if (count($opciones) < 2) {
                $this->aviso("el ejercicio {$num} tiene menos de dos opciones");
                continue;
            }

            $salida[$num] = [
                'enunciado' => $this->limpiarMd($enunciado),
                'opciones'  => $opciones,
            ];
        }

        return $salida;
    }

    protected function soluciones(string $md): array
    {
        $salida = [];
        $actual = null;

        foreach (explode("\n", $md) as $l) {
            $t = trim($l);

            if (preg_match('/^\*\*(\d+)\.\s*([a-z])\)?\s*(.*?)\*\*\s*(.*)$/u', $t, $m)) {
                if ($actual) {
                    $salida[$actual['num']] = $actual['datos'];
                }

                $explicacion = ltrim($m[4], "— –- \t");

                $actual = [
                    'num'   => (int) $m[1],
                    'datos' => [
                        'letra'       => $m[2],
                        'explicacion' => trim($explicacion),
                        'repaso'      => null,
                    ],
                ];
                continue;
            }

            if ($actual && $t !== '' && $t !== '---') {
                $actual['datos']['explicacion'] = trim($actual['datos']['explicacion'] . ' ' . $t);
            }
        }

        if ($actual) {
            $salida[$actual['num']] = $actual['datos'];
        }

        // La marca de repaso viaja en la explicacion: «(повторение модуля N2-04)»
        foreach ($salida as &$s) {
            if (preg_match('/\b([nN][123]-[mM]\d{2}|[oO]jo\s*#?\d+)/u', $s['explicacion'], $m)) {
                $s['repaso'] = mb_strtolower(str_replace(' ', '', $m[1]));
            }
            $s['explicacion'] = $this->limpiarMd($s['explicacion']);
        }
        unset($s);

        return $salida;
    }

    // -------------------------------------------------------------- enlaces

    protected function enlaces(array $secciones): array
    {
        $s = $this->seccion($secciones, 'enlaces');
        if (! $s) {
            return [];
        }

        $salida   = [];
        $posicion = 0;

        foreach (explode("\n", $s['body_md']) as $l) {
            $t = trim($l);
            if (! str_starts_with($t, '-')) {
                continue;
            }

            $t = ltrim($t, "- \t");

            // «**N2-13** — Perdona, ¿dónde está…?»
            if (! preg_match('/\*\*(.+?)\*\*/u', $t, $m)) {
                continue;
            }

            $destino = mb_strtolower(trim($m[1]));
            $destino = str_replace([' ', '#', '—'], ['', '', ''], $destino);
            $destino = preg_replace('/^ojo(\d+)$/u', 'ojo-$1', $destino);

            $salida[] = [
                'to_slug'  => mb_substr($destino, 0, 64),
                'label'    => mb_substr($this->limpiarMd($t), 0, 200),
                'position' => $posicion++,
            ];
        }

        return $salida;
    }

    // ------------------------------------------------------------ validacion

    protected function validar(array $cab, array $datos): void
    {
        $declarados = (int) ($cab['ejercicios'] ?? 0);
        $reales     = count($datos['ejercicios']);

        if ($declarados > 0 && $declarados !== $reales) {
            $this->error("la cabecera declara {$declarados} ejercicios pero se han leido {$reales}");
        }

        if (empty($datos['secciones'])) {
            $this->error('no se ha reconocido ninguna seccion');
        }

        foreach ($datos['ejercicios'] as $ej) {
            $correctas = array_filter($ej['opciones'], fn ($o) => $o['is_correct']);
            if (count($correctas) !== 1) {
                $this->error("el ejercicio {$ej['position']} no tiene exactamente una opcion correcta");
            }
        }
    }

    // ------------------------------------------------------------- utiles

    protected function celdas(string $linea): array
    {
        $t = trim($linea, "| \t");
        return array_map('trim', explode('|', $t));
    }

    protected function esSeparador(array $celdas): bool
    {
        return (bool) preg_match('/^:?-{2,}:?$/', str_replace(' ', '', $celdas[0]));
    }

    protected function esCabecera(array $celdas): bool
    {
        return in_array(mb_strtolower($celdas[0]), ['español', 'espanol', 'verbo', 'palabra'], true)
            && in_array(mb_strtolower($celdas[1] ?? ''), ['русский', 'ruso'], true);
    }

    public function limpiarMd(string $t): string
    {
        $t = preg_replace('/\*\*(.+?)\*\*/u', '$1', $t);
        $t = preg_replace('/\*(.+?)\*/u', '$1', $t);
        $t = preg_replace('/`(.+?)`/u', '$1', $t);
        $t = preg_replace('/\[(.+?)\]\(.+?\)/u', '$1', $t);
        return trim($t);
    }

    protected function error(string $m): void  { $this->incidencias[] = ['nivel' => 'error', 'mensaje' => $m]; }
    protected function aviso(string $m): void  { $this->incidencias[] = ['nivel' => 'aviso', 'mensaje' => $m]; }
}
