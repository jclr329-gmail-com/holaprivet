<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $bd = ['ok' => false, 'mensaje' => '', 'version' => '', 'cotejamiento' => ''];

    try {
        $bd['version']      = DB::selectOne('SELECT VERSION() AS v')->v;
        $fila               = DB::selectOne('SELECT @@collation_database AS c');
        $bd['cotejamiento'] = $fila->c;
        $bd['tablas']       = count(DB::select('SHOW TABLES'));
        $bd['ok']           = true;
    } catch (\Throwable $e) {
        $bd['mensaje'] = $e->getMessage();
    }

    return view('bienvenida', ['bd' => $bd]);
});
