<?php

namespace App\Support;

/**
 * Convertidor de Markdown a HTML, hecho a medida del curso.
 *
 * No se usa una libreria general por un motivo concreto: aqui el HTML no es
 * neutro. La primera columna de cada tabla y el texto en negrita son espanol,
 * y tienen que salir envueltos en <span class="es"> para que se puedan
 * escuchar. Una libreria generica no sabe eso.
 *
 * Solo cubre lo que usan los 95 archivos: encabezados, parrafos, listas,
 * tablas, citas, negrita, cursiva y separadores.
 */
class Markdown
{
    public function __construct(protected bool $audio = true) {}

    public function aHtml(string $md): string
    {
        $lineas = explode("\n", str_replace("\r", '', $md));
        $salida = [];
        $i      = 0;
        $n      = count($lineas);

        while ($i < $n) {
            $l = $lineas[$i];
            $t = trim($l);

            if ($t === '') { $i++; continue; }

            if ($t === '---') { $salida[] = '<hr>'; $i++; continue; }

            // Encabezado de tercer nivel
            if (preg_match('/^###\s+(.+)$/u', $t, $m)) {
                $salida[] = '<h3>' . $this->enLinea($m[1]) . '</h3>';
                $i++; continue;
            }

            // Tabla
            if (str_starts_with($t, '|')) {
                [$html, $i] = $this->tabla($lineas, $i);
                $salida[] = $html;
                continue;
            }

            // Cita
            if (str_starts_with($t, '>')) {
                [$html, $i] = $this->cita($lineas, $i);
                $salida[] = $html;
                continue;
            }

            // Lista numerada
            if (preg_match('/^\d+\.\s/u', $t)) {
                [$html, $i] = $this->lista($lineas, $i, true);
                $salida[] = $html;
                continue;
            }

            // Lista de puntos
            if (preg_match('/^[-*]\s/u', $t)) {
                [$html, $i] = $this->lista($lineas, $i, false);
                $salida[] = $html;
                continue;
            }

            // Parrafo: se juntan las lineas seguidas
            $bloque = [];
            while ($i < $n && trim($lineas[$i]) !== '' && ! $this->esEspecial(trim($lineas[$i]))) {
                $bloque[] = trim($lineas[$i]);
                $i++;
            }
            if ($bloque) {
                $salida[] = '<p>' . $this->enLinea(implode(' ', $bloque)) . '</p>';
            }
        }

        return implode("\n", $salida);
    }

    protected function esEspecial(string $t): bool
    {
        return $t === '---'
            || str_starts_with($t, '|')
            || str_starts_with($t, '>')
            || str_starts_with($t, '#')
            || preg_match('/^\d+\.\s/u', $t)
            || preg_match('/^[-*]\s/u', $t);
    }

    // ------------------------------------------------------------ elementos

    protected function tabla(array $lineas, int $i): array
    {
        $filas = [];
        $n     = count($lineas);

        while ($i < $n && str_starts_with(trim($lineas[$i]), '|')) {
            $filas[] = $this->celdas($lineas[$i]);
            $i++;
        }

        if (! $filas) {
            return ['', $i + 1];
        }

        $html      = '<div class="tabla-scroll"><table>';
        $primera   = true;
        $hayCabecera = isset($filas[1]) && $this->esSeparador($filas[1]);

        foreach ($filas as $k => $celdas) {
            if ($k === 1 && $hayCabecera) {
                continue;
            }

            $etiqueta = ($primera && $hayCabecera) ? 'th' : 'td';
            $html .= '<tr>';

            foreach ($celdas as $c => $celda) {
                // Primera columna: normalmente es el espanol y se marca
                // audible entera. Pero hay tablas cuya primera columna es rusa
                // («паспорт») o mixta («**es** aburrido — он скучный»): ahi se
                // procesa como texto normal y solo suena la negrita latina.
                $contenido = ($etiqueta === 'td' && $c === 0
                              && ! preg_match('/\p{Cyrillic}/u', $celda))
                    ? $this->marcarEspanol($celda)
                    : $this->enLinea($celda);
                $html .= "<{$etiqueta}>{$contenido}</{$etiqueta}>";
            }

            $html .= '</tr>';
            $primera = false;
        }

        return [$html . '</table></div>', $i];
    }

    protected function cita(array $lineas, int $i): array
    {
        $bloque = [];
        $n      = count($lineas);

        while ($i < $n && str_starts_with(trim($lineas[$i]), '>')) {
            $bloque[] = trim(ltrim(trim($lineas[$i]), '> '));
            $i++;
        }

        $texto = $this->enLinea(implode(' ', array_filter($bloque)));

        return ['<blockquote class="ojo"><p>' . $texto . '</p></blockquote>', $i];
    }

    protected function lista(array $lineas, int $i, bool $numerada): array
    {
        $items = [];
        $n     = count($lineas);
        $patron = $numerada ? '/^\d+\.\s+(.+)$/u' : '/^[-*]\s+(.+)$/u';

        while ($i < $n) {
            $t = trim($lineas[$i]);

            if ($t === '') {
                // Una linea en blanco solo continua la lista si lo que viene
                // despues es otro punto. Si viene un parrafo, la lista se
                // cierra aqui: sin esta mirada hacia delante, el parrafo
                // siguiente acababa DENTRO del ultimo punto.
                $j = $i + 1;
                while ($j < $n && trim($lineas[$j]) === '') {
                    $j++;
                }
                if ($j < $n && preg_match($patron, trim($lineas[$j]))) {
                    $i = $j;
                    continue;
                }
                break;
            }

            if (preg_match($patron, $t, $m)) {
                $items[] = $m[1];
                $i++;
                continue;
            }

            // Continuacion del punto anterior: se acumula EN CRUDO y el
            // Markdown se procesa al final, una sola vez por punto. Si cada
            // linea se procesara por separado, la negrita que cruza el salto
            // saldria con los asteriscos sin cerrar.
            if ($items && ! $this->esEspecial($t)) {
                $items[count($items) - 1] .= ' ' . $t;
                $i++;
                continue;
            }

            break;
        }

        $etiqueta = $numerada ? 'ol' : 'ul';
        $clase    = $numerada ? '' : ' class="limpia"';
        $html     = "<{$etiqueta}{$clase}>";

        foreach ($items as $it) {
            $html .= '<li>' . $this->enLinea($it) . '</li>';
        }

        return [$html . "</{$etiqueta}>", $i];
    }

    // ------------------------------------------------------------- en linea

    public function enLinea(string $t): string
    {
        $t = e($t);

        // Negrita: solo el ESPANOL se marca como audible. La negrita rusa
        // —«**не нужно учить**», «**три разных глагола**»— es enfasis normal
        // y va en <strong>: si llevara la clase .es, saldria con el punteado
        // de audio y seria pulsable sin tener nada que sonar.
        $t = preg_replace_callback('/\*\*(.+?)\*\*/u', function ($m) {
            $esEspanol = ! preg_match('/\p{Cyrillic}/u', $m[1])
                         && preg_match('/\p{Latin}/u', $m[1]);
            // El hash se calcula sobre el texto sin escapar, igual que hace
            // el generador con el .md: asi el archivo siempre casa.
            return ($this->audio && $esEspanol)
                ? '<span class="es" lang="es" translate="no" data-audio="'
                    . Refs::audio(html_entity_decode($m[1], ENT_QUOTES)) . '">'
                    . $m[1] . '</span>'
                : '<strong>' . $m[1] . '</strong>';
        }, $t);

        $t = preg_replace('/\*(.+?)\*/u', '<em>$1</em>', $t);
        $t = preg_replace('/`(.+?)`/u', '<code>$1</code>', $t);
        $t = preg_replace('/\[(.+?)\]\((.+?)\)/u', '<a href="$2">$1</a>', $t);

        // Marcas de correccion propias del curso.
        //
        // La forma marcada es la ESPANOLA; la marca termina donde empieza el
        // ruso, un « — » separador o una nota *(N1-02)*. Sin ese freno, el
        // verde/rojo se comeria tambien la explicacion entera del parrafo.
        $freno = '(?=\s+[—–]\s|\s[\p{Cyrillic}«]|\s*<em>\(|\s*→|$)';
        $t = preg_replace('/Неправильно:\s*((?:(?!Правильно:)[^→])+?)' . $freno . '/u',
                          'Неправильно: <span class="mal">$1</span>', $t);
        $t = preg_replace('/Правильно:\s*((?:(?!Неправильно:).)+?)' . $freno . '/u',
                          'Правильно: <span class="bien">$1</span>', $t);

        return $t;
    }

    /** Envuelve todo el contenido como espanol audible. */
    public function marcarEspanol(string $t): string
    {
        $limpio = trim(preg_replace('/\*\*(.+?)\*\*/u', '$1', $t));

        if ($limpio === '' || $limpio === '—') {
            return e($limpio);
        }

        // Si lleva una acotacion en cursiva, se deja fuera del audio
        if (preg_match('/^(.*?)\s*\*\((.+?)\)\*\s*$/u', $limpio, $m)) {
            return '<span class="es" lang="es" translate="no" data-audio="' . Refs::audio($m[1]) . '">' . e(trim($m[1])) . '</span> <em>(' . e($m[2]) . ')</em>';
        }

        return '<span class="es" lang="es" translate="no" data-audio="' . Refs::audio($limpio) . '">' . e($limpio) . '</span>';
    }

    // ----------------------------------------------------------------- util

    protected function celdas(string $linea): array
    {
        return array_map('trim', explode('|', trim($linea, "| \t")));
    }

    protected function esSeparador(array $celdas): bool
    {
        return (bool) preg_match('/^:?-{2,}:?$/', str_replace(' ', '', $celdas[0] ?? ''));
    }
}
