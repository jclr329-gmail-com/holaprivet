<?php

namespace App\Http\Controllers;

use App\Models\Piece;
use Illuminate\Support\Facades\Cache;

/**
 * /sitemap.xml: la lista de todo lo que los buscadores deben conocer.
 *
 * Se genera desde la base de datos, no desde un archivo: asi refleja cada
 * reimportacion de contenido sin que nadie tenga que acordarse de nada.
 * Quedan fuera las paginas de cuenta, el muro de un apadrinamiento en curso
 * y la gestion, que ademas llevan noindex.
 */
class SitemapController extends Controller
{
    public function xml()
    {
        // Una hora en cache: 95 piezas no pesan, pero los rastreadores insisten.
        $xml = Cache::remember('sitemap.xml', 3600, fn () => $this->generar());

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=utf-8']);
    }

    protected function generar(): string
    {
        $casa = rtrim(config('app.url'), '/');

        $piezas = Piece::orderBy('type')->orderBy('level')->orderBy('position')
            ->get(['slug', 'type', 'updated_at']);

        $ultima = optional($piezas->max('updated_at'))->toDateString() ?? now()->toDateString();

        // [ruta, prioridad, fecha]
        $urls = [
            ['/',            '1.0', $ultima],
            ['/bienvenida',  '0.8', $ultima],
            ['/fichas',      '0.9', $ultima],
            ['/recursos',    '0.7', $ultima],
            ['/nivel/1',     '0.7', $ultima],
            ['/nivel/2',     '0.7', $ultima],
            ['/nivel/3',     '0.7', $ultima],
            ['/nosotros',    '0.5', $ultima],
            ['/muro',        '0.4', $ultima],
        ];

        $prioridad = [
            'ficha_ojo'      => '0.9',   // lo que nadie mas publica
            'modulo'         => '0.8',
            'ficha_practica' => '0.7',
            'cuento'         => '0.6',
        ];

        foreach ($piezas as $p) {
            $urls[] = [
                '/p/' . $p->slug,
                $prioridad[$p->type] ?? '0.6',
                optional($p->updated_at)->toDateString() ?? $ultima,
            ];
        }

        $lineas = ['<?xml version="1.0" encoding="UTF-8"?>',
                   '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'];

        foreach ($urls as [$ruta, $prio, $fecha]) {
            $lineas[] = '  <url>';
            $lineas[] = '    <loc>' . htmlspecialchars($casa . $ruta, ENT_XML1) . '</loc>';
            $lineas[] = '    <lastmod>' . $fecha . '</lastmod>';
            $lineas[] = '    <priority>' . $prio . '</priority>';
            $lineas[] = '  </url>';
        }

        $lineas[] = '</urlset>';

        return implode("\n", $lineas) . "\n";
    }
}
