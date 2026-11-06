@extends('reportes.pdf-base')
@section('titulo', 'Reporte de ventas')
@section('subtitulo', 'Periodo: ' . $textoPeriodo . ($idVendedor ? ' - Vendedor: ' . ($vendedores->firstWhere('id', $idVendedor)->nombre ?? '') : '') . ' - Precios con IGV incluido')
@section('contenido')
    <table class="resumen"><tr>
        <td><div class="lbl">Vendido con IGV (S/)</div><div class="val">{{ number_format($tot['total'], 2) }}</div></td>
        <td><div class="lbl">Sin IGV (S/)</div><div class="val">{{ number_format($tot['neto'], 2) }}</div></td>
        <td><div class="lbl">Ventas</div><div class="val">{{ $tot['n'] }}</div></td>
        <td><div class="lbl">Ticket promedio (S/)</div><div class="val">{{ number_format($tot['ticket'], 2) }}</div></td>
        <td><div class="lbl">Ganancia estimada (S/)</div><div class="val">{{ number_format($tot['ganancia'], 2) }}{{ $tot['margen'] !== null ? ' (' . $tot['margen'] . '%)' : '' }}</div></td>
    </tr></table>
    <p style="font-size:9px;color:#777;margin:0 0 8px">
        {{ $tot['unidades'] }} productos vendidos.
        @if($anterior && $anterior['variacion'] !== null) Periodo anterior: S/ {{ number_format($anterior['total'], 2) }} ({{ $anterior['variacion'] >= 0 ? '+' : '' }}{{ $anterior['variacion'] }}%).@endif
        @if($horaPico) Hora pico: {{ sprintf('%02d:00 a %02d:00', $horaPico['hora'], $horaPico['hora'] + 1) }}.@endif
        @if($tot['anuladas']) Anuladas: {{ $tot['anuladas'] }} (S/ {{ number_format($tot['monto_anulado'], 2) }}), no se cuentan.@endif
    </p>

    @if($ventas->isEmpty())
        <p>No hubo ventas en este periodo.</p>
    @else
    <div class="seccion">Productos más vendidos</div>
    <table class="reporte">
        <thead><tr><th>#</th><th>Producto</th><th class="num">Unidades</th><th class="num">Ingreso (S/)</th><th class="num">% ventas</th><th class="num">Costo (S/)</th><th class="num">Ganancia (S/)</th><th class="num">Margen</th></tr></thead>
        <tbody>@foreach($porProducto as $i => $p)<tr>
            <td>{{ $i + 1 }}</td><td>{{ $p['nombre'] }}</td><td class="num">{{ $p['cantidad'] }}</td><td class="num">{{ number_format($p['ingreso'], 2) }}</td>
            <td class="num">{{ $tot['total'] > 0 ? round($p['ingreso'] / $tot['total'] * 100, 1) : 0 }}%</td>
            <td class="num">{{ $p['costo'] !== null ? number_format($p['costo'], 2) : '—' }}</td>
            <td class="num">{{ $p['ganancia'] !== null ? number_format($p['ganancia'], 2) : '—' }}</td>
            <td class="num {{ ($p['margen'] ?? 100) < 20 ? 'menos' : '' }}">{{ $p['margen'] !== null ? $p['margen'] . '%' : 'sin costo' }}</td>
        </tr>@endforeach</tbody>
        <tfoot><tr><td colspan="2">Total</td><td class="num">{{ $tot['unidades'] }}</td><td class="num">{{ number_format($tot['total'], 2) }}</td><td></td>
            <td class="num">{{ number_format($tot['costo'], 2) }}</td><td class="num">{{ number_format($tot['ganancia'], 2) }}</td><td class="num">{{ $tot['margen'] !== null ? $tot['margen'] . '%' : '' }}</td></tr></tfoot>
    </table>
    <p style="font-size:8px;color:#888;margin-top:-6px">Ganancia = ingreso sin IGV menos el costo de producción registrado en cada producto.</p>

    <div class="seccion">Ventas por {{ ['dia' => 'día', 'semana' => 'semana', 'mes' => 'mes'][$agrupar] }}</div>
    <table class="reporte">
        <thead><tr><th>{{ ['dia' => 'Día', 'semana' => 'Semana', 'mes' => 'Mes'][$agrupar] }}</th><th class="num">Ventas</th><th class="num">Total (S/)</th></tr></thead>
        <tbody>@foreach($porTiempo as $t)<tr><td>{{ $t['etiqueta'] }}</td><td class="num">{{ $t['n'] }}</td><td class="num">{{ number_format($t['total'], 2) }}</td></tr>@endforeach</tbody>
    </table>

    <table style="width:100%"><tr>
        <td style="width:50%;vertical-align:top;padding-right:6px">
            <div class="seccion">Por medio de pago</div>
            <table class="reporte">
                <thead><tr><th>Medio</th><th class="num">Ventas</th><th class="num">Total (S/)</th><th class="num">%</th></tr></thead>
                <tbody>@foreach($porMedio as $m)<tr><td>{{ $m['texto'] }}</td><td class="num">{{ $m['n'] }}</td><td class="num">{{ number_format($m['total'], 2) }}</td><td class="num">{{ $tot['total'] > 0 ? round($m['total'] / $tot['total'] * 100) : 0 }}%</td></tr>@endforeach</tbody>
            </table>
            <div class="seccion">Por vendedor</div>
            <table class="reporte">
                <thead><tr><th>Vendedor</th><th class="num">Ventas</th><th class="num">Total (S/)</th><th class="num">Anuladas</th></tr></thead>
                <tbody>@foreach($porVendedor as $v)<tr><td>{{ $v['nombre'] }}</td><td class="num">{{ $v['n'] }}</td><td class="num">{{ number_format($v['total'], 2) }}</td><td class="num">{{ $v['anuladas'] }}</td></tr>@endforeach</tbody>
            </table>
        </td>
        <td style="width:50%;vertical-align:top;padding-left:6px">
            <div class="seccion">Por hora del día</div>
            <table class="reporte">
                <thead><tr><th>Hora</th><th class="num">Ventas</th><th class="num">Total (S/)</th></tr></thead>
                <tbody>@foreach($porHora->where('n', '>', 0) as $h)<tr><td>{{ sprintf('%02d:00 a %02d:00', $h['hora'], $h['hora'] + 1) }}</td><td class="num">{{ $h['n'] }}</td><td class="num">{{ number_format($h['total'], 2) }}</td></tr>@endforeach</tbody>
            </table>
            <div class="seccion">Mejores clientes</div>
            <table class="reporte">
                <thead><tr><th>Cliente</th><th class="num">Compras</th><th class="num">Total (S/)</th></tr></thead>
                <tbody>@foreach($porCliente->take(10) as $c)<tr><td>{{ $c['nombre'] }}</td><td class="num">{{ $c['n'] }}</td><td class="num">{{ number_format($c['total'], 2) }}</td></tr>@endforeach</tbody>
            </table>
        </td>
    </tr></table>
    @endif
@endsection
