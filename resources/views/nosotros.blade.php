@extends('layout')
@section('titulo', 'О нас')

@section('cuerpo')
<div class="ancho estrecho-medio">

  <div class="nivel-cab">
    <div class="n">О нас</div>
    <h2>Кто делает holaprivet</h2>
  </div>

  @if ($hayFoto)
    <figure class="nosotros-foto">
      <img src="/img/nosotros.png?v={{ @filemtime((rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/') ?: public_path()) . '/img/nosotros.png') ?: 1 }}" alt="Мы">
    </figure>
  @endif

  {{-- ===== ЗАГЛУШКА: el texto definitivo llega en breve ===== --}}
  <p>Nos — это двое: она учит испанский, он его преподаёт. Из наших вечерних
  занятий на кухне в Севилье вырос этот курс: сцены из настоящей жизни,
  объяснения по-русски и упражнения, которые мы сначала проверили друг на
  друге.</p>

  <p>Курс был и останется бесплатным. Мы делаем его, потому что помним, каково
  это — приехать и не понимать ни слова.</p>
  {{-- ===== FIN DE LA ЗАГЛУШКА ===== --}}

</div>
@endsection
