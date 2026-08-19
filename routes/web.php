<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $bd = ['ok' => false, 'mensaje' => ''];

    try {
        $bd['version']      = DB::selectOne('SELECT VERSION() AS v')->v;
        $bd['cotejamiento'] = DB::selectOne('SELECT @@collation_database AS c')->c;

        // Recuento de filas de las tablas que nos interesan
        $tablas = [];
        foreach (['pieces', 'exercises', 'exercise_options', 'vocabulary',
                  'dialogue_lines', 'phrases', 'tags', 'users'] as $t) {
            $tablas[$t] = DB::table($t)->count();
        }
        $bd['tablas']  = $tablas;
        $bd['totales'] = count(DB::select('SHOW TABLES'));
        $bd['ok']      = true;
    } catch (\Throwable $e) {
        $bd['mensaje'] = $e->getMessage();
    }

    return view('bienvenida', ['bd' => $bd]);
});
