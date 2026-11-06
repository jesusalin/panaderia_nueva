{{-- Vista rápida del cliente (ventana js-ver-detalle). La página completa es clientes/show. --}}
@php
    $c = $cliente;
    $activo = ($c->estado ?? 'activo') !== 'inactivo';
@endphp
<style>
    .cd-cab { display: flex; gap: .8rem; align-items: center; margin-bottom: .9rem; }
    .cd-av { width: 52px; height: 52px; border-radius: 14px; color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 1.1rem; flex-shrink: 0; }
    .cd-cab b { font-size: 1.05rem; color: #1a1a2e; display: block; line-height: 1.2; }
    body.dark-mode .cd-cab b { color: #f0f0f7; }
    .cd-cab small { color: #8a8a9d; }
    .cd-kpis { display: grid; grid-template-columns: repeat(3, 1fr); gap: .5rem; margin-bottom: .9rem; }
    .cd-kpis div { background: #faf7f4; border-radius: 10px; padding: .5rem; text-align: center; }
    .cd-kpis b { display: block; font-size: .98rem; color: #1a1a2e; }
    .cd-kpis span { font-size: .66rem; color: #8a8a9d; font-weight: 700; text-transform: uppercase; }
    body.dark-mode .cd-kpis div { background: #24243b; }
    body.dark-mode .cd-kpis b { color: #f0f0f7; }
    .cd-tit { font-size: .68rem; font-weight: 800; letter-spacing: .05em; text-transform: uppercase; color: #adb5bd; margin: .8rem 0 .35rem; }
    .cd-fila { display: flex; justify-content: space-between; gap: .6rem; font-size: .86rem; padding: .25rem 0; border-bottom: 1px dashed #eee5dd; }
    .cd-fila:last-child { border-bottom: 0; }
    .cd-contacto a { display: inline-flex; align-items: center; gap: .3rem; border-radius: 20px; padding: .2rem .65rem; font-size: .76rem; font-weight: 700; background: #fdf1ea; color: #b5451b; margin: 0 .25rem .25rem 0; }
    .cd-contacto a.wa { background: rgba(37,211,102,.14); color: #128c4a; }
    .cd-aviso { border-radius: 10px; padding: .45rem .7rem; font-size: .82rem; margin-top: .5rem; }
    .cd-aviso.nota { background: #fff8ec; color: #6c5a48; }
    .cd-aviso.mal { background: #fdecea; color: #a93226; }
    body.dark-mode .cd-aviso.nota { background: rgba(212,136,15,.12); color: #f0d6a8; }
    body.dark-mode .cd-aviso.mal { background: rgba(231,76,60,.15); color: #f1a097; }
</style>

<div class="cd-cab">
    <div class="cd-av" style="background: {{ $activo ? $c->color_tipo : '#adb5bd' }}">@if($c->esGeneral())<i class="fas fa-store-alt"></i>@else{{ $c->getIniciales() }}@endif</div>
    <div style="min-width:0">
        <b>{{ $c->nombre }}</b>
        <small><i class="fas {{ $c->icono }} mr-1"></i>{{ $c->tipo_label }}{{ $c->distrito ? ' · ' . $c->distrito : '' }}{{ $c->documento ? ' · ' . $c->documento : '' }}{{ $activo ? '' : ' · inactivo' }}</small>
    </div>
</div>

<div class="cd-kpis">
    <div><b>S/ {{ number_format($resumen['total'], 0) }}</b><span>Total</span></div>
    <div><b>{{ $resumen['compras'] }}</b><span>Compras</span></div>
    <div><b>{{ $resumen['ultima'] ? ($resumen['dias_sin'] === 0 ? 'Hoy' : 'Hace ' . $resumen['dias_sin'] . ' d') : '—' }}</b><span>Última</span></div>
</div>

@if($c->contacto || $c->telefono || $c->email)
    <div class="cd-contacto">
        @if($c->contacto)<div class="small mb-1"><i class="fas fa-user mr-1 text-muted"></i>{{ $c->contacto }}</div>@endif
        @if($c->whatsapp)<a class="wa" href="https://wa.me/{{ $c->whatsapp }}" target="_blank" rel="noopener"><i class="fab fa-whatsapp"></i>WhatsApp</a>@endif
        @if($c->telefono)<a href="tel:{{ preg_replace('/[^\d+]/', '', $c->telefono) }}"><i class="fas fa-phone"></i>{{ $c->telefono }}</a>@endif
        @if($c->email)<a href="mailto:{{ $c->email }}"><i class="fas fa-envelope"></i>Correo</a>@endif
    </div>
@endif
@if($c->notas)<div class="cd-aviso nota"><i class="fas fa-sticky-note mr-1"></i>{{ $c->notas }}</div>@endif
@if($resumen['recuperar'] && $activo)<div class="cd-aviso mal"><i class="fas fa-user-clock mr-1"></i>Hace {{ $resumen['dias_sin'] }} días que no compra.</div>@endif

@if($productos->count())
    <div class="cd-tit">Lo que más compra</div>
    @foreach($productos->take(4) as $p)
        <div class="cd-fila"><span>{{ (int) $p->cantidad }} × {{ $p->producto->nombre ?? 'Producto' }}</span><b>S/ {{ number_format($p->total, 2) }}</b></div>
    @endforeach
@endif

@if($ventas->count())
    <div class="cd-tit">Últimas compras</div>
    @foreach($ventas->take(5) as $v)
        <div class="cd-fila"><span>{{ $v->fecha_venta->format('d/m H:i') }} · {{ $v->numero_venta }}{{ $v->estado === 'anulada' ? ' · anulada' : '' }}</span><b style="{{ $v->estado === 'anulada' ? 'text-decoration:line-through;opacity:.6' : '' }}">S/ {{ number_format($v->total, 2) }}</b></div>
    @endforeach
@endif

<div class="d-flex flex-wrap mt-3" style="gap:.4rem">
    <a href="{{ route('clientes.show', $c) }}" class="btn btn-primary btn-sm"><i class="fas fa-external-link-alt mr-1"></i>Abrir ficha</a>
    <a href="{{ route('clientes.edit', $c) }}" class="btn btn-light btn-sm"><i class="fas fa-edit mr-1"></i>Editar</a>
</div>
