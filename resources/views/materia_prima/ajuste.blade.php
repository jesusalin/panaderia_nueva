@extends('layouts.app')
@section('title', 'Ajuste de Inventario')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('materia-prima.index') }}">Materia Prima</a></li>
    <li class="breadcrumb-item active">Ajuste de Inventario</li>
@endsection

@include('materia_prima.partials._styles')

@push('styles')
<style>
    .ajuste-info {
        display: flex; gap: .7rem; align-items: flex-start; background: #eef4fb; border: 1px solid #d8e7f7;
        border-radius: 10px; padding: .85rem 1rem; font-size: .84rem; color: #2170a3; margin-bottom: .5rem;
    }
    .ajuste-info i { margin-top: .15rem; }
    body.dark-mode .ajuste-info { background: rgba(52,152,219,.12); border-color: rgba(52,152,219,.25); color: #7ec3f5; }

    .compare-grid { display: grid; grid-template-columns: 1fr auto 1fr; gap: 1rem; align-items: stretch; }
    .compare-box { background: #f7f5f3; border-radius: 12px; padding: 1.1rem; text-align: center; }
    body.dark-mode .compare-box { background: #24243b; }
    .compare-box .cb-label { font-size: .72rem; font-weight: 800; text-transform: uppercase; letter-spacing: .04em; color: #8a8a9d; }
    body.dark-mode .compare-box .cb-label { color: #9a9ac0; }
    .compare-box .cb-value { font-size: 1.6rem; font-weight: 800; color: #1a1a2e; margin-top: .3rem; }
    body.dark-mode .compare-box .cb-value { color: #f0f0f7; }
    .compare-box .cb-unit { font-size: .78rem; font-weight: 600; color: #adb5bd; }
    .compare-box.real { background: #fff8f2; border: 2px solid #f0dccd; }
    body.dark-mode .compare-box.real { background: rgba(181,69,27,.1); border-color: rgba(181,69,27,.35); }
    .compare-arrow { display: flex; align-items: center; justify-content: center; color: #d8b9a3; font-size: 1.3rem; }

    .stepper { display: flex; align-items: center; gap: .5rem; margin-top: .6rem; }
    .stepper input {
        text-align: center; font-size: 1.2rem; font-weight: 800; border-radius: 8px !important;
        border: 1.5px solid #e9ecef; padding: .4rem .3rem; width: 100%;
    }
    body.dark-mode .stepper input { background: #1a1a2e; border-color: #33334d; color: #f0f0f7; }
    .stepper input:focus { border-color: #b5451b; box-shadow: 0 0 0 3px rgba(181,69,27,.12); outline: none; }
    .stepper-btn {
        width: 34px; height: 34px; flex-shrink: 0; border-radius: 8px; border: 1.5px solid #e9ecef; background: #fff;
        font-weight: 800; color: #b5451b; display: flex; align-items: center; justify-content: center; cursor: pointer;
    }
    .stepper-btn:hover { background: #fff3ea; }
    body.dark-mode .stepper-btn { background: #1a1a2e; border-color: #33334d; color: #ff9d6e; }
    .use-actual-link { display: block; margin-top: .5rem; font-size: .74rem; color: #8a8a9d; text-decoration: underline; cursor: pointer; background: none; border: none; padding: 0; }
    body.dark-mode .use-actual-link { color: #9a9ac0; }

    .diff-banner { border-radius: 10px; padding: .8rem 1rem; display: flex; align-items: center; gap: .6rem; font-weight: 700; margin: 1.1rem 0 0; font-size: .92rem; transition: background .15s, color .15s; }
    .diff-banner.neutro   { background: #eef0f3; color: #6c757d; }
    .diff-banner.sobrante { background: rgba(46,204,113,.14); color: #1e8e5a; }
    .diff-banner.faltante { background: rgba(231,76,60,.12); color: #c0392b; }
    body.dark-mode .diff-banner.neutro   { background: #2c2c44; color: #b0b0cc; }
    body.dark-mode .diff-banner.sobrante { background: rgba(46,204,113,.16); color: #6ee7a5; }
    body.dark-mode .diff-banner.faltante { background: rgba(231,76,60,.18); color: #ff9b8f; }
    .diff-banner .db-sub { font-weight: 600; opacity: .9; margin-left: auto; font-size: .8rem; }

    .motivo-chips { display: flex; flex-wrap: wrap; gap: .4rem; margin-bottom: .7rem; }
    .motivo-chip {
        border: 1.5px solid #e9ecef; background: #fff; border-radius: 20px; padding: .3rem .8rem;
        font-size: .78rem; font-weight: 600; color: #6c757d; cursor: pointer; transition: .12s;
    }
    .motivo-chip:hover { border-color: #b5451b; color: #b5451b; }
    body.dark-mode .motivo-chip { background: #1a1a2e; border-color: #33334d; color: #b0b0cc; }
    body.dark-mode .motivo-chip:hover { border-color: #ff9d6e; color: #ff9d6e; }

    .preview-diff-pill {
        display: inline-flex; align-items: center; gap: .3rem; font-size: .74rem; font-weight: 800;
        padding: .2rem .55rem; border-radius: 20px; margin-top: .4rem;
    }
    .preview-diff-pill.neutro   { background: #eef0f3; color: #8a8a9d; }
    .preview-diff-pill.sobrante { background: rgba(46,204,113,.14); color: #1e8e5a; }
    .preview-diff-pill.faltante { background: rgba(231,76,60,.12); color: #c0392b; }
    body.dark-mode .preview-diff-pill.neutro   { background: #2c2c44; color: #9a9ac0; }

    @media (max-width: 576px) {
        .compare-grid { grid-template-columns: 1fr; }
        .compare-arrow { transform: rotate(90deg); padding: .3rem 0; }
    }
</style>
@endpush

@section('content')
<div class="row">
    <div class="col-lg-8">
        <div class="card mp-form-card">
            <div class="mp-form-header">
                <div class="mp-form-icon"><i class="fas fa-balance-scale"></i></div>
                <div>
                    <h5>Ajuste de Inventario</h5>
                    <p>{{ $materiaPrima->nombre }} · {{ $materiaPrima->proveedor->nombre ?? 'Sin proveedor asignado' }}</p>
                </div>
            </div>
            <div class="mp-form-body">

                <div class="ajuste-info">
                    <i class="fas fa-info-circle"></i>
                    <div>
                        Usa este formulario cuando el stock físico (contado a mano) sea diferente al que muestra el sistema.
                        El ajuste quedará registrado en el Kardex como un movimiento de tipo <strong>Ajuste</strong> para
                        mantener la trazabilidad.
                    </div>
                </div>

                <form action="{{ route('materia-prima.ajuste.store', $materiaPrima) }}" method="POST" id="formAjuste">
                    @csrf

                    <label class="section-label"><i class="fas fa-clipboard-check mr-1"></i> Conteo físico</label>
                    <div class="compare-grid">
                        <div class="compare-box">
                            <div class="cb-label">Stock en el sistema</div>
                            <div class="cb-value">{{ number_format($materiaPrima->stock_actual, 3) }}</div>
                            <div class="cb-unit">{{ $materiaPrima->unidad->abreviatura }} · mínimo {{ number_format($materiaPrima->stock_minimo, 3) }}</div>
                        </div>

                        <div class="compare-arrow"><i class="fas fa-arrow-right"></i></div>

                        <div class="compare-box real">
                            <div class="cb-label">Stock real contado</div>
                            <div class="stepper">
                                <button type="button" class="stepper-btn" onclick="ajustarPaso(-1)">−</button>
                                <input type="number" step="0.001" min="0" name="stock_real"
                                       class="@error('stock_real') is-invalid @enderror"
                                       value="{{ old('stock_real', $materiaPrima->stock_actual) }}"
                                       id="stockReal" oninput="actualizarTodo()">
                                <button type="button" class="stepper-btn" onclick="ajustarPaso(1)">+</button>
                            </div>
                            <button type="button" class="use-actual-link" onclick="usarValorSistema()">Sin diferencia, usar el del sistema</button>
                            @error('stock_real')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div id="diffBanner" class="diff-banner neutro">
                        <i class="fas fa-equals"></i> <span id="diffTexto">Sin diferencia</span>
                        <span class="db-sub" id="diffStockBajo"></span>
                    </div>

                    <label class="section-label"><i class="fas fa-pen mr-1"></i> Motivo del ajuste</label>
                    <div class="form-group">
                        <div class="motivo-chips">
                            <button type="button" class="motivo-chip" onclick="usarMotivo('Merma por humedad o manipulación')">Merma</button>
                            <button type="button" class="motivo-chip" onclick="usarMotivo('Producto vencido, se dio de baja')">Vencido</button>
                            <button type="button" class="motivo-chip" onclick="usarMotivo('Error de registro en un movimiento anterior')">Error de registro anterior</button>
                            <button type="button" class="motivo-chip" onclick="usarMotivo('Sobrante encontrado en el inventario físico')">Sobrante encontrado</button>
                            <button type="button" class="motivo-chip" onclick="usarMotivo('Diferencia encontrada en el inventario físico mensual')">Conteo mensual</button>
                        </div>
                        <textarea name="observacion" rows="3"
                                  class="form-control @error('observacion') is-invalid @enderror"
                                  placeholder="Describe brevemente por qué hay diferencia entre el stock físico y el del sistema..."
                                  id="observacionTxt">{{ old('observacion') }}</textarea>
                        @error('observacion')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="form-actions">
                        <a href="{{ route('materia-prima.index') }}" class="btn btn-light">Cancelar</a>
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-check mr-1"></i>Confirmar Ajuste
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="preview-wrap">
            <span class="preview-label"><i class="fas fa-eye mr-1"></i>Así quedará en el listado</span>
            <div class="item-card">
                <div class="item-card-media" style="height:80px;"><i class="fas fa-wheat-awn"></i></div>
                <div class="item-card-body">
                    <div class="item-card-cat">{{ $materiaPrima->proveedor->nombre ?? 'Sin proveedor asignado' }}</div>
                    <h3 class="item-card-title">{{ $materiaPrima->nombre }}</h3>
                    <div class="stock-gauge">
                        <div class="sg-track"><div class="sg-fill ok" id="prevGaugeFill" style="width:0%;"></div></div>
                        <div class="sg-labels">
                            <span>Nuevo: <strong><span id="prevStock">0</span> {{ $materiaPrima->unidad->abreviatura }}</strong></span>
                            <span>Mínimo: {{ number_format($materiaPrima->stock_minimo, 2) }} {{ $materiaPrima->unidad->abreviatura }}</span>
                        </div>
                    </div>
                    <span class="preview-diff-pill neutro" id="prevDiffPill"><i class="fas fa-equals"></i> Sin cambios</span>
                </div>
                <div class="item-card-footer">
                    <span class="badge-soft badge-soft-success" id="prevEstadoStock">Normal</span>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const stockSistema = {{ $materiaPrima->stock_actual }};
const stockMinimo  = {{ $materiaPrima->stock_minimo }};
const unidad       = "{{ $materiaPrima->unidad->abreviatura }}";
const referenciaGauge = Math.max(stockMinimo * 2, 0.001);

function ajustarPaso(dir) {
    const input = document.getElementById('stockReal');
    let valor = (parseFloat(input.value) || 0) + dir;
    if (valor < 0) valor = 0;
    input.value = parseFloat(valor.toFixed(3));
    actualizarTodo();
}

function usarValorSistema() {
    document.getElementById('stockReal').value = stockSistema;
    actualizarTodo();
}

function usarMotivo(texto) {
    document.getElementById('observacionTxt').value = texto;
}

function actualizarTodo() {
    const stockReal = parseFloat(document.getElementById('stockReal').value) || 0;
    const diferencia = stockReal - stockSistema;
    const bajo = stockReal <= stockMinimo;

    // Banner de diferencia (dentro del formulario)
    const banner = document.getElementById('diffBanner');
    const texto = document.getElementById('diffTexto');
    const subBajo = document.getElementById('diffStockBajo');
    banner.classList.remove('neutro', 'sobrante', 'faltante');

    let claseDiff = 'neutro', icono = 'fa-equals', textoDiff = 'Sin diferencia respecto al sistema', textoPill = 'Sin cambios';
    if (diferencia > 0) {
        claseDiff = 'sobrante'; icono = 'fa-arrow-up';
        textoDiff = 'Sobrante de +' + diferencia.toFixed(3) + ' ' + unidad;
        textoPill = '+' + diferencia.toFixed(3) + ' ' + unidad;
    } else if (diferencia < 0) {
        claseDiff = 'faltante'; icono = 'fa-arrow-down';
        textoDiff = 'Faltante de ' + diferencia.toFixed(3) + ' ' + unidad;
        textoPill = diferencia.toFixed(3) + ' ' + unidad;
    }
    banner.classList.add(claseDiff);
    banner.querySelector('i').className = 'fas ' + icono;
    texto.textContent = textoDiff;
    subBajo.innerHTML = bajo ? '<i class="fas fa-exclamation-triangle mr-1"></i>Quedará en stock bajo' : '';

    // Vista previa de la tarjeta
    document.getElementById('prevStock').textContent = stockReal.toFixed(2);
    const porcentaje = Math.min(100, Math.round((stockReal / referenciaGauge) * 100));
    const fill = document.getElementById('prevGaugeFill');
    fill.style.width = porcentaje + '%';
    fill.className = 'sg-fill ' + (bajo ? 'bajo' : 'ok');

    const pill = document.getElementById('prevDiffPill');
    pill.className = 'preview-diff-pill ' + claseDiff;
    pill.innerHTML = '<i class="fas ' + icono + '"></i> ' + textoPill;

    const estadoBadge = document.getElementById('prevEstadoStock');
    estadoBadge.textContent = bajo ? 'Stock bajo' : 'Normal';
    estadoBadge.className = 'badge-soft ' + (bajo ? 'badge-soft-danger' : 'badge-soft-success');
}
actualizarTodo();
</script>
@endsection
