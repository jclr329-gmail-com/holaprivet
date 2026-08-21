<?php

namespace App\Http\Controllers;

use App\Models\Piece;
use Illuminate\Support\Facades\Cache;

/**
 * El modo sin conexion: la lista completa de lo que hay que descargar
 * («estudiar en el metro»). El navegador la recorre y lo guarda todo en la
 * cache del service worker; este controlador solo hace el inventario.
 */
class OfflineController extends Controller
{
    public function manifiesto()
    {
        $raiz = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/') ?: public_path();

        $piezas = Piece::orderBy('level')->orderBy('position')
            ->get(['slug', 'type', 'content_html']);

        // Paginas: las fijas y todas las piezas.
        $paginas = ['/', '/curso', '/fichas', '/recursos', '/nosotros', '/muro'];
        foreach ($piezas as $p) {
            $paginas[] = '/p/' . $p->slug;
        }

        // Audio: cada hash distinto que aparece en el contenido, mas las
        // narraciones de los cuentos que existan.
        $hashes = [];
        foreach ($piezas as $p) {
            if (preg_match_all('/data-audio="([0-9a-f]{40})"/', $p->content_html ?? '', $m)) {
                foreach ($m[1] as $h) {
                    $hashes[$h] = true;
                }
            }
        }
        $medios = [];
        foreach (array_keys($hashes) as $h) {
            $medios[] = '/audio/' . substr($h, 0, 2) . '/' . $h . '.mp3';
        }
        foreach ($piezas as $p) {
            if ($p->type === 'cuento' && is_file($raiz . '/audio/cuentos/' . $p->slug . '.mp3')) {
                $medios[] = '/audio/cuentos/' . $p->slug . '.mp3';
            }
            if (is_file($raiz . '/img/piezas/' . $p->slug . '.webp')) {
                $medios[] = '/img/piezas/' . $p->slug . '.webp';
            }
        }

        // El peso total, medido de verdad y recordado un dia: recorrer miles
        // de archivos en cada peticion seria un despilfarro.
        $bytes = Cache::remember('offline.bytes', 86400, function () use ($raiz) {
            $total = 0;
            foreach (['/audio', '/img/piezas'] as $carpeta) {
                if (! is_dir($raiz . $carpeta)) {
                    continue;
                }
                $it = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($raiz . $carpeta,
                        \FilesystemIterator::SKIP_DOTS)
                );
                foreach ($it as $archivo) {
                    $total += $archivo->getSize();
                }
            }

            return $total;
        });

        return response()->json([
            'paginas' => $paginas,
            'medios'  => $medios,
            'bytes'   => $bytes,
        ]);
    }
}
