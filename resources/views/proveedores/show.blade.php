@extends('layouts.app')
@section('title', $proveedor->nombre)
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('proveedores.index') }}">Proveedores</a></li>
    <li class="breadcrumb-item active">{{ $proveedor->nombre }}</li>
@endsection

@include('partials.compras-estilos')

@push('styles')
<style>
    .pv-meses { display: flex; align-items: flex-end; gap: .6rem; height: 130px; margin-top: .4rem; }
    .pv-meses .m { flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: flex-end; height: 100%; }
    .pv-meses .m span { width: 100%; max-width: 46px; background: linear-gradient(180deg, #d9653a, #b5451b); border-radius: 6px 6px 0 0; min-height: 3px; }
    .pv-meses .m em { font-style: normal; font-size: .7rem; color: #8a8a9d; margin-top: .3rem; text-transform: capitalize; }
    .pv-meses .m b { font-size: .68rem; color: #6c757d; margin-bottom: .2rem; white-space: nowrap; }
    .pv-dato-lista div { display: flex; justify-content: space-between; gap: .8rem; padding: .35rem 0; border-bottom: 1px solid #f6f3f0; font-size: .86rem; }
    .pv-dato-lista div:last-child { border-bottom: none; }
    .pv-dato-lista span { color: #8a8a9d; }
    body.dark-mode .pv-dato-lista div { border-color: #33334d; }
</style>
@endpush

@section('content')
@php
    $C = \App\Support\Cantidad::class;
    $p = $proveedor;
    $maxMes = max(1, $meses->max('total'));
    $puedeComprar = auth()->user()->hasPermiso('compras.compras');
    $textoEstado = ['pendiente' => 'Por recibir', 'atrasada' => 'Atrasada', 'recibida' => 'Recibida', 'anulada' => 'Anulada'];
@endphp

<div class="inv-hero" style="--acento: {{ $p->estado === 'activo' ? '#b5451b' : '#6c757d' }}">
    <div class="pv-avatar {{ $p->estado === 'activo' ? '' : 'inactivo' }}" style="width:62px;height:62px;font-size:1.35rem;border-radius:14px">{{ $p->getIniciales() }}</div>
    <div>
        <small>Proveedor{{ $p->ruc ? ' · RUC ' . $p->ruc : '' }}{{ $p->estado !== 'activo' ? ' · inactivo' : '' }}</small>
        <h3>{{ $p->nombre }}</h3>
    </div>
    <div class="ih-datos">
        <div><b>S/ {{ number_format($resumen['total'], 2) }}</b><span>comprado en total ({{ $resumen['compras'] }} compra{{ $resumen['compras'] == 1 ? '' : 's' }})</span></div>
        <div><b>S/ {{ number_format($resumen['promedio'], 2) }}</b><span>por compra en promedio</span></div>
        <div><b>{{ $resumen['ultima'] ? \Carbon\Carbon::parse($resumen['ultima'])->format('d/m/Y') : '—' }}</b><span>última compra</span></div>
    </div>
    <div class="ih-acciones">
        @if($puedeComprar && $p->estado === 'activo')
            <a href="{{ route('compras.create', ['proveedor' => $p->id]) }}" class="btn btn-light btn-sm"><i class="fas fa-cart-plus mr-1"></i>Nueva compra</a>
        @endif
        @if($p->whatsapp)<a href="https://wa.me/{{ $p->whatsapp }}" target="_blank" rel="noopener" class="btn btn-light btn-sm"><i class="fab fa-whatsapp mr-1" style="color:#25d366"></i>WhatsApp</a>@endif
        @if($p->telefono)<a href="tel:{{ preg_replace('/[^\d+]/', '', $p->telefono) }}" class="btn btn-outline-light btn-sm"><i class="fas fa-phone mr-1"></i>Llamar</a>@endif
        <a href="{{ route('proveedores.edit', $p) }}" class="btn btn-outline-light btn-sm"><i class="fas fa-edit mr-1"></i>Editar</a>
        <a href="{{ route('proveedores.index') }}" class="btn btn-outline-light btn-sm"><i class="fas fa-arrow-left mr-1"></i>Volver</a>
    </div>
</div>

@if($resumen['pendientes'] || $ordenes->count())
<div class="inv-resumen">
    @if($resumen['pendientes'])
        <a href="{{ $puedeComprar ? route('compras.index', ['id_proveedor' => $p->id, 'estado' => 'pendiente']) : '#' }}" class="ir"><i class="fas fa-truck" style="color:#d4880f"></i><b>{{ $resumen['pendientes'] }}</b> pedido{{ $resumen['pendientes'] == 1 ? '' : 's' }} por recibir</a>
    @endif
    @if($ordenes->count())
        <a href="{{ auth()->user()->hasPermiso('compras.ordenes') ? route('ordenes-automaticas.index') : '#' }}" class="ir"><i class="fas fa-robot" style="color:#2980b9"></i><b>{{ $ordenes->count() }}</b> orden{{ $ordenes->count() == 1 ? '' : 'es' }} automática{{ $ordenes->count() == 1 ? '' : 's' }}: {{ $ordenes->map(fn ($o) => $o->materia->nombre ?? '')->join(', ') }}</a>
    @endif
</div>
@endif

<div class="inv-grid2 mb-3">
    <div class="inv-box">
        <h5><i class="fas fa-chart-bar"></i>Lo que le compramos (últimos 6 meses)</h5>
        <div class="pv-meses">
            @foreach($meses as $m)
                <div class="m" title="{{ $m['mes']->locale('es')->translatedFormat('F Y') }}: S/ {{ number_format($m['total'], 2) }}">
                    <b>{{ $m['total'] > 0 ? 'S/ ' . number_format($m['total'], 0) : '' }}</b>
                    <span style="height: {{ round($m['total'] / $maxMes * 80) }}%"></span>
                    <em>{{ $m['mes']->locale('es')->translatedFormat('M') }}</em>
                </div>
            @endforeach
        </div>
        <p class="small text-muted mt-2 mb-0">Este año: <b>S/ {{ number_format($resumen['anio'], 2) }}</b></p>
    </div>

    <div class="inv-box">
        <h5><i class="fas fa-address-card"></i>Datos de contacto</h5>
        <div class="pv-dato-lista">
            <div><span>Contacto</span><b>{{ $p->contacto ?: '—' }}</b></div>
            <div><span>Teléfono</span><b>{{ $p->telefono ?: '—' }}</b></div>
            <div><span>Correo</span><b>@if($p->email)<a href="mailto:{{ $p->email }}">{{ $p->email }}</a>@else — @endif</b></div>
            <div><span>Dirección</span><b class="text-right">{{ $p->direccion ?: '—' }}</b></div>
            <div><span>RUC</span><b>{{ $p->ruc ?: '—' }}</b></div>
            <div><span>Registrado</span><b>{{ $p->created_at?->format('d/m/Y') ?? '—' }}</b></div>
        </div>
    </div>
</div>

<div class="inv-grid2">
    <div class="inv-box">
        <h5><i class="fas fa-wheat-awn"></i>Insumos que nos vende</h5>
        @if($insumos->count())
            <table class="inv-tabla">
                <thead><tr><th>Insumo</th><th class="text-right">Último precio</th><th class="text-right">Comprado</th></tr></thead>
                <tbody>
                @foreach($insumos as $i)
                    @php $m = $i['materia']; $u = $m->unidad->abreviatura ?? ''; @endphp
                    <tr>
                        <td>
                            @if(auth()->user()->hasPermiso('inventario.materia_prima'))
                                <a href="{{ route('materia-prima.show', $m) }}" class="font-weight-bold">{{ $m->nombre }}</a>
                            @else <b>{{ $m->nombre }}</b> @endif
                            <div class="small text-muted">
                                @if($i['asignado'])Proveedor principal @else Se le compró, pero su proveedor principal es otro @endif
                                @if($m->tieneStockBajo()) · <span class="text-danger font-weight-bold">stock bajo</span>@endif
                            </div>
                        </td>
                        <td class="text-right">
                            @if($i['precio'] !== null)
                                S/ {{ number_format($i['precio'], 2) }}<small class="text-muted">/{{ $u }}</small>
                                @if($i['var'])<br><span class="cp-var {{ $i['var'] > 0 ? 'sube' : 'baja' }}">{{ $i['var'] > 0 ? '▲' : '▼' }} {{ abs($i['var']) }}%</span>@endif
                            @else <span class="text-muted">—</span> @endif
                        </td>
                        <td class="text-right small">{{ $i['veces'] ? $C::conUnidad($i['cantidad'], $u) . ' en ' . $i['veces'] . ' compra' . ($i['veces'] == 1 ? '' : 's') : '—' }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            <p class="small text-muted mt-2 mb-0">Precios sin IGV. ▲▼ = comparado con la compra anterior.</p>
        @else
            <p class="text-muted mb-0">Ningún insumo lo tiene como proveedor. Asígnalo al editar el insumo en Materia Prima.</p>
        @endif
    </div>

    <div class="inv-box">
        <h5><i class="fas fa-shopping-cart"></i>Últimas compras</h5>
        @if($compras->count())
            <table class="inv-tabla">
                <thead><tr><th>Compra</th><th>Insumos</th><th class="text-right">Total</th><th></th></tr></thead>
                <tbody>
                @foreach($compras as $c)
                    @php $est = $c->estado === 'pendiente' && $c->atrasada ? 'atrasada' : $c->estado; @endphp
                    <tr>
                        <td>@if($puedeComprar)<a href="{{ route('compras.show', $c) }}" class="font-weight-bold">{{ $c->codigo }}</a>@else<b>{{ $c->codigo }}</b>@endif<div class="small text-muted">{{ $c->fecha_compra->format('d/m/Y') }}</div></td>
                        <td class="small">{{ \Illuminate\Support\Str::limit($c->detalles->map(fn ($d) => $d->materia->nombre ?? '')->join(', '), 40) }}</td>
                        <td class="text-right font-weight-bold">S/ {{ number_format($c->total, 2) }}</td>
                        <td class="text-right"><span class="cp-estado {{ $est }}">{{ $textoEstado[$est] }}</span></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            @if($puedeComprar)
                <a href="{{ route('compras.index', ['id_proveedor' => $p->id]) }}" class="small d-inline-block mt-2">Ver todas sus compras →</a>
            @endif
        @else
            <p class="text-muted mb-0">Todavía no le compramos nada.</p>
        @endif
    </div>
</div>
@endsection
