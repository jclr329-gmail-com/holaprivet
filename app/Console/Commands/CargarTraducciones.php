<?php

namespace App\Console\Commands;

use App\Models\Exercise;
use App\Models\Piece;
use Illuminate\Console\Command;

/**
 * Carga las traducciones rusas de los enunciados desde un TSV que vive
 * junto al contenido (private/contenido/traducciones-ejercicios.tsv):
 *
 *     slug<TAB>numero<TAB>traduccion rusa
 *
 * Idempotente: se puede relanzar siempre; solo pisa lo que trae el archivo.
 * Los ejercicios sin traduccion siguen sin pulsador, y en paz.
 */
class CargarTraducciones extends Command
{
    protected $signature = 'ejercicios:traducciones {--archivo=}';
    protected $description = 'Carga prompt_ru de los ejercicios desde el TSV del contenido';

    public function handle(): int
    {
        $archivo = $this->option('archivo')
            ?: rtrim(config('contenido.ruta'), '/') . '/traducciones-ejercicios.tsv';

        if (! is_file($archivo)) {
            $this->line("No hay traducciones en {$archivo} - se omite.");

            return self::SUCCESS;
        }

        $piezas = Piece::pluck('id', 'slug');
        $puestas = 0;
        $huerfanas = [];

        foreach (file($archivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $linea) {
            $partes = explode("\t", $linea);
            if (count($partes) < 3 || str_starts_with($linea, '#')) {
                continue;
            }
            [$slug, $n, $ru] = [trim($partes[0]), (int) $partes[1], trim($partes[2])];

            $piezaId = $piezas[$slug] ?? null;
            if (! $piezaId) {
                $huerfanas[] = $slug;
                continue;
            }

            $puestas += Exercise::where('piece_id', $piezaId)
                ->where('position', $n)
                ->update(['prompt_ru' => $ru]);
        }

        $this->info("Traducciones cargadas: {$puestas}");
        if ($huerfanas) {
            $this->warn('Slugs sin pieza: ' . implode(', ', array_unique($huerfanas)));
        }

        return self::SUCCESS;
    }
}
