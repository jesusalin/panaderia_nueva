@extends('layouts.app')
@section('title', 'Reporte de Kardex')
@section('breadcrumb') <li class="breadcrumb-item active">Reporte de Kardex</li> @endsection

@push('styles')
<style>
    .rp-stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 1rem; margin-bottom: 1.2rem; }
    .rp-stat { background: var(--m-surface); border-radius: 14px; padding: 1rem 1.2rem; box-shadow: 0 2px 12px rgba(0,0,0,.05); border-left: 4px solid var(--m-border-strong); }
    .rp-stat.acc-info    { border-left-color: #3498db; }
    .rp-stat.acc-success { border-left-color: #2ecc71; }
    .rp-stat.acc-danger  { border-left-color: #e74c3c; }
    .rp-stat.acc-warn    { border-left-color: #f39c12; }
    .rp-stat .rp-label { font-size: .72rem; font-weight: 700; color: var(--m-muted); text-transform: uppercase; letter-spacing: .03em; }
    .rp-stat .rp-value { font-size: 1.3rem; font-weight: 800; color: var(--m-text); margin-top: .2rem; }
    .rp-stat .rp-sub { font-size: .74rem; color: var(--m-muted); }
    body.dark-mode .rp-stat { background: #1f1f33; }
    body.dark-mode .rp-stat .rp-value { color: #f0f0f7; }

    .rp-filter { border: none; border-radius: 14px; box-shadow: 0 2px 12px rgba(0,0,0,.05); padding: 1rem 1.3rem; margin-bottom: 1.2rem; }
    .rp-filter .form-control { border-radius: 10px; border: 1.5px solid var(--m-border); }
    body.dark-mode .rp-filter .form-control { background: #24243b; border-color: #33334d; color: #e4e4ef; }

    .kd-chips { display: flex; flex-wrap: wrap; gap: .4rem; margin-top: .8rem; align-items: center; }
    .kd-chip {
        padding: .3rem .8rem; border-radius: 20px; font-size: .8rem; font-weight: 700; text-decoration: none;
        background: var(--m-surface-3); color: var(--m-muted); border: 1.5px solid transparent; transition: all .15s;
    }
    .kd-chip:hover { background: #e7e7ec; color: var(--m-text); text-decoration: none; }
    .kd-chip.active { background: rgba(181,69,27,.12); color: var(--m-brand-text); border-color: rgba(181,69,27,.35); }
    body.dark-mode .kd-chip { background: #2c2c44; color: #b0b0cc; }
    body.dark-mode .kd-chip:hover { background: #33334d; color: #fff; }
    body.dark-mode .kd-chip.active { background: rgba(181,69,27,.25); color: #ff9d6e; border-color: rgba(255,157,110,.4); }

    .kd-estado { display: inline-flex; align-items: center; gap: .4rem; font-size: .8rem; font-weight: 700; padding: .3rem .75rem; border-radius: 20px; }
    .kd-estado.ok   { background: rgba(46,204,113,.14); color: var(--m-success-text); }
    .kd-estado.warn { background: rgba(243,156,18,.16); color: var(--m-warning-text); }
    body.dark-mode .kd-estado.ok   { color: #6ee7a5; }
    body.dark-mode .kd-estado.warn { color: #ffc673; }

    .kd-motivos { display: flex; flex-wrap: wrap; gap: .5rem; margin-bottom: 1.2rem; }
    .kd-motivo { background: var(--m-surface); border-radius: 10px; padding: .45rem .8rem; font-size: .8rem; box-shadow: 0 1px 6px rgba(0,0,0,.05); }
    .kd-motivo b { color: var(--m-text); }
    body.dark-mode .kd-motivo { background: #1f1f33; color: #b0b0cc; }
    body.dark-mode .kd-motivo b { color: #f0f0f7; }

    .kd-row-inicial td { background: var(--m-surface-2); font-style: italic; font-weight: 700; }
    body.dark-mode .kd-row-inicial td { background: #24243b; }
</style>
@endpush

@section('content')

@php
    $motivos = \App\Http\Controllers\ReportesController::MOTIVOS_KARDEX;
    $queryBase = request()->except(['periodo', 'fecha_desde', 'fecha_hasta']);
    $itemActual = $tipoItem && $idItem ? $tipoItem . '-' . $idItem : '';
@endphp

<div class="page-toolbar">
    <div>
        <h2><i class="fas fa-file-invoice mr-2 text-info"></i>Reporte de Kardex</h2>
        <p>Ficha cronológica de entradas, salidas y saldo de un insumo o producto</p>
    </div>
    @if($item)
    <div class="toolbar-actions">
        @if($tipoItem === 'materia')
            @if(($stockBajo || !$cuadra) && auth()->user()->hasPermiso('inventario.conteo'))
            <a href="{{ route('conteo-fisico.index') }}" class="btn btn-warning"
               title="{{ !$cuadra ? 'El kardex no cuadra: verifica con un conteo físico' : 'Stock bajo: confirma cuánto hay realmente' }}">
                <i class="fas fa-clipboard-check mr-1"></i>Registrar conteo físico
            </a>
            @endif
            @if(auth()->user()->hasPermiso('inventario.materia_prima'))
            <a href="{{ route('materia-prima.ajuste', $item) }}" class="btn btn-outline-warning">
                <i class="fas fa-balance-scale mr-1"></i>Ajustar stock
            </a>
            @endif
        @endif
        <a href="{{ route('reportes.kardex.pdf', request()->query()) }}" target="_blank" class="btn btn-outline-secondary js-exp-kardex">
            <i class="fas fa-file-pdf mr-1"></i>PDF
        </a>
        <a href="{{ route('reportes.kardex.excel', request()->query()) }}" class="btn btn-outline-success js-exp-kardex">
            <i class="fas fa-file-excel mr-1"></i>Excel
        </a>
    </div>
    @endif
</div>

<div class="card rp-filter">
    <form method="GET" id="formKardex" class="form-inline flex-wrap" style="gap:.6rem;">
        <input type="hidden" name="periodo" value="{{ $periodo !== 'personalizado' ? $periodo : '' }}">

        {{-- Buscador con sugerencias (ver layout): al hacer clic muestra todos los insumos y
             productos y se va achicando mientras se escribe; el valor real viaja en "item". --}}
        <input type="hidden" name="item" id="itemValor" value="{{ $itemActual }}">
        <div class="search-box" style="min-width:320px;">
            <i class="fas fa-search"></i>
            <input type="text" id="itemBuscar" class="form-control" autocomplete="off"
                   data-sugerencias="kardex" data-sugerencias-lista="itemsKardex" data-sugerencias-valor="#itemValor"
                   placeholder="Busca un insumo o producto…"
                   value="{{ $item ? $item->nombre : '' }}">
        </div>
        @php
            $itemsKardex = $materias->map(fn ($m) => ['t' => $m->nombre, 's' => 'Insumo · stock ' . \App\Support\Cantidad::conUnidad($m->stock_actual, $m->unidad->abreviatura ?? ''), 'i' => 'fa-wheat-awn', 'v' => 'materia-' . $m->id])
                ->merge($productos->map(fn ($p) => ['t' => $p->nombre, 's' => 'Producto · stock ' . $p->stock_actual, 'i' => 'fa-bread-slice', 'v' => 'producto-' . $p->id]))
                ->values();
        @endphp
        <script type="application/json" id="itemsKardex">@json($itemsKardex)</script>

        <details class="ml-1" {{ $periodo === 'personalizado' ? 'open' : '' }}>
            <summary class="text-muted small" style="cursor:pointer;">Fechas exactas</summary>
            <div class="form-inline mt-2" style="gap:.4rem;">
                <input type="date" name="fecha_desde" class="form-control" value="{{ request('fecha_desde') }}" title="Desde">
                <input type="date" name="fecha_hasta" class="form-control" value="{{ request('fecha_hasta') }}" title="Hasta">
                <button class="btn btn-primary"><i class="fas fa-search mr-1"></i>Aplicar</button>
            </div>
        </details>
    </form>

    @if($item)
    <div class="kd-chips">
        <span class="small text-muted mr-1"><i class="far fa-calendar-alt mr-1"></i>Periodo:</span>
        @foreach(\App\Http\Controllers\ReportesController::PERIODOS as $clave => $etiqueta)
            <a class="kd-chip {{ $periodo === (string)$clave ? 'active' : '' }}"
               href="{{ route('reportes.kardex', array_merge($queryBase, ['periodo' => $clave])) }}">{{ $etiqueta }}</a>
        @endforeach
        @if($periodo === 'personalizado')
            <span class="kd-chip active">
                {{ $desde ? $desde->format('d/m/Y') : 'inicio' }} — {{ $hasta ? $hasta->format('d/m/Y') : 'hoy' }}
            </span>
        @endif
    </div>
    @endif
</div>

@if($item)
    <div class="rp-stat-grid">
        <div class="rp-stat">
            <div class="rp-label">Saldo inicial</div>
            <div class="rp-value">{{ \App\Support\Cantidad::conUnidad($saldoInicial, $unidad) }}</div>
            <div class="rp-sub">al empezar el periodo</div>
        </div>
        <div class="rp-stat acc-success">
            <div class="rp-label">Entradas</div>
            <div class="rp-value">+ {{ \App\Support\Cantidad::conUnidad($totalEntradas, $unidad) }}</div>
        </div>
        <div class="rp-stat acc-danger">
            <div class="rp-label">Salidas</div>
            <div class="rp-value">− {{ \App\Support\Cantidad::conUnidad($totalSalidas, $unidad) }}</div>
        </div>
        <div class="rp-stat {{ $stockBajo ? 'acc-danger' : 'acc-info' }}">
            <div class="rp-label">Saldo actual
                @if($stockBajo)
                    <span class="badge-soft badge-soft-danger ml-1" style="font-size:.62rem;vertical-align:middle;">
                        <i class="fas fa-triangle-exclamation mr-1"></i>Stock bajo
                    </span>
                @endif
            </div>
            <div class="rp-value">{{ \App\Support\Cantidad::conUnidad($item->stock_actual, $unidad) }}</div>
            <div class="rp-sub">mínimo: {{ \App\Support\Cantidad::conUnidad($item->stock_minimo, $unidad) }}</div>
        </div>
        <div class="rp-stat acc-warn">
            <div class="rp-label">Valor del saldo</div>
            <div class="rp-value">S/ {{ number_format($item->stock_actual * $costo, 2) }}</div>
            <div class="rp-sub">a S/ {{ number_format($costo, 2) }} c/u</div>
        </div>
    </div>

    <div class="mb-3">
        @if($cuadra)
            <span class="kd-estado ok"><i class="fas fa-check-circle"></i>El kardex cuadra: saldo inicial + entradas − salidas = saldo final</span>
        @else
            <span class="kd-estado warn"><i class="fas fa-exclamation-triangle"></i>No cuadra: hay una diferencia en el periodo. Revisa los movimientos o haz un conteo físico.</span>
        @endif
    </div>

    @if($porMotivo->isNotEmpty())
    <div class="kd-motivos">
        @foreach($porMotivo as $motivo => $d)
            <div class="kd-motivo">
                {{ $motivos[$motivo] ?? ucfirst(str_replace('_', ' ', $motivo)) }}:
                @if($d['entrada'] > 0)<b class="text-success">+{{ \App\Support\Cantidad::conUnidad($d['entrada'], $unidad) }}</b>@endif
                @if($d['salida'] > 0)<b class="text-danger">−{{ \App\Support\Cantidad::conUnidad($d['salida'], $unidad) }}</b>@endif
                <span class="text-muted">({{ $d['cuenta'] }})</span>
            </div>
        @endforeach
    </div>
    @endif

    @if($movimientos->count() >= 2)
    @php
        $chartLabels = collect(['Inicio'])->concat($movimientos->map(fn($m) => $m->created_at->format('d/m H:i')))->all();
        $chartSaldos = collect([(float) $saldoInicial])->concat($movimientos->map(fn($m) => (float) $m->stock_despues))->all();
    @endphp
    <div class="card rp-filter" style="padding:1rem 1.3rem;">
        <div class="rp-label mb-2" style="font-size:.72rem;font-weight:700;color: var(--m-muted);text-transform:uppercase;letter-spacing:.03em;">
            Evolución del saldo ({{ $unidad }}) — la línea punteada es el stock mínimo
        </div>
        <div style="height:220px;"><canvas id="chartSaldo"></canvas></div>
    </div>
    @endif

    @if($movimientos->count() > 0)
    <p class="small text-muted mb-2"><i class="fas fa-hand-pointer mr-1"></i>Toca el título de una columna para ordenar; usa el buscador y los filtros para ver solo lo que necesitas. El PDF y el Excel salen igual.</p>
    <div class="table-card">
        <table class="table table-modern mb-0" data-tabla="kardex" data-exportar=".js-exp-kardex">
            <thead>
                <tr>
                    <th data-clave="fecha">Fecha</th>
                    <th data-clave="tipo" data-filtro>Tipo</th>
                    <th data-clave="motivo" data-filtro>Motivo</th>
                    <th data-clave="entrada" class="text-right">Entrada</th>
                    <th data-clave="salida" class="text-right">Salida</th>
                    <th data-clave="saldo" class="text-right">Saldo</th>
                    <th data-clave="usuario" data-filtro>Usuario</th>
                    <th data-clave="detalle">Detalle</th>
                </tr>
            </thead>
            <tbody>
                <tr class="kd-row-inicial" data-fijo>
                    <td colspan="5">Saldo inicial</td>
                    <td class="text-right">{{ \App\Support\Cantidad::conUnidad($saldoInicial, $unidad) }}</td>
                    <td colspan="2"></td>
                </tr>
                @foreach($movimientos as $mov)
                <tr>
                    <td data-v="{{ $mov->created_at->timestamp * 1000000 + $mov->id }}">{{ $mov->created_at->format('d/m/Y H:i') }}</td>
                    <td data-f="{{ $mov->tipo_txt }}">
                        <span class="badge-soft {{ $mov->es_ajuste ? 'badge-soft-warning' : ($mov->entrada > 0 ? 'badge-soft-success' : 'badge-soft-danger') }}">{{ $mov->tipo_txt }}</span>
                    </td>
                    <td class="text-muted small" data-f="{{ $mov->motivo_txt }}">{{ $mov->motivo_txt }}</td>
                    <td class="text-right text-success font-weight-bold" data-v="{{ (float) $mov->entrada }}">{{ $mov->entrada > 0 ? '+'.\App\Support\Cantidad::conUnidad($mov->entrada, $unidad) : '' }}</td>
                    <td class="text-right text-danger font-weight-bold" data-v="{{ (float) $mov->salida }}">{{ $mov->salida > 0 ? '−'.\App\Support\Cantidad::conUnidad($mov->salida, $unidad) : '' }}</td>
                    <td class="text-right font-weight-bold" data-v="{{ (float) $mov->stock_despues }}">{{ \App\Support\Cantidad::conUnidad($mov->stock_despues, $unidad) }}</td>
                    <td class="text-muted small" data-f="{{ $mov->usuario_txt }}">{{ $mov->usuario_txt }}</td>
                    <td class="text-muted small">{{ $mov->observacion }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @else
    <div class="empty-state">
        <i class="fas fa-file-invoice"></i>
        <p>Este ítem no tiene movimientos en el periodo seleccionado.<br>
           <a href="{{ route('reportes.kardex', array_merge($queryBase, ['periodo' => 'todo'])) }}">Ver todo el historial</a></p>
    </div>
    @endif
@else
<div class="empty-state">
    <i class="fas fa-hand-pointer"></i>
    <p>Elige un insumo o producto arriba y su kardex aparece al instante.</p>
</div>
@endif

@endsection

@push('scripts')
<script>
    // El buscador del ítem usa las sugerencias del layout (data-sugerencias).
</script>
@if($item && $movimientos->count() >= 2)
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    (function () {
        if (typeof Chart === 'undefined') return;   // sin internet no se puede cargar el gráfico; lo demás sigue funcionando
        const oscuro = document.body.classList.contains('dark-mode');
        const texto  = oscuro ? '#b0b0cc' : '#6c757d';
        const rejilla = oscuro ? 'rgba(255,255,255,.08)' : 'rgba(0,0,0,.06)';
        const etiquetas = @json($chartLabels);
        const saldos    = @json($chartSaldos);
        const minimo    = {{ (float) $item->stock_minimo }};

        new Chart(document.getElementById('chartSaldo'), {
            type: 'line',
            data: {
                labels: etiquetas,
                datasets: [
                    { label: 'Saldo', data: saldos, borderColor: '#b5451b', backgroundColor: 'rgba(181,69,27,.12)',
                      fill: true, tension: .25, stepped: false, pointRadius: 3 },
                    { label: 'Stock mínimo', data: etiquetas.map(() => minimo), borderColor: '#e74c3c',
                      borderDash: [6, 5], pointRadius: 0, fill: false }
                ]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { labels: { color: texto } } },
                scales: {
                    x: { ticks: { color: texto, maxRotation: 0, autoSkip: true }, grid: { color: rejilla } },
                    y: { beginAtZero: true, ticks: { color: texto }, grid: { color: rejilla } }
                }
            }
        });
    })();
</script>
@endif
@endpush
