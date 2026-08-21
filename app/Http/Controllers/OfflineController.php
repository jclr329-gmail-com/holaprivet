<?php

namespace App\Http\Controllers;

use App\Models\Piece;
use Illuminate\Support\Facades\Cache;

/**
 * El modo sin conexion: la lista completa de lo que hay que descargar
 * («estudiar en el metro»). El inventario de medios se hace sobre el DISCO
 * —las carpetas audio/ e img/piezas/ de la raiz servida— que es la unica
 * fuente que no puede mentir; las paginas salen de la base. El resultado
 * se recuerda un dia: recorrer miles de archivos por peticion seria un
 * despilfarro.
 */
class OfflineController extends Controller
{
    public function manifiesto()
    {
        $paginas = ['/', '/curso', '/fichas', '/recursos', '/nosotros', '/muro'];
        foreach (Piece::orderBy('level')->orderBy('position')->pluck('slug') as $slug) {
            $paginas[] = '/p/' . $slug;
        }

        [$medios, $bytes] = Cache::remember('offline.medios.v2', 86400, function () {
            $raiz = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/') ?: public_path();
            $urls = [];
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
                    if (! $archivo->isFile()) {
                        continue;
                    }
                    $urls[] = substr($archivo->getPathname(), strlen($raiz));
                    $total += $archivo->getSize();
                }
            }
            sort($urls);

            return [$urls, $total];
        });

        return response()->json([
            'paginas' => $paginas,
            'medios'  => $medios,
            'bytes'   => $bytes,
        ]);
    }
}
