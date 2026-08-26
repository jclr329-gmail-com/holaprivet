@extends('layout')
@section('robots', 'noindex, nofollow')
@section('titulo', 'Новый пароль')

@section('cuerpo')
<div class="ancho estrecho">
  <h1 class="titulo-cuenta">Новый пароль</h1>

  <form method="POST" action="{{ route('password.update') }}" class="forma">
    @csrf
    <input type="hidden" name="token" value="{{ $token }}">
    <label>Почта
      <input type="email" name="email" value="{{ old('email', $email) }}" required>
    </label>
    <label>Новый пароль <span class="pista">(не короче 8 знаков)</span>
      <input type="password" name="password" required autocomplete="new-password">
    </label>
    <label>Пароль ещё раз
      <input type="password" name="password_confirmation" required autocomplete="new-password">
    </label>
    @if ($errors->any())<p class="fallo">{{ $errors->first() }}</p>@endif
    <button class="boton" type="submit">Сохранить</button>
  </form>
</div>
@endsection
