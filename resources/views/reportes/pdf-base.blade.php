<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>@yield('titulo')</title>
<style>
    @page { margin: 28px 30px 42px; }
    body { font-family: Helvetica, Arial, sans-serif; color: #1a1a2e; font-size: 11px; }
    .encabezado { border-bottom: 2px solid #1a1a2e; padding-bottom: 8px; margin-bottom: 10px; }
    .encabezado .marca { font-size: 9px; color: #b5451b; font-weight: bold; letter-spacing: .08em; text-transform: uppercase; }
    .encabezado h1 { font-size: 17px; margin: 2px 0 3px; }
    .encabezado p { font-size: 10px; color: #666; margin: 0; }
    .resumen { width: 100%; margin: 8px 0 14px; border-collapse: separate; border-spacing: 6px 0; }
    .resumen td { background: #f7f5f3; border-radius: 6px; padding: 7px 10px; }
    .resumen .lbl { font-size: 8px; color: #888; text-transform: uppercase; }
    .resumen .val { font-size: 13px; font-weight: bold; }
    .seccion { font-size: 12px; font-weight: bold; margin: 14px 0 6px; }
    table.reporte { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
    table.reporte thead th { background: #1a1a2e; color: #fff; text-align: left; padding: 6px 7px; font-size: 8px; text-transform: uppercase; letter-spacing: .03em; }
    table.reporte th.num, table.reporte td.num { text-align: right; }
    table.reporte tbody td { padding: 5px 7px; border-bottom: 1px solid #eee; font-size: 9.5px; }
    table.reporte tbody tr:nth-child(even) { background: #faf9f7; }
    table.reporte tfoot td { padding: 6px 7px; font-weight: bold; border-top: 2px solid #1a1a2e; font-size: 10px; }
    .mas { color: #1e8e5a; } .menos { color: #c0392b; } .aj { color: #a5680d; } .gris { color: #999; }
    .badge { display: inline-block; padding: 1px 6px; border-radius: 8px; font-size: 7.5px; font-weight: bold; }
    .badge-bad { background: #fbe9e7; color: #c0392b; } .badge-ok { background: #e5f6ec; color: #1e8e5a; } .badge-warn { background: #fff4e0; color: #a5680d; }
    .nota { font-size: 9px; color: #555; background: #f7f5f3; padding: 6px 9px; border-radius: 5px; margin: 6px 0 10px; }
    .firmas { width: 100%; margin-top: 34px; }
    .firmas td { width: 50%; text-align: center; font-size: 9px; color: #555; padding-top: 4px; }
    .firmas .linea { border-top: 1px solid #1a1a2e; width: 70%; margin: 0 auto 3px; }
    .pie { position: fixed; bottom: -26px; left: 0; right: 0; font-size: 8px; color: #999; border-top: 1px solid #eee; padding-top: 5px; }
</style>
@yield('estilos')
</head>
<body>
    <div class="pie">Panadería Muruwasi · Sistema de Gestión de Inventario · Generado por {{ auth()->user()->nombre ?? '—' }} el {{ now()->format('d/m/Y H:i') }}</div>
    <div class="encabezado">
        <div class="marca">Panadería Muruwasi</div>
        <h1>@yield('titulo')</h1>
        <p>@yield('subtitulo')</p>
    </div>
    @yield('contenido')
</body>
</html>
