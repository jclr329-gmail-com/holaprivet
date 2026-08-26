@extends('layout')
@section('titulo', 'Уровень ' . $n . ': ' . $nombre[0])
@section('descripcion', 'Уровень ' . $n . ' курса испанского для русскоговорящих — «' . $nombre[0] . '»: ' . ['', 'поздороваться, купить хлеб, спросить дорогу.', 'рассказать о себе, о доме, о планах.', 'врач, квартира, документы, работа.'][$n] . ' Модули и рассказы с аудио.')

@section('cuerpo')
<div class="ancho">
  <div class="eyebrow"><span class="pipe"></span> Уровень {{ $n }}</div>
  <h1>{{ $nombre[0] }}</h1>
  <p class="h1-ru">{{ $nombre[1] }}</p>

  <div class="rejilla tres">
    @foreach ($modulos as $m)
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

  @if ($cuentos->count())
    <div class="nivel-cab">
      <div class="n">Рассказы</div>
      <h2>Чтение</h2>
      <p>Каждый рассказ открывается после определённого модуля.</p>
    </div>
    <div class="rejilla tres">
      @foreach ($cuentos as $c)
        <a class="tarjeta" data-slug="{{ $c->slug }}" href="{{ route('pieza', $c->slug) }}">
          <div class="num">Рассказ {{ $c->position }}</div>
          <div class="tit">{{ $c->title_es }}</div>
          <div class="sub">{{ $c->title_ru }}</div>
          <div class="pie">
            @if ($c->word_count)<span>{{ $c->word_count }} слов</span>@endif
            @if ($c->read_after_slug)<span>после {{ strtoupper($c->read_after_slug) }}</span>@endif
          </div>
        </a>
      @endforeach
    </div>
  @endif
</div>

<script type="application/json" id="camino-datos">@json($camino)</script>
@push('js')
  <script src="/js/camino.js?v={{ @filemtime(public_path('js/camino.js')) ?: 1 }}" defer></script>
@endpush
@endsection
