<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * La puerta de /gestion.
 *
 * Solo pasa quien tiene sesion Y su correo esta en admin_users (tabla que
 * se alimenta a mano en phpMyAdmin). Para todos los demas —incluido quien
 * no ha entrado— la respuesta es un 404: ni siquiera se confirma que la
 * zona exista.
 */
class EsAdmin
{
    public function handle(Request $peticion, Closure $siguiente)
    {
        $usuario = $peticion->user();

        if (! $usuario ||
            ! DB::table('admin_users')->where('email', $usuario->email)->exists()) {
            abort(404);
        }

        return $siguiente($peticion);
    }
}
