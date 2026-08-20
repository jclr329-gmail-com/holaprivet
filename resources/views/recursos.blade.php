@extends('layout')
@section('titulo', 'Материалы')

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
