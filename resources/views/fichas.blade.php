@extends('layout')
@section('titulo', 'Карточки по испанской грамматике для русскоговорящих')
@section('descripcion', 'Ir и venir, ser и estar, por и para, saber и conocer, ложные друзья, артикль: слова, которые русский объединяет, а испанский разделяет. И карточки для печати.')

@section('cuerpo')
<div class="ancho">
  <div class="eyebrow"><span class="pipe"></span> Библиотека</div>
  <h1>Карточки</h1>
  <p class="h1-ru">Справочные материалы, к которым возвращаются</p>

  <div class="nivel-cab">
    <div class="n">Осторожно, похожие слова</div>
    <h2>Ojo, que se parecen</h2>
    <p>Слова, которые русский язык объединяет, а испанский разделяет.</p>
  </div>
  <div class="rejilla tres">
    @foreach ($ojo as $f)
      <a class="tarjeta" href="{{ route('pieza', $f->slug) }}">
        <div class="num">№ {{ $f->position }}</div>
        <div class="tit">{{ $f->title_es }}</div>
        <div class="sub">{{ $f->title_ru }}</div>
      </a>
    @endforeach
  </div>

  <div class="nivel-cab">
    <div class="n">Практические карточки</div>
    <h2>Fichas prácticas</h2>
    <p>Грамматика и лексика для распечатки.</p>
  </div>
  <div class="rejilla tres">
    @foreach ($practicas as $f)
      <a class="tarjeta" href="{{ route('pieza', $f->slug) }}">
        <div class="num">{{ $f->printable ? 'Для печати' : 'Справка' }}</div>
        <div class="tit">{{ $f->title_es }}</div>
        <div class="sub">{{ $f->title_ru }}</div>
      </a>
    @endforeach
  </div>
</div>
@endsection
