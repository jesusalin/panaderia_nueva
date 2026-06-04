@extends('layouts.app')
@section('title', 'Dashboard')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.min.css">
@endpush

@section('content')

{{-- Fila 1: KPIs principales --}}
<div class="row">
    <div class="col-lg-3 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>S/ {{ number_format($ventasHoy, 2) }}</h3>
                <p>Ventas de Hoy</p>
            </div>
            <div class="icon"><i class="fas fa-cash-register"></i></div>
            <a href="{{ route('ventas.index') }}" class="small-box-footer">Ver ventas <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3>S/ {{ number_format($ventasMes, 2) }}</h3>
                <p>Ventas del Mes</p>
            </div>
            <div class="icon"><i class="fas fa-chart-line"></i></div>
            <a href="{{ route('ventas.index') }}" class="small-box-footer">Ver detalle <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3>S/ {{ number_format($comprasMes, 2) }}</h3>
                <p>Compras del Mes</p>
            </div>
            <div class="icon"><i class="fas fa-shopping-cart"></i></div>
            <a href="{{ route('compras.index') }}" class="small-box-footer">Ver compras <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box {{ $gananciaMes >= 0 ? 'bg-success' : 'bg-danger' }}">
            <div class="inner">
                <h3>S/ {{ number_format($gananciaMes, 2) }}</h3>
                <p>Ganancia Bruta del Mes</p>
            </div>
            <div class="icon"><i class="fas fa-coins"></i></div>
            <a href="{{ route('kardex.rotacion') }}" class="small-box-footer">Ver rotación <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
</div>

{{-- Fila 2: KPIs de tesis --}}
<div class="row">
    <div class="col-lg-3 col-6">
        <div class="small-box {{ ($productosStockBajo + $materiaStockBaja) > 0 ? 'bg-danger' : 'bg-secondary' }}">
            <div class="inner">
                <h3>{{ $productosStockBajo + $materiaStockBaja }}</h3>
                <p>Alertas de Stock Bajo</p>
            </div>
            <div class="icon"><i class="fas fa-exclamation-triangle"></i></div>
            <a href="{{ route('materia-prima.index') }}" class="small-box-footer">Revisar <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box {{ $exactitudInventario >= 90 ? 'bg-success' : ($exactitudInventario >= 70 ? 'bg-warning' : 'bg-danger') }}">
            <div class="inner">
                <h3>{{ $exactitudInventario }}%</h3>
                <p>Exactitud del Inventario</p>
            </div>
            <div class="icon"><i class="fas fa-bullseye"></i></div>
            <a href="{{ route('kardex.index') }}" class="small-box-footer">Detalle de Inventario <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-primary">
            <div class="inner">
                <h3>{{ $registrosHoy }}</h3>
                <p>Registros Procesados Hoy</p>
            </div>
            <div class="icon"><i class="fas fa-database"></i></div>
            <a href="{{ route('movimientos.index') }}" class="small-box-footer">Ver movimientos <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box bg-teal" style="background-color:#20c997!important">
            <div class="inner">
                <h3>{{ $rotacionStock->count() }}</h3>
                <p>Productos en Rotación</p>
            </div>
            <div class="icon"><i class="fas fa-sync-alt"></i></div>
            <a href="{{ route('kardex.rotacion') }}" class="small-box-footer">Ver reporte <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
</div>

<div class="row">
    {{-- Gráfico de ventas --}}
    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="card-title mb-0"><i class="fas fa-chart-bar mr-2 text-primary"></i>Ventas últimos 7 días</h5>
            </div>
            <div class="card-body">
                <canvas id="chartVentas" height="120"></canvas>
            </div>
        </div>
    </div>

    {{-- Top productos --}}
    <div class="col-md-4">
        <div class="card">
            <div class="card-header bg-white">
                <h5 class="card-title mb-0"><i class="fas fa-trophy mr-2 text-warning"></i>Más vendidos (mes)</h5>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    @forelse($topProductos as $i => $p)
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span>
                            <span class="badge badge-{{ ['primary','success','info','warning','secondary'][$i] }} mr-2">{{ $i+1 }}</span>
                            {{ $p->nombre }}
                        </span>
                        <span class="badge badge-light">{{ $p->total_vendido }} uds</span>
                    </li>
                    @empty
                    <li class="list-group-item text-muted text-center">Sin ventas este mes</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</div>

{{-- Rotación de stock y últimas ventas --}}
<div class="row">
    {{-- Rotación rápida --}}
    <div class="col-md-5">
        <div class="card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0"><i class="fas fa-sync-alt mr-2 text-success"></i>Rotación de Stock (mes)</h5>
                <a href="{{ route('kardex.rotacion') }}" class="btn btn-sm btn-outline-success">Ver completo</a>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0 table-sm">
                    <thead class="bg-light">
                        <tr>
                            <th>Producto</th>
                            <th class="text-center">Vendido</th>
                            <th class="text-center">Stock</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rotacionStock as $p)
                        <tr>
                            <td>{{ $p->nombre }}</td>
                            <td class="text-center font-weight-bold text-success">{{ $p->total_vendido }}</td>
                            <td class="text-center">{{ $p->stock_actual }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="3" class="text-center text-muted py-3">Sin ventas este mes</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Últimas ventas --}}
    <div class="col-md-7">
        <div class="card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0"><i class="fas fa-history mr-2 text-success"></i>Últimas ventas</h5>
                <a href="{{ route('ventas.create') }}" class="btn btn-sm btn-success">
                    <i class="fas fa-plus mr-1"></i>Nueva venta
                </a>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th>Nro.</th><th>Cliente</th><th>Pago</th><th>Total</th><th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($ultimasVentas as $v)
                        <tr>
                            <td><a href="{{ route('ventas.show', $v) }}">{{ $v->numero_venta }}</a></td>
                            <td>{{ $v->cliente->nombre ?? 'General' }}</td>
                            <td><span class="badge badge-light">{{ ucfirst($v->tipo_pago) }}</span></td>
                            <td class="font-weight-bold">S/ {{ number_format($v->total, 2) }}</td>
                            <td>
                                <span class="badge badge-{{ $v->estado === 'completada' ? 'success' : 'danger' }}">
                                    {{ ucfirst($v->estado) }}
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">No hay ventas aún</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
const labels = @json($ventasSemana->pluck('fecha'));
const data   = @json($ventasSemana->pluck('total'));

new Chart(document.getElementById('chartVentas'), {
    type: 'bar',
    data: {
        labels,
        datasets: [{
            label: 'Ventas (S/)',
            data,
            backgroundColor: 'rgba(181,69,27,0.7)',
            borderColor: 'rgba(181,69,27,1)',
            borderWidth: 2,
            borderRadius: 6,
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, ticks: { callback: v => 'S/ ' + v } } }
    }
});
</script>
@endpush
