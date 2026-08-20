<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>@yield('titulo', 'holaprivet') · holaprivet</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=PT+Serif:ital,wght@0,400;0,700;1,400&family=PT+Sans:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">
{{-- La version sale de la fecha del archivo: cada despliegue que toque el
     CSS invalida la cache de los navegadores sin acordarse de nada. --}}
<link rel="stylesheet" href="/css/app.css?v={{ @filemtime(public_path('css/app.css')) ?: 1 }}">
</head>
<body>

<header class="barra">
  <div class="barra-in">
    <a class="marca" href="{{ route('portada') }}">hola<span>privet</span></a>
    <nav>
      <a href="{{ route('portada') }}" class="{{ request()->is('/') || request()->is('nivel/*') ? 'act' : '' }}">Курс</a>
      <a href="{{ route('fichas') }}"  class="{{ request()->is('fichas') ? 'act' : '' }}">Карточки</a>
    </nav>
  </div>
</header>

@yield('cuerpo')

<footer class="pie-web">
  <div>
    <span>holaprivet · Испанский для русскоговорящих</span>
    <span>Бета-версия</span>
  </div>
</footer>

<script src="/js/audio.js?v={{ @filemtime(public_path('js/audio.js')) ?: 1 }}" defer></script>
@stack('js')
</body>
</html>
