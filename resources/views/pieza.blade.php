@extends('layout')
@section('titulo', $pieza->title_es)

@section('cuerpo')
<div class="envoltura">

  {{-- Índice lateral: solo en pantallas anchas --}}
  <aside class="indice">
    <h2>В этом материале</h2>
    <ol>
      @foreach ($secciones as $s)
        <li>
          <a href="#s{{ $s->number }}">
            <span class="n">{{ $s->number }}</span>
            <span>{{ $s->title_ru ?: $s->title_es }}</span>
          </a>
        </li>
      @endforeach
    </ol>
  </aside>

  <main>
    @php
      $etiqueta = match ($pieza->type) {
        'modulo'         => 'Уровень ' . $pieza->level . ' · Модуль ' . $pieza->position,
        'cuento'         => 'Рассказ · Уровень ' . $pieza->level,
        'ficha_ojo'      => 'Осторожно, похожие слова № ' . $pieza->position,
        default          => 'Справочная карточка',
      };
    @endphp

    <div class="eyebrow"><span class="pipe"></span> {{ $etiqueta }}</div>
    <h1>{{ $pieza->title_es }}</h1>
    <p class="h1-ru">{{ $pieza->title_ru }}</p>

    {{-- La ilustracion vive en img/piezas/<slug>.webp, en la raiz del
         subdominio y fuera del repositorio (como el audio). Si el archivo
         todavia no existe, no se pinta nada: la web funciona igual con las
         piezas ilustradas a medias. --}}
    @php
      // La imagen vive en la raiz REAL del subdominio (donde el despliegue
      // copia public/ y donde se sube img/), no en app/public: en este
      // hosting son carpetas distintas. DOCUMENT_ROOT es la que se sirve.
      $ilustracion = 'img/piezas/' . $pieza->slug . '.webp';
      $raiz = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/') ?: public_path();
      $fisica = $raiz . '/' . $ilustracion;
      $hayIlustracion = is_file($fisica);
    @endphp
    @if ($hayIlustracion)
      <figure class="ilustracion {{ $pieza->type === 'cuento' ? 'alta' : '' }}">
        <img src="/{{ $ilustracion }}?v={{ @filemtime($fisica) ?: 1 }}"
             alt="" loading="lazy" decoding="async">
      </figure>
    @endif

    <div class="meta">
      @if ($pieza->duration_min)<span class="chip"><b>{{ $pieza->duration_min }}</b> минут</span>@endif
      @if ($pieza->exercise_count)<span class="chip"><b>{{ $pieza->exercise_count }}</b> упражнений</span>@endif
      @if ($pieza->word_count)<span class="chip"><b>{{ $pieza->word_count }}</b> слов</span>@endif
      @if ($pieza->characters)<span class="chip">{{ implode(' · ', $pieza->characters) }}</span>@endif
    </div>

    @foreach ($secciones as $s)
      <section id="s{{ $s->number }}" @class(['tarea' => $s->kind === 'tarea'])>
        <h2>{{ $s->title_es }}</h2>
        @if ($s->title_ru)<p class="h2-ru">{{ $s->title_ru }}</p>@endif

        @switch($s->kind)
          @case('apoyo')
            @include('partes.glosario', ['pieza' => $pieza])
            @break

          @case('escena')
          @case('cuento')
            @include('partes.escena', ['pieza' => $pieza, 'esCuento' => $s->kind === 'cuento'])
            @break

          @case('frases')
            @include('partes.frases', ['pieza' => $pieza])
            @break

          @case('enlaces')
            @include('partes.enlaces', ['pieza' => $pieza])
            @break

          @case('ejercicios')
          @case('preguntas')
            @if ($interactivo)
              @include('partes.ejercicios', ['pieza' => $pieza, 'repasos' => $repasos])
            @else
              {!! $s->html !!}
            @endif
            @break

          @default
            {!! $s->html !!}
        @endswitch
      </section>
    @endforeach

    @if ($siguiente)
      <div class="siguiente">
        <div>
          <div class="et">Дальше</div>
          <div class="ti">{{ $siguiente->title_es }}</div>
        </div>
        <a href="{{ route('pieza', $siguiente->slug) }}">Продолжить →</a>
      </div>
    @endif
  </main>
</div>

@if ($interactivo)
  @push('js')
    <script src="/js/ejercicios.js?v={{ @filemtime(public_path('js/ejercicios.js')) ?: 1 }}" defer></script>
  @endpush
@endif
@endsection
