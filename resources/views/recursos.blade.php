@extends('layout')
@section('titulo', 'Материалы для скачивания: испанская грамматика в PDF')
@section('descripcion', 'PDF для печати: предлоги, спряжение глаголов, первые 100 фраз, ложные друзья, числа и время. Бесплатные материалы по испанскому для русскоговорящих.')

@section('cuerpo')
<div class="ancho">

  <div class="nivel-cab">
    <div class="n">Материалы</div>
    <h2>Скачать, распечатать и держать под рукой</h2>
    <p>Короткие конспекты — одна тема на нескольких страницах, с примерами и
    типичными ошибками. Удобно распечатать и положить рядом, когда занимаетесь.</p>
  </div>

  @if ($descargables->isEmpty())
    <p class="recursos-vacio">Первые материалы уже готовятся — заглядывайте.</p>
  @else
    <div class="rejilla dos recursos">
      @foreach ($descargables as $d)
        <a class="tarjeta recurso" href="{{ $d['url'] }}" download>
          <span class="pdf-marca" aria-hidden="true">PDF</span>
          <div class="tit">{{ $d['titulo'] }}</div>
          <div class="sub">{{ $d['nota'] }}</div>
          <div class="recurso-pie">Скачать · {{ number_format($d['peso'], 1, ',', '') }} МБ</div>
        </a>
      @endforeach
    </div>
  @endif

  <div class="nivel-cab">
    <div class="n">Офлайн-режим</div>
    <h2>Скачать курс целиком — и учиться в метро</h2>
  </div>

  <div id="zona-offline" class="tarjeta quieta zona-offline">
    <p class="sub">Весь курс — тексты, звук, картинки — сохранится в этом
    браузере и будет работать без интернета. Это большая загрузка (сотни
    мегабайт): лучше по Wi-Fi. Позже той же кнопкой можно докачать новое.</p>
    <button class="boton" type="button" data-offline-boton>Скачать курс целиком</button>
    <progress hidden></progress>
    <p class="sub" data-offline-texto></p>
  </div>

  <div class="nivel-cab">
    <div class="n">Полезное в сети</div>
    <h2>Проверенные внешние ресурсы</h2>
    <p>Немного, но по делу: то, чем мы сами пользуемся каждый день.</p>
  </div>

  <div class="rejilla dos recursos">
    @foreach ($enlaces as $e)
      <a class="tarjeta recurso" href="{{ $e['url'] }}" target="_blank" rel="noopener">
        <div class="tit">{{ $e['titulo'] }} <span class="fuera" aria-hidden="true">↗</span></div>
        <div class="sub">{{ $e['nota'] }}</div>
      </a>
    @endforeach
  </div>

</div>
@endsection

@push('js')
<script src="/js/offline.js?v={{ @filemtime(public_path('js/offline.js')) ?: 1 }}" defer></script>
@endpush
