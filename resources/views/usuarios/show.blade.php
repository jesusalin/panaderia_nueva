@extends('layouts.app')
@section('title', $usuario->nombre)
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('usuarios.index') }}">Usuarios</a></li>
    <li class="breadcrumb-item active">{{ $usuario->nombre }}</li>
@endsection

@include('partials.reportes-estilos')

@push('styles')
<style>
    .us-av { width: 64px; height: 64px; border-radius: 50%; flex-shrink: 0; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 1.4rem;
        background: rgba(255,255,255,.14); border: 2px solid rgba(255,255,255,.3); overflow: hidden; position: relative; }
    .us-av img { width: 100%; height: 100%; object-fit: cover; }
    .us-punto { position: absolute; bottom: 2px; right: 2px; width: 14px; height: 14px; border-radius: 50%; border: 2px solid #1a1a2e; background: #adb5bd; }
    .us-punto.en_linea { background: #2ecc71; } .us-punto.activo { background: #2ecc71; } .us-punto.poco_activo { background: #f1c40f; } .us-punto.sin_actividad, .us-punto.nunca { background: #e67e22; }
    .us-dias { display: flex; align-items: flex-end; gap: 4px; height: 110px; margin-top: .4rem; }
    .us-dias .d { flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: flex-end; height: 100%; }
    .us-dias .d span { width: 100%; max-width: 26px; background: linear-gradient(180deg, #d9653a, #b5451b); border-radius: 4px 4px 0 0; min-height: 2px; }
    .us-dias .d.hoy span { background: linear-gradient(180deg, #34c27d, #1e8e5a); }
    .us-dias .d em { font-style: normal; font-size: .6rem; color: #8a8a9d; margin-top: .2rem; }
    .us-dias .d b { font-size: .6rem; color: #6c757d; }
    .us-trab { display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: .6rem; }
    .us-trab div { background: #faf7f4; border-radius: 10px; padding: .6rem .75rem; }
    .us-trab b { display: block; font-size: 1.15rem; color: #1a1a2e; line-height: 1.15; }
    .us-trab span { font-size: .72rem; color: #8a8a9d; }
    body.dark-mode .us-trab div { background: #24243b; }
    body.dark-mode .us-trab b { color: #f0f0f7; }
    .us-perm { margin-bottom: .7rem; }
    .us-perm h6 { font-size: .78rem; font-weight: 800; text-transform: uppercase; letter-spacing: .04em; color: #8a8a9d; margin: 0 0 .35rem; }
    .us-perm .p { display: inline-flex; align-items: center; gap: .3rem; background: #fdf1ea; color: #b5451b; border-radius: 20px; padding: .18rem .6rem; font-size: .76rem; font-weight: 700; margin: 0 .25rem .3rem 0; }
    .us-perm .p.no { background: #f3f3f5; color: #adb5bd; font-weight: 600; text-decoration: line-through; }
    body.dark-mode .us-perm .p { background: rgba(181,69,27,.2); color: #ffb38f; }
    body.dark-mode .us-perm .p.no { background: #24243b; color: #5c5c78; }
    .us-act { list-style: none; margin: 0; padding: 0; }
    .us-act li { display: grid; grid-template-columns: 30px minmax(0, 1fr) auto; gap: .6rem; align-items: center; padding: .4rem 0; border-bottom: 1px dashed #eee5dd; font-size: .84rem; }
    .us-act li:last-child { border-bottom: 0; }
    .us-act .ic { width: 30px; height: 30px; border-radius: 9px; display: flex; align-items: center; justify-content: center; font-size: .78rem; }
    .us-act .ic.info { background: #eef4fb; color: #2170a3; } .us-act .ic.secondary { background: #f3f3f5; color: #8a8a9d; } .us-act .ic.danger { background: #fdecea; color: #c0392b; }
    .us-act .ic.success { background: #e9f8ef; color: #1e8e5a; } .us-act .ic.warning { background: #fff4e0; color: #b9770e; } .us-act .ic.primary { background: #fdf1ea; color: #b5451b; }
    .us-act small { color: #8a8a9d; white-space: nowrap; }
    body.dark-mode .us-act li { border-color: #33334d; }
</style>
@endpush

@section('content')
@php
    $u = $usuario;
    $act = $u->estadoActividad();
    $yo = auth()->id() === $u->id;
    $esAdmin = $u->isAdmin();
    $mios = $u->modulosAsignados();
    $A = \App\Models\ActividadUsuario::ACCIONES;
    $maxDia = max(1, $dias->max('n'));
@endphp

<div class="inv-hero" style="--acento: {{ $u->estado === 'activo' ? ($esAdmin ? '#c0392b' : '#b5451b') : '#6c757d' }}">
    <div class="us-av">@if($u->foto_url)<img src="{{ $u->foto_url }}" alt="">@else{{ $u->iniciales }}@endif<span class="us-punto {{ $act['clave'] }}"></span></div>
    <div>
        <small>{{ $esAdmin ? 'Administrador' : 'Usuario' }} · {{ '@' . $u->usuario }}{{ $u->apodo ? ' · ' . $u->apodo : '' }}{{ $u->estado !== 'activo' ? ' · cuenta desactivada' : '' }}{{ $yo ? ' · eres tú' : '' }}</small>
        <h3>{{ $u->nombre }}</h3>
        <small style="text-transform:none;letter-spacing:0;opacity:.85">
            @if($act['clave'] === 'en_linea')<i class="fas fa-circle mr-1" style="color:#2ecc71;font-size:.55rem"></i>En línea ahora
            @elseif($u->ultimo_acceso) Último acceso: {{ $u->ultimo_acceso->format('d/m/Y H:i') }} ({{ $u->ultimo_acceso->locale('es')->diffForHumans() }})
            @else Nunca ingresó @endif
        </small>
    </div>
    <div class="ih-datos">
        <div><b>{{ $accesos['ingresos_30'] }}</b><span>ingresos en 30 días</span></div>
        <div><b>{{ $accesos['acciones_30'] }}</b><span>acciones en 30 días</span></div>
        <div><b>{{ $esAdmin ? 'Todo' : count($mios) }}</b><span>{{ $esAdmin ? 'acceso total' : 'pantalla(s) permitidas' }}</span></div>
    </div>
    <div class="ih-acciones">
        <a href="{{ route('usuarios.edit', $u) }}" class="btn btn-light btn-sm"><i class="fas fa-edit mr-1"></i>Editar</a>
        @unless($yo)
            <form action="{{ route('usuarios.clave-temporal', $u) }}" method="POST" class="d-inline js-confirm"
                  data-confirm-title="¿Darle una contraseña nueva?" data-confirm="La contraseña actual de &quot;{{ $u->nombre }}&quot; dejará de funcionar. Te mostraremos la nueva para que se la des.">
                @csrf @method('PUT')
                <button class="btn btn-outline-light btn-sm"><i class="fas fa-key mr-1"></i>Contraseña nueva</button>
            </form>
            @unless($ultimoAdmin)
                <form action="{{ route('usuarios.toggle-estado', $u) }}" method="POST" class="d-inline">
                    @csrf @method('PUT')
                    <button class="btn btn-outline-light btn-sm"><i class="fas {{ $u->estado === 'activo' ? 'fa-ban' : 'fa-check' }} mr-1"></i>{{ $u->estado === 'activo' ? 'Desactivar' : 'Activar' }}</button>
                </form>
            @endunless
        @endunless
        <a href="{{ route('actividad.index', ['usuario' => $u->id]) }}" class="btn btn-outline-light btn-sm"><i class="fas fa-history mr-1"></i>Todo su historial</a>
        <a href="{{ route('usuarios.index') }}" class="btn btn-outline-light btn-sm"><i class="fas fa-arrow-left mr-1"></i>Volver</a>
    </div>
</div>

@if($accesos['fallidos_30'])
    <div class="alert alert-warning"><i class="fas fa-user-lock mr-1"></i><b>{{ $accesos['fallidos_30'] }} intento(s) fallido(s)</b> de ingreso con esta cuenta en los últimos 30 días. Si no fue la persona, dale una contraseña nueva.</div>
@endif
@if($u->estado === 'activo' && in_array($act['clave'], ['sin_actividad', 'nunca']) && !$yo)
    <div class="alert alert-secondary"><i class="fas fa-lightbulb mr-1"></i>{{ $act['clave'] === 'nunca' ? 'Esta cuenta nunca se usó.' : 'No entra hace ' . $act['dias'] . ' días.' }} Si ya no trabaja aquí, desactívala.</div>
@endif
@if($ultimoAdmin)
    <div class="alert alert-info"><i class="fas fa-shield-alt mr-1"></i>Es el <b>único administrador activo</b>: no se puede desactivar ni quitarle el rol hasta que haya otro.</div>
@endif

<div class="inv-grid2 mb-3">
    <div class="inv-box">
        <h5><i class="fas fa-briefcase"></i>Lo que hizo este mes</h5>
        <div class="us-trab">
            <div><b>{{ $trabajo['ventas_mes'] }}</b><span>ventas · S/ {{ number_format($trabajo['vendido_mes'], 2) }}</span></div>
            <div><b>{{ $trabajo['prod_mes'] }}</b><span>producciones</span></div>
            <div><b>{{ $trabajo['compras_mes'] }}</b><span>compras registradas</span></div>
            <div><b>{{ $trabajo['movs_mes'] }}</b><span>movimientos de stock</span></div>
        </div>
        <p class="small text-muted mt-2 mb-0">En total: {{ $trabajo['ventas'] }} venta(s){{ $trabajo['anuladas'] ? ' (anuló ' . $trabajo['anuladas'] . ')' : '' }}, {{ $trabajo['producciones'] }} producción(es) y {{ $trabajo['compras'] }} compra(s).</p>
        <h5 class="mt-3"><i class="fas fa-chart-bar"></i>Acciones por día (últimos 14 días)</h5>
        <div class="us-dias">
            @foreach($dias as $d)
                <div class="d {{ $d['dia']->isToday() ? 'hoy' : '' }}" title="{{ $d['dia']->locale('es')->translatedFormat('l d/m') }}: {{ $d['n'] }} acción(es)">
                    <b>{{ $d['n'] ?: '' }}</b>
                    <span style="height: {{ round($d['n'] / $maxDia * 80) }}%"></span>
                    <em>{{ $d['dia']->format('d') }}</em>
                </div>
            @endforeach
        </div>
    </div>

    <div class="inv-box">
        <h5><i class="fas fa-key"></i>Qué puede usar</h5>
        @if($esAdmin)
            <p class="mb-0"><i class="fas fa-infinity mr-1 text-danger"></i>Es <b>administrador</b>: puede usar todo el sistema, incluidos Usuarios y el historial de actividad.</p>
        @else
            @foreach(\App\Models\Usuario::PERMISOS as $seccion => $def)
                @php
                    $pantallas = collect(\App\Models\Usuario::pantallasPlanas())->filter(fn ($p) => $p['seccion'] === $seccion);
                    $tiene = $pantallas->keys()->intersect($mios);
                @endphp
                <div class="us-perm">
                    <h6><i class="fas {{ $def['icono'] }} mr-1"></i>{{ $def['etiqueta'] }} · {{ $tiene->count() }}/{{ $pantallas->count() }}</h6>
                    @foreach($pantallas as $clave => $p)
                        <span class="p {{ in_array($clave, $mios, true) ? '' : 'no' }}" title="{{ $p['desc'] ?? '' }}">@if(in_array($clave, $mios, true))<i class="fas fa-check"></i>@endif{{ $p['padre'] ? $p['padre'] . ' › ' : '' }}{{ $p['nombre'] }}</span>
                    @endforeach
                </div>
            @endforeach
            @if(!$mios)<p class="text-muted small mb-0">Sin permisos: al entrar solo ve el Dashboard.</p>@endif
        @endif
        <p class="small text-muted mt-2 mb-0"><i class="far fa-envelope mr-1"></i>{{ $u->email }} · cuenta creada el {{ $u->created_at?->format('d/m/Y') ?? '—' }}{{ $accesos['ultimo_ip'] ? ' · último ingreso desde ' . $accesos['ultimo_ip'] : '' }}</p>
    </div>
</div>

<div class="inv-box">
    <h5><i class="fas fa-history"></i>Últimas acciones</h5>
    @if($actividad->count())
        <ul class="us-act">
            @foreach($actividad as $a)
                @php $info = $A[$a->accion] ?? $A['otra']; @endphp
                <li>
                    <span class="ic {{ $info[2] }}" title="{{ $info[0] }}"><i class="fas {{ $info[1] }}"></i></span>
                    <span style="min-width:0">{{ $a->descripcion }}@if($a->modulo)<span class="text-muted"> · {{ $a->modulo }}</span>@endif</span>
                    <small title="{{ $a->created_at->format('d/m/Y H:i:s') }}">{{ $a->created_at->isToday() ? 'hoy ' . $a->created_at->format('H:i') : $a->created_at->format('d/m H:i') }}</small>
                </li>
            @endforeach
        </ul>
        <a href="{{ route('actividad.index', ['usuario' => $u->id]) }}" class="small d-inline-block mt-2">Ver todo su historial →</a>
    @else
        <p class="text-muted mb-0">Todavía no hay acciones registradas.</p>
    @endif
</div>
@endsection
