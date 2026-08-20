<?php

namespace App\Support;

/**
 * Referencias de audio por contenido.
 *
 * Cada fragmento espanol audible se identifica por el sha1 de su texto
 * normalizado: el archivo vive en audio/<2 primeras>/<hash>.mp3. El
 * generador local (herramientas/generar-audio.py) usa EXACTAMENTE la misma
 * normalizacion; si se cambia aqui, hay que cambiarla alli.
 *
 * Normalizar = quitar marcas de Markdown (* y `), quitar etiquetas,
 * comprimir espacios y recortar. Asi «**hay**» en teoria y «hay» en una
 * frase apuntan al mismo archivo.
 */
class Refs
{
    public static function audio(string $texto): string
    {
        return sha1(self::normalizar($texto));
    }

    public static function normalizar(string $t): string
    {
        $t = strip_tags($t);
        $t = str_replace(['*', '`'], '', $t);
        $t = preg_replace('/\s+/u', ' ', $t);

        return trim($t);
    }
}
