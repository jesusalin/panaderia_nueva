<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Reporte de Stock</title>
<style>
    @page { margin: 26px 30px; }
    body { font-family: Helvetica, Arial, sans-serif; color: #1a1a2e; font-size: 12px; }

    .encabezado { border-bottom: 2px solid #1a1a2e; padding-bottom: 10px; margin-bottom: 6px; }
    .encabezado h1 { font-size: 17px; margin: 0 0 3px; }
    .encabezado p { font-size: 10px; color: #666; margin: 0; }

    .resumen { margin: 14px 0 18px; }
    .resumen td { padding: 6px 10px; }
    .resumen .caja { background: #f7f5f3; border-radius: 6px; padding: 8px 12px; }
    .resumen .caja .lbl { font-size: 9px; color: #888; text-transform: uppercase; }
    .resumen .caja .val { font-size: 14px; font-weight: bold; color: #1a1a2e; }

    .seccion { font-size: 13px; font-weight: bold; margin: 16px 0 6px; color: #1a1a2e; }

    table.reporte { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
    table.reporte thead th {
        background: #1a1a2e; color: #fff; text-align: left; padding: 7px 8px; font-size: 9px;
        text-transform: uppercase; letter-spacing: .03em;
    }
    table.reporte thead th.num { text-align: right; }
    table.reporte tbody td { padding: 6px 8px; border-bottom: 1px solid #eee; font-size: 10px; }
    table.reporte tbody td.num { text-align: right; }
    table.reporte tbody tr:nth-child(even) { background: #faf9f7; }

    .badge { display: inline-block; padding: 2px 7px; border-radius: 8px; font-size: 8px; font-weight: bold; }
    .badge-ok  { background: #e5f6ec; color: #1e8e5a; border: 1px solid #b6e3c8; }
    .badge-bad { background: #fbe9e7; color: #c0392b; border: 1px solid #f1c2bb; }
    .badge-warn { background: #fff4e0; color: #a5680d; border: 1px solid #f5deb3; }

    .pie { margin-top: 20px; text-align: center; font-size: 9px; color: #999; border-top: 1px solid #eee; padding-top: 10px; }
</style>
</head>
<body>

    <div class="encabezado">
        <h1>Reporte de Stock</h1>
        <p>Valorización de inventario: materia prima y productos terminados</p>
    </div>

    <table class="resumen" width="100%">
        <tr>
            <td width="25%"><div class="caja"><div class="lbl">Ítems</div><div class="val">{{ $materias->count() + $productos->count() }}</div></div></td>
            <td width="25%"><div class="caja"><div class="lbl">Valor Materia Prima</div><div class="val">S/ {{ number_format($valorMateria, 2) }}</div></div></td>
            <td width="25%"><div class="caja"><div class="lbl">Valor Productos</div><div class="val">S/ {{ number_format($valorProducto, 2) }}</div></div></td>
            <td width="25%"><div class="caja"><div class="lbl">Valor Total</div><div class="val">S/ {{ number_format($valorMateria + $valorProducto, 2) }}</div></div></td>
        </tr>
    </table>

    @if($materias->count() > 0)
    <div class="seccion">Materia Prima</div>
    <table class="reporte">
        <thead>
            <tr>
                <th>Insumo</th><th>Proveedor</th><th class="num">Stock</th>
                <th class="num">Mínimo</th><th class="num">Costo Unit.</th><th class="num">Valor Total</th><th>Estado</th><th class="num">Reponer</th>
            </tr>
        </thead>
        <tbody>
            @foreach($materias as $m)
            <tr>
                <td>{{ $m->nombre }}</td>
                <td>{{ $m->proveedor->nombre ?? '—' }}</td>
                <td class="num">{{ number_format($m->stock_actual, 2) }} {{ $m->unidad->abreviatura }}</td>
                <td class="num">{{ number_format($m->stock_minimo, 2) }}</td>
                <td class="num">S/ {{ number_format($m->costo_unitario, 2) }}</td>
                <td class="num">S/ {{ number_format($m->stock_actual * $m->costo_unitario, 2) }}</td>
                <td>
                    @if($m->nivel === 'agotado')
                        <span class="badge badge-bad">Agotado</span>
                    @elseif($m->nivel === 'bajo')
                        <span class="badge badge-warn">Stock bajo</span>
                    @else
                        <span class="badge badge-ok">Normal</span>
                    @endif
                </td>
                <td class="num">{{ $m->reponer > 0 ? number_format($m->reponer, 2) . ' ' . $m->unidad->abreviatura : '' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    @if($productos->count() > 0)
    <div class="seccion">Productos Terminados</div>
    <table class="reporte">
        <thead>
            <tr>
                <th>Producto</th><th>Categoría</th><th class="num">Stock</th>
                <th class="num">Mínimo</th><th class="num">Costo Unit.</th><th class="num">Valor Total</th><th>Estado</th><th class="num">Reponer</th>
            </tr>
        </thead>
        <tbody>
            @foreach($productos as $p)
            <tr>
                <td>{{ $p->nombre }}</td>
                <td>{{ $p->categoria->nombre ?? '—' }}</td>
                <td class="num">{{ $p->stock_actual }} uds</td>
                <td class="num">{{ $p->stock_minimo }}</td>
                <td class="num">S/ {{ number_format($p->costo_produccion ?? 0, 2) }}</td>
                <td class="num">S/ {{ number_format($p->stock_actual * ($p->costo_produccion ?? 0), 2) }}</td>
                <td>
                    @if($p->nivel === 'agotado')
                        <span class="badge badge-bad">Agotado</span>
                    @elseif($p->nivel === 'bajo')
                        <span class="badge badge-warn">Stock bajo</span>
                    @else
                        <span class="badge badge-ok">Normal</span>
                    @endif
                </td>
                <td class="num">{{ $p->reponer > 0 ? number_format($p->reponer, 2) . ' uds' : '' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <div class="pie">
        Documento generado el {{ now()->format('d/m/Y H:i') }} · Reporte interno del sistema de gestión de panadería
    </div>

</body>
</html>
