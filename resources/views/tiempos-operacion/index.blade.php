@extends('layouts.app')
@section('title', 'Tiempos por Operación')
@section('breadcrumb') <li class="breadcrumb-item active">Tiempos por Operación</li> @endsection

@section('content')

<div class="alert alert-info alert-dismissible fade show">
    <i class="fas fa-info-circle mr-2"></i>
    Este reporte mide el <strong>OE3 (reducción de tiempos muertos)</strong>: el tiempo que el sistema
    tarda en buscar productos, verificar stock y registrar ventas, comparado contra el
    <strong>tiempo muerto inicial</strong> obtenido en tu ficha de observación (pre-test manual).
    Fórmula: <code>(tiempo inicial - tiempo final) / tiempo final × 100</code>
    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
</div>

{{-- Filtro de fechas --}}
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="form-inline flex-wrap gap-2">
            <label class="mr-2">Desde:</label>
            <input type="date" name="fecha_desde" class="form-control mr-3 mb-2" value="{{ $fechaDesde }}">
            <label class="mr-2">Hasta:</label>
            <input type="date" name="fecha_hasta" class="form-control mr-3 mb-2" value="{{ $fechaHasta }}">
            <button class="btn btn-primary mb-2"><i class="fas fa-filter mr-1"></i>Filtrar</button>
            <a href="{{ route('tiempos-operacion.index') }}" class="btn btn-secondary mb-2">Limpiar</a>
        </form>
    </div>
</div>

{{-- KPIs por operación --}}
<div class="row mb-3">
    @foreach($resultados as $tipo => $r)
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body text-center">
                <h6 class="text-muted text-uppercase" style="font-size:.75rem; letter-spacing:.5px;">{{ $r['etiqueta'] }}</h6>

                <div class="row mt-3">
                    <div class="col-6 border-right">
                        <div class="text-muted small">Tiempo inicial (manual)</div>
                        <div class="h4 mb-0 text-secondary">
                            {{ $r['inicial'] > 0 ? number_format($r['inicial'], 1).'s' : '—' }}
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="text-muted small">Tiempo final (sistema)</div>
                        <div class="h4 mb-0 text-primary">
                            {{ $r['final'] !== null ? number_format($r['final'], 1).'s' : 'Sin datos' }}
                        </div>
                    </div>
                </div>

                <hr>

                @if($r['reduccion'] !== null)
                    <span class="badge badge-{{ $r['reduccion'] >= 0 ? 'success' : 'danger' }} p-2" style="font-size:.95rem;">
                        <i class="fas fa-arrow-{{ $r['reduccion'] >= 0 ? 'down' : 'up' }} mr-1"></i>
                        {{ number_format(abs($r['reduccion']), 1) }}% {{ $r['reduccion'] >= 0 ? 'de reducción' : 'de aumento' }}
                    </span>
                @else
                    <span class="badge badge-light p-2">Falta tiempo inicial o datos del sistema</span>
                @endif

                <div class="text-muted small mt-2">{{ $r['registros'] }} registro(s) del sistema</div>
            </div>
        </div>
    </div>
    @endforeach
</div>

{{-- Gráfico comparativo --}}
<div class="card mb-3">
    <div class="card-header bg-white">
        <h5 class="mb-0"><i class="fas fa-chart-bar mr-2 text-primary"></i>Comparación de tiempos (segundos)</h5>
    </div>
    <div class="card-body">
        <canvas id="chartTiempos" height="90"></canvas>
    </div>
</div>

{{-- Actualizar tiempo muerto inicial (pre-test) --}}
<div class="card">
    <div class="card-header bg-white">
        <h5 class="mb-0"><i class="fas fa-clipboard-list mr-2 text-warning"></i>Actualizar tiempo muerto inicial (pre-test manual)</h5>
    </div>
    <div class="card-body">
        <p class="text-muted small">
            Ingresa aquí, en segundos, el tiempo promedio que tomaba cada operación de forma manual,
            según tu ficha de observación aplicada antes de implementar el sistema.
        </p>
        <form method="POST" action="{{ route('tiempos-operacion.baseline') }}" class="form-inline flex-wrap gap-2">
            @csrf
            @foreach($resultados as $tipo => $r)
                <div class="form-group mr-4 mb-2">
                    <label class="mr-2">{{ $r['etiqueta'] }} (seg.)</label>
                    <input type="number" step="0.1" min="0" name="baseline[{{ $tipo }}]"
                        class="form-control" style="width:110px" value="{{ $r['inicial'] }}">
                </div>
            @endforeach
            <button class="btn btn-warning mb-2"><i class="fas fa-save mr-1"></i>Guardar</button>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
const etiquetas = @json(collect($resultados)->pluck('etiqueta')->values());
const iniciales = @json(collect($resultados)->pluck('inicial')->values());
const finales   = @json(collect($resultados)->map(fn($r) => $r['final'] ?? 0)->values());

new Chart(document.getElementById('chartTiempos'), {
    type: 'bar',
    data: {
        labels: etiquetas,
        datasets: [
            {
                label: 'Tiempo inicial (manual)',
                data: iniciales,
                backgroundColor: 'rgba(108,117,125,0.6)',
                borderColor: 'rgba(108,117,125,1)',
                borderWidth: 2,
                borderRadius: 6,
            },
            {
                label: 'Tiempo final (sistema)',
                data: finales,
                backgroundColor: 'rgba(181,69,27,0.7)',
                borderColor: 'rgba(181,69,27,1)',
                borderWidth: 2,
                borderRadius: 6,
            },
        ]
    },
    options: {
        responsive: true,
        scales: { y: { beginAtZero: true, title: { display: true, text: 'segundos' } } }
    }
});
</script>
@endpush
