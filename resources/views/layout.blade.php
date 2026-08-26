@php
  // Lo que leen los buscadores y los mensajeros. Cada vista puede definir
  // sus secciones «titulo», «descripcion», «imagen» y «robots»; lo que no
  // defina cae a los valores por defecto de App\Support\Seo. Las secciones
  // llegan ya escapadas por Blade, por eso abajo se imprimen con {!! !!}.
  $esBeta      = str_contains(config('app.url'), '//beta.');
  $casa        = rtrim(config('app.url'), '/');
  $seoTitulo   = trim($__env->yieldContent('titulo', 'holaprivet')) . ' · holaprivet';
  $seoTexto    = trim($__env->yieldContent('descripcion', \App\Support\Seo::DESCRIPCION));
  $seoImagen   = trim($__env->yieldContent('imagen', \App\Support\Seo::IMAGEN));
  $seoRobots   = trim($__env->yieldContent('robots', ''));
  $seoTipo     = trim($__env->yieldContent('og_tipo', 'website'));
  $ruta        = request()->getPathInfo();
  $seoCanonica = $casa . ($ruta === '/curso' ? '/' : $ruta);   // /curso es la portada
  $seoImagen   = str_starts_with($seoImagen, 'http') ? $seoImagen : $casa . $seoImagen;
@endphp
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
@if ($esBeta)
<meta name="robots" content="noindex, nofollow">
@elseif ($seoRobots !== '')
<meta name="robots" content="{!! $seoRobots !!}">
@else
<link rel="canonical" href="{{ $seoCanonica }}">
@endif
<meta name="description" content="{!! $seoTexto !!}">
<meta property="og:site_name" content="holaprivet">
<meta property="og:locale" content="ru_RU">
<meta property="og:type" content="{!! $seoTipo !!}">
<meta property="og:title" content="{!! $seoTitulo !!}">
<meta property="og:description" content="{!! $seoTexto !!}">
<meta property="og:url" content="{{ $seoCanonica }}">
<meta property="og:image" content="{{ $seoImagen }}">
@if ($seoImagen === $casa . \App\Support\Seo::IMAGEN)
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
@endif
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{!! $seoTitulo !!}">
<meta name="twitter:description" content="{!! $seoTexto !!}">
<meta name="twitter:image" content="{{ $seoImagen }}">
<link rel="manifest" href="/manifest.webmanifest">
<meta name="theme-color" content="#1F5C8B">
<link rel="apple-touch-icon" href="/img/icono-192.png">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<meta name="apple-mobile-web-app-title" content="holaprivet">
<meta name="csrf" content="{{ csrf_token() }}">
<title>{!! $seoTitulo !!}</title>
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
      <a href="{{ route('portada') }}" class="{{ request()->is('/') || request()->is('curso') || request()->is('nivel/*') ? 'act' : '' }}">Курс</a>
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
    @if ($esBeta)<span>Бета-версия</span>@endif
  </div>
</footer>

<script src="/js/audio.js?v={{ @filemtime(public_path('js/audio.js')) ?: 1 }}" defer></script>
<script src="/js/compartir.js?v={{ @filemtime(public_path('js/compartir.js')) ?: 1 }}" defer></script>
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
