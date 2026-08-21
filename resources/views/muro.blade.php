@extends('layout')
@section('titulo', 'Стена слов')

@section('cuerpo')
<div class="ancho">

  <div class="nivel-cab">
    <div class="n">Стена слов</div>
    <h2>Курс бесплатный. Стена — то, что его держит.</h2>
    <p>Каждое слово на этой стене — из курса, и у каждого может быть свой
    человек. Возьмите слово под опеку на год ({{ number_format($precios['normal']/100, 0) }} € обычное,
    {{ number_format($precios['especial']/100, 0) }} € особое) — и оно будет
    носить ваше имя, пока курс остаётся бесплатным для всех.</p>
  </div>

  @if (session('estado'))
    <div class="aviso bien-aviso">{{ session('estado') }}</div>
  @endif
  @if (request('cancelado'))
    <div class="aviso">Оплата отменена — слово снова свободно через полчаса,
    или выберите его заново прямо сейчас.</div>
  @endif

  <div class="muro">
    @foreach ($palabras as $p)
      @php
        $prop = $p->status === 'ocupada' ? $p->propiedad : null;
        $conDedicatoria = $prop && $prop->moderation === 'aprobada';
      @endphp
      @if ($p->status === 'libre')
        <a class="ladrillo libre {{ $p->kind }}" href="{{ route('muro.formulario', $p) }}"
           title="свободно · {{ number_format($p->price_cents/100, 0) }} €">
          <span class="pal">{{ $p->word }}</span>
          <span class="tra">{{ $p->translation_ru }}</span>
        </a>
      @else
        <div class="ladrillo {{ $p->status }} {{ $p->kind }}"
             @if($conDedicatoria && $prop->dedication) title="{{ $prop->dedication }}" @endif>
          <span class="pal">{{ $p->word }}</span>
          <span class="tra">{{ $p->translation_ru }}</span>
          @if ($prop)
            <span class="quien">{{ $conDedicatoria ? $prop->display_name : '★' }}</span>
          @endif
        </div>
      @endif
    @endforeach
  </div>

  <p class="muro-pie">Слова с ★ — оплаченные, чьи посвящения ещё на модерации.
  Наведите на слово с именем, чтобы прочитать посвящение.</p>

</div>
@endsection
