@extends('layout')
@section('titulo', 'Gestión')

@section('cuerpo')
<div class="ancho">
  <div class="nivel-cab">
    <div class="n">Gestión · sin enlaces públicos</div>
    <h2>@yield('g-titulo')</h2>
  </div>

  <nav class="g-nav">
    <a href="{{ route('gestion.panel') }}"      class="{{ request()->is('gestion') ? 'act' : '' }}">Panel</a>
    <a href="{{ route('gestion.alumnos') }}"    class="{{ request()->is('gestion/alumnos') ? 'act' : '' }}">Alumnos</a>
    <a href="{{ route('gestion.embudo') }}"     class="{{ request()->is('gestion/embudo') ? 'act' : '' }}">Embudo</a>
    <a href="{{ route('gestion.fallos') }}"     class="{{ request()->is('gestion/fallos') ? 'act' : '' }}">Dónde fallan</a>
    <a href="{{ route('gestion.materiales') }}" class="{{ request()->is('gestion/materiales') ? 'act' : '' }}">Materiales</a>
    <a href="{{ route('gestion.donaciones') }}" class="{{ request()->is('gestion/donaciones') ? 'act' : '' }}">Donaciones</a>
  </nav>

  @yield('g-cuerpo')
</div>
@endsection
