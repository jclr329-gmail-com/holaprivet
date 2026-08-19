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
    .caja { max-width:720px; width:100%; background:#fff; border-radius:14px; padding:40px;
            box-shadow:0 2px 24px rgba(0,0,0,.07); }
    h1 { margin:0 0 4px; font-size:32px; color:var(--azul); letter-spacing:-.5px; }
    .lema { margin:0 0 26px; color:#7A6E5F; font-size:15px; }
    h2 { font-size:13px; text-transform:uppercase; letter-spacing:.08em; color:#9A8E7F;
         margin:28px 0 10px; font-weight:600; }
    .estado { display:flex; gap:10px; align-items:center; padding:13px 16px; border-radius:9px;
              margin-bottom:9px; font-size:14px; }
    .ok   { background:#EAF5EC; color:#1E6B33; }
    .malo { background:#FBEAEA; color:#8B1E1E; }
    table { width:100%; border-collapse:collapse; font-size:14px; }
    td { padding:8px 0; border-bottom:1px solid #F4EFE7; }
    td:first-child { color:#7A6E5F; }
    td:last-child { text-align:right; font-family:ui-monospace,Menlo,Consolas,monospace; }
    .cero { color:#B8AFA2; }
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
        <div class="estado ok">
            Base de datos correcta · {{ $bd['totales'] }} tablas · {{ $bd['cotejamiento'] }}
        </div>
    @else
        <div class="estado malo">Base de datos: {{ $bd['mensaje'] }}</div>
    @endif

    <h2>Aplicación</h2>
    <table>
        <tr><td>Entorno</td><td>{{ app()->environment() }}</td></tr>
        <tr><td>Depuración</td><td>{{ config('app.debug') ? 'activada' : 'desactivada' }}</td></tr>
        <tr><td>Idioma</td><td>{{ app()->getLocale() }}</td></tr>
        <tr><td>Zona horaria</td><td>{{ config('app.timezone') }}</td></tr>
        <tr><td>Hora del servidor</td><td>{{ now()->format('d/m/Y H:i') }}</td></tr>
    </table>

    @if ($bd['ok'])
        <h2>Contenido en la base de datos</h2>
        <table>
            @foreach ($bd['tablas'] as $tabla => $filas)
                <tr>
                    <td>{{ $tabla }}</td>
                    <td class="{{ $filas === 0 ? 'cero' : '' }}">{{ number_format($filas, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </table>
    @endif

    <p class="pie">
        Entorno de pruebas.
        Hito 2: esquema completo creado, a la espera de importar los 95 archivos.
    </p>
</div>
</body>
</html>
