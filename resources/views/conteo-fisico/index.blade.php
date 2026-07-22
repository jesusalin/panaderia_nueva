@extends('layouts.app')
@section('title', 'Conteo Físico')
@section('breadcrumb') <li class="breadcrumb-item active">Conteo Físico</li> @endsection

@push('styles')
<style>
    .cf-row { align-items: center; }
    .cf-input-wrap { width: 130px; flex-shrink: 0; }
    .cf-input-wrap label { font-size: .66rem; color: #adb5bd; text-transform: uppercase; font-weight: 700; display: block; margin-bottom: .15rem; }
    .cf-input-wrap input {
        width: 100%; border: 1.5px solid #e9ecef; border-radius: 8px; padding: .4rem .6rem; font-weight: 700; font-size: .9rem;
        text-align: center;
    }
    .cf-input-wrap input:focus { border-color: #b5451b; outline: none; box-shadow: 0 0 0 3px rgba(181,69,27,.12); }
    body.dark-mode .cf-input-wrap input { background: #24243b; border-color: #33334d; color: #e4e4ef; }

    .cf-diff { min-width: 90px; text-align: center; font-weight: 800; font-size: .82rem; }
    .cf-diff.ok { color: #1e8e5a; } .cf-diff.mal { color: #c0392b; } .cf-diff.vacio { color: #cfd4da; }
    body.dark-mode .cf-diff.ok { color: #6ee7a5; } body.dark-mode .cf-diff.mal { color: #ff9b8f; } body.dark-mode .cf-diff.vacio { color: #4a4a63; }

    .cf-summary-bar {
        position: sticky; bottom: 0; background: #fff; border-top: 1px solid #eee; padding: 1rem 1.4rem; margin: 1rem -1px 0;
        border-radius: 0 0 14px 14px; box-shadow: 0 -6px 18px rgba(0,0,0,.06); display: flex; align-items: center; gap: 1.2rem; flex-wrap: wrap;
    }
    body.dark-mode .cf-summary-bar { background: #1a1a2c; border-top-color: #2c2c44; }
    .cf-summary-item { font-size: .82rem; color: #6c757d; }
    .cf-summary-item strong { color: #1a1a2e; font-size: 1rem; }
    body.dark-mode .cf-summary-item { color: #9a9ac0; }
    body.dark-mode .cf-summary-item strong { color: #f0f0f7; }

    /* Botón + panel de info (mismo patrón que Órdenes Automáticas) */
    .cf-info-wrap { position: relative; display: inline-block; }
    .cf-info-btn {
        display: inline-flex; align-items: center; gap: .4rem; background: rgba(52,152,219,.12); color: #2f7fb0;
        border: none; border-radius: 20px; padding: .4rem .9rem; font-weight: 700; font-size: .8rem; cursor: pointer;
    }
    .cf-info-btn:hover { background: rgba(52,152,219,.22); }
    body.dark-mode .cf-info-btn { background: rgba(52,152,219,.18); color: #7ec3f5; }
    body.dark-mode .cf-info-btn:hover { background: rgba(52,152,219,.28); }

    .cf-info-pop {
        position: absolute; top: calc(100% + 10px); left: 0; width: 340px; max-width: 85vw;
        background: #fff; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,.18); padding: 1rem 1.1rem;
        font-size: .82rem; color: #495057; line-height: 1.5; z-index: 1030;
        opacity: 0; transform: translateY(-6px); pointer-events: none; transition: all .15s ease;
    }
    .cf-info-pop.show { opacity: 1; transform: translateY(0); pointer-events: auto; }
    .cf-info-pop .cf-info-close {
        position: absolute; top: .5rem; right: .6rem; background: none; border: none; color: #adb5bd; font-size: .85rem;
    }
    body.dark-mode .cf-info-pop { background: #1f1f33; color: #d5d5e2; box-shadow: 0 10px 30px rgba(0,0,0,.45); }
</style>
@endpush

@section('content')

<div class="page-toolbar">
    <div>
        <h2><i class="fas fa-clipboard-check mr-2 text-info"></i>Conteo Físico de Inventario</h2>
        <div class="d-flex align-items-center flex-wrap" style="gap:.6rem;">
            <p class="mb-0">Compara lo que dice el sistema contra lo que hay realmente en el almacén</p>
            <span class="cf-info-wrap">
                <button type="button" class="cf-info-btn" id="cfInfoBtn" onclick="toggleInfoCF()">
                    <i class="fas fa-circle-info"></i> ¿Cómo funciona?
                </button>
                <div class="cf-info-pop" id="cfInfoPop">
                    <button type="button" class="cf-info-close" onclick="toggleInfoCF()"><i class="fas fa-times"></i></button>
                    <i class="fas fa-info-circle mr-1"></i>
                    Solo llena el campo <strong>"Físico"</strong> de los insumos que vayas a contar en esta jornada
                    — puedes dejar el resto en blanco. Si hay diferencia con el sistema, el stock se ajusta
                    automáticamente y queda registrado como evidencia para tu indicador de
                    <strong>Exactitud del Inventario</strong>.
                </div>
            </span>
        </div>
    </div>
    <div class="toolbar-actions">
        <a href="{{ route('conteo-fisico.historial') }}" class="btn btn-light">
            <i class="fas fa-history mr-1"></i>Ver historial
        </a>
    </div>
</div>

@if($materias->count() > 0)
<form action="{{ route('conteo-fisico.store') }}" method="POST" id="formConteo">
    @csrf

    <div class="list-rows">
        @foreach($materias as $m)
            <div class="list-row cf-row">
                <div class="lr-icon"><i class="fas fa-wheat-awn"></i></div>

                <div class="lr-main">
                    <div class="lr-title">{{ $m->nombre }}</div>
                    <div class="lr-subtitle">{{ $m->proveedor->nombre ?? 'Sin proveedor' }}</div>
                </div>

                <div class="lr-meta">
                    <div class="lm-item">
                        <span class="lm-label">Sistema</span>
                        <span class="lm-value" id="sistema-{{ $m->id }}">{{ number_format($m->stock_actual, 2) }} {{ $m->unidad->abreviatura }}</span>
                    </div>
                </div>

                <div class="lr-side">
                    <span class="cf-diff vacio" id="diff-{{ $m->id }}">—</span>
                    <div class="cf-input-wrap">
                        <label>Físico</label>
                        <input type="number" step="0.001" min="0" placeholder="0.00"
                            name="conteo[{{ $m->id }}][stock_fisico]"
                            data-sistema="{{ $m->stock_actual }}" data-unidad="{{ $m->unidad->abreviatura }}"
                            oninput="calcularDiferencia({{ $m->id }})" id="input-{{ $m->id }}">
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="cf-summary-bar">
        <div class="cf-summary-item">Insumos contados: <strong id="resumenContados">0</strong> / {{ $materias->count() }}</div>
        <div class="cf-summary-item">Con diferencia: <strong id="resumenDiferencias">0</strong></div>
        <div class="flex-grow-1" style="min-width:200px;">
            <input type="text" name="observacion_general" class="form-control form-control-sm"
                placeholder="Observación general de esta jornada (opcional)">
        </div>
        <button type="submit" class="btn btn-primary" id="btnGuardarConteo" disabled>
            <i class="fas fa-save mr-1"></i>Guardar Conteo
        </button>
    </div>
</form>
@else
<div class="empty-state">
    <i class="fas fa-boxes"></i>
    <p>No tienes insumos activos para contar todavía.</p>
</div>
@endif

@endsection

@push('scripts')
<script>
    // Botón "¿Cómo funciona?"
    function toggleInfoCF() {
        document.getElementById('cfInfoPop').classList.toggle('show');
    }
    document.addEventListener('click', function (e) {
        const pop = document.getElementById('cfInfoPop');
        const btn = document.getElementById('cfInfoBtn');
        if (pop.classList.contains('show') && !pop.contains(e.target) && e.target !== btn && !btn.contains(e.target)) {
            pop.classList.remove('show');
        }
    });

    function calcularDiferencia(id) {
        const input = document.getElementById('input-' + id);
        const diffEl = document.getElementById('diff-' + id);
        const sistema = parseFloat(input.dataset.sistema);
        const unidad = input.dataset.unidad;
        const valor = input.value;

        if (valor === '') {
            diffEl.textContent = '—';
            diffEl.className = 'cf-diff vacio';
        } else {
            const fisico = parseFloat(valor) || 0;
            const diff = fisico - sistema;
            if (diff === 0) {
                diffEl.textContent = 'Coincide';
                diffEl.className = 'cf-diff ok';
            } else {
                diffEl.textContent = (diff > 0 ? '+' : '') + diff.toFixed(2) + ' ' + unidad;
                diffEl.className = 'cf-diff mal';
            }
        }
        actualizarResumen();
    }

    function actualizarResumen() {
        const inputs = document.querySelectorAll('#formConteo input[type=number]');
        let contados = 0, conDiferencia = 0;
        inputs.forEach(inp => {
            if (inp.value !== '') {
                contados++;
                const sistema = parseFloat(inp.dataset.sistema);
                const fisico = parseFloat(inp.value) || 0;
                if (fisico !== sistema) conDiferencia++;
            }
        });
        document.getElementById('resumenContados').textContent = contados;
        document.getElementById('resumenDiferencias').textContent = conDiferencia;
        document.getElementById('btnGuardarConteo').disabled = contados === 0;
    }
</script>
@endpush
