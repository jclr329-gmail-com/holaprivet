@extends('layout')
@section('robots', 'noindex, nofollow')
@section('titulo', 'Подтвердите почту')

@section('cuerpo')
<div class="ancho estrecho">
  <h1 class="titulo-cuenta">Остался один шаг</h1>
  <p class="sub-cuenta">Мы отправили письмо на
  <b>{{ auth()->user()->email }}</b>. Откройте его и нажмите кнопку
  подтверждения — и ваш прогресс начнёт сохраняться в аккаунте.</p>
  <p class="sub-cuenta">Письма нет? Загляните в «Спам» — или отправим ещё раз:</p>

  <form method="POST" action="{{ route('verification.send') }}" class="forma">
    @csrf
    <button class="boton claro" type="submit">Отправить письмо ещё раз</button>
  </form>

  <p class="pie-forma"><a href="{{ route('curso') }}">Пока просто посмотреть курс →</a></p>
</div>
@endsection
