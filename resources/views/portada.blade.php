@extends('layout')
@section('titulo', 'Бесплатный курс испанского для русскоговорящих')
@section('descripcion', "Бесплатный курс испанского для русскоговорящих: {$modulos} модулей, {$cuentos} рассказов, {$fichas} карточек, " . number_format($ejercicios, 0, ',', ' ') . " упражнений. Объяснения по-русски, аудио, сцены из жизни в Испании.")

@section('cuerpo')
<div class="ancho">

  @guest
    <div class="banda-invitado">
      <p>Бесплатный курс испанского для русскоговорящих. Аккаунт нужен только
      для одного: чтобы прогресс сохранялся на любом устройстве.</p>
      <span class="banda-botones">
        <a class="boton" href="{{ route('registro') }}">Создать аккаунт</a>
        <a class="boton claro" href="{{ route('login') }}">Войти</a>
        <a class="enlace-suave" href="{{ route('bienvenida') }}">Что это за курс? →</a>
      </span>
    </div>
  @endguest

  <div class="hero">
    <div class="eyebrow"><span class="pipe"></span> Курс испанского языка</div>
    <h1>Испанский для тех, кто уже здесь живёт</h1>
    <p class="lema">Три уровня, объяснения по-русски, примеры из настоящей жизни в Испании.</p>

    <a class="continuar" data-continuar hidden href="#">
      <span>
        <span class="c-et"></span>
        <span class="c-ti"></span>
      </span>
      <span class="c-flecha" aria-hidden="true">→</span>
    </a>

    <button class="compartir" type="button" data-compartir
            title="Поделиться курсом">Поделиться курсом ↗</button>

    <div class="cifras">
      <div><b>{{ $modulos }}</b> модулей</div>
      <div><b>{{ $cuentos }}</b> рассказов</div>
      <div><b>{{ $fichas }}</b> карточек</div>
      <div><b>{{ number_format($ejercicios, 0, ',', ' ') }}</b> упражнений</div>
    </div>
  </div>

  @foreach ([1 => ['Ничего не знаю, но очень хочу', 'Поздороваться, купить хлеб, спросить дорогу.'],
             2 => ['Уже строю свои фразы', 'Рассказать о себе, о доме, о планах.'],
             3 => ['Решаю свои дела по-испански', 'Врач, квартира, документы, работа.']] as $n => $texto)
    <div class="nivel-cab">
      <div class="n">Уровень {{ $n }}</div>
      <h2>{{ $texto[0] }}</h2>
      <p>{{ $texto[1] }}</p>
    </div>

    <div class="rejilla tres">
      @foreach ($niveles[$n] as $m)
        <a class="tarjeta" data-slug="{{ $m->slug }}" href="{{ route('pieza', $m->slug) }}">
          <div class="num">Модуль {{ $m->position }}</div>
          <div class="tit">{{ $m->title_es }}</div>
          <div class="sub">{{ $m->title_ru }}</div>
          <div class="pie">
            @if ($m->duration_min)<span>{{ $m->duration_min }} мин</span>@endif
            @if ($m->exercise_count)<span>{{ $m->exercise_count }} упражнений</span>@endif
          </div>
        </a>
      @endforeach
    </div>
  @endforeach

</div>

<script type="application/json" id="camino-datos">@json($camino)</script>
@push('js')
  <script src="/js/camino.js?v={{ @filemtime(public_path('js/camino.js')) ?: 1 }}" defer></script>
@endpush
@endsection
