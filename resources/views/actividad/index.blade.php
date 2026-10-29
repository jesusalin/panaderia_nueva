@extends('layouts.app')
@section('title', 'Historial de actividad')
@section('breadcrumb') <li class="breadcrumb-item active">Historial de actividad</li> @endsection

@push('styles')
<style>
    .act-aviso { display: flex; align-items: center; gap: .8rem; background: #fde8e6; border: 1px solid #f7c9c4; color: #9b2c22; border-radius: 12px; padding: .8rem 1.1rem; margin-bottom: 1rem; font-size: .88rem; }
    .act-aviso a { color: #9b2c22; font-weight: 800; text-decoration: underline; }
    .act-aviso-cerrar { margin-left: auto; background: none; border: none; color: #9b2c22; font-size: 1.4rem; line-height: 1; padding: 0 .25rem; cursor: pointer; opacity: .6; }
    .act-aviso-cerrar:hover { opacity: 1; }

    .act-ficha { background: #fff; border-radius: 14px; box-shadow: 0 2px 12px rgba(0,0,0,.05); padding: 1.1rem 1.3rem; margin-bottom: 1.2rem; display: flex; flex-wrap: wrap; align-items: center; gap: 1.2rem; }
    .act-ficha .af-avatar { width: 52px; height: 52px; border-radius: 50%; background: linear-gradient(135deg, #1a1a2e, #b5451b); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 1.3rem; }
    .act-ficha .af-nombre { font-weight: 800; color: #1a1a2e; font-size: 1.1rem; }
    .act-ficha .af-sub { font-size: .82rem; color: #8a8a9d; }
    .act-ficha .af-datos { display: flex; gap: 1.5rem; margin-left: auto; flex-wrap: wrap; }
    .act-ficha .af-dato b { display: block; font-size: 1.3rem; font-weight: 800; color: #1a1a2e; line-height: 1.1; }
    .act-ficha .af-dato small { font-size: .72rem; color: #8a8a9d; font-weight: 700; text-transform: uppercase; }

    .act-dia { font-size: .78rem; font-weight: 800; color: #8a8a9d; text-transform: uppercase; letter-spacing: .05em; margin: 1.2rem 0 .5rem; }
    .act-dia:first-child { margin-top: 0; }
    .act-lista { background: #fff; border-radius: 14px; box-shadow: 0 2px 12px rgba(0,0,0,.05); overflow: hidden; }
    .act-fila { display: grid; grid-template-columns: 58px 34px minmax(140px, 220px) 1fr auto; gap: .8rem; align-items: center; padding: .65rem 1.1rem; border-bottom: 1px solid #f3f0ec; font-size: .87rem; }
    .act-fila:last-child { border-bottom: none; }
    .act-hora { color: #8a8a9d; font-variant-numeric: tabular-nums; font-weight: 700; }
    .act-ico { width: 30px; height: 30px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: .78rem; }
    .act-ico.info { background: rgba(52,152,219,.14); color: #2170a3; }
    .act-ico.secondary { background: #f1f1f4; color: #6c757d; }
    .act-ico.danger { background: rgba(231,76,60,.13); color: #b3261e; }
    .act-ico.success { background: rgba(46,204,113,.15); color: #1e8e5a; }
    .act-ico.warning { background: rgba(243,156,18,.16); color: #a5680d; }
    .act-ico.primary { background: rgba(181,69,27,.12); color: #b5451b; }
    .act-quien a { font-weight: 700; color: #1a1a2e; }
    .act-quien small { display: block; color: #adb5bd; font-size: .72rem; }
    .act-desc { color: #343a40; }
    .act-fila.fallido .act-desc { color: #b3261e; font-weight: 600; }
    .act-meta { text-align: right; white-space: nowrap; }
    .act-meta .badge-soft { font-size: .68rem; }
    .act-meta small { display: block; color: #adb5bd; font-size: .68rem; margin-top: .15rem; }
    @media (max-width: 768px) {
        .act-fila { grid-template-columns: 50px 30px 1fr; }
        .act-quien { grid-column: 3; } .act-desc { grid-column: 1 / -1; } .act-meta { grid-column: 1 / -1; text-align: left; }
    }
    body.dark-mode .act-ficha, body.dark-mode .act-lista { background: #1f1f33; }
    body.dark-mode .act-ficha .af-nombre, body.dark-mode .act-ficha .af-dato b, body.dark-mode .act-quien a { color: #f0f0f7; }
    body.dark-mode .act-desc { color: #d5d5e2; }
    body.dark-mode .act-fila { border-bottom-color: #33334d; }
</style>
@endpush

@section('content')
@php $acciones = \App\Models\ActividadUsuario::ACCIONES; @endphp

<div class="page-toolbar">
    <div>
        <h2><i class="fas fa-history mr-2 text-primary"></i>Historial de actividad</h2>
        <p>Quién hizo qué y cuándo: inicios de sesión, registros, ediciones y eliminaciones</p>
    </div>
    <div class="toolbar-actions">
        <a href="{{ route('usuarios.index') }}" class="btn btn-outline-secondary"><i class="fas fa-users mr-1"></i>Usuarios</a>
    </div>
</div>

@if($fallidosSemana > 0 && request('accion') !== 'login_fallido')
<div class="act-aviso" id="avisoFallidos">
    <i class="fas fa-user-lock"></i>
    <span>Hubo <strong>{{ $fallidosSemana }}</strong> intento(s) de ingreso fallido en los últimos 7 días.
        <a href="{{ route('actividad.index', ['accion' => 'login_fallido', 'desde' => now()->subDays(7)->toDateString()]) }}">Ver cuáles</a></span>
    <button type="button" class="act-aviso-cerrar" aria-label="Cerrar aviso" title="Cerrar">&times;</button>
</div>
<script>
    // La X solo lo oculta en esta visita: cada vez que se entra al historial vuelve a aparecer
    document.querySelector('#avisoFallidos .act-aviso-cerrar').addEventListener('click', function () {
        document.getElementById('avisoFallidos').style.display = 'none';
    });
</script>
@endif

@if($usuarioSel)
@php $estadoSel = $usuarioSel->estadoActividad(); @endphp
<div class="act-ficha">
    <div class="af-avatar">{{ strtoupper(mb_substr($usuarioSel->nombre, 0, 1)) }}</div>
    <div>
        <div class="af-nombre">{{ $usuarioSel->nombre }} <span class="badge-soft badge-soft-secondary">{{ '@' . $usuarioSel->usuario }}</span></div>
        <div class="af-sub">
            {{ ucfirst($usuarioSel->rol->nombre ?? '') }} · Cuenta {{ $usuarioSel->estado === 'activo' ? 'activa' : 'desactivada' }} ·
            @if($usuarioSel->ultimo_acceso)
                Último acceso: <strong>{{ $usuarioSel->ultimo_acceso->format('d/m/Y H:i') }}</strong> ({{ $estadoSel['texto'] }})
            @else
                Nunca ingresó
            @endif
        </div>
    </div>
    <div class="af-datos">
        <div class="af-dato"><b>{{ $resumenUsuario['sesiones30'] }}</b><small>Ingresos (30 días)</small></div>
        <div class="af-dato"><b>{{ $resumenUsuario['acciones30'] }}</b><small>Acciones (30 días)</small></div>
        <div class="af-dato"><b class="{{ $resumenUsuario['fallidos30'] ? 'text-danger' : '' }}">{{ $resumenUsuario['fallidos30'] }}</b><small>Intentos fallidos</small></div>
    </div>
</div>
@endif

<form method="GET" action="{{ route('actividad.index') }}" class="filter-bar flex-wrap" style="gap:.6rem">
    <div class="search-box flex-grow-1" style="min-width:200px;">
        <i class="fas fa-search"></i>
        <input type="text" name="buscar" class="form-control" placeholder="Buscar en la descripción (ej. harina, V-000120)…" value="{{ request('buscar') }}">
    </div>
    <select name="usuario" class="form-control" style="width:auto;" onchange="this.form.submit()" aria-label="Usuario">
        <option value="">Todos los usuarios</option>
        @foreach($usuarios as $us)
            <option value="{{ $us->id }}" {{ request('usuario') == $us->id ? 'selected' : '' }}>{{ $us->nombre }}</option>
        @endforeach
    </select>
    <select name="accion" class="form-control" style="width:auto;" onchange="this.form.submit()" aria-label="Tipo de acción">
        <option value="">Todas las acciones</option>
        @foreach($acciones as $clave => [$etiqueta])
            <option value="{{ $clave }}" {{ request('accion') === $clave ? 'selected' : '' }}>{{ $etiqueta }}</option>
        @endforeach
    </select>
    <select name="modulo" class="form-control" style="width:auto;" onchange="this.form.submit()" aria-label="Módulo">
        <option value="">Todos los módulos</option>
        @foreach($modulos as $mod)
            <option value="{{ $mod }}" {{ request('modulo') === $mod ? 'selected' : '' }}>{{ $mod }}</option>
        @endforeach
    </select>
    <input type="date" name="desde" class="form-control" style="width:auto;" value="{{ request('desde') }}" aria-label="Desde">
    <input type="date" name="hasta" class="form-control" style="width:auto;" value="{{ request('hasta') }}" aria-label="Hasta">
    <button type="submit" class="btn btn-light"><i class="fas fa-filter mr-1"></i>Filtrar</button>
    @if(request()->hasAny(['buscar', 'usuario', 'accion', 'modulo', 'desde', 'hasta']))
        <a href="{{ route('actividad.index') }}" class="btn btn-light text-muted"><i class="fas fa-times mr-1"></i>Limpiar</a>
    @endif
</form>

@if($actividades->count())
    @foreach($actividades->groupBy(fn ($a) => $a->created_at->toDateString()) as $fecha => $delDia)
        @php $f = \Carbon\Carbon::parse($fecha); @endphp
        <div class="act-dia">
            {{ $f->isToday() ? 'Hoy' : ($f->isYesterday() ? 'Ayer' : '') }}
            {{ $f->locale('es')->translatedFormat($f->isToday() || $f->isYesterday() ? '· d \d\e F' : 'l d \d\e F \d\e Y') }}
        </div>
        <div class="act-lista">
            @foreach($delDia as $a)
                @php [$etiqueta, $icono, $color] = $acciones[$a->accion] ?? $acciones['otra']; @endphp
                <div class="act-fila {{ $a->accion === 'login_fallido' ? 'fallido' : '' }}">
                    <span class="act-hora">{{ $a->created_at->format('H:i') }}</span>
                    <span class="act-ico {{ $color }}" title="{{ $etiqueta }}"><i class="fas {{ $icono }}"></i></span>
                    <span class="act-quien">
                        @if($a->usuario)
                            <a href="{{ route('actividad.index', ['usuario' => $a->id_usuario]) }}">{{ $a->usuario->nombre }}</a>
                            <small>{{ $a->usuario->apodo ?? ('@' . $a->usuario->usuario) }}</small>
                        @else
                            <span class="text-muted">{{ $a->usuario_texto ?? 'Desconocido' }}</span>
                        @endif
                    </span>
                    <span class="act-desc">{{ $a->descripcion }}</span>
                    <span class="act-meta">
                        @if($a->modulo)<span class="badge-soft badge-soft-secondary">{{ $a->modulo }}</span>@endif
                        @if($a->ip)<small>IP {{ $a->ip }}</small>@endif
                    </span>
                </div>
            @endforeach
        </div>
    @endforeach
    <div class="mt-3">{{ $actividades->links() }}</div>
@else
    <div class="empty-state">
        <i class="fas fa-history"></i>
        <p>{{ request()->hasAny(['buscar', 'usuario', 'accion', 'modulo', 'desde', 'hasta']) ? 'No hay actividad con esos filtros' : 'Todavía no hay actividad registrada' }}</p>
    </div>
@endif
@endsection
