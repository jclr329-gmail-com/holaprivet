@extends('layout')
@section('titulo', 'Курс испанского')

@section('cuerpo')
<div class="ancho">

  <div class="hero">
    <div class="eyebrow"><span class="pipe"></span> Курс испанского языка</div>
    <h1>Испанский для тех, кто уже здесь живёт</h1>
    <p class="lema">Три уровня, объяснения по-русски, примеры из настоящей жизни в Испании.</p>

    <div class="cifras">
      <div><b>49</b> модулей</div>
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
        <a class="tarjeta" href="{{ route('pieza', $m->slug) }}">
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
@endsection
