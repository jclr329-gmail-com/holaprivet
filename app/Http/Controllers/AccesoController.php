<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;

/**
 * Cuentas: registro con correo, entrada con Google, verificacion y
 * contrasena olvidada. Todo lo visible va en ruso; el codigo, en espanol.
 */
class AccesoController extends Controller
{
    // ------------------------------------------------------------ registro

    public function formularioRegistro()
    {
        return view('cuenta.registro');
    }

    public function registrar(Request $peticion)
    {
        $datos = $peticion->validate([
            'name'     => ['required', 'string', 'max:100'],
            'email'    => ['required', 'string', 'email', 'max:190', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [], ['name' => 'имя', 'email' => 'почта', 'password' => 'пароль']);

        $usuario = User::create($datos);

        event(new Registered($usuario));   // dispara el correo de verificacion

        Auth::login($usuario, remember: true);

        return redirect()->route('verification.notice');
    }

    // ------------------------------------------------------------- entrada

    public function formularioEntrar()
    {
        return view('cuenta.entrar');
    }

    public function entrar(Request $peticion)
    {
        $credenciales = $peticion->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (! Auth::attempt($credenciales, remember: true)) {
            throw ValidationException::withMessages([
                'email' => 'Почта или пароль не подходят.',
            ]);
        }

        $peticion->session()->regenerate();

        return redirect()->intended(route('portada'));
    }

    public function salir(Request $peticion)
    {
        Auth::logout();
        $peticion->session()->invalidate();
        $peticion->session()->regenerateToken();

        return redirect()->route('portada');
    }

    // -------------------------------------------------------------- google

    public function google()
    {
        return Socialite::driver('google')->redirect();
    }

    public function googleVuelta()
    {
        try {
            $deGoogle = Socialite::driver('google')->user();
        } catch (\Throwable) {
            return redirect()->route('login')
                ->withErrors(['email' => 'Не получилось войти через Google. Попробуйте ещё раз.']);
        }

        $usuario = User::where('google_id', $deGoogle->getId())
            ->orWhere('email', $deGoogle->getEmail())
            ->first();

        if (! $usuario) {
            $usuario = User::create([
                'name'     => $deGoogle->getName() ?: Str::before($deGoogle->getEmail(), '@'),
                'email'    => $deGoogle->getEmail(),
                // Nunca se usara: la entrada es por Google. Pero la columna
                // no admite nulos y una contrasena aleatoria no abre puertas.
                'password' => Str::random(40),
            ]);
        }

        // Google ya verifico ese correo: no se le pide verificarlo otra vez.
        $usuario->forceFill([
            'google_id'         => $deGoogle->getId(),
            'email_verified_at' => $usuario->email_verified_at ?? now(),
        ])->save();

        Auth::login($usuario, remember: true);

        return redirect()->route('portada');
    }

    // -------------------------------------------------------- verificacion

    public function avisoVerificacion()
    {
        return Auth::user()->hasVerifiedEmail()
            ? redirect()->route('portada')
            : view('cuenta.verificar');
    }

    public function verificar(EmailVerificationRequest $peticion)
    {
        $peticion->fulfill();

        return redirect()->route('portada')->with('estado', 'Почта подтверждена. ¡Bienvenida!');
    }

    public function reenviarVerificacion(Request $peticion)
    {
        $peticion->user()->sendEmailVerificationNotification();

        return back()->with('estado', 'Письмо отправлено ещё раз — проверьте почту (и папку «Спам»).');
    }

    // ---------------------------------------------------------- contrasena

    public function formularioOlvido()
    {
        return view('cuenta.olvido');
    }

    public function enviarOlvido(Request $peticion)
    {
        $peticion->validate(['email' => ['required', 'email']]);

        Password::sendResetLink($peticion->only('email'));

        // Siempre la misma respuesta: no se revela que correos existen.
        return back()->with('estado', 'Если такая почта у нас есть, письмо уже в пути.');
    }

    public function formularioRestablecer(Request $peticion, string $token)
    {
        return view('cuenta.restablecer', [
            'token' => $token,
            'email' => $peticion->query('email', ''),
        ]);
    }

    public function restablecer(Request $peticion)
    {
        $datos = $peticion->validate([
            'token'    => ['required'],
            'email'    => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $resultado = Password::reset($datos, function (User $usuario, string $contrasena) {
            $usuario->forceFill(['password' => Hash::make($contrasena)])->save();
            Auth::login($usuario, remember: true);
        });

        return $resultado === Password::PASSWORD_RESET
            ? redirect()->route('portada')->with('estado', 'Пароль обновлён.')
            : back()->withErrors(['email' => 'Ссылка устарела. Запросите новую.']);
    }
}
