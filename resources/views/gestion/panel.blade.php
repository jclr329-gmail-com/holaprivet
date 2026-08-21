@extends('gestion.base')
@section('g-titulo', 'Panel')

@section('g-cuerpo')
<div class="g-cifras">
  <div><b>{{ $alumnos }}</b><span>alumnos</span></div>
  <div><b>{{ $verificados }}</b><span>verificados</span></div>
  <div><b>{{ $nuevosSemana }}</b><span>nuevos (7 días)</span></div>
  <div><b>{{ $activosSemana }}</b><span>activos (7 días)</span></div>
  <div><b>{{ $completadas }}</b><span>piezas completadas</span></div>
  <div><b>{{ $respuestas }}</b><span>piezas con respuestas</span></div>
</div>
<p class="g-nota">Los datos cubren a los alumnos con cuenta; quien estudia sin
registrarse vive solo en su navegador.</p>
@endsection
