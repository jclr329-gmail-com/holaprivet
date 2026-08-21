@extends('gestion.base')
@section('g-titulo', 'Materiales')

@section('g-cuerpo')
@if (session('estado'))<p class="g-ok">{{ session('estado') }}</p>@endif

<div class="tabla-scroll"><table class="g-tabla">
<tr><th>Cat.</th><th>Título</th><th>Archivo / URL</th><th>Orden</th><th>Visible</th><th></th></tr>
@foreach ($recursos as $r)
  <tr class="{{ $r->categoria === 'descarga' && ! $r->existe ? 'g-mal' : '' }}">
    <td>{{ $r->categoria }}</td>
    <td>{{ $r->titulo }}</td>
    <td>{{ $r->archivo ?? $r->url }}{{ $r->categoria === 'descarga' && ! $r->existe ? ' · ¡falta el PDF!' : '' }}</td>
    <td>{{ $r->orden }}</td>
    <td>{{ $r->visible ? 'sí' : 'no' }}</td>
    <td>
      <form method="POST" action="{{ route('gestion.materiales.borrar') }}"
            onsubmit="return confirm('¿Quitar «{{ $r->titulo }}» de la lista?')">
        @csrf
        <input type="hidden" name="id" value="{{ $r->id }}">
        <button class="g-borrar" type="submit">quitar</button>
      </form>
    </td>
  </tr>
@endforeach
</table></div>

<h3 class="g-sub">Añadir o actualizar</h3>
<p class="g-nota">Para editar una entrada existente, escribe su ID (columna
implícita: pasa el ratón por «quitar»). Para una nueva, deja el ID vacío. El
PDF se sube directo a <code>descargas/</code> — máximo 20 MB.</p>

<form method="POST" action="{{ route('gestion.materiales.guardar') }}"
      enctype="multipart/form-data" class="forma g-forma">
  @csrf
  <div class="g-fila">
    <label>ID (vacío = nuevo)<input type="number" name="id" min="1"></label>
    <label>Categoría
      <select name="categoria">
        <option value="descarga">descarga (PDF)</option>
        <option value="enlace">enlace externo</option>
      </select>
    </label>
    <label>Orden<input type="number" name="orden" value="10" required></label>
    <label class="g-check"><input type="checkbox" name="visible" value="1" checked> visible</label>
  </div>
  <label>Título<input type="text" name="titulo" required maxlength="190"></label>
  <label>Nota (descripción en ruso)<input type="text" name="nota" maxlength="500"></label>
  <label>URL (solo enlaces)<input type="text" name="url" maxlength="300" placeholder="https://…"></label>
  <label>PDF (solo descargas)<input type="file" name="pdf" accept="application/pdf"></label>
  @if ($errors->any())<p class="fallo">{{ $errors->first() }}</p>@endif
  <button class="boton" type="submit">Guardar</button>
</form>
@endsection
