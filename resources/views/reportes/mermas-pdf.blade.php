@extends('reportes.pdf-base')
@php $C = \App\Support\Cantidad::class; $M = \App\Http\Controllers\ReportesGestionController::MOTIVOS; @endphp
@section('titulo', 'Reporte de mermas')
@section('subtitulo', 'Periodo: ' . $textoPeriodo . ($tipo !== 'todos' ? ' · Solo ' . $tipo : ''))
@section('contenido')
    <table class="resumen"><tr>
        <td><div class="lbl">Pérdida total (S/)</div><div class="val">{{ number_format($total, 2) }}</div></td>
        <td><div class="lbl">Registros</div><div class="val">{{ $filas->count() }}</div></td>
        <td><div class="lbl">Insumos / productos</div><div class="val">{{ $porItem->count() }}</div></td>
        <td><div class="lbl">Índice de merma (insumos)</div><div class="val">{{ $indice !== null ? $indice . ' %' : '—' }}</div></td>
    </tr></table>

    @if($filas->isEmpty())
        <p>No hubo mermas en este periodo.</p>
    @else
    <div class="seccion">Por motivo</div>
    <table class="reporte">
        <thead><tr><th>Motivo</th><th class="num">Registros</th><th class="num">Valor (S/)</th><th class="num">% del total</th></tr></thead>
        <tbody>@foreach($porMotivo as $m)<tr><td>{{ $m['texto'] }}</td><td class="num">{{ $m['veces'] }}</td><td class="num">{{ number_format($m['valor'], 2) }}</td><td class="num">{{ $total > 0 ? round($m['valor'] / $total * 100) : 0 }}%</td></tr>@endforeach</tbody>
    </table>

    <div class="seccion">Por insumo / producto</div>
    <table class="reporte">
        <thead><tr><th>Insumo / producto</th><th>Tipo</th><th class="num">Cantidad</th><th class="num">Veces</th><th>Motivo más frecuente</th><th class="num">Valor (S/)</th></tr></thead>
        <tbody>@foreach($porItem as $i)<tr>
            <td>{{ $i['nombre'] }}</td><td>{{ $i['tipo'] === 'insumo' ? 'Insumo' : 'Producto' }}</td>
            <td class="num">{{ $i['tipo'] === 'insumo' ? $C::conUnidad($i['cantidad'], $i['unidad']) : (int) $i['cantidad'] . ' und' }}</td>
            <td class="num">{{ $i['veces'] }}</td><td>{{ $M[$i['motivo']] }}</td><td class="num">{{ number_format($i['valor'], 2) }}</td>
        </tr>@endforeach</tbody>
        <tfoot><tr><td colspan="5">Total</td><td class="num">{{ number_format($total, 2) }}</td></tr></tfoot>
    </table>

    <div class="seccion">Detalle</div>
    <table class="reporte">
        <thead><tr><th>Fecha</th><th>Insumo / producto</th><th class="num">Cantidad</th><th class="num">S/</th><th>Motivo</th><th>Detalle</th><th>Registró</th></tr></thead>
        <tbody>@foreach($filas as $f)<tr>
            <td>{{ $f['fecha']->format('d/m/Y H:i') }}</td><td>{{ $f['nombre'] }}</td><td class="num">{{ $f['texto'] }}</td><td class="num">{{ number_format($f['valor'], 2) }}</td>
            <td>{{ $M[$f['motivo']] }}</td><td>{{ \Illuminate\Support\Str::limit($f['detalle'], 45) }}</td><td>{{ $f['usuario'] }}</td>
        </tr>@endforeach</tbody>
    </table>
    <p class="gris" style="font-size:8.5px">Valorizado al costo actual de cada insumo (promedio ponderado) y al costo de producción de cada producto. Índice de merma = valor perdido de insumos / (valor usado en producción + valor perdido) x 100.</p>
    @endif
@endsection
