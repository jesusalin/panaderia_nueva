@extends('layouts.app')
@section('title', $m->nombre)
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('materia-prima.index') }}">Materia Prima</a></li>
    <li class="breadcrumb-item active">{{ $m->nombre }}</li>
@endsection

@include('partials.inv-estilos')

@section('content')
@php
    $u = $m->unidad->abreviatura ?? '';
    $C = \App\Support\Cantidad::class;
    $nivel = $m->estaAgotado() ? 'agotado' : ($m->tieneStockBajo() ? 'bajo' : 'ok');
    // consumo diario redondeado para leerlo fácil (≈ 15.6 kg, no 15.607 kg)
    $porDiaTxt = $porDia ? $C::conUnidad(round($porDia, $porDia >= 10 ? 1 : ($porDia >= 1 ? 2 : 3)), $u) : null;
    $maxDia = max(0.0001, $consumoDias->max('cantidad'));
    $motivos = ['compra' => 'Compra', 'produccion' => 'Producción', 'merma' => 'Merma', 'ajuste_manual' => 'Ajuste', 'devolucion' => 'Devolución'];
@endphp

<div class="inv-hero" style="--acento: {{ $nivel === 'ok' ? '#1e8e5a' : '#c0392b' }}">
    <div class="ih-icono"><i class="fas fa-wheat-awn"></i></div>
    <div>
        <small>Insumo · {{ $m->proveedor->nombre ?? 'sin proveedor' }}
            @if($m->es_control) · <span title="Se cuenta en el conteo rápido del PER">insumo de control</span>@endif
            @if($m->estado !== 'activo') · inactivo @endif
        </small>
        <h3>{{ $m->nombre }}</h3>
    </div>
    <div class="ih-datos">
        <div><b>{{ $C::conUnidad($m->stock_actual, $u) }}</b><span>en almacén (mín. {{ $C::conUnidad($m->stock_minimo, $u) }})</span></div>
        <div><b>{{ $dias === null ? '—' : ($dias < 1 ? '< 1 día' : $dias . ' día' . ($dias == 1 ? '' : 's')) }}</b><span>{{ $porDia ? 'se usan ≈ ' . $porDiaTxt . '/día' : 'sin consumo reciente' }}</span></div>
        <div><b>S/ {{ number_format($m->costo_unitario, 2) }}</b><span>por {{ $u }} · valor S/ {{ number_format($m->valor, 2) }}</span></div>
    </div>
    <div class="ih-acciones">
        <a href="{{ route('materia-prima.ajuste', $m) }}" class="btn btn-light btn-sm"><i class="fas fa-balance-scale mr-1"></i>Ajustar stock</a>
        <a href="{{ route('materia-prima.edit', $m) }}" class="btn btn-light btn-sm"><i class="fas fa-edit mr-1"></i>Editar</a>
        @if(auth()->user()->hasPermiso('compras.compras') && $m->tieneStockBajo())
            <a href="{{ route('compras.create') }}" class="btn btn-warning btn-sm"><i class="fas fa-shopping-cart mr-1"></i>Registrar compra</a>
        @endif
        @if(auth()->user()->hasPermiso('reportes.kardex'))
            <a href="{{ route('reportes.kardex', ['item' => 'materia-' . $m->id]) }}" class="btn btn-outline-light btn-sm"><i class="fas fa-file-invoice mr-1"></i>Kardex completo</a>
        @endif
        <a href="{{ route('materia-prima.index') }}" class="btn btn-outline-light btn-sm"><i class="fas fa-arrow-left mr-1"></i>Volver</a>
    </div>
</div>

<div class="inv-grid2 mb-3">
    <div class="inv-box">
        <h5><i class="fas fa-chart-bar"></i>Lo que salió los últimos {{ \App\Models\MateriaPrima::DIAS_CONSUMO }} días</h5>
        <div class="inv-barras">
            @foreach($consumoDias as $d)
                <div class="b" title="{{ $d['fecha']->locale('es')->translatedFormat('D d/m') }}: {{ $C::conUnidad($d['cantidad'], $u) }}">
                    <span style="height: {{ round($d['cantidad'] / $maxDia * 90) }}%"></span>
                    <em>{{ $d['fecha']->format('d') }}</em>
                </div>
            @endforeach
        </div>
        <table class="inv-tabla mt-3">
            <tr><td>Consumo promedio</td><td class="text-right"><b>{{ $porDia ? '≈ ' . $porDiaTxt . ' al día' : '—' }}</b></td></tr>
            <tr><td>Reponer cuando baje de</td><td class="text-right">{{ $C::conUnidad($m->stock_minimo, $u) }}</td></tr>
            <tr><td>Cantidad que se compra</td><td class="text-right">{{ $m->cantidad_reposicion ? $C::conUnidad($m->cantidad_reposicion, $u) : '—' }}</td></tr>
            <tr><td>Último conteo físico</td><td class="text-right">
                @if($ultimoConteo)
                    {{ $ultimoConteo->created_at->format('d/m/Y') }} ·
                    @if($ultimoConteo->diferencia == 0)<span class="inv-mas">coincidió</span>
                    @else<span class="inv-menos">diferencia {{ $C::conUnidad($ultimoConteo->diferencia, $u, true) }}</span>@endif
                @else — @endif
            </td></tr>
        </table>
    </div>

    <div class="inv-box">
        <h5><i class="fas fa-clipboard-list"></i>Se usa en {{ $recetas->count() }} receta{{ $recetas->count() == 1 ? '' : 's' }}</h5>
        @if($recetas->count())
            <table class="inv-tabla">
                <thead><tr><th>Producto</th><th class="text-right">Por tanda</th><th class="text-right">Rinde</th></tr></thead>
                <tbody>
                @foreach($recetas as $d)
                    <tr>
                        <td>
                            @if(auth()->user()->hasPermiso('produccion.recetas'))
                                <a href="{{ route('produccion.receta.editar', $d->receta->producto) }}">{{ $d->receta->producto->nombre }}</a>
                            @else {{ $d->receta->producto->nombre }} @endif
                        </td>
                        <td class="text-right">{{ $C::conUnidad($d->cantidad, $u) }}</td>
                        <td class="text-right text-muted">{{ $d->receta->rendimiento }} und.</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @else
            <p class="text-muted mb-0">Todavía no está en ninguna receta.</p>
        @endif
    </div>
</div>

<div class="inv-grid2">
    <div class="inv-box">
        <h5><i class="fas fa-truck"></i>Últimas compras</h5>
        @if($compras->count())
            <table class="inv-tabla">
                <thead><tr><th>Fecha</th><th>Proveedor</th><th class="text-right">Cantidad</th><th class="text-right">Precio</th><th></th></tr></thead>
                <tbody>
                @foreach($compras as $d)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($d->compra->fecha_compra)->format('d/m') }}</td>
                        <td class="text-muted">{{ \Illuminate\Support\Str::limit($d->compra->proveedor->nombre ?? '—', 22) }}</td>
                        <td class="text-right">{{ $C::conUnidad($d->cantidad, $u) }}</td>
                        <td class="text-right">S/ {{ number_format($d->precio_unitario, 2) }}</td>
                        <td>@if($d->compra->estado === 'pendiente')<span class="badge-soft badge-soft-warning">Por recibir</span>@endif</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @else
            <p class="text-muted mb-0">Sin compras registradas.</p>
        @endif
    </div>

    <div class="inv-box">
        <h5><i class="fas fa-exchange-alt"></i>Últimos movimientos</h5>
        @if($movimientos->count())
            <table class="inv-tabla">
                <thead><tr><th>Fecha</th><th>Motivo</th><th class="text-right">Cantidad</th><th class="text-right">Quedó</th></tr></thead>
                <tbody>
                @foreach($movimientos as $mv)
                    <tr title="{{ $mv->observacion }} — {{ $mv->usuario->nombre ?? '' }}">
                        <td>{{ $mv->created_at?->format('d/m H:i') }}</td>
                        <td>{{ $motivos[$mv->motivo] ?? ucfirst($mv->motivo) }}</td>
                        <td class="text-right {{ $mv->stock_despues >= $mv->stock_antes ? 'inv-mas' : 'inv-menos' }}">
                            {{ $C::conUnidad($mv->stock_despues - $mv->stock_antes, $u, true) }}
                        </td>
                        <td class="text-right">{{ $C::conUnidad($mv->stock_despues, $u) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            @if(auth()->user()->hasPermiso('inventario.movimientos'))
                <a href="{{ route('movimientos.index', ['id_materia' => $m->id, 'periodo' => 'todo']) }}" class="small d-inline-block mt-2">Ver todos sus movimientos →</a>
            @endif
        @else
            <p class="text-muted mb-0">Sin movimientos.</p>
        @endif
    </div>
</div>
@endsection
