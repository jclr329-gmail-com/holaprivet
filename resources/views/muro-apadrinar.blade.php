@extends('layout')
@section('titulo', 'Взять слово')

@section('cuerpo')
<div class="ancho estrecho">
  <h1 class="titulo-cuenta">Слово «{{ $palabra->word }}»</h1>
  <p class="sub-cuenta">{{ $palabra->translation_ru }} ·
    {{ $palabra->kind === 'especial' ? 'особое слово' : 'обычное слово' }} ·
    <b>{{ number_format($palabra->price_cents/100, 0) }} € в год</b></p>

  <form method="POST" action="{{ route('muro.apadrinar', $palabra) }}" class="forma">
    @csrf
    <label>Имя на стене
      <input type="text" name="display_name" maxlength="60" required
             value="{{ old('display_name', auth()->user()->name) }}">
    </label>
    <label>Посвящение <span class="pista">(необязательно, до 100 знаков — появится после модерации)</span>
      <input type="text" name="dedication" maxlength="100" value="{{ old('dedication') }}">
    </label>
    @if ($errors->any())<p class="fallo">{{ $errors->first() }}</p>@endif
    <button class="boton" type="submit">Оплатить через Stripe</button>
  </form>

  <p class="pie-forma">Оплата на защищённой странице Stripe — данные карты не
  проходят через наш сайт. Слово резервируется за вами на 30 минут.</p>
</div>
@endsection
