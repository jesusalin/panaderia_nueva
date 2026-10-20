@extends('layouts.app')
@section('title', 'Reportes')
@section('breadcrumb') <li class="breadcrumb-item active">Reportes</li> @endsection

@push('styles')
<style>
    .rep-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 1.1rem; }
    .rep-card {
        background: #fff; border-radius: 14px; box-shadow: 0 2px 12px rgba(0,0,0,.05); padding: 1.4rem 1.5rem;
        display: flex; flex-direction: column; gap: .7rem; text-decoration: none; color: inherit;
        border: 1.5px solid transparent; transition: box-shadow .15s, border-color .15s;
    }
    .rep-card:hover { box-shadow: 0 8px 24px rgba(0,0,0,.09); border-color: #e9e4de; text-decoration: none; color: inherit; }
    body.dark-mode .rep-card { background: #1e1e33; }

    .rep-card .rc-icon {
        width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center;
        font-size: 1.25rem; color: #fff; background: linear-gradient(135deg, #1a1a2e, #b5451b);
    }
    .rep-card h5 { margin: 0; font-weight: 800; font-size: 1.02rem; }
    .rep-card p { margin: 0; color: #8a8a9d; font-size: .85rem; line-height: 1.4; }

    .rep-card .rc-alert {
        display: inline-flex; align-items: center; gap: .35rem; font-size: .76rem; font-weight: 700;
        padding: .25rem .6rem; border-radius: 8px; width: fit-content;
    }
    .rc-alert.ok   { background: #e5f6ec; color: #1e8e5a; }
    .rc-alert.warn { background: #fbe9e7; color: #c0392b; }
    body.dark-mode .rc-alert.ok   { background: rgba(30,142,90,.18); color: #6ee7a5; }
    body.dark-mode .rc-alert.warn { background: rgba(192,57,43,.2); color: #ff9d8c; }
</style>
@endpush

@section('content')

<div class="page-toolbar">
    <div>
        <h2><i class="fas fa-chart-pie mr-2 text-primary"></i>Reportes</h2>
        <p>Todo lo que necesitas para revisar y sustentar el estado del inventario, en un solo lugar</p>
    </div>
</div>

<div class="rep-grid">

    <a href="{{ route('reportes.stock', ['stock' => 'bajo']) }}" class="rep-card">
        <div class="rc-icon"><i class="fas fa-warehouse"></i></div>
        <h5>Reporte de Stock</h5>
        <p>Insumos y productos con su cantidad actual y valorización (stock × costo).</p>
        @php $totalBajo = $insumosStockBajo + $productosStockBajo; @endphp
        <span class="rc-alert {{ $totalBajo > 0 ? 'warn' : 'ok' }}">
            <i class="fas {{ $totalBajo > 0 ? 'fa-triangle-exclamation' : 'fa-check' }}"></i>
            {{ $totalBajo > 0 ? "{$totalBajo} ítem(s) con stock bajo" : 'Todo el stock está en orden' }}
        </span>
    </a>

    <a href="{{ route('reportes.kardex') }}" class="rep-card">
        <div class="rc-icon"><i class="fas fa-file-invoice"></i></div>
        <h5>Kardex</h5>
        <p>Ficha cronológica de entradas, salidas y saldo de un insumo o producto puntual.</p>
        <span class="rc-alert ok"><i class="fas fa-clock"></i>{{ $movimientosHoy }} movimiento(s) registrados hoy</span>
    </a>

    <a href="{{ route('tiempos-operacion.index') }}" class="rep-card">
        <div class="rc-icon"><i class="fas fa-stopwatch"></i></div>
        <h5>Tiempos por Operación</h5>
        <p>Compara el tiempo manual (antes) contra el tiempo real del sistema (ahora).</p>
        <span class="rc-alert ok"><i class="fas fa-chart-line"></i>Indicador OE3</span>
    </a>

    @if(auth()->user()->hasModulo('inventario'))
    <a href="{{ route('conteo-fisico.historial') }}" class="rep-card">
        <div class="rc-icon"><i class="fas fa-clipboard-check"></i></div>
        <h5>Conteo Físico</h5>
        <p>Historial de conteos de inventario: exactitud real del sistema vs. lo contado a mano.</p>
        @if($exactitudUltimoConteo !== null)
            <span class="rc-alert {{ $exactitudUltimoConteo >= 90 ? 'ok' : 'warn' }}">
                <i class="fas fa-percent"></i>Último conteo: {{ $exactitudUltimoConteo }}% de exactitud
            </span>
        @else
            <span class="rc-alert warn"><i class="fas fa-triangle-exclamation"></i>Todavía no se ha registrado ningún conteo</span>
        @endif
    </a>
    @endif

    @if(auth()->user()->hasModulo('ventas'))
    <a href="{{ route('ventas.exportar-excel') }}" class="rep-card">
        <div class="rc-icon"><i class="fas fa-file-excel"></i></div>
        <h5>Historial de Ventas</h5>
        <p>Descarga el historial completo de ventas en Excel, listo para llevar evidencia o cuadrar caja.</p>
        <span class="rc-alert ok"><i class="fas fa-download"></i>Exportar ahora</span>
    </a>
    @endif

</div>

@endsection
