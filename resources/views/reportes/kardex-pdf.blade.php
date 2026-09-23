<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Kardex - {{ $item->nombre }}</title>
<style>
    @page { margin: 26px 30px; }
    body { font-family: Helvetica, Arial, sans-serif; color: #1a1a2e; font-size: 12px; }

    .encabezado { border-bottom: 2px solid #1a1a2e; padding-bottom: 10px; margin-bottom: 6px; }
    .encabezado h1 { font-size: 17px; margin: 0 0 3px; }
    .encabezado p { font-size: 10px; color: #666; margin: 0; }

    .rango { font-size: 10px; color: #666; margin: 8px 0 14px; }

    .resumen { margin-bottom: 16px; }
    .resumen td { padding: 6px 10px; }
    .resumen .caja { background: #f7f5f3; border-radius: 6px; padding: 8px 12px; }
    .resumen .caja .lbl { font-size: 9px; color: #888; text-transform: uppercase; }
    .resumen .caja .val { font-size: 14px; font-weight: bold; color: #1a1a2e; }

    table.reporte { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
    table.reporte thead th {
        background: #1a1a2e; color: #fff; text-align: left; padding: 7px 8px; font-size: 9px;
        text-transform: uppercase; letter-spacing: .03em;
    }
    table.reporte thead th.num { text-align: right; }
    table.reporte tbody td { padding: 6px 8px; border-bottom: 1px solid #eee; font-size: 10px; }
    table.reporte tbody td.num { text-align: right; }
    table.reporte tbody tr:nth-child(even) { background: #faf9f7; }

    .pie { margin-top: 20px; text-align: center; font-size: 9px; color: #999; border-top: 1px solid #eee; padding-top: 10px; }
</style>
</head>
<body>

    <div class="encabezado">
        <h1>Kardex: {{ $item->nombre }}</h1>
        <p>Ficha cronológica de entradas, salidas y saldo</p>
    </div>

    @if(request('fecha_desde') || request('fecha_hasta'))
    <div class="rango">
        Periodo: {{ request('fecha_desde') ? \Carbon\Carbon::parse(request('fecha_desde'))->format('d/m/Y') : 'inicio' }}
        &nbsp;—&nbsp;
        {{ request('fecha_hasta') ? \Carbon\Carbon::parse(request('fecha_hasta'))->format('d/m/Y') : 'hoy' }}
    </div>
    @endif

    @php
        $totalEntradas = $movimientos->where('tipo', 'entrada')->sum('cantidad');
        $totalSalidas  = $movimientos->where('tipo', 'salida')->sum('cantidad');
    @endphp

    <table class="resumen" width="100%">
        <tr>
            <td width="33%"><div class="caja"><div class="lbl">Total Entradas</div><div class="val">{{ number_format($totalEntradas, 2) }} {{ $unidad }}</div></div></td>
            <td width="33%"><div class="caja"><div class="lbl">Total Salidas</div><div class="val">{{ number_format($totalSalidas, 2) }} {{ $unidad }}</div></div></td>
            <td width="33%"><div class="caja"><div class="lbl">Saldo Actual</div><div class="val">{{ number_format($item->stock_actual, 2) }} {{ $unidad }}</div></div></td>
        </tr>
    </table>

    <table class="reporte">
        <thead>
            <tr>
                <th>Fecha</th><th>Tipo</th><th>Motivo</th>
                <th class="num">Entrada</th><th class="num">Salida</th><th class="num">Saldo</th><th>Usuario</th>
            </tr>
        </thead>
        <tbody>
            @forelse($movimientos as $mov)
            <tr>
                <td>{{ $mov->created_at->format('d/m/Y H:i') }}</td>
                <td>{{ ucfirst($mov->tipo) }}</td>
                <td>{{ ucfirst(str_replace('_', ' ', $mov->motivo)) }}</td>
                <td class="num">{{ $mov->tipo === 'entrada' ? number_format($mov->cantidad, 2) : '' }}</td>
                <td class="num">{{ $mov->tipo === 'salida' ? number_format($mov->cantidad, 2) : '' }}</td>
                <td class="num">{{ number_format($mov->stock_despues, 2) }}</td>
                <td>{{ $mov->usuario->apodo ?? $mov->usuario->nombre ?? '—' }}</td>
            </tr>
            @empty
            <tr><td colspan="7" style="text-align:center;color:#999;">Sin movimientos en el periodo seleccionado</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="pie">
        Documento generado el {{ now()->format('d/m/Y H:i') }} · Reporte interno del sistema de gestión de panadería
    </div>

</body>
</html>
