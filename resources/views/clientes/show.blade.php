@extends('layouts.app')
@section('title', $cliente->nombre)
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('clientes.index') }}">Clientes</a></li>
    <li class="breadcrumb-item active">{{ $cliente->nombre }}</li>
@endsection

@include('partials.compras-estilos')

@push('styles')
<style>
    .cs-meses { display: flex; align-items: flex-end; gap: .6rem; height: 130px; margin-top: .4rem; }
    .cs-meses .m { flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: flex-end; height: 100%; }
    .cs-meses .m span { width: 100%; max-width: 46px; background: linear-gradient(180deg, #34c27d, #1e8e5a); border-radius: 6px 6px 0 0; min-height: 3px; }
    .cs-meses .m em { font-style: normal; font-size: .7rem; color: #8a8a9d; margin-top: .3rem; text-transform: capitalize; }
    .cs-meses .m b { font-size: .68rem; color: #6c757d; margin-bottom: .2rem; white-space: nowrap; }
    .cs-datos div { display: flex; justify-content: space-between; gap: .8rem; padding: .35rem 0; border-bottom: 1px solid #f6f3f0; font-size: .86rem; }
    .cs-datos div:last-child { border-bottom: none; }
    .cs-datos span { color: #8a8a9d; flex-shrink: 0; }
    .cs-datos b { text-align: right; }
    body.dark-mode .cs-datos div { border-color: #33334d; }
    .cs-av { width: 62px; height: 62px; border-radius: 14px; color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 1.35rem; flex-shrink: 0;
        background: rgba(255,255,255,.14); border: 2px solid rgba(255,255,255,.25); }
    .cs-nota { background: #fff8ec; border-radius: 12px; padding: .7rem .9rem; margin-bottom: 1rem; color: #6c5a48; font-size: .88rem; display: flex; gap: .6rem; }
    body.dark-mode .cs-nota { background: rgba(212,136,15,.12); color: #f0d6a8; }
    .cs-alerta { background: #fdecea; border-radius: 12px; padding: .7rem .9rem; margin-bottom: 1rem; color: #a93226; font-size: .88rem; display: flex; gap: .6rem; align-items: center; flex-wrap: wrap; }
    body.dark-mode .cs-alerta { background: rgba(231,76,60,.15); color: #f1a097; }
    .cs-prod { display: grid; grid-template-columns: 32px minmax(0, 1fr) auto; gap: .6rem; align-items: center; padding: .4rem 0; border-bottom: 1px dashed #eee5dd; }
    .cs-prod:last-child { border-bottom: 0; }
    .cs-prod .ic { width: 32px; height: 32px; border-radius: 9px; display: flex; align-items: center; justify-content: center; font-size: .85rem;
        background: color-mix(in srgb, var(--c) 14%, #fff); color: var(--c); }
    body.dark-mode .cs-prod { border-color: #33334d; }
    body.dark-mode .cs-prod .ic { background: #2c2c44; }
    .cs-venta td { vertical-align: middle; }
</style>
@endpush

@section('content')
@php
    $c = $cliente;
    $activo = ($c->estado ?? 'activo') !== 'inactivo';
    $general = $c->esGeneral();
    $maxMes = max(1, $meses->max('total'));
    $M = \App\Models\Venta::MEDIOS;
    $puedeVentas = auth()->user()->hasPermiso('ventas');
@endphp

<div class="inv-hero" style="--acento: {{ $activo ? $c->color_tipo : '#6c757d' }}">
    <div class="cs-av">@if($general)<i class="fas fa-store-alt"></i>@else{{ $c->getIniciales() }}@endif</div>
    <div>
        <small><i class="fas {{ $c->icono }} mr-1"></i>{{ $c->tipo_label }}{{ $c->documento ? ' · ' . $c->documento : '' }}{{ $activo ? '' : ' · inactivo' }}</small>
        <h3>{{ $c->nombre }}</h3>
        @if($c->contacto)<small style="text-transform:none;letter-spacing:0;opacity:.85"><i class="fas fa-user mr-1"></i>{{ $c->contacto }}</small>@endif
    </div>
    <div class="ih-datos">
        <div><b>S/ {{ number_format($resumen['total'], 2) }}</b><span>comprado en total ({{ $resumen['compras'] }} compra{{ $resumen['compras'] == 1 ? '' : 's' }})</span></div>
        <div><b>S/ {{ number_format($resumen['promedio'], 2) }}</b><span>por compra en promedio</span></div>
        <div><b>{{ $resumen['ultima'] ? ($resumen['dias_sin'] === 0 ? 'Hoy' : ($resumen['dias_sin'] === 1 ? 'Ayer' : 'Hace ' . $resumen['dias_sin'] . ' días')) : '—' }}</b><span>última compra{{ $resumen['cada'] ? ' · compra cada ' . $resumen['cada'] . ' día(s)' : '' }}</span></div>
    </div>
    <div class="ih-acciones">
        @if($c->whatsapp)<a href="https://wa.me/{{ $c->whatsapp }}" target="_blank" rel="noopener" class="btn btn-light btn-sm"><i class="fab fa-whatsapp mr-1" style="color:#25d366"></i>WhatsApp</a>@endif
        @if($c->telefono)<a href="tel:{{ preg_replace('/[^\d+]/', '', $c->telefono) }}" class="btn btn-outline-light btn-sm"><i class="fas fa-phone mr-1"></i>Llamar</a>@endif
        <a href="{{ route('clientes.edit', $c) }}" class="btn btn-outline-light btn-sm"><i class="fas fa-edit mr-1"></i>Editar</a>
        @unless($general)
            <form action="{{ route('clientes.toggle-estado', $c) }}" method="POST" class="d-inline">
                @csrf @method('PUT')
                <button class="btn btn-outline-light btn-sm"><i class="fas {{ $activo ? 'fa-ban' : 'fa-check' }} mr-1"></i>{{ $activo ? 'Desactivar' : 'Activar' }}</button>
            </form>
        @endunless
        <a href="{{ route('clientes.index') }}" class="btn btn-outline-light btn-sm"><i class="fas fa-arrow-left mr-1"></i>Volver</a>
    </div>
</div>

@if($resumen['recuperar'] && $activo)
    <div class="cs-alerta">
        <i class="fas fa-user-clock"></i>
        <div class="flex-grow-1"><b>Hace {{ $resumen['dias_sin'] }} días que no compra</b>{{ $resumen['cada'] ? ' (antes compraba cada ' . $resumen['cada'] . ' día(s))' : '' }}. Puede ser buen momento para escribirle.</div>
        @if($c->whatsapp)
            <a href="https://wa.me/{{ $c->whatsapp }}?text={{ rawurlencode('Hola' . ($c->contacto ? ' ' . $c->contacto : '') . ', le saludamos de Panadería Muruwasi. ¿Le gustaría hacer su pedido de pan esta semana?') }}" target="_blank" rel="noopener" class="btn btn-sm btn-success"><i class="fab fa-whatsapp mr-1"></i>Escribirle</a>
        @endif
    </div>
@endif
@if($c->notas)
    <div class="cs-nota"><i class="fas fa-sticky-note mt-1"></i><div><b>Notas:</b> {{ $c->notas }}</div></div>
@endif

<div class="inv-grid2 mb-3">
    <div class="inv-box">
        <h5><i class="fas fa-chart-bar"></i>Lo que nos compró (últimos 6 meses)</h5>
        <div class="cs-meses">
            @foreach($meses as $m)
                <div class="m" title="{{ $m['mes']->locale('es')->translatedFormat('F Y') }}: S/ {{ number_format($m['total'], 2) }} en {{ $m['n'] }} compra(s)">
                    <b>{{ $m['total'] > 0 ? 'S/ ' . number_format($m['total'], 0) : '' }}</b>
                    <span style="height: {{ round($m['total'] / $maxMes * 80) }}%"></span>
                    <em>{{ $m['mes']->locale('es')->translatedFormat('M') }}</em>
                </div>
            @endforeach
        </div>
        <p class="small text-muted mt-2 mb-0">Este mes: <b>S/ {{ number_format($resumen['mes'], 2) }}</b>{{ $resumen['anuladas'] ? ' · ' . $resumen['anuladas'] . ' venta(s) anulada(s), no se cuentan' : '' }}</p>
    </div>

    <div class="inv-box">
        <h5><i class="fas fa-address-card"></i>Datos</h5>
        <div class="cs-datos">
            <div><span>Contacto</span><b>{{ $c->contacto ?: '—' }}</b></div>
            <div><span>Teléfono</span><b>{{ $c->telefono ?: '—' }}</b></div>
            <div><span>Correo</span><b>@if($c->email)<a href="mailto:{{ $c->email }}">{{ $c->email }}</a>@else — @endif</b></div>
            <div><span>Documento</span><b>{{ $c->documento ?? '—' }}</b></div>
            <div><span>Dirección</span><b>
                @if($c->direccion)
                    <a href="https://www.google.com/maps/search/?api=1&query={{ rawurlencode($c->direccion . ($c->distrito ? ', ' . $c->distrito : '') . ', Lima') }}" target="_blank" rel="noopener" title="Ver en el mapa">{{ $c->direccion }}{{ $c->distrito ? ', ' . $c->distrito : '' }} <i class="fas fa-external-link-alt" style="font-size:.7rem"></i></a>
                @else {{ $c->distrito ?: '—' }} @endif
            </b></div>
            @if($c->referencia)<div><span>Referencia</span><b>{{ $c->referencia }}</b></div>@endif
            <div><span>Cliente desde</span><b>{{ $c->created_at?->format('d/m/Y') ?? '—' }}{{ $resumen['primera'] ? ' · 1.ª compra ' . \Carbon\Carbon::parse($resumen['primera'])->format('d/m/Y') : '' }}</b></div>
        </div>
    </div>
</div>

<div class="inv-grid2">
    <div class="inv-box">
        <h5><i class="fas fa-bread-slice"></i>Lo que más compra</h5>
        @forelse($productos as $p)
            <div class="cs-prod">
                <span class="ic" style="--c: {{ $p->producto?->categoria?->color_final ?? '#b5451b' }}"><i class="fas {{ $p->producto?->categoria?->icono_final ?? 'fa-bread-slice' }}"></i></span>
                <div style="min-width:0"><b class="d-block text-truncate">{{ $p->producto->nombre ?? 'Producto eliminado' }}</b>
                    <small class="text-muted">{{ (int) $p->cantidad }} und en {{ $p->veces }} compra(s) · {{ round($p->cantidad / max(1, $p->veces)) }} por vez</small></div>
                <b>S/ {{ number_format($p->total, 2) }}</b>
            </div>
        @empty
            <p class="text-muted mb-0">Todavía no ha comprado.</p>
        @endforelse
        @if($medios->count())
            <p class="small text-muted mt-3 mb-0">Paga con:
                @foreach($medios as $m)<span class="ml-1"><i class="fas {{ ($M[$m->tipo_pago] ?? $M['otro'])['icono'] }}" style="color: {{ ($M[$m->tipo_pago] ?? $M['otro'])['color'] }}"></i> {{ ($M[$m->tipo_pago] ?? $M['otro'])['texto'] }} ({{ $m->n }})</span>@endforeach
            </p>
        @endif
    </div>

    <div class="inv-box">
        <h5><i class="fas fa-receipt"></i>Sus compras</h5>
        @if($ventas->count())
            <div class="table-responsive">
            <table class="inv-tabla cs-venta">
                <thead><tr><th>Venta</th><th>Productos</th><th class="text-right">Total</th><th></th></tr></thead>
                <tbody>
                @foreach($ventas as $v)
                    @php $med = $v->medio; @endphp
                    <tr @if($puedeVentas) class="clic-fila" data-href="{{ route('ventas.show', $v) }}" tabindex="0" aria-label="Ver venta {{ $v->numero_venta }}" @endif>
                        <td><b>{{ $v->numero_venta }}</b><div class="small text-muted">{{ $v->fecha_venta->format('d/m/Y H:i') }}</div></td>
                        <td class="small">{{ \Illuminate\Support\Str::limit($v->detalles->map(fn ($d) => $d->cantidad . ' ' . ($d->producto->nombre ?? '?'))->join(', '), 48) }}</td>
                        <td class="text-right font-weight-bold" style="{{ $v->estado === 'anulada' ? 'text-decoration:line-through;opacity:.6' : '' }}">S/ {{ number_format($v->total, 2) }}
                            <div class="small text-muted font-weight-normal"><i class="fas {{ $med['icono'] }}" style="color: {{ $med['color'] }}"></i> {{ $med['texto'] }}</div></td>
                        <td class="text-right">@if($v->estado === 'anulada')<span class="cp-estado anulada">Anulada</span>@endif</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            </div>
            <div class="mt-2">{{ $ventas->links() }}</div>
        @else
            <p class="text-muted mb-0">Sin compras registradas.</p>
        @endif
    </div>
</div>
@endsection
