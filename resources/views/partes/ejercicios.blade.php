{{-- Ejercicios interactivos: los datos vienen de la base, la correccion es
     inmediata y el progreso vive en el navegador (localStorage). La letra
     correcta viaja en el HTML: para una beta sin notas ni examenes, que la
     respuesta se pueda ver en el codigo fuente no es un problema. --}}

<div class="ejercicios" data-ejercicios data-pieza="{{ $pieza->slug }}">

  <p class="ej-como">Выберите вариант — объяснение появится сразу. Ответы
  сохраняются в этом браузере, можно уйти и вернуться.</p>

  <noscript>
    <p class="ej-como">Для интерактивных упражнений нужен JavaScript. Сейчас он
    выключен, поэтому упражнения ниже — только для чтения.</p>
  </noscript>

  <div class="ej-progreso" aria-hidden="true"><span class="ej-barra"></span></div>
  <p class="ej-cuenta"><span class="ej-hechas">0</span> из {{ $pieza->exercises->count() }}</p>

  <ol class="ej-lista">
    @foreach ($pieza->exercises as $ej)
      @php $correcta = $ej->options->firstWhere('is_correct', true); @endphp
      <li class="ejercicio" value="{{ $ej->position }}"
          data-n="{{ $ej->position }}" data-correcta="{{ $correcta?->letter }}">
        <p class="ej-enunciado">{{ $ej->prompt }}</p>
        <div class="opciones" role="group">
          @foreach ($ej->options as $o)
            <button type="button" class="opcion" data-letra="{{ $o->letter }}">
              <span class="letra">{{ $o->letter }})</span> {{ $o->text }}
            </button>
          @endforeach
        </div>
        <p class="explicacion" hidden>
          <b class="veredicto"></b>
          {{ $ej->explanation_ru }}
        </p>
      </li>
    @endforeach
  </ol>

  <div class="ej-resumen" hidden>
    <div class="ej-nota"></div>
    <p class="ej-frase"></p>
    @if ($repasos->isNotEmpty())
      <p class="ej-repaso">Стоит повторить:
        @foreach ($repasos as $r)
          <a href="{{ route('pieza', $r->slug) }}">{{ $r->title_es }}</a>@if(!$loop->last) · @endif
        @endforeach
      </p>
    @endif
    <button type="button" class="ej-reiniciar">Пройти ещё раз</button>
  </div>

</div>
