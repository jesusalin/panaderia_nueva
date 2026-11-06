<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Ticket {{ $venta->numero_venta }}</title>
<style>
    /* Ticket para impresora térmica de 80 mm (área útil ~72 mm) */
    @page { size: 80mm auto; margin: 3mm; }
    * { box-sizing: border-box; }
    body { margin: 0; background: #eee; font-family: 'Courier New', ui-monospace, monospace; color: #000; font-size: 12px; }
    .ticket { width: 72mm; margin: 12px auto; background: #fff; padding: 4mm 3mm; box-shadow: 0 2px 10px rgba(0,0,0,.15); }
    .c { text-align: center; }
    .neg { font-weight: bold; }
    h1 { font-size: 15px; margin: 0 0 2px; }
    .sep { border: 0; border-top: 1px dashed #000; margin: 6px 0; }
    table { width: 100%; border-collapse: collapse; }
    td { vertical-align: top; padding: 1px 0; }
    td.r { text-align: right; white-space: nowrap; }
    .total td { font-size: 15px; font-weight: bold; padding-top: 3px; }
    .anulada { border: 2px solid #000; text-align: center; font-weight: bold; font-size: 14px; padding: 3px; margin: 6px 0; }
    .acciones { text-align: center; margin: 10px auto; font-family: system-ui, sans-serif; }
    .acciones button, .acciones a { font-size: 14px; padding: 8px 16px; margin: 0 4px; border-radius: 8px; border: 1px solid #ccc; background: #fff; color: #000; text-decoration: none; cursor: pointer; }
    .acciones button { background: #1e8e5a; border-color: #1e8e5a; color: #fff; }
    @media print {
        body { background: #fff; }
        .ticket { margin: 0; box-shadow: none; width: auto; padding: 0; }
        .acciones { display: none; }
    }
</style>
</head>
<body>
<div class="acciones">
    <button type="button" onclick="window.print()">Imprimir</button>
    <a href="{{ route('ventas.show', $venta) }}">Ver venta</a>
</div>
<div class="ticket">
    <div class="c">
        <h1>PANADERÍA MURUWASI</h1>
        <div>Ticket de venta</div>
        <div class="neg">{{ $venta->numero_venta }}</div>
    </div>
    <hr class="sep">
    <div>Fecha: {{ $venta->fecha_venta->format('d/m/Y H:i') }}</div>
    <div>Cliente: {{ $venta->cliente->nombre ?? 'Cliente General' }}</div>
    @if($venta->cliente?->ruc || $venta->cliente?->dni)
        <div>{{ $venta->cliente->ruc ? 'RUC' : 'DNI' }}: {{ $venta->cliente->ruc ?: $venta->cliente->dni }}</div>
    @endif
    <div>Atendió: {{ $venta->usuario->nombre ?? '—' }}</div>
    @if($venta->estado === 'anulada')<div class="anulada">ANULADA</div>@endif
    <hr class="sep">
    <table>
        @foreach($venta->detalles as $d)
            <tr><td colspan="2">{{ $d->producto->nombre ?? 'Producto' }}</td></tr>
            <tr><td>&nbsp;&nbsp;{{ $d->cantidad }} x {{ number_format($d->precio_unitario, 2) }}</td><td class="r">{{ number_format($d->subtotal, 2) }}</td></tr>
        @endforeach
    </table>
    <hr class="sep">
    <table>
        <tr><td>Op. gravada</td><td class="r">{{ number_format($venta->subtotal, 2) }}</td></tr>
        <tr><td>IGV 18% (incluido)</td><td class="r">{{ number_format($venta->igv, 2) }}</td></tr>
        <tr class="total"><td>TOTAL S/</td><td class="r">{{ number_format($venta->total, 2) }}</td></tr>
    </table>
    <hr class="sep">
    <table>
        <tr><td>Pago</td><td class="r">{{ $venta->medio['texto'] }}</td></tr>
        @if($venta->tipo_pago === 'efectivo' && $venta->monto_recibido !== null)
            <tr><td>Recibido</td><td class="r">{{ number_format($venta->monto_recibido, 2) }}</td></tr>
            <tr><td class="neg">Vuelto</td><td class="r neg">{{ number_format($venta->vuelto ?? 0, 2) }}</td></tr>
        @endif
        @if($venta->numero_operacion)<tr><td>N° operación</td><td class="r">{{ $venta->numero_operacion }}</td></tr>@endif
    </table>
    @if($venta->observaciones)<hr class="sep"><div>Nota: {{ $venta->observaciones }}</div>@endif
    <hr class="sep">
    <div class="c">¡Gracias por su compra!</div>
    <div class="c" style="font-size:10px;margin-top:3px">Comprobante interno, no válido como boleta electrónica SUNAT</div>
</div>
<script>
    // Se abre el diálogo de impresión solo, como en una caja
    window.addEventListener('load', function () { setTimeout(function () { window.print(); }, 300); });
</script>
</body>
</html>
