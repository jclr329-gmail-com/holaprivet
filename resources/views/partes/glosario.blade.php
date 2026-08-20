@php
  $bloques = [
    'nuevas'    => ['Palabras nuevas', 'Новые слова'],
    'conocidas' => ['Ya lo conoces',   'Это вы уже знаете'],
    'clave'     => ['Frases clave',    'Ключевые фразы'],
  ];
@endphp

@foreach ($bloques as $clave => $titulo)
  @php $voces = $pieza->vocabulary->where('block', $clave); @endphp

  @if ($voces->count())
    <div class="glosario">
      <div class="glosario-top">{{ $titulo[1] }} · {{ $titulo[0] }}</div>
      <ul class="voces">
        @foreach ($voces as $v)
          <li>
            <span><span class="es" data-audio="{{ \App\Support\Refs::audio($v->term_es) }}">{{ $v->term_es }}</span></span>
            <span class="rus">
              {{ $v->term_ru }}
              @if ($v->seen_in_slug)
                <span class="visto">· {{ strtoupper($v->seen_in_slug) }}</span>
              @endif
            </span>
          </li>
        @endforeach
      </ul>
    </div>
  @endif
@endforeach

@if ($pieza->vocabulary->isEmpty())
  <p class="ru">В этом материале нет новых слов.</p>
@endif
