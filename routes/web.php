<?php

use App\Http\Controllers\CursoController;
use Illuminate\Support\Facades\Route;

Route::get('/',                [CursoController::class, 'portada'])->name('portada');
Route::get('/nivel/{n}',       [CursoController::class, 'nivel'])->whereNumber('n')->name('nivel');
Route::get('/fichas',          [CursoController::class, 'fichas'])->name('fichas');
Route::get('/p/{slug}',        [CursoController::class, 'pieza'])->name('pieza');
