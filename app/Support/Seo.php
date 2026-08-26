<?php

namespace App\Support;

use App\Models\Piece;

/**
 * Lo que los buscadores y los mensajeros leen de cada pagina: titulo,
 * descripcion e imagen. Nada de esto lo ve quien estudia; lo ve Google,
 * Yandex y la tarjeta que aparece al pegar un enlace en Telegram.
 *
 * Las descripciones de las piezas no se escriben a mano: salen del primer
 * parrafo util de la propia pieza (en las fichas Ojo, «В чём сложность»),
 * que es justo lo que alguien teclearia en un buscador.
 */
class Seo
{
    /** Longitud maxima de una descripcion: lo que Google muestra sin cortar. */
    public const MAXIMO = 155;

    /** Descripcion por defecto, para las paginas que no traen la suya. */
    public const DESCRIPCION = 'Бесплатный курс испанского для русскоговорящих: объяснения по-русски, аудио, упражнения и примеры из настоящей жизни в Испании.';

    /** Imagen por defecto de las tarjetas de enlace (1200×630). */
    public const IMAGEN = '/img/og-holaprivet.png';

    /** Secciones donde buscar el parrafo que resume la pieza, por preferencia. */
    protected const SECCIONES = ['problema', 'proposito', 'objetivos', 'idea', 'teoria', 'texto'];

    /** Titulo bilingue de una pieza: lo español y lo ruso, que es lo que se busca. */
    public static function tituloPieza(Piece $p): string
    {
        return $p->title_ru
            ? $p->title_es . ' — ' . $p->title_ru
            : $p->title_es;
    }

    /** Descripcion de una pieza a partir de su propio texto. */
    public static function descripcionPieza(Piece $p): string
    {
        $secciones = $p->relationLoaded('sections') ? $p->sections : $p->sections()->get();

        foreach (self::SECCIONES as $tipo) {
            foreach ($secciones as $s) {
                if ($s->kind !== $tipo || trim((string) $s->body_md) === '') {
                    continue;
                }
                $texto = self::texto($s->body_md);
                if (mb_strlen($texto) >= 40) {
                    return self::recortar($texto);
                }
            }
        }

        return self::recortar(self::descripcionTipo($p));
    }

    /** Cuando la pieza no tiene parrafo aprovechable: una frase segun su tipo. */
    protected static function descripcionTipo(Piece $p): string
    {
        $titulo = $p->title_ru ?: $p->title_es;

        return match ($p->type) {
            'cuento'         => "Рассказ «{$titulo}»: чтение по-испански с переводом и аудио. Уровень {$p->level} бесплатного курса для русскоговорящих.",
            'ficha_ojo'      => "{$titulo}: слова, которые русский язык объединяет, а испанский разделяет. Объяснение по-русски, примеры и упражнения.",
            'ficha_practica' => "{$titulo}: карточка для печати. Испанская грамматика и лексика для русскоговорящих, с примерами.",
            default          => "{$titulo}: модуль {$p->position} уровня {$p->level}. Объяснения по-русски, сцена из жизни в Испании, аудио и упражнения.",
        };
    }

    /**
     * Texto plano a partir de Markdown: fuera encabezados, tablas, marcas de
     * lista, negritas, enlaces y etiquetas. Queda una sola linea.
     */
    public static function texto(string $md): string
    {
        $lineas = [];

        foreach (explode("\n", $md) as $l) {
            $l = trim($l);

            // Encabezados, tablas, separadores y bloques de codigo no resumen nada.
            if ($l === '' || str_starts_with($l, '#') || str_starts_with($l, '|')
                || preg_match('/^(-{3,}|\*{3,}|`{3,})$/', $l)) {
                continue;
            }

            $l = preg_replace('/^>\s?/', '', $l);                    // cita

            // Los puntos de una lista, seguidos, se separan con un punto
            // medio para que no se lean como una sola frase sin sentido.
            if (preg_match('/^(?:[-*+]|\d+[.)])\s+/u', $l)) {
                $l = preg_replace('/^(?:[-*+]|\d+[.)])\s+/u', '', $l);
                if (! preg_match('/[.!?:;…]$/u', $l)) {
                    $l .= ' ·';
                }
            }

            $lineas[] = $l;
        }

        $t = implode(' ', $lineas);

        $t = preg_replace('/!\[[^\]]*\]\([^)]*\)/u', '', $t);          // imagenes
        $t = preg_replace('/\[([^\]]+)\]\([^)]*\)/u', '$1', $t);      // enlaces
        $t = preg_replace('/(\*\*|__)(.+?)\1/u', '$2', $t);           // negrita
        $t = preg_replace('/(?<![\p{L}\p{N}])[*_](.+?)[*_](?![\p{L}\p{N}])/u', '$1', $t); // cursiva
        $t = preg_replace('/`([^`]*)`/u', '$1', $t);                  // codigo
        $t = strip_tags($t);
        $t = html_entity_decode($t, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $t = preg_replace('/\s+/u', ' ', $t);

        return trim(preg_replace('/[\s·]+$/u', '', $t));
    }

    /** Corta por la ultima palabra entera que cabe y pone puntos suspensivos. */
    public static function recortar(string $t, int $maximo = self::MAXIMO): string
    {
        $t = trim($t);
        if (mb_strlen($t) <= $maximo) {
            return $t;
        }

        $corte = mb_substr($t, 0, $maximo - 1);
        $espacio = mb_strrpos($corte, ' ');
        if ($espacio !== false && $espacio > $maximo * 0.6) {
            $corte = mb_substr($corte, 0, $espacio);
        }

        // Nada de rtrim con caracteres de varios bytes: rompe la ultima letra.
        return preg_replace('/[\s,;:—\-.·]+$/u', '', $corte) . '…';
    }
}
