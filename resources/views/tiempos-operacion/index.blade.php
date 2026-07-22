@extends('layouts.app')
@section('title', 'Tiempos por Operación')
@section('breadcrumb') <li class="breadcrumb-item active">Tiempos por Operación</li> @endsection

@push('styles')
<style>
    /* ── Aviso "¿Cómo funciona?" (mismo patrón que Órdenes Automáticas) ── */
    .to-info-wrap { position: relative; display: inline-block; }
    .to-info-btn {
        display: inline-flex; align-items: center; gap: .4rem; background: rgba(52,152,219,.12); color: #2f7fb0;
        border: none; border-radius: 20px; padding: .4rem .9rem; font-weight: 700; font-size: .8rem; cursor: pointer;
    }
    .to-info-btn:hover { background: rgba(52,152,219,.22); }
    body.dark-mode .to-info-btn { background: rgba(52,152,219,.18); color: #7ec3f5; }
    body.dark-mode .to-info-btn:hover { background: rgba(52,152,219,.28); }

    .to-info-pop {
        position: absolute; top: calc(100% + 10px); left: 0; width: 380px; max-width: 85vw;
        background: #fff; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,.18); padding: 1rem 1.1rem;
        font-size: .82rem; color: #495057; line-height: 1.5; z-index: 1030;
        opacity: 0; transform: translateY(-6px); pointer-events: none; transition: all .15s ease;
    }
    .to-info-pop.show { opacity: 1; transform: translateY(0); pointer-events: auto; }
    .to-info-pop .to-info-close {
        position: absolute; top: .5rem; right: .6rem; background: none; border: none; color: #adb5bd; font-size: .85rem;
    }
    .to-info-pop code { background: #f4e9e3; color: #b5451b; padding: .1em .4em; border-radius: 5px; }
    body.dark-mode .to-info-pop { background: #1f1f33; color: #d5d5e2; box-shadow: 0 10px 30px rgba(0,0,0,.45); }
    body.dark-mode .to-info-pop code { background: rgba(181,69,27,.22); color: #ff9d6e; }

    /* ── Filtro ──────────────────────────────────────────── */
    .to-filter-card { border: none; border-radius: 14px; box-shadow: 0 2px 12px rgba(0,0,0,.05); padding: 1.1rem 1.4rem; margin-bottom: 1.5rem; }
    .to-filter-card .form-control { border-radius: 10px; border: 1.5px solid #e9ecef; }
    .to-filter-card .form-control:focus { border-color: #b5451b; box-shadow: 0 0 0 3px rgba(181,69,27,.12); }
    body.dark-mode .to-filter-card .form-control { background: #24243b; border-color: #33334d; color: #e4e4ef; }

    /* ── Tarjetas comparativas (elemento central) ───────────
       Cada tarjeta es un "carril" de carrera: dos barras horizontales,
       tiempo manual vs tiempo del sistema, a la misma escala. La barra
       más corta gana — visualiza justo lo que mide esta pantalla. */
    .to-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.1rem; margin-bottom: 1.5rem; }
    .to-card {
        background: #fff; border-radius: 14px; padding: 1.4rem 1.5rem; box-shadow: 0 2px 12px rgba(0,0,0,.05);
        border: 1.5px solid transparent; transition: box-shadow .15s;
    }
    .to-card:hover { box-shadow: 0 6px 20px rgba(0,0,0,.08); }
    body.dark-mode .to-card { background: #1e1e33; }

    .to-card-head { display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 1.1rem; }
    .to-card-head h6 { margin: 0; font-weight: 800; color: #1a1a2e; font-size: .95rem; }
    body.dark-mode .to-card-head h6 { color: #f0f0f7; }
    .to-card-head .to-regs { font-size: .72rem; color: #adb5bd; font-weight: 600; }

    .to-lane { margin-bottom: .85rem; }
    .to-lane:last-of-type { margin-bottom: 1rem; }
    .to-lane-label {
        display: flex; justify-content: space-between; font-size: .72rem; font-weight: 700;
        text-transform: uppercase; letter-spacing: .03em; color: #adb5bd; margin-bottom: .3rem;
    }
    .to-lane-label .to-lane-val { color: #1a1a2e; font-size: .8rem; }
    body.dark-mode .to-lane-label .to-lane-val { color: #e4e4ef; }
    .to-track { height: 10px; border-radius: 6px; background: #f1eee9; overflow: hidden; }
    body.dark-mode .to-track { background: #2c2c44; }
    .to-fill { height: 100%; border-radius: 6px; min-width: 6px; transition: width .5s ease; }
    .to-fill-manual { background: #b7b7c4; }
    .to-fill-sistema { background: linear-gradient(90deg, #b5451b, #d9663a); }
    .to-lane.is-empty .to-track { background: repeating-linear-gradient(45deg, #f1eee9, #f1eee9 4px, #e9e5df 4px, #e9e5df 8px); }
    body.dark-mode .to-lane.is-empty .to-track { background: repeating-linear-gradient(45deg, #2c2c44, #2c2c44 4px, #262640 4px, #262640 8px); }

    .to-card-foot { display: flex; justify-content: space-between; align-items: center; padding-top: .9rem; border-top: 1px solid #f2f2f2; }
    body.dark-mode .to-card-foot { border-top-color: #2c2c44; }

    /* ── Gráfico ─────────────────────────────────────────── */
    .to-chart-card { border: none; border-radius: 14px; box-shadow: 0 2px 12px rgba(0,0,0,.05); margin-bottom: 1.5rem; }
    .to-chart-card .card-header { background: #fff; border-radius: 14px 14px 0 0; border-bottom: 1px solid #f2f2f2; padding: 1.1rem 1.4rem; }
    body.dark-mode .to-chart-card .card-header { background: #1e1e33; border-bottom-color: #2c2c44; }

    /* ── Formulario de baseline ──────────────────────────── */
    .to-baseline-body .form-group label { font-size: .78rem; font-weight: 700; color: #8a8a9d; text-transform: uppercase; letter-spacing: .02em; }
    .to-baseline-body .form-control { border-radius: 10px; border: 1.5px solid #e9ecef; }
    .to-baseline-body .form-control:focus { border-color: #b5451b; box-shadow: 0 0 0 3px rgba(181,69,27,.12); }
    body.dark-mode .to-baseline-body .form-control { background: #24243b; border-color: #33334d; color: #e4e4ef; }
</style>
@endpush

@section('content')

<div class="page-toolbar">
    <div>
        <h2><i class="fas fa-stopwatch mr-2 text-primary"></i>Tiempos por Operación</h2>
        <div class="d-flex align-items-center flex-wrap" style="gap:.6rem;">
            <p class="mb-0">Compara el tiempo manual (antes) contra el tiempo real del sistema (ahora)</p>
            <span class="to-info-wrap">
                <button type="button" class="to-info-btn" id="toInfoBtn" onclick="toggleInfoTO()">
                    <i class="fas fa-circle-info"></i> ¿Cómo funciona?
                </button>
                <div class="to-info-pop" id="toInfoPop">
                    <button type="button" class="to-info-close" onclick="toggleInfoTO()"><i class="fas fa-times"></i></button>
                    <i class="fas fa-info-circle mr-1"></i>
                    Mide el <strong>OE3 (reducción de tiempos muertos)</strong>: compara el tiempo que tomaba cada
                    tarea de forma manual (ficha de observación pre-test) contra el tiempo real que registra el
                    sistema hoy. Fórmula: <code>(inicial − final) / final × 100</code>
                </div>
            </span>
        </div>
    </div>
</div>

{{-- Filtro de fechas --}}
<div class="card to-filter-card">
    <form method="GET" class="form-inline flex-wrap gap-2">
        <label class="mr-2 mb-2 font-weight-bold text-muted small">Desde</label>
        <input type="date" name="fecha_desde" class="form-control mr-3 mb-2" value="{{ $fechaDesde }}">
        <label class="mr-2 mb-2 font-weight-bold text-muted small">Hasta</label>
        <input type="date" name="fecha_hasta" class="form-control mr-3 mb-2" value="{{ $fechaHasta }}">
        <button class="btn btn-primary mb-2"><i class="fas fa-filter mr-1"></i>Filtrar</button>
        <a href="{{ route('tiempos-operacion.index') }}" class="btn btn-light text-muted mb-2">Limpiar</a>
    </form>
</div>

{{-- Tarjetas comparativas: carril manual vs sistema --}}
@php
    $escalaMax = collect($resultados)->flatMap(fn($r) => [$r['inicial'], $r['final']])->filter()->max() ?: 1;
@endphp
<div class="to-grid">
    @foreach($resultados as $tipo => $r)
        @php
            $pctManual  = $r['inicial'] > 0 ? max(4, round($r['inicial'] / $escalaMax * 100)) : 0;
            $pctSistema = $r['final']   > 0 ? max(4, round($r['final']   / $escalaMax * 100)) : 0;
        @endphp
        <div class="to-card">
            <div class="to-card-head">
                <h6>{{ $r['etiqueta'] }}</h6>
                <span class="to-regs">{{ $r['registros'] }} registro{{ $r['registros'] === 1 ? '' : 's' }}</span>
            </div>

            <div class="to-lane {{ $r['inicial'] > 0 ? '' : 'is-empty' }}">
                <div class="to-lane-label">
                    <span>Manual (antes)</span>
                    <span class="to-lane-val">{{ $r['inicial'] > 0 ? number_format($r['inicial'], 1).'s' : 'Sin dato' }}</span>
                </div>
                <div class="to-track"><div class="to-fill to-fill-manual" style="width:{{ $pctManual }}%"></div></div>
            </div>

            <div class="to-lane {{ $r['final'] !== null ? '' : 'is-empty' }}">
                <div class="to-lane-label">
                    <span>Sistema (ahora)</span>
                    <span class="to-lane-val">{{ $r['final'] !== null ? number_format($r['final'], 1).'s' : 'Sin dato' }}</span>
                </div>
                <div class="to-track"><div class="to-fill to-fill-sistema" style="width:{{ $pctSistema }}%"></div></div>
            </div>

            <div class="to-card-foot">
                @if($r['reduccion'] !== null)
                    <span class="badge-soft {{ $r['reduccion'] >= 0 ? 'badge-soft-success' : 'badge-soft-danger' }}">
                        <i class="fas fa-arrow-{{ $r['reduccion'] >= 0 ? 'down' : 'up' }} mr-1"></i>
                        {{ number_format(abs($r['reduccion']), 1) }}% {{ $r['reduccion'] >= 0 ? 'más rápido' : 'más lento' }}
                    </span>
                @else
                    <span class="badge-soft badge-soft-secondary">Falta tiempo inicial o datos del sistema</span>
                @endif
            </div>
        </div>
    @endforeach
</div>

{{-- Gráfico comparativo --}}
<div class="card to-chart-card">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-chart-bar mr-2 text-primary"></i>Comparación de tiempos (segundos)</h5>
    </div>
    <div class="card-body">
        <canvas id="chartTiempos" height="90"></canvas>
    </div>
</div>

{{-- Actualizar tiempo muerto inicial (pre-test) --}}
<div class="card prod-form-card">
    <div class="prod-form-header">
        <div class="prod-form-icon"><i class="fas fa-clipboard-list"></i></div>
        <div>
            <h5>Actualizar tiempo muerto inicial</h5>
            <p>Tiempo promedio (en segundos) que tomaba cada operación de forma manual, según tu ficha de observación pre-test.</p>
        </div>
    </div>
    <div class="prod-form-body to-baseline-body">
        <form method="POST" action="{{ route('tiempos-operacion.baseline') }}">
            @csrf
            <div class="row">
                @foreach($resultados as $tipo => $r)
                    <div class="col-md-4 form-group">
                        <label>{{ $r['etiqueta'] }} (seg.)</label>
                        <input type="number" step="0.1" min="0" name="baseline[{{ $tipo }}]"
                            class="form-control" value="{{ $r['inicial'] }}">
                    </div>
                @endforeach
            </div>
            <div class="form-actions">
                <button class="btn btn-warning"><i class="fas fa-save mr-1"></i>Guardar</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
    // ── Botón "¿Cómo funciona?" (mismo patrón que Órdenes Automáticas) ──
    function toggleInfoTO() {
        document.getElementById('toInfoPop').classList.toggle('show');
    }
    document.addEventListener('click', function (e) {
        const pop = document.getElementById('toInfoPop');
        const btn = document.getElementById('toInfoBtn');
        if (pop.classList.contains('show') && !pop.contains(e.target) && e.target !== btn && !btn.contains(e.target)) {
            pop.classList.remove('show');
        }
    });
</script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
const etiquetas = @json(collect($resultados)->pluck('etiqueta')->values());
const iniciales = @json(collect($resultados)->pluck('inicial')->values());
const finales   = @json(collect($resultados)->map(fn($r) => $r['final'] ?? 0)->values());

const esOscuro = document.body.classList.contains('dark-mode');

new Chart(document.getElementById('chartTiempos'), {
    type: 'bar',
    data: {
        labels: etiquetas,
        datasets: [
            {
                label: 'Manual (antes)',
                data: iniciales,
                backgroundColor: 'rgba(183,183,196,0.65)',
                borderRadius: 6,
                maxBarThickness: 46,
            },
            {
                label: 'Sistema (ahora)',
                data: finales,
                backgroundColor: 'rgba(181,69,27,0.75)',
                borderRadius: 6,
                maxBarThickness: 46,
            },
        ]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { labels: { color: esOscuro ? '#e4e4ef' : '#1a1a2e', font: { weight: '600' } } }
        },
        scales: {
            x: { grid: { display: false }, ticks: { color: esOscuro ? '#9a9ac0' : '#6c757d' } },
            y: {
                beginAtZero: true,
                title: { display: true, text: 'segundos', color: esOscuro ? '#9a9ac0' : '#6c757d' },
                ticks: { color: esOscuro ? '#9a9ac0' : '#6c757d' },
                grid: { color: esOscuro ? 'rgba(255,255,255,.06)' : 'rgba(0,0,0,.05)' },
            }
        }
    }
});
</script>
@endpush
