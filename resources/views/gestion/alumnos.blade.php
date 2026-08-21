@extends('gestion.base')
@section('g-titulo', 'Alumnos')

@section('g-cuerpo')
<div class="tabla-scroll"><table class="g-tabla">
<tr><th>Nombre</th><th>Correo</th><th>Alta</th><th>Vía</th><th>Verificado</th><th>Hechas</th><th>Última actividad</th></tr>
@forelse ($alumnos as $a)
  <tr>
    <td>{{ $a->name }}</td>
    <td>{{ $a->email }}</td>
    <td>{{ $a->created_at->format('d/m/y') }}</td>
    <td>{{ $a->google_id ? 'Google' : 'correo' }}</td>
    <td>{{ $a->email_verified_at ? 'sí' : '—' }}</td>
    <td>{{ $a->hechas }}</td>
    <td>{{ $a->ultima_actividad ? \Carbon\Carbon::parse($a->ultima_actividad)->format('d/m/y H:i') : '—' }}</td>
  </tr>
@empty
  <tr><td colspan="7">Aún no hay alumnos registrados.</td></tr>
@endforelse
</table></div>
@endsection
