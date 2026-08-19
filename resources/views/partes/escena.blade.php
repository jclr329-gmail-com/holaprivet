<div class="escena">
  <div class="escena-top">
    <span class="lugar">
      {{ $pieza->lines->firstWhere('is_break', true)?->stage_note_ru ?? ($esCuento ? 'Чтение' : 'Сцена') }}
    </span>
    <button class="oir" type="button">
      <svg viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
      Слушать
    </button>
  </div>

  <div class="replicas @if($esCuento) prosa @endif">
    @foreach ($pieza->lines as $l)
      @if ($l->is_break)
        <div class="corte">{{ $l->stage_note_ru }}</div>
      @elseif ($l->character)
        <div class="replica">
          <div class="quien">
            {{ $l->character }}
            @if ($l->stage_note_ru)<span class="acota">{{ $l->stage_note_ru }}</span>@endif
          </div>
          <div class="dice"><span class="es">{{ $l->text_es }}</span></div>
        </div>
      @else
        <p><span class="es">{{ $l->text_es }}</span>
          @if ($l->text_ru)<br><span class="ru">{{ $l->text_ru }}</span>@endif
        </p>
      @endif
    @endforeach
  </div>
</div>
