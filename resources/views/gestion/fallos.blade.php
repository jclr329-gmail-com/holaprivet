@extends('gestion.base')
@section('g-titulo', 'Dónde fallan')

@section('g-cuerpo')
<p class="g-nota">Ejercicios con mayor tasa de fallo (mínimo 3 intentos).
Un porcentaje alto y sostenido señala un ejercicio o una explicación que
merecen retoque.</p>

<div class="tabla-scroll"><table class="g-tabla">
<tr><th>Pieza</th><th>Nº</th><th>Enunciado</th><th>Intentos</th><th>Fallos</th><th>%</th></tr>
@forelse ($filas as $f)
  <tr class="{{ $f['porcentaje'] >= 60 ? 'g-mal' : '' }}">
    <td><a href="/p/{{ $f['pieza'] }}" target="_blank" rel="noopener">{{ $f['pieza'] }}</a></td>
    <td>{{ $f['n'] }}</td>
    <td>{{ $f['enunciado'] }}</td>
    <td>{{ $f['intentos'] }}</td>
    <td>{{ $f['fallos'] }}</td>
    <td><b>{{ $f['porcentaje'] }}%</b></td>
  </tr>
@empty
  <tr><td colspan="6">Todavía no hay respuestas suficientes para medir.</td></tr>
@endforelse
</table></div>
@endsection
