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

    <div class="rango">
        Periodo:
        @if($periodo === 'personalizado')
            {{ $desde ? $desde->format('d/m/Y') : 'inicio' }} &nbsp;—&nbsp; {{ $hasta ? $hasta->format('d/m/Y') : 'hoy' }}
        @else
            {{ \App\Http\Controllers\ReportesController::PERIODOS[$periodo] ?? 'Últimos 30 días' }}
        @endif
        &nbsp;·&nbsp; {{ $cuadra ? 'El kardex cuadra' : 'Atención: el kardex no cuadra en este periodo' }}
        @if($stockBajo)
            &nbsp;·&nbsp;<strong style="color:#c0392b;">⚠ Stock bajo</strong>
        @endif
    </div>

    <table class="resumen" width="100%">
        <tr>
            <td width="25%"><div class="caja"><div class="lbl">Saldo inicial</div><div class="val">{{ \App\Support\Cantidad::fmt($saldoInicial) }} {{ $unidad }}</div></div></td>
            <td width="25%"><div class="caja"><div class="lbl">Entradas</div><div class="val">+{{ \App\Support\Cantidad::fmt($totalEntradas) }} {{ $unidad }}</div></div></td>
            <td width="25%"><div class="caja"><div class="lbl">Salidas</div><div class="val">-{{ \App\Support\Cantidad::fmt($totalSalidas) }} {{ $unidad }}</div></div></td>
            <td width="25%"><div class="caja"><div class="lbl">Saldo actual</div><div class="val">{{ \App\Support\Cantidad::fmt($item->stock_actual) }} {{ $unidad }}</div></div></td>
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
            @if($movimientos->isNotEmpty())
            <tr style="background:#f4f1ee;font-style:italic;font-weight:bold;">
                <td colspan="5">Saldo inicial</td>
                <td class="num">{{ \App\Support\Cantidad::fmt($saldoInicial) }}</td>
                <td></td>
            </tr>
            @endif
            @forelse($movimientos as $mov)
            <tr>
                <td>{{ $mov->created_at->format('d/m/Y H:i') }}</td>
                <td>{{ $mov->es_ajuste ? 'Ajuste' : ucfirst($mov->tipo) }}</td>
                <td>{{ ucfirst(str_replace('_', ' ', $mov->motivo)) }}</td>
                <td class="num">{{ $mov->entrada > 0 ? \App\Support\Cantidad::fmt($mov->entrada) : '' }}</td>
                <td class="num">{{ $mov->salida > 0 ? \App\Support\Cantidad::fmt($mov->salida) : '' }}</td>
                <td class="num">{{ \App\Support\Cantidad::fmt($mov->stock_despues) }}</td>
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
