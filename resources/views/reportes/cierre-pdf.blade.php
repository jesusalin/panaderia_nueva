@extends('reportes.pdf-base')
@php $C = \App\Support\Cantidad::class; @endphp
@section('titulo', 'Cierre diario de inventario')
@section('subtitulo')
    {{ ucfirst($dia->locale('es')->translatedFormat('l d \d\e F \d\e Y')) }} ·
    {{ $cierre ? 'Cerrado por ' . ($cierre->usuario->nombre ?? '—') . ' a las ' . $cierre->updated_at->format('H:i') : 'Día sin cerrar (saldos calculados al ' . now()->format('d/m/Y H:i') . ')' }}
    @if($filtro !== 'todos') · Vista: {{ \App\Http\Controllers\CierreInventarioController::FILTROS[$filtro] }}@endif
@endsection
@section('contenido')
    <table class="resumen"><tr>
        <td><div class="lbl">Insumos</div><div class="val">{{ $totales['insumos'] }}</div></td>
        <td><div class="lbl">Con movimiento</div><div class="val">{{ $totales['con_movimiento'] }} ({{ $totales['movimientos'] }} mov.)</div></td>
        <td><div class="lbl">Entró (S/)</div><div class="val">{{ number_format($totales['valor_entradas'], 2) }}</div></td>
        <td><div class="lbl">Salió (S/)</div><div class="val">{{ number_format($totales['valor_salidas'], 2) }}</div></td>
        <td><div class="lbl">Valor al cierre (S/)</div><div class="val">{{ number_format($totales['valor_final'], 2) }}</div></td>
        <td><div class="lbl">Bajo el mínimo</div><div class="val">{{ $totales['bajo_minimo'] }}</div></td>
    </tr></table>
    @if($cierre && $cierre->observacion)<div class="nota">Nota del cierre: {{ $cierre->observacion }}</div>@endif
    @if($totales['no_cuadran'])<div class="nota" style="color:#c0392b">{{ $totales['no_cuadran'] }} insumo(s) no cuadran: saldo inicial + entradas - salidas +/- ajustes no es igual al saldo final.</div>@endif

    <table class="reporte">
        <thead><tr>
            <th>Insumo</th><th class="num">Saldo inicial</th><th class="num">Entradas</th><th class="num">Salidas</th><th class="num">Ajustes</th>
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
                <td>@if(!$f['cuadra'])<span class="badge badge-bad">No cuadra</span>@elseif($f['bajo'])<span class="badge badge-warn">Bajo mínimo</span>@endif</td>
            </tr>
        @endforeach
        </tbody>
        <tfoot><tr><td colspan="7">Total ({{ $vista->count() }} insumos)</td><td class="num">{{ number_format($vista->sum('valor_final'), 2) }}</td><td></td></tr></tfoot>
    </table>

    <table class="firmas"><tr>
        <td><div class="linea"></div>Almacenero</td>
        <td><div class="linea"></div>Revisado por</td>
    </tr></table>
@endsection
