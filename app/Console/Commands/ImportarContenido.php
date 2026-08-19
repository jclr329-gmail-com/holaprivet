<?php

namespace App\Console\Commands;

use App\Models\ImportLog;
use App\Services\Importador;
use Illuminate\Console\Command;

class ImportarContenido extends Command
{
    protected $signature = 'contenido:importar
                            {--ruta= : carpeta con los .md (por defecto, la de configuracion)}
                            {--simulacro : analiza y valida sin guardar nada}';

    protected $description = 'Importa los archivos Markdown del curso a la base de datos';

    public function handle(): int
    {
        $ruta = $this->option('ruta')
            ?: config('contenido.ruta', base_path('../private/contenido'));

        if (! is_dir($ruta)) {
            $this->error("No existe la carpeta: {$ruta}");
            return self::FAILURE;
        }

        $simulacro = (bool) $this->option('simulacro');

        $this->info($simulacro ? 'SIMULACRO — no se guardara nada' : 'Importando contenido');
        $this->line("Carpeta: {$ruta}");
        $this->newLine();

        $inicio = microtime(true);

        try {
            $r = (new Importador($ruta, $simulacro))->ejecutar();
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        $segundos = round(microtime(true) - $inicio, 1);

        $this->table(
            ['Concepto', 'Cantidad'],
            [
                ['Archivos leidos', $r['archivos']],
                ['Piezas importadas', $r['piezas']],
                ['Ejercicios', $r['ejercicios']],
                ['Avisos', $r['avisos']],
                ['Errores', $r['errores']],
                ['Segundos', $segundos],
            ]
        );

        if ($r['errores'] > 0 || $r['avisos'] > 0) {
            $this->newLine();
            $this->warn('Incidencias:');

            ImportLog::where('version_id', $r['version'])
                ->whereIn('level', ['error', 'aviso'])
                ->orderByRaw("FIELD(level,'error','aviso')")
                ->limit(40)
                ->get()
                ->each(function ($l) {
                    $marca = $l->level === 'error' ? '  ERROR' : '  aviso';
                    $this->line("{$marca}  " . ($l->file ? "[{$l->file}] " : '') . $l->message);
                });
        }

        $this->newLine();

        if ($r['errores'] > 0) {
            $this->error("Terminado con {$r['errores']} errores. Version {$r['version']}.");
            return self::FAILURE;
        }

        $this->info("Terminado correctamente. Version {$r['version']}.");

        return self::SUCCESS;
    }
}
