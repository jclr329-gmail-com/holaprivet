<div class="escena">
  <div class="escena-top">
    <span class="lugar">
      @if ($esCuento)
        Чтение
      @else
        {{ $pieza->lines->firstWhere('is_break', true)?->stage_note_ru ?? 'Сцена' }}
      @endif
    </span>
    <button class="oir" type="button">
      <svg viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
      Слушать
    </button>
  </div>

  @if ($esCuento)
    {{-- Prosa: parrafos normales. El subrayado punteado en cada frase
         convertiria el cuento en un campo de puntos, asi que aqui el audio
         se marca solo al pasar por encima. --}}
    <div class="relato">
      @foreach ($pieza->lines as $l)
        @if ($l->stage_note_ru === 'cita')
          <blockquote class="nota-cuento"><span class="es lisa">{{ $l->text_es }}</span></blockquote>
        @else
          <p><span class="es lisa">{{ $l->text_es }}</span></p>
          @if ($l->text_ru)<p class="ru trad">{{ $l->text_ru }}</p>@endif
        @endif
      @endforeach
    </div>
  @else
    <div class="replicas">
      @foreach ($pieza->lines as $l)
        @if ($l->is_break)
          <div class="corte">{{ $l->stage_note_ru }}</div>
        @else
          <div class="replica">
            <div class="quien">
              {{ $l->character }}
              @if ($l->stage_note_ru)<span class="acota">{{ $l->stage_note_ru }}</span>@endif
            </div>
            <div class="dice"><span class="es">{{ $l->text_es }}</span></div>
          </div>
        @endif
      @endforeach
    </div>
  @endif
</div>
