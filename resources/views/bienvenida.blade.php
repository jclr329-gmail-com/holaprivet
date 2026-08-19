<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>holaprivet — beta</title>
<style>
    :root { --ocre:#B8813A; --azul:#2E5C7A; --fondo:#FBF7F0; --texto:#2A2520; }
    * { box-sizing:border-box; }
    body { margin:0; min-height:100vh; display:flex; align-items:center; justify-content:center;
           background:var(--fondo); color:var(--texto);
           font-family:system-ui,-apple-system,"Segoe UI",Roboto,sans-serif; padding:24px; }
    .caja { max-width:640px; width:100%; background:#fff; border-radius:14px; padding:40px;
            box-shadow:0 2px 24px rgba(0,0,0,.07); }
    h1 { margin:0 0 4px; font-size:32px; color:var(--azul); letter-spacing:-.5px; }
    .lema { margin:0 0 28px; color:#7A6E5F; font-size:15px; }
    .estado { display:flex; gap:10px; align-items:center; padding:14px 16px; border-radius:9px;
              margin-bottom:10px; font-size:14px; }
    .ok   { background:#EAF5EC; color:#1E6B33; }
    .malo { background:#FBEAEA; color:#8B1E1E; }
    table { width:100%; border-collapse:collapse; margin-top:22px; font-size:14px; }
    td { padding:9px 0; border-bottom:1px solid #F0EAE0; }
    td:first-child { color:#7A6E5F; width:45%; }
    td:last-child { font-family:ui-monospace,Menlo,Consolas,monospace; }
    .pie { margin-top:26px; padding-top:18px; border-top:1px solid #F0EAE0;
           font-size:13px; color:#9A8E7F; }
</style>
</head>
<body>
<div class="caja">
    <h1>holaprivet</h1>
    <p class="lema">Испанский для русскоговорящих · Español para rusohablantes</p>

    <div class="estado ok">Laravel {{ app()->version() }} · PHP {{ PHP_VERSION }}</div>

    @if ($bd['ok'])
        <div class="estado ok">Conexión con la base de datos correcta</div>
    @else
        <div class="estado malo">Base de datos: {{ $bd['mensaje'] }}</div>
    @endif

    <table>
        <tr><td>Entorno</td><td>{{ app()->environment() }}</td></tr>
        <tr><td>Depuración</td><td>{{ config('app.debug') ? 'activada' : 'desactivada' }}</td></tr>
        <tr><td>Idioma</td><td>{{ app()->getLocale() }}</td></tr>
        <tr><td>Zona horaria</td><td>{{ config('app.timezone') }}</td></tr>
        @if ($bd['ok'])
            <tr><td>MySQL</td><td>{{ $bd['version'] }}</td></tr>
            <tr><td>Cotejamiento</td><td>{{ $bd['cotejamiento'] }}</td></tr>
            <tr><td>Tablas</td><td>{{ $bd['tablas'] }}</td></tr>
        @endif
        <tr><td>Sesiones</td><td>{{ config('session.driver') }}</td></tr>
        <tr><td>Caché</td><td>{{ config('cache.default') }}</td></tr>
    </table>

    <p class="pie">Entorno de pruebas. Hito 1: la cadena de despliegue funciona.</p>
</div>
</body>
</html>
