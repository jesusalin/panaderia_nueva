@extends('reportes.pdf-base')
@php $C = \App\Support\Cantidad::class; @endphp
@section('titulo', 'Cierre diario de inventario')
@section('subtitulo')
    {{ ucfirst($dia->locale('es')->translatedFormat('l d \d\e F \d\e Y')) }} ·
    {{ $cierre ? 'Cerrado por ' . ($cierre->usuario->nombre ?? '—') . ' a las ' . $cierre->updated_at->format('H:i') . ($cierre->duracion ? ' (tiempo de cierre: ' . $cierre->duracion . ')' : '') : 'Día sin cerrar (saldos calculados al ' . now()->format('d/m/Y H:i') . ')' }}
    · {{ $de === 'productos' ? 'Productos terminados' : 'Insumos' }}
    @if($filtro !== 'todos') · Vista: {{ \App\Http\Controllers\CierreInventarioController::FILTROS[$filtro] }}@endif
    @if($filtroTabla) · {{ $filtroTabla }}@endif
@endsection
@section('contenido')
    @php $t = $de === 'productos' ? $totalesProd : $totales; @endphp
    <table class="resumen"><tr>
        <td><div class="lbl">Valor insumos (S/)</div><div class="val">{{ number_format($totales['valor_final'], 2) }}</div></td>
        <td><div class="lbl">Valor productos (S/)</div><div class="val">{{ number_format($totalesProd['valor_final'], 2) }}</div></td>
        <td><div class="lbl">Compras recibidas</div><div class="val">{{ $resumenDia['compras'] }} · S/ {{ number_format($resumenDia['compras_total'], 2) }}</div></td>
        <td><div class="lbl">Producción</div><div class="val">{{ $resumenDia['producciones'] }} · {{ $C::conUnidad($resumenDia['producido'], 'uds') }}</div></td>
        <td><div class="lbl">Ventas</div><div class="val">{{ $resumenDia['ventas'] }} · S/ {{ number_format($resumenDia['ventas_total'], 2) }}</div></td>
        <td><div class="lbl">Mermas (S/)</div><div class="val">{{ number_format($resumenDia['merma'], 2) }}</div></td>
    </tr></table>
    <table class="resumen"><tr>
        <td><div class="lbl">{{ $de === 'productos' ? 'Productos' : 'Insumos' }}</div><div class="val">{{ $t['insumos'] }}</div></td>
        <td><div class="lbl">Con movimiento</div><div class="val">{{ $t['con_movimiento'] }} ({{ $t['movimientos'] }} mov.)</div></td>
        <td><div class="lbl">Entró (S/)</div><div class="val">{{ number_format($t['valor_entradas'], 2) }}</div></td>
        <td><div class="lbl">Salió (S/)</div><div class="val">{{ number_format($t['valor_salidas'], 2) }}</div></td>
        <td><div class="lbl">Bajo el mínimo</div><div class="val">{{ $t['bajo_minimo'] }}</div></td>
        <td><div class="lbl">No cuadran</div><div class="val">{{ $t['no_cuadran'] }}</div></td>
    </tr></table>
    @if($cierre && $cierre->observacion)<div class="nota">Nota del cierre: {{ $cierre->observacion }}</div>@endif
    @if($t['no_cuadran'])<div class="nota" style="color:#c0392b">{{ $t['no_cuadran'] }} ítem(s) no cuadran: saldo inicial + entradas - salidas +/- ajustes no es igual al saldo final.</div>@endif

    <table class="reporte">
        <thead><tr>
            <th>{{ $de === 'productos' ? 'Producto' : 'Insumo' }}</th><th class="num">Saldo inicial</th><th class="num">Entradas</th><th class="num">Salidas</th><th class="num">Ajustes</th>
            <th class="num">Saldo final</th><th class="num">Costo unit.</th><th class="num">Valor (S/)</th><th>Obs.</th>
        </tr></thead>
        <tbody>
        @foreach($vista as $f)
            <tr>
                <td>{{ $f['nombre'] }}</td>
                <td class="num">{{ $C::conUnidad($f['inicial'], $f['unidad']) }}</td>
                <td class="num {{ $f['entradas'] > 0 ? 'mas' : 'gris' }}">{{ $f['entradas'] > 0 ? '+' . $C::conUnidad($f['entradas'], $f['unidad']) : '—' }}</td>
                <td class="num {{ $f['salidas'] > 0 ? 'menos' : 'gris' }}">{{ $f['salidas'] > 0 ? '-' . $C::conUnidad($f['salidas'], $f['unidad']) : '—' }}</td>
                <td class="num {{ $f['ajuste'] != 0 ? 'aj' : 'gris' }}">{{ $f['ajuste'] != 0 ? $C::conUnidad($f['ajuste'], $f['unidad'], true) : '—' }}</td>
                <td class="num"><b>{{ $C::conUnidad($f['final'], $f['unidad']) }}</b></td>
                <td class="num">{{ number_format($f['costo'], 2) }}</td>
                <td class="num">{{ number_format($f['valor_final'], 2) }}</td>
                <td>@if(!$f['cuadra'])<span class="badge badge-bad">No cuadra</span>@elseif($f['bajo'])<span class="badge badge-warn">Bajo mínimo</span>@else Normal @endif</td>
            </tr>
        @endforeach
        </tbody>
        <tfoot><tr><td colspan="7">Total ({{ $vista->count() }} {{ $de === 'productos' ? 'productos' : 'insumos' }})</td><td class="num">{{ number_format($vista->sum('valor_final'), 2) }}</td><td></td></tr></tfoot>
    </table>

    <table class="firmas"><tr>
        <td><div class="linea"></div>Almacenero</td>
        <td><div class="linea"></div>Revisado por</td>
    </tr></table>
@endsection
