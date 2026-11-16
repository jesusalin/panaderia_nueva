@extends('layouts.app')
@section('title', 'Cierre diario')
@section('breadcrumb')
    @if(auth()->user()->hasPermiso('reportes.resumen'))<li class="breadcrumb-item"><a href="{{ route('reportes.index') }}">Reportes</a></li>@endif
    <li class="breadcrumb-item active">Cierre diario</li>
@endsection

@include('partials.reportes-estilos')

@push('styles')
<style>
    .cd-dia { display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: .7rem; margin-bottom: 1.1rem; }
    .cd-dia > * { background: var(--m-surface); border-radius: 12px; padding: .7rem .9rem; box-shadow: 0 1px 6px rgba(0,0,0,.05); color: inherit; text-decoration: none !important;
                  border: 1.5px solid transparent; display: flex; gap: .7rem; align-items: center; transition: border-color .15s, transform .15s; }
    .cd-dia a:hover { border-color: #e2c9b8; transform: translateY(-2px); }
    .cd-dia .cd-ic { width: 38px; height: 38px; border-radius: 10px; display: grid; place-items: center; flex-shrink: 0; font-size: .95rem; }
    .cd-dia small { display: block; font-size: .7rem; font-weight: 700; text-transform: uppercase; letter-spacing: .03em; color: var(--m-muted); }
    .cd-dia b { font-size: 1.02rem; color: var(--m-text); }
    .cd-dia span.sub { display: block; font-size: .76rem; color: var(--m-muted); }
    body.dark-mode .cd-dia > * { background: #1f1f33; }
    body.dark-mode .cd-dia b { color: #f0f0f7; }

    .cd-tabs { display: flex; gap: .4rem; margin-bottom: .8rem; flex-wrap: wrap; }
    .cd-tabs a { padding: .5rem 1rem; border-radius: 12px; font-weight: 700; font-size: .9rem; background: var(--m-surface-3); color: var(--m-text-2); text-decoration: none !important; }
    .cd-tabs a.activo { background: #1a1a2e; color: #fff; }
    .cd-tabs a span { opacity: .7; font-weight: 600; margin-left: .25rem; }
    body.dark-mode .cd-tabs a { background: #2c2c44; color: #b0b0cc; }
    body.dark-mode .cd-tabs a.activo { background: #b5451b; color: #fff; }

    .cd-cerrar { display: flex; flex-wrap: wrap; gap: .6rem; align-items: center; background: var(--m-surface); border-radius: 14px; padding: .8rem 1rem;
                 box-shadow: 0 2px 12px rgba(0,0,0,.05); margin-bottom: 1.2rem; border-left: 4px solid #1e8e5a; }
    .cd-cerrar .cd-reloj { font-size: .78rem; color: var(--m-muted); white-space: nowrap; }
    .cd-cerrar .cd-reloj b { color: var(--m-text); font-variant-numeric: tabular-nums; }
    body.dark-mode .cd-cerrar { background: #1f1f33; }
    body.dark-mode .cd-cerrar .cd-reloj b { color: #f0f0f7; }

    .cd-tci { display: inline-flex; align-items: center; gap: .4rem; font-size: .8rem; background: rgba(30,142,90,.1); color: var(--m-success-text); border-radius: 20px; padding: .25rem .75rem; font-weight: 700; }
    .cd-hist { background: var(--m-surface); border-radius: 14px; padding: .7rem .9rem .55rem; box-shadow: 0 2px 12px rgba(0,0,0,.05); margin-bottom: 1rem; }
    .cd-hist-cab { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: .5rem; font-size: .82rem; color: var(--m-muted); margin-bottom: .5rem; }
    .cd-hist-dias { display: flex; gap: .35rem; overflow-x: auto; padding-bottom: .3rem; scrollbar-width: thin; }
    .cd-d { flex: 0 0 auto; width: 58px; text-align: center; border-radius: 10px; padding: .35rem .2rem; text-decoration: none !important;
            border: 1.5px solid var(--m-border); color: var(--m-text); display: flex; flex-direction: column; align-items: center; gap: .05rem; transition: transform .12s, border-color .12s; }
    .cd-d:hover { transform: translateY(-2px); border-color: #d9b9a5; }
    .cd-d small { font-size: .66rem; font-weight: 700; text-transform: capitalize; color: var(--m-muted); }
    .cd-d b { font-size: 1rem; line-height: 1.1; }
    .cd-d i { font-size: .72rem; }
    .cd-d em { font-style: normal; font-size: .6rem; color: var(--m-info-text); font-weight: 700; white-space: nowrap; }
    .cd-d.ok { background: rgba(30,142,90,.07); } .cd-d.ok i { color: var(--m-success-text); }
    .cd-d.no { background: rgba(192,57,43,.06); } .cd-d.no i { color: var(--m-danger-text); }
    .cd-d.nada i { color: #c4c4cf; }
    .cd-d.activo { border-color: #b5451b; box-shadow: 0 0 0 2px rgba(181,69,27,.18); }
    .cd-hist-ley { display: flex; gap: .9rem; font-size: .7rem; color: var(--m-muted); margin-top: .15rem; }
    .cd-hist-ley .ok i { color: var(--m-success-text); } .cd-hist-ley .no i { color: var(--m-danger-text); } .cd-hist-ley .nada i { color: #c4c4cf; }
    body.dark-mode .cd-hist { background: #1f1f33; }
    body.dark-mode .cd-d { border-color: #33334d; color: #f0f0f7; }
</style>
@endpush

@section('content')
@php
    $C = \App\Support\Cantidad::class;
    $esHoy = $dia->isToday();
    $esProd = $de === 'productos';
    $base = array_filter(['fecha' => $dia->toDateString(), 'de' => $esProd ? 'productos' : null]);
    $q = array_filter($base + ['ver' => $filtro !== 'todos' ? $filtro : null, 'buscar' => request('buscar')]) + \App\Support\TablaFiltro::parametros(request());
    $acento = $cierre ? ($cambio ? '#d4880f' : '#1e8e5a') : '#b5451b';
    $tot = $esProd ? $totalesProd : $totales;
    $difAyer = $totales['valor_final'] - $valorAyer;
    $u = auth()->user();
    $diaQ = ['desde' => $dia->toDateString(), 'hasta' => $dia->toDateString()];
@endphp

<div class="page-toolbar">
    <div>
        <h2><i class="fas fa-calendar-check mr-2 text-secondary"></i>Cierre diario de inventario</h2>
        <p>Saldo inicial, entradas, salidas, ajustes y saldo final del día — de insumos y de productos terminados</p>
    </div>
    <div class="toolbar-actions rp-acciones-exp">
        <a href="{{ route('reportes.cierre.pdf', $q) }}" target="_blank" class="btn btn-light js-exp-cierre"><i class="fas fa-file-pdf mr-1 text-danger"></i>PDF</a>
        <a href="{{ route('reportes.cierre.excel', $q) }}" class="btn btn-light js-exp-cierre"><i class="fas fa-file-excel mr-1 text-success"></i>Excel</a>
    </div>
</div>

<form method="GET" action="{{ route('reportes.cierre') }}" class="rp-dias">
    @if($esProd)<input type="hidden" name="de" value="productos">@endif
    <a href="{{ route('reportes.cierre', array_merge($base, ['fecha' => $dia->copy()->subDay()->toDateString()])) }}" class="btn btn-light" title="Día anterior" aria-label="Día anterior"><i class="fas fa-chevron-left"></i></a>
    <input type="date" name="fecha" class="form-control" value="{{ $dia->toDateString() }}" max="{{ today()->toDateString() }}" onchange="this.form.submit()" aria-label="Día del cierre">
    @unless($esHoy)
        <a href="{{ route('reportes.cierre', array_merge($base, ['fecha' => $dia->copy()->addDay()->toDateString()])) }}" class="btn btn-light" title="Día siguiente" aria-label="Día siguiente"><i class="fas fa-chevron-right"></i></a>
        <a href="{{ route('reportes.cierre', array_filter(['de' => $esProd ? 'productos' : null])) }}" class="btn btn-light">Hoy</a>
    @endunless
</form>

{{-- Últimos 14 días, arriba y compacto: se ve de un vistazo qué días faltan cerrar --}}
<div class="cd-hist">
    <div class="cd-hist-cab">
        <span><i class="fas fa-history mr-1"></i>Últimos 14 días · <b>{{ $tci['cerrados'] }}</b> cerrados de {{ $tci['con_movimiento'] }} con movimiento</span>
        @if($tci['promedio'] !== null)
            <span class="cd-tci" title="Indicador TCI: promedio del tiempo que tomó cada cierre con tiempo medido">
                <i class="far fa-clock"></i>Tiempo promedio de cierre: {{ \App\Models\CierreInventario::duracion($tci['promedio']) }}
            </span>
        @endif
    </div>
    <div class="cd-hist-dias" id="cdHistDias">
        @foreach($historial->reverse() as $h)
            @php $estado = $h['cierre'] ? 'ok' : ($h['movimientos'] ? 'no' : 'nada'); @endphp
            <a href="{{ route('reportes.cierre', array_filter(['fecha' => $h['dia']->toDateString(), 'de' => $esProd ? 'productos' : null])) }}"
               class="cd-d {{ $estado }} {{ $h['dia']->isSameDay($dia) ? 'activo' : '' }}"
               title="{{ ucfirst($h['dia']->locale('es')->translatedFormat('l d/m')) }} · {{ $h['cierre'] ? 'Cerrado a las ' . $h['cierre']->updated_at->format('H:i') . ($h['cierre']->duracion ? ' (tardó ' . $h['cierre']->duracion . ')' : '') : ($h['movimientos'] ? 'Sin cerrar' : 'Sin movimientos') }} · {{ $h['movimientos'] }} mov.">
                <small>{{ $h['dia']->isToday() ? 'Hoy' : ($h['dia']->isYesterday() ? 'Ayer' : $h['dia']->locale('es')->translatedFormat('D')) }}</small>
                <b>{{ $h['dia']->format('d') }}</b>
                <i class="fas {{ $estado === 'ok' ? 'fa-lock' : ($estado === 'no' ? 'fa-lock-open' : 'fa-minus') }}"></i>
                @if($h['cierre'] && $h['cierre']->duracion)<em>{{ $h['cierre']->duracion }}</em>@endif
            </a>
        @endforeach
    </div>
    <div class="cd-hist-ley"><span class="ok"><i class="fas fa-lock"></i> cerrado</span><span class="no"><i class="fas fa-lock-open"></i> falta cerrar</span><span class="nada"><i class="fas fa-minus"></i> sin movimientos</span></div>
</div>

<div class="inv-hero" style="--acento: {{ $acento }}">
    <div class="ih-icono"><i class="fas {{ $cierre ? 'fa-lock' : 'fa-lock-open' }}"></i></div>
    <div>
        <small>{{ $esHoy ? 'Hoy · ' : '' }}{{ $cierre ? 'Día cerrado' : 'Día sin cerrar' }}</small>
        <h3>{{ ucfirst($dia->locale('es')->translatedFormat('l d \d\e F \d\e Y')) }}</h3>
        @if($cierre)
            <small style="text-transform:none;letter-spacing:0;opacity:.85">Cerrado por {{ $cierre->usuario->apodo ?? $cierre->usuario->nombre ?? '—' }} el {{ $cierre->updated_at->format('d/m H:i') }}{{ $cierre->duracion ? ' · tardó ' . $cierre->duracion : '' }}</small>
        @endif
    </div>
    <div class="ih-datos">
        <div><b>S/ {{ number_format($totales['valor_final'], 2) }}</b><span>insumos al cierre
            @if(abs($difAyer) >= 0.01) · <span title="Comparado con el día anterior">{{ $difAyer > 0 ? '▲' : '▼' }} S/ {{ number_format(abs($difAyer), 2) }} vs. ayer</span>@endif</span></div>
        <div><b>S/ {{ number_format($totalesProd['valor_final'], 2) }}</b><span>productos al cierre</span></div>
        <div><b>{{ $totales['movimientos'] + $totalesProd['movimientos'] }}</b><span>movimientos del día</span></div>
    </div>
</div>

@if($cierre && $cambio)
    <div class="alert alert-warning"><i class="fas fa-exclamation-triangle mr-1"></i>Después de cerrar se registraron movimientos de este día: los saldos cambiaron. <b>Vuelve a cerrar</b> para guardar los saldos correctos.</div>
@endif
@if($tot['no_cuadran'])
    <div class="alert alert-danger"><i class="fas fa-times-circle mr-1"></i><b>{{ $tot['no_cuadran'] }} {{ $esProd ? 'producto(s)' : 'insumo(s)' }} no cuadran</b> (saldo inicial + entradas − salidas ± ajustes ≠ saldo final). <a href="{{ route('reportes.cierre', $base + ['ver' => 'descuadre']) }}" class="alert-link">Ver cuáles</a></div>
@endif

{{-- Lo que pasó en el día: de dónde vienen las entradas y las salidas --}}
<p class="cp-titulo"><i class="fas fa-stream"></i>Lo que pasó en el día</p>
<div class="cd-dia">
    @php
        $tarjetas = [
            ['Compras recibidas', $resumenDia['compras'], 'S/ ' . number_format($resumenDia['compras_total'], 2), 'fa-truck-ramp-box', '#2170a3', 'rgba(33,112,163,.12)',
             $u->hasPermiso('reportes.compras') ? route('reportes.compras', $diaQ) : null],
            ['Producción', $resumenDia['producciones'], $C::conUnidad($resumenDia['producido'], 'uds') . ' horneadas', 'fa-industry', '#b5700a', 'rgba(212,136,15,.14)',
             $u->hasPermiso('produccion.produccion') ? route('produccion.index', ['fecha_desde' => $dia->toDateString(), 'fecha_hasta' => $dia->toDateString()]) : null],
            ['Ventas', $resumenDia['ventas'], 'S/ ' . number_format($resumenDia['ventas_total'], 2), 'fa-cash-register', '#1e8e5a', 'rgba(30,142,90,.12)',
             $u->hasPermiso('reportes.ventas') ? route('reportes.ventas', $diaQ) : null],
            ['Mermas', 'S/ ' . number_format($resumenDia['merma'], 2), 'perdido en el día', 'fa-trash-can', '#c0392b', 'rgba(192,57,43,.1)',
             $u->hasPermiso('reportes.mermas') ? route('reportes.mermas', $diaQ) : null],
        ];
    @endphp
    @php
        // Quien no maneja ventas (por ejemplo, el almacenero) no ve la tarjeta de ventas
        if (!$u->hasPermiso('ventas') && !$u->hasPermiso('reportes.ventas')) unset($tarjetas[2]);
    @endphp
    @foreach($tarjetas as [$titulo, $valor, $sub, $icono, $color, $fondo, $enlace])
        @if($enlace)<a href="{{ $enlace }}" title="Ver el detalle de este día">@else<div>@endif
            <span class="cd-ic" style="background:{{ $fondo }};color:{{ $color }}"><i class="fas {{ $icono }}"></i></span>
            <div><small>{{ $titulo }}</small><b>{{ $valor }}</b><span class="sub">{{ $sub }}</span></div>
        @if($enlace)</a>@else</div>@endif
    @endforeach
</div>

{{-- Registrar el cierre (el tiempo desde que se abrió esta pantalla es el tiempo de cierre: TCI) --}}
<form method="POST" action="{{ route('reportes.cierre.guardar') }}" class="cd-cerrar js-confirm"
      data-confirm-title="{{ $cierre ? '¿Volver a cerrar el día?' : '¿Cerrar el día?' }}"
      data-confirm="Se guardan los saldos de {{ $totales['insumos'] }} insumos y {{ $totalesProd['insumos'] }} productos del {{ $dia->format('d/m/Y') }} (insumos: S/ {{ number_format($totales['valor_final'], 2) }}).{{ $cierre ? ' Reemplaza el cierre anterior.' : '' }}">
    @csrf
    <input type="hidden" name="fecha" value="{{ $dia->toDateString() }}">
    <input type="hidden" name="abierto" value="{{ $abierto }}">
    <div class="flex-grow-1" style="min-width:220px">
        <input type="text" name="observacion" maxlength="300" class="form-control" value="{{ old('observacion', $cierre->observacion ?? '') }}" placeholder="Nota del cierre (opcional): ej. se recibió harina tarde">
    </div>
    <span class="cd-reloj" title="Se mide desde que abriste esta pantalla hasta que confirmas el cierre (indicador TCI)"><i class="far fa-clock mr-1"></i>Tiempo de cierre: <b id="cdReloj">0:00</b></span>
    <button class="btn {{ $cierre ? 'btn-outline-success' : 'btn-success' }}"><i class="fas fa-lock mr-1"></i>{{ $cierre ? 'Volver a cerrar el día' : 'Cerrar el día' }}</button>
</form>

<div class="cd-tabs" role="tablist">
    <a href="{{ route('reportes.cierre', ['fecha' => $dia->toDateString()]) }}" class="{{ $esProd ? '' : 'activo' }}" role="tab" aria-selected="{{ $esProd ? 'false' : 'true' }}"><i class="fas fa-wheat-awn mr-1"></i>Insumos<span>{{ $totales['insumos'] }}</span></a>
    <a href="{{ route('reportes.cierre', ['fecha' => $dia->toDateString(), 'de' => 'productos']) }}" class="{{ $esProd ? 'activo' : '' }}" role="tab" aria-selected="{{ $esProd ? 'true' : 'false' }}"><i class="fas fa-bread-slice mr-1"></i>Productos terminados<span>{{ $totalesProd['insumos'] }}</span></a>
</div>

<div class="inv-resumen">
    <span class="ir"><span class="im-flecha sube mr-1"><i class="fas fa-arrow-up"></i></span>{{ $esProd ? 'Producido' : 'Entró' }}: <b>S/ {{ number_format($tot['valor_entradas'], 2) }}</b></span>
    <span class="ir"><span class="im-flecha baja mr-1"><i class="fas fa-arrow-down"></i></span>{{ $esProd ? 'Vendido y mermas' : 'Salió' }}: <b>S/ {{ number_format($tot['valor_salidas'], 2) }}</b></span>
    <span class="ir"><i class="fas fa-exchange-alt text-muted"></i><b>{{ $tot['con_movimiento'] }} / {{ $tot['insumos'] }}</b> con movimiento</span>
    <a href="{{ route('reportes.cierre', $base + ['ver' => 'bajo']) }}" class="ir {{ $filtro === 'bajo' ? 'activo' : '' }}"><i class="fas fa-exclamation-triangle" style="color:#e67e22"></i><b>{{ $tot['bajo_minimo'] }}</b> bajo el mínimo</a>
</div>

<div class="d-flex flex-wrap align-items-center mb-2" style="gap:.5rem">
    <div class="inv-chips mb-0">
        @foreach(\App\Http\Controllers\CierreInventarioController::FILTROS as $clave => $texto)
            <a href="{{ route('reportes.cierre', array_filter($base + ['ver' => $clave !== 'todos' ? $clave : null, 'buscar' => request('buscar')])) }}" class="inv-chip {{ $filtro === $clave ? 'activo' : '' }}">{{ $esProd ? str_replace('insumos', 'productos', $texto) : $texto }}</a>
        @endforeach
    </div>
</div>

<p class="small text-muted mb-2"><i class="fas fa-hand-pointer mr-1"></i>Toca el título de una columna para ordenar. El PDF y el Excel salen con el mismo orden y filtros.</p>
<div class="rp-caja mb-4">
    <div class="rp-scroll">
    <table class="rp-tabla" data-tabla="cierre" data-exportar=".js-exp-cierre" data-buscar>
        <thead><tr>
            <th data-clave="nombre">{{ $esProd ? 'Producto' : 'Insumo' }}</th>
            <th data-clave="inicial" class="num">Saldo inicial</th>
            <th data-clave="entradas" class="num">{{ $esProd ? 'Producido' : 'Entradas' }}</th>
            <th data-clave="salidas" class="num">{{ $esProd ? 'Vendido / mermas' : 'Salidas' }}</th>
            <th data-clave="ajuste" class="num">Ajustes</th>
            <th data-clave="final" class="num">Saldo final</th>
            <th data-clave="valor" class="num rp-ocultar-movil">Valor</th>
            <th data-clave="estado" data-filtro>Estado</th>
        </tr></thead>
        <tbody>
        @forelse($vista as $f)
            <tr class="{{ $f['movimientos'] ? '' : 'quieto' }}">
                <td data-v="{{ $f['nombre'] }}">
                    @if(!$esProd && $u->hasPermiso('reportes.kardex'))
                        <a href="{{ route('reportes.kardex', ['item' => 'materia-' . $f['id'], 'fecha_desde' => $dia->toDateString(), 'fecha_hasta' => $dia->toDateString()]) }}" class="font-weight-bold" title="Ver su kardex de este día">{{ $f['nombre'] }}</a>
                    @elseif($esProd && $u->hasPermiso('reportes.kardex'))
                        <a href="{{ route('reportes.kardex', ['item' => 'producto-' . $f['id'], 'fecha_desde' => $dia->toDateString(), 'fecha_hasta' => $dia->toDateString()]) }}" class="font-weight-bold" title="Ver su kardex de este día">{{ $f['nombre'] }}</a>
                    @else <b>{{ $f['nombre'] }}</b> @endif
                    <div class="small text-muted">{{ $f['movimientos'] ? $f['movimientos'] . ' mov.' : 'sin movimiento' }}</div>
                </td>
                <td class="num" data-v="{{ $f['inicial'] }}">{{ $C::conUnidad($f['inicial'], $f['unidad']) }}</td>
                <td class="num {{ $f['entradas'] > 0 ? 'inv-mas' : '' }}" data-v="{{ $f['entradas'] }}">{{ $f['entradas'] > 0 ? '+' . $C::conUnidad($f['entradas'], $f['unidad']) : '—' }}</td>
                <td class="num {{ $f['salidas'] > 0 ? 'inv-menos' : '' }}" data-v="{{ $f['salidas'] }}">{{ $f['salidas'] > 0 ? '−' . $C::conUnidad($f['salidas'], $f['unidad']) : '—' }}</td>
                <td class="num {{ $f['ajuste'] != 0 ? 'inv-aj' : '' }}" data-v="{{ $f['ajuste'] }}">{{ $f['ajuste'] != 0 ? $C::conUnidad($f['ajuste'], $f['unidad'], true) : '—' }}</td>
                <td class="num" data-v="{{ $f['final'] }}"><b>{{ $C::conUnidad($f['final'], $f['unidad']) }}</b></td>
                <td class="num rp-ocultar-movil" data-v="{{ $f['valor_final'] }}">S/ {{ number_format($f['valor_final'], 2) }}</td>
                <td class="text-right" data-f="{{ $f['estado'] }}" data-v="{{ $f['estado'] }}">
                    @if(!$f['cuadra'])<span class="rp-chip mal" title="No cuadra">no cuadra</span>
                    @elseif($f['bajo'])<span class="rp-chip bajo">bajo mín.</span>
                    @else<span class="text-muted small">normal</span>@endif
                </td>
            </tr>
        @empty
            <tr><td colspan="8" class="text-center text-muted py-4">Nada en esta vista.</td></tr>
        @endforelse
        </tbody>
        <tfoot><tr>
            <td data-tb-cuenta="{{ $esProd ? 'producto|productos' : 'insumo|insumos' }}">{{ $vista->count() }} {{ $esProd ? ($vista->count() == 1 ? 'producto' : 'productos') : ($vista->count() == 1 ? 'insumo' : 'insumos') }}</td>
            <td colspan="5" class="num rp-ocultar-movil">Valor al cierre</td>
            <td class="num" data-tb-suma="6">S/ {{ number_format($vista->sum('valor_final'), 2) }}</td>
            <td></td>
        </tr></tfoot>
    </table>
    </div>
</div>

@endsection

@push('scripts')
<script>
    // El historial empieza mostrando el día elegido (a la derecha están los más recientes)
    (function () {
        const c = document.getElementById('cdHistDias'), a = c && c.querySelector('.activo');
        if (a) c.scrollLeft = a.offsetLeft - c.clientWidth + a.offsetWidth + 20;
    })();
    // Reloj del tiempo de cierre (TCI): desde que se abrió la pantalla
    (function () {
        const el = document.getElementById('cdReloj');
        if (!el) return;
        const inicio = Date.now();
        setInterval(() => {
            const s = Math.floor((Date.now() - inicio) / 1000);
            el.textContent = Math.floor(s / 60) + ':' + String(s % 60).padStart(2, '0');
        }, 1000);
    })();
</script>
@endpush
