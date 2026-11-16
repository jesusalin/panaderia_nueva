{{--
    Paginación con selector de cantidad por página.
    Uso: @include('partials.paginacion', ['p' => $productos, 'defecto' => 12, 'nombre' => 'productos'])
    "Todos" muestra hasta App\Support\PorPagina::TODOS registros en una sola página.
--}}
@php
    $defecto = $defecto ?? 12;
    $nombre = $nombre ?? 'registros';
    $actual = $p->perPage() >= \App\Support\PorPagina::TODOS ? 'todos' : $p->perPage();
    $opciones = array_merge(\App\Support\PorPagina::opciones($defecto), ['todos']);
@endphp
@if($p->total() > $defecto || $actual === 'todos')
<div class="pp-barra">
    <div class="pp-ver" role="group" aria-label="Cuántos ver por página">
        <span>Ver</span>
        @foreach($opciones as $op)
            @if($op === 'todos' || $op < $p->total() || $op == $actual || $loop->first)
                <a href="{{ request()->fullUrlWithQuery(['por' => $op, 'page' => null]) }}"
                   class="{{ (string) $actual === (string) $op ? 'activo' : '' }}"
                   @if((string) $actual === (string) $op) aria-current="true" @endif>{{ $op === 'todos' ? 'Todos' : $op }}</a>
            @endif
        @endforeach
        <span class="pp-de">de {{ $p->total() }} {{ $nombre }}</span>
    </div>
</div>
@endif
<div class="mt-2">{{ $p->links() }}</div>
@once
<style>
    .pp-barra { display: flex; flex-wrap: wrap; align-items: center; gap: .6rem; margin-top: 1rem; }
    .pp-ver { display: inline-flex; align-items: center; gap: .2rem; background: var(--m-surface-3); border-radius: 22px; padding: .2rem; }
    .pp-ver span { font-size: .78rem; font-weight: 700; color: var(--m-muted); padding: 0 .5rem 0 .6rem; }
    .pp-ver span.pp-de { font-weight: 600; padding: 0 .7rem 0 .4rem; }
    .pp-ver a { font-size: .82rem; font-weight: 700; color: var(--m-text-2); padding: .25rem .7rem; border-radius: 18px; text-decoration: none !important; }
    .pp-ver a:hover { background: var(--m-surface); color: var(--m-text); }
    .pp-ver a.activo { background: #b5451b; color: #fff; }
    body.dark-mode .pp-ver { background: #2c2c44; }
    body.dark-mode .pp-ver a { color: #b0b0cc; }
    body.dark-mode .pp-ver a:hover { background: #33334d; color: #fff; }
    body.dark-mode .pp-ver a.activo { background: #b5451b; color: #fff; }
</style>
@endonce
