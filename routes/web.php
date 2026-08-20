<?php

use App\Http\Controllers\AccesoController;
use App\Http\Controllers\CursoController;
use App\Http\Controllers\ProgresoController;
use Illuminate\Support\Facades\Route;

// ------------------------------------------------------------------ curso
// El contenido es libre: leer no exige cuenta. La cuenta guarda el progreso.

Route::get('/',          [CursoController::class, 'inicio'])->name('portada');
Route::get('/curso',     [CursoController::class, 'curso'])->name('curso');
Route::get('/nivel/{n}', [CursoController::class, 'nivel'])->whereNumber('n')->name('nivel');
Route::get('/fichas',    [CursoController::class, 'fichas'])->name('fichas');
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
