<?php

namespace App\Support;

/**
 * Lector minimo de cabeceras YAML.
 *
 * No se usa una libreria completa porque nuestras cabeceras son planas:
 * claves con valores escalares y listas en linea. Menos dependencias y
 * comportamiento predecible.
 */
class FrontMatter
{
    /**
     * Separa la cabecera del cuerpo.
     *
     * @return array{0: array<string,mixed>, 1: string}
     */
    public static function separar(string $contenido): array
    {
        $contenido = str_replace(["\r\n", "\r"], "\n", $contenido);
        $contenido = preg_replace('/^\xEF\xBB\xBF/', '', $contenido);

        if (! preg_match('/^---\n(.*?)\n---\n(.*)$/s', $contenido, $m)) {
            return [[], $contenido];
        }

        return [self::analizar($m[1]), ltrim($m[2], "\n")];
    }

    /** @return array<string,mixed> */
    public static function analizar(string $yaml): array
    {
        $datos = [];

        foreach (explode("\n", $yaml) as $linea) {
            $linea = rtrim($linea);

            if ($linea === '' || str_starts_with(ltrim($linea), '#')) {
                continue;
            }
            if (! str_contains($linea, ':')) {
                continue;
            }

            [$clave, $valor] = explode(':', $linea, 2);
            $clave = trim($clave);
            $valor = trim($valor);

            $datos[$clave] = self::valor($valor);
        }

        return $datos;
    }

    protected static function valor(string $v): mixed
    {
        if ($v === '') {
            return null;
        }

        // Lista en linea: [a, b, c]
        if (str_starts_with($v, '[') && str_ends_with($v, ']')) {
            $interior = trim(substr($v, 1, -1));
            if ($interior === '') {
                return [];
            }

            return array_values(array_filter(array_map(
                fn ($x) => self::limpiar(trim($x)),
                explode(',', $interior)
            ), fn ($x) => $x !== ''));
        }

        $v = self::limpiar($v);

        if (in_array(strtolower($v), ['si', 'sí', 'yes', 'true'], true))  return true;
        if (in_array(strtolower($v), ['no', 'false'], true))              return false;
        if (strtolower($v) === 'null')                                    return null;
        if (preg_match('/^-?\d+$/', $v))                                  return (int) $v;

        return $v;
    }

    protected static function limpiar(string $v): string
    {
        if (strlen($v) >= 2) {
            $ini = $v[0];
            $fin = $v[strlen($v) - 1];
            if (($ini === '"' && $fin === '"') || ($ini === "'" && $fin === "'")) {
                return substr($v, 1, -1);
            }
        }

        return $v;
    }
}
