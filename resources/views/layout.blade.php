<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
@if (str_contains(config('app.url'), '//beta.'))
<meta name="robots" content="noindex, nofollow">
@endif
<link rel="manifest" href="/manifest.webmanifest">
<meta name="theme-color" content="#1F5C8B">
<link rel="apple-touch-icon" href="/img/icono-192.png">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<meta name="apple-mobile-web-app-title" content="holaprivet">
<meta name="csrf" content="{{ csrf_token() }}">
<title>@yield('titulo', 'holaprivet') · holaprivet</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=PT+Serif:ital,wght@0,400;0,700;1,400&family=PT+Sans:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">
{{-- La version sale de la fecha del archivo: cada despliegue que toque el
     CSS invalida la cache de los navegadores sin acordarse de nada. --}}
<link rel="stylesheet" href="/css/app.css?v={{ @filemtime(public_path('css/app.css')) ?: 1 }}">
</head>
<body data-usuario="{{ auth()->check() ? '1' : '' }}">

<header class="barra">
  <div class="barra-in">
    <a class="marca" href="{{ route('portada') }}">hola<span>privet</span></a>
    <nav>
      <a href="{{ route('portada') }}" class="{{ request()->is('/') || request()->is('nivel/*') ? 'act' : '' }}">Курс</a>
      <a href="{{ route('fichas') }}"  class="{{ request()->is('fichas') ? 'act' : '' }}">Карточки</a>
      <a href="{{ route('recursos') }}" class="{{ request()->is('recursos') ? 'act' : '' }}">Материалы</a>
      <a href="{{ route('muro') }}" class="{{ request()->is('muro*') ? 'act' : '' }}">Стена</a>
      <a href="{{ route('nosotros') }}" class="{{ request()->is('nosotros') ? 'act' : '' }}">О нас</a>
      @auth
        <span class="quien-soy" title="{{ auth()->user()->email }}">{{ \Illuminate\Support\Str::before(auth()->user()->name, ' ') }}</span>
        <form method="POST" action="{{ route('salir') }}" class="forma-salir">
          @csrf
          <button type="submit">Выйти</button>
        </form>
      @else
        <a href="{{ route('login') }}" class="entrar-enlace {{ request()->is('entrar') ? 'act' : '' }}">Войти</a>
      @endauth
    </nav>
  </div>
</header>

@if (session('estado'))
  <div class="aviso bien-aviso">{{ session('estado') }}</div>
@endif
@auth
  @if (! auth()->user()->hasVerifiedEmail())
    <div class="aviso">Подтвердите почту, чтобы прогресс сохранялся в аккаунте —
    письмо уже у вас. <a href="{{ route('verification.notice') }}">Подробнее</a></div>
  @endif
@endauth

@yield('cuerpo')

<footer class="pie-web">
  <div>
    <span>holaprivet · Испанский для русскоговорящих</span>
    <span>Бета-версия</span>
  </div>
</footer>

<script src="/js/audio.js?v={{ @filemtime(public_path('js/audio.js')) ?: 1 }}" defer></script>
@auth
<script src="/js/cuenta.js?v={{ @filemtime(public_path('js/cuenta.js')) ?: 1 }}" defer></script>
@endauth
<script>
if ('serviceWorker' in navigator) {
  navigator.serviceWorker.register('/sw.js?v={{ @filemtime(public_path('sw.js')) ?: 1 }}');
}
</script>
@stack('js')
</body>
</html>
