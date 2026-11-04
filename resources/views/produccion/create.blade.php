@extends('layouts.app')
@section('title', 'Nueva Producción')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('produccion.index') }}">Producción</a></li>
    <li class="breadcrumb-item active">Nueva</li>
@endsection

@push('styles')
<style>
    .prod-form-card { border-radius: 14px; overflow: hidden; border: none; box-shadow: 0 2px 12px rgba(0,0,0,.05); }
    body.dark-mode .prod-form-card { background: #1f1f33; box-shadow: 0 2px 12px rgba(0,0,0,.3); }
    .prod-form-header {
        background: linear-gradient(135deg, #b5451b, #8a3213); color: #fff; padding: 1.1rem 1.4rem;
        display: flex; align-items: center; gap: .7rem;
    }
    .prod-form-header .pfh-icon {
        width: 42px; height: 42px; border-radius: 10px; background: rgba(255,255,255,.18);
        display: flex; align-items: center; justify-content: center; font-size: 1.1rem; flex-shrink: 0;
    }
    .prod-form-header h5 { margin: 0; font-weight: 800; }
    .prod-form-header small { display: block; opacity: .85; font-weight: 400; }
    .prod-form-body { padding: 1.5rem; }
    body.dark-mode .prod-form-body label { color: #d5d5e2; }

    .prod-preview {
        display: none; align-items: center; gap: .7rem; margin-top: .6rem; padding: .6rem .8rem;
        background: #f7f5f3; border-radius: 10px; border: 1px solid #eee;
    }
    body.dark-mode .prod-preview { background: #24243b; border-color: #33334d; }
    .prod-preview.mostrar { display: flex; }
    .prod-preview-icon {
        width: 40px; height: 40px; border-radius: 9px; background: #fff; color: #b5451b;
        display: flex; align-items: center; justify-content: center; font-size: 1rem; flex-shrink: 0; overflow: hidden;
    }
    body.dark-mode .prod-preview-icon { background: #1f1f33; color: #ff9d6e; }
    .prod-preview-icon img { width: 100%; height: 100%; object-fit: cover; }
    .prod-preview-info strong { display: block; color: #1a1a2e; font-size: .92rem; }
    body.dark-mode .prod-preview-info strong { color: #f0f0f7; }
    .prod-preview-info span { font-size: .78rem; color: #8a8a9d; }

    .prod-form-actions { padding: 1rem 1.4rem; border-top: 1px solid #eee; text-align: right; }
    body.dark-mode .prod-form-actions { border-top-color: #33334d; }

    /* Panel de ingredientes */
    .prod-recipe-summary { font-size: .82rem; color: #8a8a9d; margin-bottom: .9rem; }
    body.dark-mode .prod-recipe-summary { color: #9a9ac0; }
    .prod-recipe-summary strong { color: #1a1a2e; }
    body.dark-mode .prod-recipe-summary strong { color: #f0f0f7; }

    .ing-row {
        display: flex; align-items: center; gap: .7rem; padding: .6rem 0; border-bottom: 1px solid #f1f1f4;
    }
    .ing-row:last-child { border-bottom: none; }
    body.dark-mode .ing-row { border-bottom-color: #2c2c44; }
    .ing-icon {
        width: 32px; height: 32px; border-radius: 8px; background: #f7f5f3; color: #b5451b;
        display: flex; align-items: center; justify-content: center; font-size: .8rem; flex-shrink: 0;
    }
    body.dark-mode .ing-icon { background: #24243b; color: #ff9d6e; }
    .ing-body { flex: 1; min-width: 0; }
    .ing-nombre { font-weight: 700; font-size: .86rem; color: #1a1a2e; }
    body.dark-mode .ing-nombre { color: #f0f0f7; }
    .ing-bar { height: 5px; border-radius: 4px; background: #eef0f3; overflow: hidden; margin-top: .35rem; }
    body.dark-mode .ing-bar { background: #33334d; }
    .ing-bar span { display: block; height: 100%; border-radius: 4px; }
    .ing-cifras { text-align: right; font-size: .8rem; white-space: nowrap; flex-shrink: 0; }
    .ing-cifras .ing-necesario { font-weight: 700; }
    .ing-cifras .ing-stock { display: block; font-size: .72rem; color: #adb5bd; }

    .prod-banner {
        display: flex; align-items: center; gap: .6rem; padding: .7rem .9rem; border-radius: 10px;
        font-size: .85rem; font-weight: 600; margin-top: 1rem;
    }
    .prod-banner.ok   { background: rgba(46,204,113,.12); color: #1e8e5a; }
    .prod-banner.warn { background: rgba(243,156,18,.12); color: #b9770e; }
    .prod-banner.bad  { background: rgba(231,76,60,.12); color: #c0392b; }
    body.dark-mode .prod-banner.ok   { background: rgba(46,204,113,.16); color: #6ee7a5; }
    body.dark-mode .prod-banner.warn { background: rgba(243,156,18,.18); color: #ffc673; }
    body.dark-mode .prod-banner.bad  { background: rgba(231,76,60,.18); color: #ff9b8f; }

    /* ¿Qué producir hoy? */
    .plan-card { background: #fff; border-radius: 14px; box-shadow: 0 2px 12px rgba(0,0,0,.05); margin-bottom: 1.2rem; overflow: hidden; }
    body.dark-mode .plan-card { background: #1f1f33; }
    .plan-head { display: flex; align-items: center; gap: .7rem; padding: .9rem 1.2rem; border-bottom: 1px solid #f1ece7; }
    .plan-head h5 { margin: 0; font-weight: 800; font-size: 1rem; color: #1a1a2e; }
    .plan-head small { color: #8a8a9d; }
    body.dark-mode .plan-head { border-bottom-color: #33334d; }
    body.dark-mode .plan-head h5 { color: #f0f0f7; }
    .plan-head .ph-icon { width: 36px; height: 36px; border-radius: 9px; background: rgba(243,156,18,.15); color: #b9770e; display: flex; align-items: center; justify-content: center; }
    .plan-tabla { width: 100%; font-size: .86rem; }
    .plan-tabla th { font-size: .7rem; text-transform: uppercase; color: #8a8a9d; font-weight: 800; padding: .5rem 1.2rem; background: #faf9f7; }
    .plan-tabla td { padding: .55rem 1.2rem; border-top: 1px solid #f6f3f0; vertical-align: middle; }
    body.dark-mode .plan-tabla th { background: #24243b; }
    body.dark-mode .plan-tabla td { border-top-color: #2c2c44; }
    .plan-tabla .pt-nombre { font-weight: 700; color: #1a1a2e; }
    body.dark-mode .plan-tabla .pt-nombre { color: #f0f0f7; }
    .plan-tabla .pt-nombre i { color: var(--cat-color, #b5451b); width: 18px; }
    .plan-tabla .pt-sub { display: block; font-size: .72rem; color: #adb5bd; }
    .plan-tabla .pt-stock.bajo { color: #c0392b; font-weight: 800; }
    .plan-tabla tr.elegido td { background: rgba(181,69,27,.06); }

    .prod-extra { display: grid; grid-template-columns: 1fr 1fr; gap: .6rem; margin-bottom: 1rem; }
    .prod-extra .pe-box { background: #f7f5f3; border-radius: 10px; padding: .6rem .8rem; font-size: .78rem; color: #8a8a9d; }
    .prod-extra .pe-box b { display: block; font-size: 1.05rem; color: #1a1a2e; }
    body.dark-mode .prod-extra .pe-box { background: #24243b; }
    body.dark-mode .prod-extra .pe-box b { color: #f0f0f7; }
    .btn-usar-max { padding: 0; font-size: .76rem; font-weight: 700; }


    /* ── Nuevo diseño del formulario ── */
    .np-grid { display: grid; grid-template-columns: minmax(0, 1.35fr) minmax(0, 1fr); gap: 1.2rem; align-items: start; }
    @media (max-width: 991px) { .np-grid { grid-template-columns: 1fr; } }
    .np-card { background: #fff; border-radius: 14px; box-shadow: 0 2px 12px rgba(0,0,0,.05); }
    body.dark-mode .np-card { background: #1f1f33; }
    .np-paso { padding: 1.1rem 1.3rem; border-bottom: 1px solid #f3eee9; }
    .np-paso:last-child { border-bottom: none; }
    body.dark-mode .np-paso { border-bottom-color: #2c2c44; }
    .np-paso-tit { display: flex; align-items: center; gap: .6rem; font-weight: 800; color: #1a1a2e; margin-bottom: .8rem; }
    body.dark-mode .np-paso-tit { color: #f0f0f7; }
    .np-paso-tit .num { width: 26px; height: 26px; border-radius: 50%; background: #b5451b; color: #fff; font-size: .8rem; display: flex; align-items: center; justify-content: center; }
    .np-paso-tit small { font-weight: 600; color: #8a8a9d; margin-left: auto; }

    .np-buscar { position: relative; margin-bottom: .8rem; }
    .np-buscar i { position: absolute; left: .85rem; top: 50%; transform: translateY(-50%); color: #adb5bd; }
    .np-buscar input { padding-left: 2.3rem; border-radius: 10px; }
    .np-tiles { position: relative; display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: .6rem; max-height: 330px; overflow-y: auto; padding: .15rem; }
    .np-tile { margin: 0; cursor: pointer; position: relative; }
    .np-tile input { position: absolute; opacity: 0; pointer-events: none; }
    .np-tile .t { display: flex; gap: .6rem; align-items: center; border: 2px solid #eee8e2; border-radius: 12px; padding: .55rem .6rem; height: 100%; transition: .12s; background: #fff; }
    body.dark-mode .np-tile .t { background: #24243b; border-color: #33334d; }
    .np-tile:hover .t { border-color: #e2c9b8; }
    .np-tile input:checked + .t { border-color: #b5451b; box-shadow: 0 0 0 3px rgba(181,69,27,.12); }
    .np-tile input:focus-visible + .t { box-shadow: 0 0 0 3px rgba(181,69,27,.3); }
    .np-tile .ti { width: 38px; height: 38px; border-radius: 10px; flex-shrink: 0; display: flex; align-items: center; justify-content: center;
        background: linear-gradient(135deg, #1a1a2e, var(--cat, #b5451b)); color: #fff; overflow: hidden; }
    .np-tile .ti img { width: 100%; height: 100%; object-fit: cover; }
    .np-tile .tn { font-weight: 700; font-size: .82rem; color: #1a1a2e; line-height: 1.15; }
    body.dark-mode .np-tile .tn { color: #f0f0f7; }
    .np-tile .ts { font-size: .7rem; color: #8a8a9d; }
    .np-tile .ts.bajo { color: #c0392b; font-weight: 700; }
    .np-tile .tb { position: absolute; top: -7px; right: 6px; font-size: .62rem; font-weight: 800; padding: .1rem .4rem; border-radius: 20px; background: #f39c12; color: #fff; }
    .np-tile.sin-receta { cursor: not-allowed; opacity: .55; }
    .np-tile.sin-receta .tb { background: #adb5bd; }

    .np-cant { display: flex; align-items: stretch; gap: .5rem; flex-wrap: wrap; }
    .np-stepper { display: flex; border: 1.5px solid #e9ecef; border-radius: 12px; overflow: hidden; }
    .np-stepper button { width: 46px; border: none; background: #f7f5f3; font-size: 1.1rem; color: #495057; }
    .np-stepper button:hover { background: #efe9e3; }
    .np-stepper input { width: 110px; border: none; text-align: center; font-size: 1.35rem; font-weight: 800; color: #1a1a2e; }
    .np-stepper input:focus { outline: none; }
    body.dark-mode .np-stepper { border-color: #33334d; }
    body.dark-mode .np-stepper button { background: #24243b; color: #d5d5e2; }
    body.dark-mode .np-stepper input { background: #1f1f33; color: #f0f0f7; }
    .np-chips { display: flex; flex-wrap: wrap; gap: .4rem; align-items: center; }
    .np-chip { border: 1.5px solid #eee3da; background: #fff; border-radius: 20px; padding: .3rem .75rem; font-size: .78rem; font-weight: 700; color: #6c757d; }
    .np-chip:hover { border-color: #b5451b; color: #b5451b; }
    .np-chip.max { border-color: rgba(46,204,113,.5); color: #1e8e5a; }
    body.dark-mode .np-chip { background: #24243b; border-color: #33334d; color: #c8c8d4; }
    .np-ayuda { font-size: .76rem; color: #8a8a9d; margin-top: .45rem; }

    .np-resumen { position: sticky; top: 1rem; overflow: hidden; }
    .np-res-head { padding: 1.1rem 1.3rem; color: #fff; background: linear-gradient(135deg, #1a1a2e 0%, #23233d 60%, var(--cat, #b5451b) 150%); display: flex; gap: .8rem; align-items: center; }
    .np-res-head .ri { width: 48px; height: 48px; border-radius: 12px; background: rgba(255,255,255,.14); display: flex; align-items: center; justify-content: center; font-size: 1.3rem; overflow: hidden; flex-shrink: 0; }
    .np-res-head .ri img { width: 100%; height: 100%; object-fit: cover; }
    .np-res-head h5 { margin: 0; font-weight: 800; font-size: 1.05rem; }
    .np-res-head small { opacity: .8; }
    .np-kpis { display: grid; grid-template-columns: 1fr 1fr; gap: .55rem; padding: 1rem 1.3rem .3rem; }
    .np-kpi { background: #f7f5f3; border-radius: 10px; padding: .55rem .7rem; }
    .np-kpi small { display: block; font-size: .66rem; font-weight: 800; letter-spacing: .04em; text-transform: uppercase; color: #8a8a9d; }
    .np-kpi b { font-size: 1.05rem; color: #1a1a2e; }
    .np-kpi .s { display: block; font-size: .7rem; color: #adb5bd; }
    .np-kpi.verde b { color: #1e8e5a; } .np-kpi.rojo b { color: #c0392b; }
    body.dark-mode .np-kpi { background: #24243b; }
    body.dark-mode .np-kpi b { color: #f0f0f7; }
    .np-ings { padding: .4rem 1.3rem .2rem; max-height: 330px; overflow-y: auto; }
    .np-res-pie { padding: .9rem 1.3rem 1.2rem; }
    .np-res-pie .btn { width: 100%; padding: .65rem; font-weight: 800; }

    .prod-empty-panel { text-align: center; padding: 2.5rem 1rem; color: #adb5bd; }
    .prod-empty-panel i { font-size: 2.2rem; margin-bottom: .7rem; opacity: .5; display: block; }
</style>
@endpush

@section('content')

<div class="page-toolbar">
    <div>
        <h2><i class="fas fa-industry mr-2 text-warning"></i>Nueva Producción</h2>
        <p>Registra una producción y descuenta automáticamente los insumos según la receta</p>
    </div>
    <div class="toolbar-actions">
        <a href="{{ route('produccion.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left mr-1"></i>Volver
        </a>
    </div>
</div>

@if($sugerencias->isNotEmpty())
<div class="plan-card">
    <div class="plan-head">
        <div class="ph-icon"><i class="fas fa-lightbulb"></i></div>
        <div>
            <h5>¿Qué producir hoy?</h5>
            <small>Productos en su mínimo o cerca. La cantidad sugerida cubre un día de ventas y deja el stock sobre el mínimo, en tandas completas.</small>
        </div>
    </div>
    <div class="table-responsive">
    <table class="plan-tabla">
        <thead><tr><th>Producto</th><th class="text-center">Stock / mín.</th><th class="text-center">Vende al día</th><th class="text-center">Sugerido</th><th class="text-center">Alcanzan los insumos</th><th></th></tr></thead>
        <tbody>
        @foreach($sugerencias as $s)
            <tr id="plan-{{ $s->producto->id }}" style="--cat-color: {{ $s->producto->categoria->color_final ?? '#b5451b' }}">
                <td>
                    <span class="pt-nombre"><i class="fas {{ $s->producto->categoria->icono_final ?? 'fa-bread-slice' }}"></i>{{ $s->producto->nombre }}</span>
                </td>
                <td class="text-center"><span class="pt-stock {{ $s->producto->tieneStockBajo() ? 'bajo' : '' }}">{{ $s->producto->stock_actual }}</span> / {{ $s->producto->stock_minimo }}</td>
                <td class="text-center">{{ $s->porDia > 0 ? '≈ ' . $s->porDia : '—' }}</td>
                <td class="text-center"><strong>{{ $s->sugerido }}</strong></td>
                <td class="text-center">
                    @if($s->alcanza >= $s->sugerido)
                        <span class="badge-soft badge-soft-success">Sí ({{ $s->alcanza }})</span>
                    @elseif($s->alcanza > 0)
                        <span class="badge-soft badge-soft-warning" title="Limita: {{ $s->limita }}">Solo {{ $s->alcanza }}</span>
                        <span class="pt-sub">falta {{ $s->limita }}</span>
                    @else
                        <span class="badge-soft badge-soft-danger" title="Limita: {{ $s->limita }}">No alcanza</span>
                        <span class="pt-sub">falta {{ $s->limita }}</span>
                    @endif
                </td>
                <td class="text-right">
                    @if($s->producir > 0)
                        <button type="button" class="btn btn-sm btn-outline-warning js-usar-plan" data-id="{{ $s->producto->id }}" data-cantidad="{{ $s->producir }}">
                            Producir {{ $s->producir }}
                        </button>
                    @endif
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
    </div>
</div>
@endif

<form action="{{ route('produccion.store') }}" method="POST" id="formProduccion">
@csrf
@php $sugeridos = $sugerencias->pluck('producir', 'producto.id'); @endphp
<div class="np-grid">
    <div class="np-card">
        @if($errors->any())
        <div class="np-paso">
            <div class="prod-banner bad" style="margin-top:0;"><i class="fas fa-exclamation-circle"></i>{{ $errors->first() }}</div>
        </div>
        @endif

        <div class="np-paso">
            <div class="np-paso-tit"><span class="num">1</span>¿Qué vas a producir?<small>{{ $productos->count() }} productos</small></div>
            <div class="np-buscar"><i class="fas fa-search"></i><input type="text" id="buscarProd" class="form-control" placeholder="Buscar producto…"></div>
            <div class="np-tiles" id="tiles">
                @foreach($productos as $p)
                    @php
                        $tieneReceta = (bool) $p->receta;
                        $elegido = old('id_producto', $preProducto) == $p->id;
                    @endphp
                    <label class="np-tile {{ $tieneReceta ? '' : 'sin-receta' }}" data-nombre="{{ mb_strtolower($p->nombre) }}"
                           style="--cat: {{ $p->categoria->color_final ?? '#b5451b' }}" title="{{ $tieneReceta ? $p->nombre : 'Sin receta: créala primero en Recetas' }}">
                        <input type="radio" name="id_producto" value="{{ $p->id }}" {{ $elegido ? 'checked' : '' }} {{ $tieneReceta ? '' : 'disabled' }}
                               data-nombre="{{ $p->nombre }}" data-categoria="{{ $p->categoria->nombre ?? 'Sin categoría' }}"
                               data-icono="{{ $p->categoria->icono_final ?? 'fa-bread-slice' }}" data-color="{{ $p->categoria->color_final ?? '#b5451b' }}"
                               data-imagen="{{ $p->imagen ? asset('storage/'.$p->imagen) : '' }}" data-rend="{{ $p->receta->rendimiento ?? 1 }}">
                        <span class="t">
                            <span class="ti">@if($p->imagen)<img src="{{ asset('storage/'.$p->imagen) }}" alt="">@else<i class="fas {{ $p->categoria->icono_final ?? 'fa-bread-slice' }}"></i>@endif</span>
                            <span>
                                <span class="tn d-block">{{ $p->nombre }}</span>
                                <span class="ts {{ $p->tieneStockBajo() ? 'bajo' : '' }}">stock {{ $p->stock_actual }} / mín. {{ $p->stock_minimo }}</span>
                            </span>
                        </span>
                        @if(!$tieneReceta)<span class="tb">Sin receta</span>
                        @elseif(isset($sugeridos[$p->id]) && $sugeridos[$p->id] > 0)<span class="tb">Sugerido</span>@endif
                    </label>
                @endforeach
            </div>
            @error('id_producto')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
        </div>

        <div class="np-paso">
            <div class="np-paso-tit"><span class="num">2</span>¿Cuántas unidades?</div>
            <div class="np-cant">
                <div class="np-stepper">
                    <button type="button" id="btnMenos" aria-label="Menos">−</button>
                    <input type="number" name="cantidad" id="inputCantidad" value="{{ old('cantidad', $preCantidad) }}" min="1" required aria-label="Cantidad" placeholder="0">
                    <button type="button" id="btnMas" aria-label="Más">+</button>
                </div>
                <div class="np-chips" id="chipsCantidad"></div>
            </div>
            <div class="np-ayuda" id="ayudaCantidad">Elige un producto para ver sus tandas.</div>
            @error('cantidad')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>

        <div class="np-paso">
            <div class="np-paso-tit"><span class="num">3</span>Fecha y nota</div>
            <div class="row">
                <div class="col-md-5 form-group mb-md-0">
                    <label class="small font-weight-bold text-muted">Fecha</label>
                    <input type="date" name="fecha" class="form-control @error('fecha') is-invalid @enderror" value="{{ old('fecha', date('Y-m-d')) }}" max="{{ date('Y-m-d') }}" required>
                </div>
                <div class="col-md-7 form-group mb-0">
                    <label class="small font-weight-bold text-muted">Observación <span class="font-weight-normal">(opcional)</span></label>
                    <input type="text" name="observacion" class="form-control" maxlength="500" value="{{ old('observacion') }}" placeholder="Ej: turno mañana, horno 2">
                </div>
            </div>
        </div>
    </div>

    <div class="np-card np-resumen" id="resumen">
        <div class="np-res-head" id="resHead">
            <div class="ri"><i class="fas fa-industry"></i></div>
            <div><h5>Resumen</h5><small>Elige un producto</small></div>
        </div>
        <div id="panelIngredientes">
            <div class="prod-empty-panel">
                <i class="fas fa-hand-pointer"></i>
                <p class="mb-0">Elige un producto para ver los insumos que se van a usar.</p>
            </div>
        </div>
        <div class="np-res-pie">
            <button type="submit" class="btn btn-warning" id="btnGuardar" disabled>
                <i class="fas fa-industry mr-1"></i>Registrar producción
            </button>
            <a href="{{ route('produccion.index') }}" class="btn btn-link btn-sm text-muted mt-1">Cancelar</a>
        </div>
    </div>
</div>
</form>
@endsection

@push('scripts')
<script>
const form          = document.getElementById('formProduccion');
const inputCantidad = document.getElementById('inputCantidad');
const panel         = document.getElementById('panelIngredientes');
const btnGuardar    = document.getElementById('btnGuardar');
let datos = null, pedido = 0;
const fmtS = n => 'S/ ' + Number(n).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
const elegido = () => form.querySelector('input[name=id_producto]:checked');
// Muestra la ficha elegida dentro de su lista, sin mover la página
const verTile = radio => { const t = radio.closest('.np-tile'), c = document.getElementById('tiles'); c.scrollTop = Math.max(0, t.offsetTop - 8); };

function pintarCabecera(opt) {
    const head = document.getElementById('resHead');
    if (!opt) return;
    head.style.setProperty('--cat', opt.dataset.color);
    head.innerHTML = `<div class="ri">${opt.dataset.imagen ? `<img src="${opt.dataset.imagen}" alt="">` : `<i class="fas ${opt.dataset.icono}"></i>`}</div>
        <div><h5>${opt.dataset.nombre}</h5><small>${opt.dataset.categoria}</small></div>`;
}

function pintarChips() {
    const cont = document.getElementById('chipsCantidad');
    const ayuda = document.getElementById('ayudaCantidad');
    if (!datos) { cont.innerHTML = ''; return; }
    const r = Math.max(1, datos.rendimiento);
    let html = [1, 2, 3].map(n => `<button type="button" class="np-chip" data-v="${r * n}">${n} tanda${n > 1 ? 's' : ''} (${r * n})</button>`).join('');
    if (datos.maximo > 0) html += `<button type="button" class="np-chip max" data-v="${datos.maximo}">Máximo (${datos.maximo})</button>`;
    cont.innerHTML = html;
    cont.querySelectorAll('.np-chip').forEach(b => b.addEventListener('click', () => { inputCantidad.value = b.dataset.v; inputCantidad.dataset.auto = '0'; calcular(); }));
    ayuda.textContent = `La receta rinde ${r} und. por tanda. Con los insumos de hoy alcanza para ${datos.maximo} und.` + (datos.limita ? ` (limita: ${datos.limita}).` : '.');
}

function calcular() {
    if (!datos) return;
    const cantidad = Math.max(0, parseInt(inputCantidad.value) || 0);
    const lotes = cantidad / Math.max(datos.rendimiento, 1);
    const costo = datos.detalles.reduce((s, d) => s + d.cantidad * lotes * (d.costo || 0), 0);
    const venta = cantidad * datos.precio;
    let sinStock = false;
    let ings = '';
    datos.detalles.forEach(d => {
        const necesario = +(d.cantidad * lotes).toFixed(6);
        const stock = parseFloat(d.stock_actual);
        const ok = stock + 1e-9 >= necesario;
        if (!ok) sinStock = true;
        const pct = necesario > 0 ? Math.min(stock / necesario * 100, 100) : 100;
        const color = ok ? '#2ecc71' : '#e74c3c';
        ings += `<div class="ing-row">
            <div class="ing-body">
                <div class="ing-nombre">${d.nombre}</div>
                <div class="ing-bar"><span style="width:${pct}%; background:${color};"></span></div>
            </div>
            <div class="ing-cifras">
                <span class="ing-necesario" style="color:${ok ? 'inherit' : color};">${fmtCantU(necesario, d.abreviatura)}</span>
                <span class="ing-stock">hay ${fmtCantU(d.stock_actual, d.abreviatura)}</span>
            </div>
        </div>`;
    });
    const valido = cantidad > 0 && !sinStock;
    panel.innerHTML = `
        <div class="np-kpis">
            <div class="np-kpi ${datos.maximo >= cantidad ? 'verde' : 'rojo'}"><small>Alcanza para</small><b>${datos.maximo} und.</b><span class="s">${datos.limita ? 'limita: ' + datos.limita : '&nbsp;'}</span></div>
            <div class="np-kpi"><small>Stock después</small><b>${datos.stock + cantidad} und.</b><span class="s">hoy hay ${datos.stock}</span></div>
            <div class="np-kpi"><small>Costo de insumos</small><b>${fmtS(costo)}</b><span class="s">${fmtS(cantidad ? costo / cantidad : 0)} por unidad</span></div>
            <div class="np-kpi ${venta - costo >= 0 ? 'verde' : 'rojo'}"><small>Ganancia estimada</small><b>${fmtS(venta - costo)}</b><span class="s">vendiendo a ${fmtS(datos.precio)}</span></div>
        </div>
        <div class="np-ings">${ings}</div>
        <div class="px-4">${cantidad <= 0
            ? '<div class="prod-banner warn"><i class="fas fa-info-circle"></i>Escribe cuántas unidades vas a producir.</div>'
            : sinStock
                ? `<div class="prod-banner bad"><i class="fas fa-times-circle"></i>No alcanzan los insumos en rojo.${datos.maximo > 0 ? ' Puedes producir hasta ' + datos.maximo + ' und.' : ''}</div>`
                : `<div class="prod-banner ok"><i class="fas fa-check-circle"></i>Alcanzan los insumos: ${fmtCant(lotes, 2)} tanda${lotes === 1 ? '' : 's'}.</div>`}
        </div>`;
    btnGuardar.disabled = !valido;
    btnGuardar.innerHTML = `<i class="fas fa-industry mr-1"></i>Registrar ${cantidad > 0 ? cantidad + ' und.' : 'producción'}`;
}

function cargar() {
    const opt = elegido();
    if (!opt) return;
    pintarCabecera(opt);
    panel.innerHTML = '<div class="prod-empty-panel"><i class="fas fa-spinner fa-spin"></i><p class="mb-0">Cargando receta…</p></div>';
    btnGuardar.disabled = true;
    const n = ++pedido;
    fetch(`{{ url('produccion/ingredientes') }}/${opt.value}`)
        .then(r => r.json())
        .then(data => {
            if (n !== pedido) return;
            datos = data;
            if (!data) {
                panel.innerHTML = `<div class="px-4 pt-3"><div class="prod-banner bad" style="margin-top:0;"><i class="fas fa-exclamation-triangle"></i>Este producto no tiene receta.</div></div>`;
                return;
            }
            pintarChips();
            // Sin cantidad escrita: empezar con una tanda completa
            if (!inputCantidad.value || inputCantidad.dataset.auto === '1') { inputCantidad.value = Math.max(1, data.rendimiento); inputCantidad.dataset.auto = '1'; }
            calcular();
        })
        .catch(() => { panel.innerHTML = '<div class="px-4 pt-3"><div class="prod-banner bad" style="margin-top:0;"><i class="fas fa-exclamation-circle"></i>No se pudo cargar la receta.</div></div>'; });
}

form.querySelectorAll('input[name=id_producto]').forEach(r => r.addEventListener('change', cargar));
inputCantidad.addEventListener('input', () => { inputCantidad.dataset.auto = '0'; calcular(); });
document.getElementById('btnMenos').addEventListener('click', () => {
    const paso = datos ? Math.max(1, datos.rendimiento) : 1;
    inputCantidad.value = Math.max(1, (parseInt(inputCantidad.value) || 0) - paso); inputCantidad.dataset.auto = '0'; calcular();
});
document.getElementById('btnMas').addEventListener('click', () => {
    const paso = datos ? Math.max(1, datos.rendimiento) : 1;
    inputCantidad.value = (parseInt(inputCantidad.value) || 0) + paso; inputCantidad.dataset.auto = '0'; calcular();
});
document.getElementById('buscarProd').addEventListener('input', function () {
    const q = this.value.trim().toLowerCase();
    document.querySelectorAll('#tiles .np-tile').forEach(t => t.style.display = !q || t.dataset.nombre.includes(q) ? '' : 'none');
});

// "Producir N" desde ¿Qué producir hoy?
document.querySelectorAll('.js-usar-plan').forEach(btn => btn.addEventListener('click', () => {
    const radio = form.querySelector(`input[name=id_producto][value="${btn.dataset.id}"]`);
    if (!radio) return;
    radio.checked = true;
    inputCantidad.value = btn.dataset.cantidad;
    inputCantidad.dataset.auto = '0';
    document.querySelectorAll('.plan-tabla tr').forEach(tr => tr.classList.remove('elegido'));
    btn.closest('tr').classList.add('elegido');
    verTile(radio);
    cargar();
    form.scrollIntoView({ behavior: 'smooth', block: 'start' });
}));

if (elegido()) { verTile(elegido()); cargar(); }
</script>
@endpush
