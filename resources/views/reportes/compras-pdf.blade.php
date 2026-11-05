@extends('reportes.pdf-base')
@php $C = \App\Support\Cantidad::class; @endphp
@section('titulo', 'Reporte de compras')
@section('subtitulo', 'Periodo: ' . $textoPeriodo . ($idProveedor ? ' · Proveedor: ' . ($proveedores->firstWhere('id', $idProveedor)->nombre ?? '') : ''))
@section('contenido')
    <table class="resumen"><tr>
        <td><div class="lbl">Gastado con IGV (S/)</div><div class="val">{{ number_format($tot['total'], 2) }}</div></td>
        <td><div class="lbl">Sin IGV (S/)</div><div class="val">{{ number_format($tot['subtotal'], 2) }}</div></td>
        <td><div class="lbl">Compras</div><div class="val">{{ $tot['n'] }}</div></td>
        <td><div class="lbl">Promedio por compra</div><div class="val">{{ number_format($tot['promedio'], 2) }}</div></td>
        <td><div class="lbl">Por recibir (S/)</div><div class="val">{{ number_format($tot['pendiente'], 2) }}</div></td>
    </tr></table>

    @if($compras->isEmpty())
        <p>No hubo compras en este periodo.</p>
    @else
    <div class="seccion">Por proveedor</div>
    <table class="reporte">
        <thead><tr><th>Proveedor</th><th class="num">Compras</th><th class="num">Total (S/)</th><th class="num">% del gasto</th></tr></thead>
        <tbody>@foreach($porProveedor as $p)<tr><td>{{ $p['nombre'] }}</td><td class="num">{{ $p['n'] }}</td><td class="num">{{ number_format($p['total'], 2) }}</td><td class="num">{{ $tot['total'] > 0 ? round($p['total'] / $tot['total'] * 100) : 0 }}%</td></tr>@endforeach</tbody>
        <tfoot><tr><td>Total</td><td class="num">{{ $tot['n'] }}</td><td class="num">{{ number_format($tot['total'], 2) }}</td><td></td></tr></tfoot>
    </table>

    <div class="seccion">Por insumo (precios sin IGV)</div>
    <table class="reporte">
        <thead><tr><th>Insumo</th><th class="num">Comprado</th><th class="num">Gasto (S/)</th><th class="num">Precio prom.</th><th class="num">Mín. – máx.</th><th class="num">Primero a último</th><th class="num">Var.</th></tr></thead>
        <tbody>@foreach($porInsumo as $i)<tr>
            <td>{{ $i['nombre'] }}</td><td class="num">{{ $C::conUnidad($i['cantidad'], $i['unidad']) }}</td><td class="num">{{ number_format($i['gasto'], 2) }}</td>
            <td class="num">{{ number_format($i['promedio'], 2) }}</td><td class="num">{{ number_format($i['min'], 2) }} – {{ number_format($i['max'], 2) }}</td>
            <td class="num">{{ number_format($i['primero'], 2) }} a {{ number_format($i['ultimo'], 2) }}</td>
            <td class="num {{ ($i['variacion'] ?? 0) > 0 ? 'menos' : (($i['variacion'] ?? 0) < 0 ? 'mas' : '') }}">{{ $i['variacion'] !== null ? ($i['variacion'] > 0 ? '+' : '') . $i['variacion'] . '%' : '—' }}</td>
        </tr>@endforeach</tbody>
        <tfoot><tr><td colspan="2">Total sin IGV</td><td class="num">{{ number_format($porInsumo->sum('gasto'), 2) }}</td><td colspan="4"></td></tr></tfoot>
    </table>

    <div class="seccion">Compras</div>
    <table class="reporte">
        <thead><tr><th>Compra</th><th>Fecha</th><th>Proveedor</th><th>Factura</th><th>Estado</th><th class="num">Total (S/)</th></tr></thead>
        <tbody>@foreach($compras->sortByDesc('fecha_compra') as $c)<tr>
            <td>{{ $c->codigo }}</td><td>{{ $c->fecha_compra->format('d/m/Y') }}</td><td>{{ $c->proveedor->nombre ?? '—' }}</td><td>{{ $c->numero_doc ?: '—' }}</td>
            <td>{{ $c->estado === 'pendiente' ? 'Por recibir' : 'Recibida' }}</td><td class="num">{{ number_format($c->total, 2) }}</td>
        </tr>@endforeach</tbody>
    </table>
    @endif
@endsection
