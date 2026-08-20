<ol class="frases">
  @foreach ($pieza->phrases as $f)
    <li>
      <div class="par">
        <span class="es" data-audio="{{ \App\Support\Refs::audio($f->text_es) }}">{{ $f->text_es }}</span>
        @if ($f->text_ru)<span class="tr">{{ $f->text_ru }}</span>@endif
      </div>
    </li>
  @endforeach
</ol>
