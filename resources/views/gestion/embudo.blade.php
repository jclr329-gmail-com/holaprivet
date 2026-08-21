@extends('gestion.base')
@section('g-titulo', 'El embudo del camino')

@section('g-cuerpo')
<p class="g-nota">Cada barra: alumnos que han completado esa pieza. La columna
«se quedan aquí»: alumnos cuya <em>última</em> pieza completada es esa — donde
la barra de abandono se concentra, ahí se pierde a la gente.</p>

<div class="tabla-scroll"><table class="g-tabla">
<tr><th>Paso</th><th>Pieza</th><th style="width:38%">Completada por</th><th>Se quedan aquí</th></tr>
@foreach ($filas as $f)
  <tr>
    <td>{{ $f['etiqueta'] }}</td>
    <td>{{ $f['titulo'] }}</td>
    <td>
      <div class="g-barra"><span style="width:{{ round($f['hechas']*100/$maximo) }}%"></span></div>
      {{ $f['hechas'] }}
    </td>
    <td>{{ $f['abandono'] ?: '—' }}</td>
  </tr>
@endforeach
</table></div>
@endsection
