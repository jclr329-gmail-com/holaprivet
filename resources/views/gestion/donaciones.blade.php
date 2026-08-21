@extends('gestion.base')
@section('g-titulo', 'Donaciones')

@section('g-cuerpo')
@if (session('estado'))<p class="g-ok">{{ session('estado') }}</p>@endif

<div class="g-cifras">
  <div><b>{{ number_format($totalCents/100, 0, ',', ' ') }} €</b><span>recaudado (pagado)</span></div>
  <div><b>{{ $ocupadas }}</b><span>palabras ocupadas</span></div>
  <div><b>{{ $pendientesModeracion }}</b><span>dedicatorias por moderar</span></div>
</div>

@if ($moderacion->isNotEmpty())
  <h3 class="g-sub">Moderación pendiente</h3>
  <div class="tabla-scroll"><table class="g-tabla">
  <tr><th>Palabra</th><th>Nombre</th><th>Dedicatoria</th><th>Alumno</th><th></th></tr>
  @foreach ($moderacion as $m)
    <tr>
      <td><b>{{ $m->palabra->word }}</b></td>
      <td>{{ $m->display_name }}</td>
      <td>{{ $m->dedication ?? '—' }}</td>
      <td>{{ $m->usuario->email }}</td>
      <td>
        <form method="POST" action="{{ route('gestion.moderar') }}" style="display:inline">
          @csrf<input type="hidden" name="id" value="{{ $m->id }}"><input type="hidden" name="decision" value="aprobada">
          <button class="g-ok" style="border:none;background:none;cursor:pointer">aprobar</button>
        </form>
        <form method="POST" action="{{ route('gestion.moderar') }}" style="display:inline">
          @csrf<input type="hidden" name="id" value="{{ $m->id }}"><input type="hidden" name="decision" value="rechazada">
          <button class="g-borrar" type="submit">rechazar</button>
        </form>
      </td>
    </tr>
  @endforeach
  </table></div>
@endif

<h3 class="g-sub">Pedidos</h3>
<div class="tabla-scroll"><table class="g-tabla">
<tr><th>#</th><th>Fecha</th><th>Alumno</th><th>Palabra</th><th>Importe</th><th>Estado</th></tr>
@forelse ($pedidos as $p)
  <tr>
    <td>{{ $p->id }}</td>
    <td>{{ $p->created_at->format('d/m/y H:i') }}</td>
    <td>{{ $p->usuario->email ?? '—' }}</td>
    <td>{{ $p->items->first()?->palabra?->word ?? '—' }}</td>
    <td>{{ number_format($p->total_cents/100, 2, ',', '') }} €</td>
    <td>{{ $p->status }}</td>
  </tr>
@empty
  <tr><td colspan="6">Aún no hay pedidos.</td></tr>
@endforelse
</table></div>
@endsection
