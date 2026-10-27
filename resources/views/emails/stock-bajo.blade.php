<!DOCTYPE html>
<html lang="es">
<body style="font-family: Arial, Helvetica, sans-serif; color:#1a1a2e; background:#f7f5f3; margin:0; padding:20px;">
    <div style="max-width:620px; margin:0 auto; background:#ffffff; border-radius:12px; overflow:hidden;">
        <div style="background:#1a1a2e; color:#ffffff; padding:18px 24px;">
            <div style="font-size:18px; font-weight:bold;">Muruwasi · Alerta de stock bajo</div>
            <div style="font-size:12px; opacity:.75;">{{ now()->format('d/m/Y H:i') }}</div>
        </div>
        <div style="padding:20px 24px;">
            <p style="margin-top:0;">
                Hay <strong>{{ count($insumos) + count($productos) }}</strong> ítem(s) por debajo de su stock mínimo.
                @if($ordenesCreadas > 0)
                    Se generaron <strong>{{ $ordenesCreadas }}</strong> orden(es) automática(s) de reposición,
                    listas para convertir en compra.
                @endif
            </p>

            @if(count($insumos) > 0)
            <h4 style="margin:18px 0 6px;">Materia prima</h4>
            <table width="100%" cellpadding="6" cellspacing="0" style="border-collapse:collapse; font-size:13px;">
                <tr style="background:#f4f1ee; text-align:left;">
                    <th>Insumo</th><th>Stock</th><th>Mínimo</th><th>Estado</th>
                </tr>
                @foreach($insumos as $i)
                <tr style="border-bottom:1px solid #eee;">
                    <td>{{ $i->nombre }}</td>
                    <td>{{ rtrim(rtrim(number_format($i->stock_actual, 2), '0'), '.') ?: '0' }} {{ $i->unidad->abreviatura ?? '' }}</td>
                    <td>{{ rtrim(rtrim(number_format($i->stock_minimo, 2), '0'), '.') ?: '0' }}</td>
                    <td style="color:{{ $i->stock_actual <= 0 ? '#c0392b' : '#a5680d' }}; font-weight:bold;">{{ $i->stock_actual <= 0 ? 'Agotado' : 'Bajo' }}</td>
                </tr>
                @endforeach
            </table>
            @endif

            @if(count($productos) > 0)
            <h4 style="margin:18px 0 6px;">Productos terminados</h4>
            <table width="100%" cellpadding="6" cellspacing="0" style="border-collapse:collapse; font-size:13px;">
                <tr style="background:#f4f1ee; text-align:left;">
                    <th>Producto</th><th>Stock</th><th>Mínimo</th><th>Estado</th>
                </tr>
                @foreach($productos as $p)
                <tr style="border-bottom:1px solid #eee;">
                    <td>{{ $p->nombre }}</td>
                    <td>{{ $p->stock_actual }} uds</td>
                    <td>{{ $p->stock_minimo }}</td>
                    <td style="color:{{ $p->stock_actual <= 0 ? '#c0392b' : '#a5680d' }}; font-weight:bold;">{{ $p->stock_actual <= 0 ? 'Agotado' : 'Bajo' }}</td>
                </tr>
                @endforeach
            </table>
            @endif

            <p style="font-size:12px; color:#8a8a9d; margin-bottom:0; margin-top:20px;">
                Mensaje automático del sistema de inventario. Revisa los detalles en Reportes → Stock.
            </p>
        </div>
    </div>
</body>
</html>
