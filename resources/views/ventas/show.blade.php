@extends('layouts.app')
@section('title', 'Venta ' . $venta->numero_venta)
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('ventas.index') }}">Ventas</a></li>
    <li class="breadcrumb-item active">{{ $venta->numero_venta }}</li>
@endsection

@include('partials.compras-estilos')

@push('styles')
<style>
    .cs-tabla td.num, .cs-tabla th.num { text-align: right; white-space: nowrap; }
    .cs-totales { margin-left: auto; max-width: 300px; font-size: .88rem; margin-top: .8rem; }
    .cs-totales div { display: flex; justify-content: space-between; padding: .15rem 0; color: #6c757d; }
    .cs-totales .tt { border-top: 1px solid #eee5dd; margin-top: .3rem; padding-top: .45rem; font-weight: 800; color: #1a1a2e; font-size: 1.05rem; }
    body.dark-mode .cs-totales .tt { color: #f0f0f7; border-color: #33334d; }
    .cs-grid { display: grid; grid-template-columns: minmax(0, 1.7fr) minmax(260px, 1fr); gap: 1.1rem; }
    @media (max-width: 991px) { .cs-grid { grid-template-columns: minmax(0, 1fr); } }
    .cs-aviso { border-radius: 12px; padding: .75rem 1rem; margin-bottom: 1rem; font-size: .88rem; display: flex; gap: .6rem; align-items: flex-start; background: #f2f2f4; color: #555; }
    body.dark-mode .cs-aviso { background: #24243b; color: #b0b0cc; }
    .vs-pago { display: grid; grid-template-columns: repeat(auto-fit, minmax(110px, 1fr)); gap: .6rem; }
    .vs-pago div { background: #faf7f4; border-radius: 10px; padding: .55rem .75rem; }
    .vs-pago small { display: block; font-size: .64rem; font-weight: 800; letter-spacing: .04em; text-transform: uppercase; color: #8a8a9d; }
    .vs-pago b { font-size: .98rem; color: #1a1a2e; }
    body.dark-mode .vs-pago div { background: #24243b; }
    body.dark-mode .vs-pago b { color: #f0f0f7; }
</style>
@endpush

@section('content')
@php
    $anulada = $venta->estado === 'anulada';
    $m = $venta->medio;
    $cli = $venta->cliente;
    $unidades = (int) $venta->detalles->sum('cantidad');
@endphp

<div class="inv-hero" style="--acento: {{ $anulada ? '#6c757d' : '#1e8e5a' }}">
    <div class="ih-icono"><i class="fas fa-receipt"></i></div>
    <div>
        <small>Venta {{ $venta->numero_venta }} · <span class="cp-estado {{ $anulada ? 'anulada' : 'recibida' }}" style="text-transform:none;letter-spacing:0">{{ $anulada ? 'Anulada' : 'Completada' }}</span></small>
        <h3 style="{{ $anulada ? 'text-decoration:line-through' : '' }}">S/ {{ number_format($venta->total, 2) }}</h3>
        <small style="text-transform:none;letter-spacing:0;opacity:.85">{{ ucfirst($venta->fecha_venta->locale('es')->translatedFormat('l d \d\e F, H:i')) }}</small>
    </div>
    <div class="ih-datos">
        <div><b>{{ $cli->nombre ?? 'Cliente General' }}</b><span>cliente</span></div>
        <div><b><i class="fas {{ $m['icono'] }} mr-1"></i>{{ $m['texto'] }}</b><span>pago</span></div>
        <div><b>{{ $unidades }}</b><span>unidad(es) · {{ $venta->detalles->count() }} producto(s)</span></div>
    </div>
    <div class="ih-acciones">
        <a href="{{ route('ventas.ticket', $venta) }}" target="_blank" class="btn btn-light btn-sm"><i class="fas fa-receipt mr-1"></i>Ticket</a>
        <a href="{{ route('ventas.pdf', $venta) }}" target="_blank" class="btn btn-light btn-sm"><i class="fas fa-file-pdf mr-1 text-danger"></i>PDF</a>
        @unless($anulada)
            <button type="button" class="btn btn-outline-light btn-sm" data-toggle="modal" data-target="#modalAnular"><i class="fas fa-ban mr-1"></i>Anular</button>
        @endunless
        <a href="{{ route('ventas.index') }}" class="btn btn-outline-light btn-sm"><i class="fas fa-arrow-left mr-1"></i>Volver</a>
    </div>
</div>

@if($anulada)
    <div class="cs-aviso"><i class="fas fa-ban mt-1"></i><div>
        <b>Venta anulada</b>{{ $venta->anulada_at ? ' el ' . $venta->anulada_at->format('d/m/Y H:i') : '' }}{{ $venta->usuarioAnula ? ' por ' . ($venta->usuarioAnula->nombre) : '' }}.
        Los productos volvieron al stock y no cuenta en la caja.
        @if($venta->motivo_anulacion)<div class="mt-1">Motivo: “{{ $venta->motivo_anulacion }}”</div>@endif
    </div></div>
@endif

<div class="cs-grid">
    <div class="inv-box">
        <h5><i class="fas fa-bread-slice"></i>Productos</h5>
        <div class="table-responsive">
        <table class="inv-tabla cs-tabla">
            <thead><tr><th>Producto</th><th class="num">Cant.</th><th class="num">Precio</th><th class="num">Importe</th></tr></thead>
            <tbody>
            @foreach($venta->detalles as $d)
                <tr>
                    <td><b>{{ $d->producto->nombre ?? 'Producto eliminado' }}</b>@if($d->producto?->categoria)<div class="small text-muted">{{ $d->producto->categoria->nombre }}</div>@endif</td>
                    <td class="num">{{ $d->cantidad }}</td>
                    <td class="num">S/ {{ number_format($d->precio_unitario, 2) }}</td>
                    <td class="num font-weight-bold">S/ {{ number_format($d->subtotal, 2) }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
        </div>
        <div class="cs-totales">
            <div><span>Op. gravada</span><span>S/ {{ number_format($venta->subtotal, 2) }}</span></div>
            <div><span>IGV (18 %) incluido</span><span>S/ {{ number_format($venta->igv, 2) }}</span></div>
            <div class="tt"><span>Total</span><span>S/ {{ number_format($venta->total, 2) }}</span></div>
        </div>
        @if($venta->observaciones)
            <p class="mt-3 mb-0 small"><i class="fas fa-comment-dots mr-1 text-muted"></i>{{ $venta->observaciones }}</p>
        @endif
    </div>

    <div>
        <div class="inv-box mb-3">
            <h5><i class="fas fa-wallet"></i>Pago</h5>
            <div class="vs-pago">
                <div><small>Medio</small><b style="color: {{ $m['color'] }}"><i class="fas {{ $m['icono'] }} mr-1"></i>{{ $m['texto'] }}</b></div>
                @if($venta->tipo_pago === 'efectivo' && $venta->monto_recibido !== null)
                    <div><small>Recibido</small><b>S/ {{ number_format($venta->monto_recibido, 2) }}</b></div>
                    <div><small>Vuelto</small><b>S/ {{ number_format($venta->vuelto ?? 0, 2) }}</b></div>
                @endif
                @if($venta->numero_operacion)
                    <div><small>N° operación</small><b>{{ $venta->numero_operacion }}</b></div>
                @endif
            </div>
        </div>

        <div class="inv-box mb-3">
            <h5><i class="fas fa-stream"></i>Seguimiento</h5>
            <ul class="cp-tiempo">
                <li><span class="tp-ic ok"><i class="fas fa-cash-register"></i></span><b>Venta registrada — el stock bajó</b>
                    <small>{{ $venta->fecha_venta->format('d/m/Y H:i') }} · {{ $venta->usuario->nombre ?? '—' }}</small></li>
                @if($anulada)
                    <li><span class="tp-ic mal"><i class="fas fa-ban"></i></span><b>Anulada — los productos volvieron al stock</b>
                        <small>{{ $venta->anulada_at?->format('d/m/Y H:i') }}{{ $venta->usuarioAnula ? ' · ' . ($venta->usuarioAnula->nombre) : '' }}</small>
                        @if($venta->motivo_anulacion)<small class="d-block">“{{ $venta->motivo_anulacion }}”</small>@endif</li>
                @endif
            </ul>
        </div>

        <div class="inv-box">
            <h5><i class="fas fa-user"></i>Cliente</h5>
            <b>{{ $cli->nombre ?? 'Cliente General' }}</b>
            <div class="small text-muted">
                {{ $cli?->ruc ? 'RUC ' . $cli->ruc : ($cli?->dni ? 'DNI ' . $cli->dni : 'Sin documento') }}{{ $cli?->telefono ? ' · ' . $cli->telefono : '' }}
            </div>
            @if($cli && $cli->nombre !== 'Cliente General' && Route::has('clientes.show') && auth()->user()->hasModulo('clientes'))
                <a href="{{ route('clientes.show', $cli) }}" class="btn btn-light btn-sm mt-2"><i class="fas fa-history mr-1"></i>Ver sus compras</a>
            @endif
        </div>
    </div>
</div>

@unless($anulada)
<div class="modal fade" id="modalAnular" tabindex="-1" role="dialog" aria-labelledby="tituloAnular" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <form class="modal-content" method="POST" action="{{ route('ventas.anular', $venta) }}">
            @csrf @method('PUT')
            <div class="modal-header">
                <h5 class="modal-title" id="tituloAnular"><i class="fas fa-ban text-danger mr-1"></i>Anular la venta {{ $venta->numero_venta }}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <p class="small text-muted">Los {{ $unidades }} producto(s) vuelven al stock (queda en el kardex como devolución) y la venta deja de contar en la caja. La venta no se borra: queda registrada como anulada.</p>
                <label for="motivoAnular">¿Por qué la anulas? <span class="text-danger">*</span></label>
                <textarea name="motivo_anulacion" id="motivoAnular" class="form-control @error('motivo_anulacion') is-invalid @enderror" rows="2" maxlength="200" required minlength="4"
                          placeholder="Ej: se cobró dos veces / el cliente devolvió el pedido">{{ old('motivo_anulacion') }}</textarea>
                @error('motivo_anulacion')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-danger"><i class="fas fa-ban mr-1"></i>Anular venta</button>
            </div>
        </form>
    </div>
</div>
@endunless
@endsection

@push('scripts')
@if($errors->has('motivo_anulacion'))
<script>document.addEventListener('DOMContentLoaded', function () { if (window.$) $('#modalAnular').modal('show'); });</script>
@endif
@endpush
