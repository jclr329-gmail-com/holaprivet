@extends('layout')
@section('titulo', 'Забыли пароль')

@section('cuerpo')
<div class="ancho estrecho">
  <h1 class="titulo-cuenta">Забыли пароль?</h1>
  <p class="sub-cuenta">Ничего страшного. Напишите почту — пришлём ссылку для
  нового пароля.</p>

  <form method="POST" action="{{ route('password.email') }}" class="forma">
    @csrf
    <label>Почта
      <input type="email" name="email" value="{{ old('email') }}" required autofocus>
    </label>
    @error('email')<p class="fallo">{{ $message }}</p>@enderror
    <button class="boton" type="submit">Прислать ссылку</button>
  </form>

  <p class="pie-forma"><a href="{{ route('login') }}">← Назад ко входу</a></p>
</div>
@endsection
