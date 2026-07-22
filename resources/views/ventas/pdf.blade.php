<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Comprobante {{ $venta->numero_venta }}</title>
<style>
    /* dompdf soporta un subconjunto de CSS: se evita flexbox/grid y se usa
       maquetación con tablas, que es lo más confiable para PDF. */
    @page { margin: 24px 28px; }
    body { font-family: Helvetica, Arial, sans-serif; color: #1a1a2e; font-size: 12px; }

    .encabezado { width: 100%; border-bottom: 2px solid #1a1a2e; padding-bottom: 10px; margin-bottom: 14px; }
    .encabezado td { vertical-align: top; }
    .negocio-nombre { font-size: 18px; font-weight: bold; color: #1a1a2e; }
    .negocio-sub { font-size: 10px; color: #777; margin-top: 2px; }
    .comprobante-num { font-size: 15px; font-weight: bold; color: #b5451b; text-align: right; }
    .comprobante-estado { text-align: right; font-size: 10px; margin-top: 3px; }

    .badge { display: inline-block; padding: 2px 8px; border-radius: 8px; font-size: 9px; font-weight: bold; }
    .badge-ok { background: #e5f6ec; color: #1e8e5a; border: 1px solid #b6e3c8; }
    .badge-anulada { background: #fbe9e7; color: #c0392b; border: 1px solid #f1c2bb; }

    .info-tabla { width: 100%; margin-bottom: 14px; }
    .info-tabla td { padding: 3px 0; font-size: 11px; }
    .info-label { color: #8a8a9d; width: 90px; }
    .info-valor { font-weight: bold; }

    table.items { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
    table.items thead th {
        background: #1a1a2e; color: #fff; text-align: left; padding: 6px 8px; font-size: 10px;
        text-transform: uppercase; letter-spacing: .03em;
    }
    table.items thead th.num { text-align: right; }
    table.items tbody td { padding: 6px 8px; border-bottom: 1px solid #eee; font-size: 11px; }
    table.items tbody td.num { text-align: right; }
    table.items tbody tr:nth-child(even) { background: #faf9f7; }

    .totales { width: 100%; margin-top: 6px; }
    .totales td { padding: 3px 8px; font-size: 11px; }
    .totales .label { text-align: right; color: #666; width: 80%; }
    .totales .valor { text-align: right; width: 20%; white-space: nowrap; }
    .totales .fila-total .label, .totales .fila-total .valor { font-size: 14px; font-weight: bold; color: #1a1a2e; border-top: 1px solid #ddd; padding-top: 6px; }

    .pie { margin-top: 30px; text-align: center; font-size: 9px; color: #999; border-top: 1px solid #eee; padding-top: 10px; }
</style>
</head>
<body>

    <table class="encabezado">
        <tr>
            <td style="width:60%;">
                <div class="negocio-nombre">Panadería</div>
                <div class="negocio-sub">Comprobante de venta interno</div>
            </td>
            <td style="width:40%;">
                <div class="comprobante-num">{{ $venta->numero_venta }}</div>
                <div class="comprobante-estado">
                    <span class="badge {{ $venta->estado === 'completada' ? 'badge-ok' : 'badge-anulada' }}">
                        {{ strtoupper($venta->estado) }}
                    </span>
                </div>
            </td>
        </tr>
    </table>

    <table class="info-tabla">
        <tr>
            <td class="info-label">Cliente:</td>
            <td class="info-valor">{{ $venta->cliente->nombre ?? 'Cliente general' }}</td>
            <td class="info-label" style="text-align:right;">Fecha:</td>
            <td class="info-valor" style="text-align:right;">{{ $venta->fecha_venta->format('d/m/Y H:i') }}</td>
        </tr>
        <tr>
            <td class="info-label">Vendedor:</td>
            <td class="info-valor">{{ $venta->usuario->nombre ?? '—' }}</td>
            <td class="info-label" style="text-align:right;">Pago:</td>
            <td class="info-valor" style="text-align:right;">{{ ucfirst($venta->tipo_pago) }}</td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th>Producto</th>
                <th class="num">Cant.</th>
                <th class="num">P. Unit.</th>
                <th class="num">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($venta->detalles as $d)
            <tr>
                <td>{{ $d->producto->nombre ?? 'Producto eliminado' }}</td>
                <td class="num">{{ $d->cantidad }}</td>
                <td class="num">S/ {{ number_format($d->precio_unitario, 2) }}</td>
                <td class="num">S/ {{ number_format($d->subtotal, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totales">
        <tr>
            <td class="label">Subtotal</td>
            <td class="valor">S/ {{ number_format($venta->subtotal, 2) }}</td>
        </tr>
        <tr>
            <td class="label">IGV (18%)</td>
            <td class="valor">S/ {{ number_format($venta->igv, 2) }}</td>
        </tr>
        <tr class="fila-total">
            <td class="label">TOTAL</td>
            <td class="valor">S/ {{ number_format($venta->total, 2) }}</td>
        </tr>
    </table>

    @if($venta->observaciones)
    <p style="margin-top:14px;font-size:10px;color:#666;"><strong>Observaciones:</strong> {{ $venta->observaciones }}</p>
    @endif

    <div class="pie">
        Documento generado el {{ now()->format('d/m/Y H:i') }} · Comprobante interno, no válido como boleta/factura electrónica SUNAT
    </div>

</body>
</html>
