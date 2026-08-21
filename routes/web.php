<?php

use App\Http\Controllers\AccesoController;
use App\Http\Controllers\CursoController;
use App\Http\Controllers\GestionController;
use App\Http\Controllers\MuroController;
use App\Http\Controllers\StripeWebhookController;
use App\Http\Controllers\ProgresoController;
use Illuminate\Support\Facades\Route;

// ------------------------------------------------------------------ curso
// El contenido es libre: leer no exige cuenta. La cuenta guarda el progreso.

Route::get('/',          [CursoController::class, 'inicio'])->name('portada');
Route::get('/curso',     [CursoController::class, 'curso'])->name('curso');
Route::get('/nivel/{n}', [CursoController::class, 'nivel'])->whereNumber('n')->name('nivel');
Route::get('/fichas',    [CursoController::class, 'fichas'])->name('fichas');
Route::get('/recursos',  [CursoController::class, 'recursos'])->name('recursos');
Route::get('/nosotros',  [CursoController::class, 'nosotros'])->name('nosotros');

// ------------------------------------------------------------------ muro
Route::get('/muro', [MuroController::class, 'muro'])->name('muro');
Route::middleware('auth')->group(function () {
    Route::get('/muro/palabra/{palabra}',  [MuroController::class, 'formulario'])->name('muro.formulario');
    Route::post('/muro/palabra/{palabra}', [MuroController::class, 'apadrinar'])->name('muro.apadrinar');
    Route::get('/muro/gracias',            [MuroController::class, 'gracias'])->name('muro.gracias');
});
Route::post('/stripe/webhook', [StripeWebhookController::class, 'recibir'])->name('stripe.webhook');
Route::get('/p/{slug}',  [CursoController::class, 'pieza'])->name('pieza');

// ----------------------------------------------------------------- cuenta

Route::middleware('guest')->group(function () {
    Route::get('/registro',  [AccesoController::class, 'formularioRegistro'])->name('registro');
    Route::post('/registro', [AccesoController::class, 'registrar']);
    Route::get('/entrar',    [AccesoController::class, 'formularioEntrar'])->name('login');
    Route::post('/entrar',   [AccesoController::class, 'entrar']);

    Route::get('/entrar/google',        [AccesoController::class, 'google'])->name('google');
    Route::get('/entrar/google/vuelta', [AccesoController::class, 'googleVuelta']);

    Route::get('/contrasena',                [AccesoController::class, 'formularioOlvido'])->name('password.request');
    Route::post('/contrasena',               [AccesoController::class, 'enviarOlvido'])->name('password.email');
    Route::get('/contrasena/nueva/{token}',  [AccesoController::class, 'formularioRestablecer'])->name('password.reset');
    Route::post('/contrasena/nueva',         [AccesoController::class, 'restablecer'])->name('password.update');
});

Route::middleware('auth')->group(function () {
    Route::post('/salir', [AccesoController::class, 'salir'])->name('salir');

    Route::get('/verificar', [AccesoController::class, 'avisoVerificacion'])->name('verification.notice');
    Route::get('/verificar/{id}/{hash}', [AccesoController::class, 'verificar'])
        ->middleware('signed')->name('verification.verify');
    Route::post('/verificar/reenviar', [AccesoController::class, 'reenviarVerificacion'])
        ->middleware('throttle:4,1')->name('verification.send');

    Route::get('/progreso/estado', [ProgresoController::class, 'estado'])->name('progreso.estado');
    Route::post('/progreso',       [ProgresoController::class, 'guardar'])->name('progreso.guardar')
        ->middleware('throttle:60,1');
});

// -------------------------------------------------------------- gestion
// Sin enlaces desde la web publica; quien no este en admin_users ve un 404.

Route::middleware([\App\Http\Middleware\EsAdmin::class])
    ->prefix('gestion')->name('gestion.')->group(function () {
        Route::get('/',            [GestionController::class, 'panel'])->name('panel');
        Route::get('/alumnos',     [GestionController::class, 'alumnos'])->name('alumnos');
        Route::get('/embudo',      [GestionController::class, 'embudo'])->name('embudo');
        Route::get('/fallos',      [GestionController::class, 'fallos'])->name('fallos');
        Route::get('/donaciones',  [GestionController::class, 'donaciones'])->name('donaciones');
        Route::get('/materiales',  [GestionController::class, 'materiales'])->name('materiales');
        Route::post('/materiales', [GestionController::class, 'materialGuardar'])->name('materiales.guardar');
        Route::post('/materiales/borrar', [GestionController::class, 'materialBorrar'])->name('materiales.borrar');
        Route::post('/moderar',    [GestionController::class, 'moderar'])->name('moderar');
    });
