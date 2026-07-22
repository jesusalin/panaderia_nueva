@extends('layouts.app')
@section('title', 'Historial de Conteos')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('conteo-fisico.index') }}">Conteo Físico</a></li>
    <li class="breadcrumb-item active">Historial</li>
@endsection

@push('styles')
<style>
    .cf-exact-badge {
        display: inline-flex; align-items: center; justify-content: center; min-width: 52px; padding: .25rem .5rem;
        border-radius: 8px; font-weight: 800; font-size: .85rem;
    }
    .cf-exact-badge.good { background: rgba(46,204,113,.15); color: #1e8e5a; }
    .cf-exact-badge.mid  { background: rgba(243,156,18,.15); color: #a5680d; }
    .cf-exact-badge.bad  { background: rgba(231,76,60,.15); color: #b3261e; }
    body.dark-mode .cf-exact-badge.good { color: #6ee7a5; }
    body.dark-mode .cf-exact-badge.mid  { color: #ffc673; }
    body.dark-mode .cf-exact-badge.bad  { color: #ff9b8f; }

    /* Pestañas (mismo patrón que Órdenes Automáticas) */
    .oa-tabs { display: flex; gap: .5rem; margin-bottom: 1.2rem; flex-wrap: wrap; }
    .oa-tab {
        display: flex; align-items: center; gap: .45rem; padding: .5rem 1rem; border-radius: 24px;
        background: #fff; border: 1.5px solid #e9ecef; color: #6c757d; font-weight: 700; font-size: .83rem; text-decoration: none;
        transition: all .12s;
    }
    .oa-tab:hover { border-color: #d4a98f; color: #b5451b; text-decoration: none; }
    .oa-tab.active { background: #b5451b; border-color: #b5451b; color: #fff; }
    .oa-tab .oa-count { background: rgba(0,0,0,.12); border-radius: 10px; padding: 0 .45rem; font-size: .74rem; min-width: 20px; text-align: center; }
    .oa-tab.active .oa-count { background: rgba(255,255,255,.25); }
    body.dark-mode .oa-tab { background: #1f1f33; border-color: #33334d; color: #9a9ac0; }
    body.dark-mode .oa-tab:hover { border-color: #b5451b; color: #ff9d6e; }
    body.dark-mode .oa-tab.active { background: #b5451b; border-color: #b5451b; color: #fff; }

    #cfHistorialContainer { transition: opacity .15s ease; }
    #cfHistorialContainer.cargando { opacity: .4; pointer-events: none; }
</style>
@endpush

@section('content')

<div class="page-toolbar">
    <div>
        <h2><i class="fas fa-history mr-2 text-info"></i>Historial de Conteos Físicos</h2>
        <p>Cada jornada de conteo y su exactitud, para tu evidencia de tesis (OE2)</p>
    </div>
    <div class="toolbar-actions">
        <a href="{{ route('conteo-fisico.index') }}" class="btn btn-primary">
            <i class="fas fa-plus mr-1"></i>Nuevo Conteo
        </a>
    </div>
</div>

<div class="oa-tabs" id="cfTabs">
    <a href="{{ route('conteo-fisico.historial') }}" class="oa-tab {{ !request('filtro') ? 'active' : '' }}">
        <i class="fas fa-layer-group"></i>Todas
        <span class="oa-count">{{ $conteos['todas'] }}</span>
    </a>
    <a href="{{ route('conteo-fisico.historial', ['filtro' => 'con_ajustes']) }}" class="oa-tab {{ request('filtro') === 'con_ajustes' ? 'active' : '' }}">
        <i class="fas fa-triangle-exclamation"></i>Con ajustes
        <span class="oa-count">{{ $conteos['con_ajustes'] }}</span>
    </a>
    <a href="{{ route('conteo-fisico.historial', ['filtro' => 'sin_diferencias']) }}" class="oa-tab {{ request('filtro') === 'sin_diferencias' ? 'active' : '' }}">
        <i class="fas fa-check"></i>Sin diferencias
        <span class="oa-count">{{ $conteos['sin_diferencias'] }}</span>
    </a>
</div>

<div id="cfHistorialContainer">
    @include('conteo-fisico._lista-historial')
</div>

@endsection

@push('scripts')
<script src="{{ asset('js/ajax-lista.js') }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        initAjaxLista({
            contenedor: 'cfHistorialContainer',
            tabs: 'cfTabs',
            tabClase: 'oa-tab',
        });
    });
</script>
@endpush
