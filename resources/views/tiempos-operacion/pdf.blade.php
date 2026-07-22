<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Reporte de Tiempos por Operación</title>
<style>
    @page { margin: 26px 30px; }
    body { font-family: Helvetica, Arial, sans-serif; color: #1a1a2e; font-size: 12px; }

    .encabezado { border-bottom: 2px solid #1a1a2e; padding-bottom: 10px; margin-bottom: 6px; }
    .encabezado h1 { font-size: 17px; margin: 0 0 3px; }
    .encabezado p { font-size: 10px; color: #666; margin: 0; }

    .rango { font-size: 10px; color: #666; margin-bottom: 14px; }
    .formula { font-size: 10px; color: #666; margin-bottom: 18px; }
    .formula code { background: #f4e9e3; color: #b5451b; padding: 1px 5px; border-radius: 4px; }

    table.reporte { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
    table.reporte thead th {
        background: #1a1a2e; color: #fff; text-align: left; padding: 7px 8px; font-size: 10px;
        text-transform: uppercase; letter-spacing: .03em;
    }
    table.reporte thead th.num { text-align: right; }
    table.reporte tbody td { padding: 7px 8px; border-bottom: 1px solid #eee; font-size: 11px; }
    table.reporte tbody td.num { text-align: right; }
    table.reporte tbody tr:nth-child(even) { background: #faf9f7; }

    .badge { display: inline-block; padding: 2px 7px; border-radius: 8px; font-size: 9px; font-weight: bold; }
    .badge-ok  { background: #e5f6ec; color: #1e8e5a; border: 1px solid #b6e3c8; }
    .badge-bad { background: #fbe9e7; color: #c0392b; border: 1px solid #f1c2bb; }
    .badge-sin { background: #f1f1f4; color: #888; border: 1px solid #ddd; }

    .pie { margin-top: 30px; text-align: center; font-size: 9px; color: #999; border-top: 1px solid #eee; padding-top: 10px; }
</style>
</head>
<body>

    <div class="encabezado">
        <h1>Reporte de Tiempos por Operación</h1>
        <p>Indicador OE3 — Reducción de tiempos muertos</p>
    </div>

    @if($fechaDesde || $fechaHasta)
    <div class="rango">
        Periodo: {{ $fechaDesde ? \Carbon\Carbon::parse($fechaDesde)->format('d/m/Y') : 'inicio' }}
        &nbsp;—&nbsp;
        {{ $fechaHasta ? \Carbon\Carbon::parse($fechaHasta)->format('d/m/Y') : 'hoy' }}
    </div>
    @endif

    <div class="formula">
        Compara el tiempo manual (ficha de observación pre-test) contra el tiempo real registrado por el
        sistema (post-test). Fórmula: <code>(inicial − final) / final × 100</code>
    </div>

    <table class="reporte">
        <thead>
            <tr>
                <th>Operación</th>
                <th class="num">Manual (antes)</th>
                <th class="num">Sistema (ahora)</th>
                <th class="num">Registros</th>
                <th class="num">Resultado</th>
            </tr>
        </thead>
        <tbody>
            @foreach($resultados as $r)
            <tr>
                <td>{{ $r['etiqueta'] }}</td>
                <td class="num">{{ $r['inicial'] > 0 ? number_format($r['inicial'], 1) . ' s' : 'Sin dato' }}</td>
                <td class="num">{{ $r['final'] !== null ? number_format($r['final'], 1) . ' s' : 'Sin dato' }}</td>
                <td class="num">{{ $r['registros'] }}</td>
                <td class="num">
                    @if($r['reduccion'] !== null)
                        <span class="badge {{ $r['reduccion'] >= 0 ? 'badge-ok' : 'badge-bad' }}">
                            {{ number_format(abs($r['reduccion']), 1) }}% {{ $r['reduccion'] >= 0 ? 'más rápido' : 'más lento' }}
                        </span>
                    @else
                        <span class="badge badge-sin">Sin datos suficientes</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="pie">
        Documento generado el {{ now()->format('d/m/Y H:i') }} · Reporte interno del sistema de gestión de panadería
    </div>

</body>
</html>
