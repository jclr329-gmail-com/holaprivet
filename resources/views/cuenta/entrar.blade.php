@extends('layout')
@section('robots', 'noindex, nofollow')
@section('titulo', 'Войти')

@section('cuerpo')
<div class="ancho estrecho">
  <h1 class="titulo-cuenta">Войти</h1>

  <a class="boton google" href="{{ route('google') }}">
    <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path fill="#EA4335" d="M12 5.04c1.62 0 3.06.56 4.2 1.64l3.12-3.12C17.4 1.8 14.9.8 12 .8 7.66.8 3.9 3.28 2.06 6.9l3.66 2.84C6.6 7.06 9.06 5.04 12 5.04z"/><path fill="#4285F4" d="M23.2 12.26c0-.9-.08-1.56-.24-2.26H12v4.28h6.36c-.14 1.06-.84 2.66-2.4 3.74l3.56 2.76c2.14-1.98 3.68-4.9 3.68-8.52z"/><path fill="#FBBC05" d="M5.72 14.26A7.05 7.05 0 0 1 5.34 12c0-.8.14-1.56.36-2.26L2.06 6.9A11.2 11.2 0 0 0 .8 12c0 1.8.44 3.5 1.26 5.1l3.66-2.84z"/><path fill="#34A853" d="M12 23.2c3.02 0 5.56-1 7.4-2.72l-3.56-2.76c-.96.66-2.24 1.12-3.84 1.12-2.94 0-5.4-2.02-6.28-4.7L2.06 17.1C3.9 20.72 7.66 23.2 12 23.2z"/></svg>
    Войти через Google
  </a>

  <div class="o-bien"><span>или по почте</span></div>

  <form method="POST" action="{{ route('login') }}" class="forma">
    @csrf
    <label>Почта
      <input type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email">
    </label>
    <label>Пароль
      <input type="password" name="password" required autocomplete="current-password">
    </label>
    @error('email')<p class="fallo">{{ $message }}</p>@enderror
    <button class="boton" type="submit">Войти</button>
  </form>

  <p class="pie-forma">
    <a href="{{ route('password.request') }}">Забыли пароль?</a> ·
    Ещё нет аккаунта? <a href="{{ route('registro') }}">Создать</a>
  </p>
</div>
@endsection
