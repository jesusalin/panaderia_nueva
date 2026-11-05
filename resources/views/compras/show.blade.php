@extends('layouts.app')
@section('title', 'Compra ' . $compra->codigo)
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('compras.index') }}">Compras</a></li>
    <li class="breadcrumb-item active">{{ $compra->codigo }}</li>
@endsection

@include('partials.compras-estilos')

@push('styles')
<style>
    .cs-tabla td.num, .cs-tabla th.num { text-align: right; white-space: nowrap; }
    .cs-dif { font-size: .72rem; font-weight: 800; color: #c0392b; display: block; }
    .cs-totales { margin-left: auto; max-width: 300px; font-size: .88rem; margin-top: .8rem; }
    .cs-totales div { display: flex; justify-content: space-between; padding: .15rem 0; color: #6c757d; }
    .cs-totales .tt { border-top: 1px solid #eee5dd; margin-top: .3rem; padding-top: .45rem; font-weight: 800; color: #1a1a2e; font-size: 1.05rem; }
    body.dark-mode .cs-totales .tt { color: #f0f0f7; border-color: #33334d; }
    .cs-grid { display: grid; grid-template-columns: minmax(0, 1.7fr) minmax(260px, 1fr); gap: 1.1rem; }
    @media (max-width: 991px) { .cs-grid { grid-template-columns: 1fr; } }
    .cs-aviso { border-radius: 12px; padding: .75rem 1rem; margin-bottom: 1rem; font-size: .88rem; display: flex; gap: .6rem; align-items: flex-start; }
    .cs-aviso.anulada { background: #f2f2f4; color: #555; }
    .cs-aviso.pendiente { background: #fff6e5; color: #7a5200; }
    .cs-aviso.atrasada { background: #fdecea; color: #a93226; }
    body.dark-mode .cs-aviso.anulada { background: #24243b; color: #b0b0cc; }
    body.dark-mode .cs-aviso.pendiente { background: rgba(212,136,15,.14); color: #f0c674; }
    body.dark-mode .cs-aviso.atrasada { background: rgba(231,76,60,.14); color: #f1a097; }
    .cs-impresion { display: none; }
    @media print {
        .main-sidebar, .main-header, .main-footer, .content-header, .ih-acciones, .no-imprimir, .breadcrumb, .alert { display: none !important; }
        .content-wrapper { margin: 0 !important; background: #fff !important; }
        .inv-hero { background: #fff !important; color: #000 !important; border: 1px solid #ccc; }
        .inv-hero .ih-icono { display: none; }
        .cs-grid { grid-template-columns: 1fr; }
        .inv-box { box-shadow: none !important; border: 1px solid #ddd; }
        .cs-impresion { display: block; margin-bottom: 1rem; }
    }
</style>
@endpush

@section('content')
@php
    $C = \App\Support\Cantidad::class;
    $est = $compra->estado === 'pendiente' && $compra->atrasada ? 'atrasada' : $compra->estado;
    $acento = ['pendiente' => '#d4880f', 'atrasada' => '#c0392b', 'recibida' => '#1e8e5a', 'anulada' => '#6c757d'][$est];
    $textoEstado = ['pendiente' => 'Por recibir', 'atrasada' => 'Atrasada', 'recibida' => 'Recibida', 'anulada' => 'Anulada'][$est];
    $prov = $compra->proveedor;
    $puedeInsumo = auth()->user()->hasPermiso('inventario.materia_prima');
    // Texto del pedido para enviarlo por WhatsApp
    $lineas = $compra->detalles->map(fn ($d) => '• ' . ($d->materia->nombre ?? '') . ': ' . $C::conUnidad($d->cantidad, $d->materia->unidad->abreviatura ?? ''))->join("\n");
    $mensajeWa = 'Hola' . ($prov && $prov->contacto ? ' ' . $prov->contacto : '') . ", le escribe Panadería Muruwasi. Pedido {$compra->codigo}:\n{$lineas}\nGracias.";
@endphp

<div class="cs-impresion">
    <h3 class="mb-0">Panadería Muruwasi — Orden de compra {{ $compra->codigo }}</h3>
    <div>Proveedor: {{ $prov->nombre ?? '—' }} {{ $prov && $prov->ruc ? '· RUC ' . $prov->ruc : '' }} · Fecha: {{ $compra->fecha_compra->format('d/m/Y') }}</div>
</div>

<div class="inv-hero" style="--acento: {{ $acento }}">
    <div class="ih-icono"><i class="fas fa-shopping-cart"></i></div>
    <div>
        <small>Compra {{ $compra->codigo }}{{ $compra->numero_doc ? ' · ' . $compra->numero_doc : ' · sin factura' }} · <span class="cp-estado {{ $est }}" style="text-transform:none;letter-spacing:0">{{ $textoEstado }}</span></small>
        <h3>{{ $prov->nombre ?? '—' }}</h3>
    </div>
    <div class="ih-datos">
        <div><b>S/ {{ number_format($compra->total, 2) }}</b><span>total {{ $compra->incluye_igv ? '(precios con IGV)' : 'con IGV' }}</span></div>
        <div><b>{{ $compra->detalles->count() }}</b><span>insumo{{ $compra->detalles->count() == 1 ? '' : 's' }}</span></div>
        <div><b>{{ $compra->fecha_compra->format('d/m/Y') }}</b><span>fecha de compra</span></div>
    </div>
    <div class="ih-acciones">
        @if($compra->estado === 'pendiente')
            <a href="{{ route('compras.recibir.form', $compra) }}" class="btn btn-success btn-sm"><i class="fas fa-box-open mr-1"></i>Recibir pedido</a>
            @if($prov && $prov->whatsapp)
                <a href="https://wa.me/{{ $prov->whatsapp }}?text={{ rawurlencode($mensajeWa) }}" target="_blank" rel="noopener" class="btn btn-light btn-sm"><i class="fab fa-whatsapp mr-1" style="color:#25d366"></i>Enviar pedido</a>
            @endif
        @endif
        <button type="button" class="btn btn-light btn-sm" onclick="window.print()"><i class="fas fa-print mr-1"></i>Imprimir</button>
        @if($compra->estado !== 'anulada')
            <a href="{{ route('compras.create', ['repetir' => $compra->id]) }}" class="btn btn-outline-light btn-sm"><i class="fas fa-redo mr-1"></i>Repetir compra</a>
            <button type="button" class="btn btn-outline-light btn-sm" data-toggle="modal" data-target="#modalAnular"><i class="fas fa-ban mr-1"></i>Anular</button>
        @else
            <a href="{{ route('compras.create', ['repetir' => $compra->id]) }}" class="btn btn-outline-light btn-sm"><i class="fas fa-redo mr-1"></i>Volver a registrarla</a>
        @endif
        <a href="{{ route('compras.index') }}" class="btn btn-outline-light btn-sm"><i class="fas fa-arrow-left mr-1"></i>Volver</a>
    </div>
</div>

@if($compra->estado === 'anulada')
    <div class="cs-aviso anulada"><i class="fas fa-ban mt-1"></i><div>
        <b>Compra anulada</b> {{ $compra->anulada_at ? 'el ' . $compra->anulada_at->format('d/m/Y H:i') : '' }}{{ $compra->usuarioAnula ? ' por ' . ($compra->usuarioAnula->apodo ?? $compra->usuarioAnula->nombre) : '' }}.
        @if($compra->motivo_anulacion)<br>Motivo: {{ $compra->motivo_anulacion }}@endif
        @if($compra->recibida_at)<br>Había sido recibida: el stock que entró se devolvió y quedó en el kardex.@endif
    </div></div>
@elseif($compra->estado === 'pendiente')
    <div class="cs-aviso {{ $est }}"><i class="fas {{ $est === 'atrasada' ? 'fa-hourglass-end' : 'fa-truck' }} mt-1"></i><div>
        <b>{{ $compra->dias_espera <= 0 ? 'Pedida hoy.' : 'Esperando el pedido hace ' . $compra->dias_espera . ' día' . ($compra->dias_espera == 1 ? '' : 's') . '.' }}</b>
        El stock todavía no subió: cuando llegue, pulsa <b>Recibir pedido</b> y anota si vino una cantidad distinta.
        @if($est === 'atrasada' && $prov && $prov->telefono) Llama al proveedor: {{ $prov->telefono }}. @endif
    </div></div>
@endif

<div class="cs-grid">
    <div class="inv-box">
        <h5><i class="fas fa-list"></i>Insumos</h5>
        <div class="table-responsive">
        <table class="inv-tabla cs-tabla">
            <thead><tr>
                <th>Insumo</th>
                <th class="num">Pedido</th>
                @if($compra->recibida_at)<th class="num">Llegó</th>@endif
                <th class="num">Precio{{ $compra->incluye_igv ? ' (con IGV)' : '' }}</th>
                <th class="num">Subtotal</th>
            </tr></thead>
            <tbody>
            @foreach($compra->detalles as $d)
                @php
                    $u = $d->materia->unidad->abreviatura ?? '';
                    $neto = $compra->incluye_igv ? $d->precio_unitario / 1.18 : $d->precio_unitario;
                    $ant = $anteriores[$d->id_materia] ?? null;
                    $var = $ant ? round(($neto - $ant) / max($ant, 0.01) * 100) : null;
                    $dif = $d->cantidad_recibida !== null ? round($d->cantidad_recibida - $d->cantidad, 3) : 0;
                @endphp
                <tr>
                    <td>
                        @if($puedeInsumo && $d->materia)
                            <a href="{{ route('materia-prima.show', $d->materia) }}" class="font-weight-bold">{{ $d->materia->nombre }}</a>
                        @else <b>{{ $d->materia->nombre ?? '—' }}</b> @endif
                    </td>
                    <td class="num">{{ $C::conUnidad($d->cantidad, $u) }}</td>
                    @if($compra->recibida_at)
                        <td class="num">
                            {{ $C::conUnidad($d->cantidad_real, $u) }}
                            @if(abs($dif) > 0.0005)<span class="cs-dif">{{ $dif < 0 ? 'faltó ' : 'sobró ' }}{{ $C::conUnidad(abs($dif), $u) }}</span>@endif
                        </td>
                    @endif
                    <td class="num">
                        S/ {{ number_format($d->precio_unitario, 2) }}<small class="text-muted">/{{ $u }}</small>
                        @if($var !== null)
                            <br><span class="cp-var {{ $var > 0 ? 'sube' : ($var < 0 ? 'baja' : 'igual') }}" title="Compra anterior: S/ {{ number_format($ant, 2) }} sin IGV">
                                {{ $var > 0 ? '▲ ' . $var . '%' : ($var < 0 ? '▼ ' . abs($var) . '%' : 'igual') }}
                            </span>
                        @endif
                    </td>
                    <td class="num font-weight-bold">S/ {{ number_format($d->subtotal, 2) }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
        </div>
        <div class="cs-totales">
            <div><span>Subtotal</span><span>S/ {{ number_format($compra->subtotal, 2) }}</span></div>
            <div><span>IGV (18 %)</span><span>S/ {{ number_format($compra->igv, 2) }}</span></div>
            <div class="tt"><span>Total</span><span>S/ {{ number_format($compra->total, 2) }}</span></div>
        </div>
        @if($compra->observaciones)
            <p class="mt-3 mb-0 small"><i class="fas fa-comment-dots mr-1 text-muted"></i>{{ $compra->observaciones }}</p>
        @endif
    </div>

    <div>
        <div class="inv-box mb-3">
            <h5><i class="fas fa-stream"></i>Seguimiento</h5>
            <ul class="cp-tiempo">
                @if($compra->orden)
                    <li><span class="tp-ic"><i class="fas fa-robot"></i></span><b>Orden automática #{{ $compra->orden->id }}</b><small>El sistema detectó stock bajo y sugirió esta compra</small></li>
                @endif
                <li><span class="tp-ic"><i class="fas fa-file-alt"></i></span><b>Registrada</b>
                    <small>{{ $compra->created_at?->format('d/m/Y H:i') }} · {{ $compra->usuario->apodo ?? $compra->usuario->nombre ?? '—' }}</small></li>
                @if($compra->estado === 'pendiente')
                    <li><span class="tp-ic espera"><i class="fas fa-truck"></i></span><b>Esperando el pedido</b>
                        <small>{{ $compra->dias_espera <= 0 ? 'Desde hoy' : 'Hace ' . $compra->dias_espera . ' día(s)' }}</small></li>
                @endif
                @if($compra->recibida_at)
                    <li><span class="tp-ic ok"><i class="fas fa-box-open"></i></span><b>Recibida — el stock subió</b>
                        <small>{{ $compra->recibida_at->format('d/m/Y H:i') }}{{ $compra->usuarioRecibe ? ' · ' . ($compra->usuarioRecibe->apodo ?? $compra->usuarioRecibe->nombre) : '' }}</small>
                        @if($compra->nota_recepcion)<small class="d-block">“{{ $compra->nota_recepcion }}”</small>@endif
                        @if($compra->recepcion_incompleta)<small class="d-block text-danger font-weight-bold">Llegó una cantidad distinta a la pedida</small>@endif
                    </li>
                @endif
                @if($compra->estado === 'anulada')
                    <li><span class="tp-ic mal"><i class="fas fa-ban"></i></span><b>Anulada</b>
                        <small>{{ $compra->anulada_at?->format('d/m/Y H:i') }}{{ $compra->usuarioAnula ? ' · ' . ($compra->usuarioAnula->apodo ?? $compra->usuarioAnula->nombre) : '' }}</small>
                        @if($compra->motivo_anulacion)<small class="d-block">“{{ $compra->motivo_anulacion }}”</small>@endif
                    </li>
                @endif
            </ul>
        </div>

        @if($prov)
        <div class="inv-box">
            <h5><i class="fas fa-truck"></i>Proveedor</h5>
            <div class="d-flex align-items-center mb-2" style="gap:.7rem">
                <div class="pv-avatar {{ $prov->estado !== 'activo' ? 'inactivo' : '' }}">{{ $prov->getIniciales() }}</div>
                <div>
                    @if(auth()->user()->hasPermiso('compras.proveedores'))
                        <a href="{{ route('proveedores.show', $prov) }}" class="font-weight-bold">{{ $prov->nombre }}</a>
                    @else <b>{{ $prov->nombre }}</b> @endif
                    <div class="small text-muted">{{ $prov->ruc ? 'RUC ' . $prov->ruc : 'Sin RUC' }}{{ $prov->contacto ? ' · ' . $prov->contacto : '' }}</div>
                </div>
            </div>
            <div class="pv-contacto no-imprimir">
                @if($prov->telefono)<a href="tel:{{ preg_replace('/[^\d+]/', '', $prov->telefono) }}"><i class="fas fa-phone"></i>{{ $prov->telefono }}</a>@endif
                @if($prov->whatsapp)<a class="wa" href="https://wa.me/{{ $prov->whatsapp }}" target="_blank" rel="noopener"><i class="fab fa-whatsapp"></i>WhatsApp</a>@endif
                @if($prov->email)<a href="mailto:{{ $prov->email }}"><i class="fas fa-envelope"></i>Correo</a>@endif
            </div>
        </div>
        @endif
    </div>
</div>

@if($compra->estado !== 'anulada')
<div class="modal fade" id="modalAnular" tabindex="-1" role="dialog" aria-labelledby="tituloAnular" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <form class="modal-content" method="POST" action="{{ route('compras.anular', $compra) }}">
            @csrf @method('PUT')
            <div class="modal-header">
                <h5 class="modal-title" id="tituloAnular"><i class="fas fa-ban text-danger mr-1"></i>Anular la compra {{ $compra->codigo }}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                @if($compra->estado === 'recibida')
                    <div class="alert alert-warning small mb-3"><i class="fas fa-exclamation-triangle mr-1"></i>Esta compra ya se recibió: al anularla, <b>el stock de sus insumos baja</b> lo que había entrado (queda en el kardex como devolución).</div>
                @else
                    <p class="small text-muted">El pedido no llegó todavía, así que el stock no cambia.</p>
                @endif
                @if($compra->orden)
                    <p class="small text-muted">Si el insumo sigue faltando, su orden automática vuelve a pendientes.</p>
                @endif
                <label for="motivoAnular">¿Por qué la anulas? <span class="text-danger">*</span></label>
                <textarea name="motivo_anulacion" id="motivoAnular" class="form-control @error('motivo_anulacion') is-invalid @enderror" rows="2" maxlength="200" required
                          placeholder="Ej: el proveedor no tenía stock / se registró dos veces">{{ old('motivo_anulacion') }}</textarea>
                @error('motivo_anulacion')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-danger"><i class="fas fa-ban mr-1"></i>Anular compra</button>
            </div>
        </form>
    </div>
</div>
@endif
@endsection

@push('scripts')
@if($errors->has('motivo_anulacion'))
<script>document.addEventListener('DOMContentLoaded', function () { if (window.$) $('#modalAnular').modal('show'); });</script>
@endif
@endpush
