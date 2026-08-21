<?php

namespace App\Console\Commands;

use App\Models\WallWord;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Puebla el muro con palabras del propio curso: el muro como material
 * didactico, no como lista de nombres. Se ejecuta una vez (o tras vaciar).
 *
 *     php artisan muro:sembrar          (140 palabras, 1 de cada 12 especial)
 *     php artisan muro:sembrar --total=200
 */
class SembrarMuro extends Command
{
    protected $signature = 'muro:sembrar {--total=140}';
    protected $description = 'Puebla wall_words desde el vocabulario del curso';

    public function handle(): int
    {
        if (WallWord::count() > 0) {
            $this->warn('El muro ya tiene palabras; no se toca nada.');

            return self::SUCCESS;
        }

        // Palabras simples del vocabulario: una sola palabra, sin notas ni
        // barras, entre 3 y 14 letras. El articulo se conserva («la calle»
        // no: solo la palabra desnuda para que el muro respire).
        $candidatas = DB::table('vocabulary')
            ->whereIn('block', ['nuevas', 'conocidas'])
            ->get(['id', 'term_es', 'term_ru'])
            ->map(function ($v) {
                $es = trim(preg_replace('/^(el|la|los|las|un|una)\s+/u', '', mb_strtolower($v->term_es)));
                $ru = trim(preg_replace('/\s*\(.*\)\s*/u', '', $v->term_ru));

                return (object) ['id' => $v->id, 'es' => $es, 'ru' => mb_strtolower($ru)];
            })
            ->filter(fn ($v) => preg_match('/^[a-záéíóúñü]{3,14}$/u', $v->es) && $v->ru !== '')
            ->unique('es')
            ->shuffle()
            ->take((int) $this->option('total'))
            ->values();

        $fila = 0;
        foreach ($candidatas as $i => $v) {
            $especial = ($i % 12) === 5;
            WallWord::create([
                'word'           => $v->es,
                'translation_ru' => mb_substr($v->ru, 0, 80),
                'kind'           => $especial ? 'especial' : 'normal',
                'price_cents'    => config('muro.precios')[$especial ? 'especial' : 'normal'],
                'grid_x'         => $i % 10,
                'grid_y'         => intdiv($i, 10),
                'grid_w'         => $especial ? 2 : 1,
                'grid_h'         => 1,
                'status'         => 'libre',
                'vocabulary_id'  => $v->id,
            ]);
            $fila++;
        }

        $this->info("Sembradas {$fila} palabras (" . intdiv($fila, 12) . ' especiales).');

        return self::SUCCESS;
    }
}
