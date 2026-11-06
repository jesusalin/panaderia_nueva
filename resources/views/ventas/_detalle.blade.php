{{-- Resumen de una venta para la ventana rápida (js-ver-detalle). La página completa es ventas/show. --}}
@php
    $anulada = $venta->estado === 'anulada';
    $m = $venta->medio;
@endphp
<style>
    .vd-cab { display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; margin-bottom: .9rem; }
    .vd-cab b { font-size: 1.5rem; color: #1a1a2e; }
    body.dark-mode .vd-cab b { color: #f0f0f7; }
    .vd-cab small { display: block; color: #8a8a9d; font-size: .8rem; }
    .vd-tabla { width: 100%; font-size: .88rem; }
    .vd-tabla td { padding: .35rem 0; border-bottom: 1px dashed #eee5dd; }
    .vd-tabla td.num { text-align: right; white-space: nowrap; }
    .vd-tot { display: flex; justify-content: space-between; font-size: .82rem; color: #8a8a9d; margin-top: .25rem; }
    .vd-tot.tt { font-size: 1rem; font-weight: 800; color: #1a1a2e; }
    body.dark-mode .vd-tot.tt { color: #f0f0f7; }
    .vd-chip { display: inline-flex; align-items: center; gap: .3rem; border-radius: 20px; padding: .18rem .6rem; font-size: .74rem; font-weight: 700; background: #f3f0ec; color: #555; }
</style>
<div class="vd-cab">
    <div>
        <b style="{{ $anulada ? 'text-decoration:line-through;opacity:.6' : '' }}">S/ {{ number_format($venta->total, 2) }}</b>
        <small>{{ $venta->numero_venta }} · {{ $venta->fecha_venta->format('d/m/Y H:i') }} · {{ $venta->usuario->nombre ?? '—' }}</small>
        <small>{{ $venta->cliente->nombre ?? 'Cliente General' }}</small>
    </div>
    <div class="text-right">
        <span class="vd-chip"><i class="fas {{ $m['icono'] }}" style="color: {{ $m['color'] }}"></i>{{ $m['texto'] }}</span>
        <div class="mt-1"><span class="badge-soft {{ $anulada ? 'badge-soft-danger' : 'badge-soft-success' }}">{{ $anulada ? 'Anulada' : 'Completada' }}</span></div>
    </div>
</div>
@if($anulada && $venta->motivo_anulacion)
    <div class="alert alert-secondary small py-2"><i class="fas fa-ban mr-1"></i>Anulada: “{{ $venta->motivo_anulacion }}”</div>
@endif
<table class="vd-tabla">
    @foreach($venta->detalles as $d)
        <tr>
            <td><b>{{ $d->cantidad }}</b> × {{ $d->producto->nombre ?? 'Producto eliminado' }} <span class="text-muted small">S/ {{ number_format($d->precio_unitario, 2) }}</span></td>
            <td class="num font-weight-bold">S/ {{ number_format($d->subtotal, 2) }}</td>
        </tr>
    @endforeach
</table>
<div class="vd-tot mt-2"><span>IGV (18 %) incluido</span><span>S/ {{ number_format($venta->igv, 2) }}</span></div>
@if($venta->tipo_pago === 'efectivo' && $venta->monto_recibido !== null)
    <div class="vd-tot"><span>Recibido · vuelto</span><span>S/ {{ number_format($venta->monto_recibido, 2) }} · S/ {{ number_format($venta->vuelto ?? 0, 2) }}</span></div>
@endif
@if($venta->numero_operacion)
    <div class="vd-tot"><span>N° de operación</span><span>{{ $venta->numero_operacion }}</span></div>
@endif
<div class="vd-tot tt"><span>Total</span><span>S/ {{ number_format($venta->total, 2) }}</span></div>
<div class="d-flex flex-wrap mt-3" style="gap:.4rem">
    <a href="{{ route('ventas.show', $venta) }}" class="btn btn-primary btn-sm"><i class="fas fa-external-link-alt mr-1"></i>Abrir venta</a>
    <a href="{{ route('ventas.ticket', $venta) }}" target="_blank" class="btn btn-light btn-sm"><i class="fas fa-receipt mr-1"></i>Ticket</a>
    <a href="{{ route('ventas.pdf', $venta) }}" target="_blank" class="btn btn-light btn-sm"><i class="fas fa-file-pdf mr-1 text-danger"></i>PDF</a>
</div>
