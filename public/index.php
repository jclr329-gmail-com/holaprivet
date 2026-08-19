<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// ---------------------------------------------------------------------------
// Ruta base de la aplicacion.
//
// Este archivo puede ejecutarse desde dos sitios:
//   1) desde  app/public/index.php      (instalacion normal)
//   2) desde  la raiz del subdominio    (copia hecha por el despliegue)
//
// Se detecta solo.
// ---------------------------------------------------------------------------
$base = is_file(__DIR__.'/../vendor/autoload.php')
    ? __DIR__.'/..'          // caso 1
    : __DIR__.'/app';        // caso 2

if (file_exists($mantenimiento = $base.'/storage/framework/maintenance.php')) {
    require $mantenimiento;
}

require $base.'/vendor/autoload.php';

/** @var Application $app */
$app = require_once $base.'/bootstrap/app.php';

$app->handleRequest(Request::capture());
