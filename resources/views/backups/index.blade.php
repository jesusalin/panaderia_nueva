@extends('layouts.app')
@section('title', 'Respaldos')
@section('breadcrumb') <li class="breadcrumb-item active">Respaldos</li> @endsection

@include('partials.reportes-estilos')

@push('styles')
<style>
    .bk-acciones { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1rem; margin-bottom: 1rem; }
    @media (max-width: 991px) { .bk-acciones { grid-template-columns: minmax(0, 1fr); } }
    .bk-accion { background: #fff; border-radius: 14px; box-shadow: 0 2px 12px rgba(0,0,0,.05); padding: 1rem 1.2rem; }
    body.dark-mode .bk-accion { background: #1f1f33; }
    .bk-accion h5 { font-size: .98rem; font-weight: 800; margin: 0 0 .25rem; display: flex; align-items: center; gap: .5rem; }
    .bk-accion h5 i { width: 30px; height: 30px; border-radius: 9px; background: rgba(181,69,27,.1); color: #b5451b; display: inline-flex; align-items: center; justify-content: center; font-size: .85rem; }
    .bk-accion p { font-size: .8rem; color: #8a8a9d; margin: 0 0 .6rem; }
    .bk-accion form { display: flex; gap: .5rem; flex-wrap: wrap; }
    .bk-accion form .form-control { flex: 1; min-width: 180px; border-radius: 10px; }

    .bk-ahora { display: flex; flex-wrap: wrap; gap: .4rem; margin-bottom: 1rem; font-size: .8rem; color: #8a8a9d; align-items: center; }
    .bk-ahora span.c { background: #fff; border-radius: 20px; padding: .2rem .7rem; box-shadow: 0 1px 4px rgba(0,0,0,.05); color: #555; }
    .bk-ahora span.c b { color: #1a1a2e; }
    body.dark-mode .bk-ahora span.c { background: #1f1f33; color: #ccc; }
    body.dark-mode .bk-ahora span.c b { color: #fff; }

    .bk-dia { display: flex; justify-content: space-between; margin: 1.1rem 0 .45rem; font-size: .76rem; font-weight: 800; text-transform: uppercase; letter-spacing: .05em; color: #8a8a9d; }
    .bk-fila { display: grid; grid-template-columns: 42px minmax(0, 1fr) auto; gap: .9rem; align-items: center; background: #fff; border-radius: 12px; padding: .75rem 1rem;
        box-shadow: 0 1px 6px rgba(0,0,0,.05); border-left: 4px solid var(--tc, #2170a3); }
    .bk-fila + .bk-fila { margin-top: .45rem; }
    body.dark-mode .bk-fila { background: #1f1f33; }
    .bk-ic { width: 42px; height: 42px; border-radius: 11px; display: flex; align-items: center; justify-content: center; color: #fff; background: var(--tc, #2170a3); }
    .bk-tit { font-weight: 800; color: #1a1a2e; font-size: .93rem; }
    body.dark-mode .bk-tit { color: #f0f0f7; }
    .bk-tit small { font-weight: 600; color: #8a8a9d; }
    .bk-sub { font-size: .76rem; color: #8a8a9d; margin-top: .1rem; }
    .bk-nota { font-size: .8rem; color: #6c5a48; background: #fff8ec; border-radius: 8px; padding: .2rem .55rem; display: inline-block; margin-top: .3rem; }
    body.dark-mode .bk-nota { background: rgba(212,136,15,.12); color: #f0d6a8; }
    .bk-tag { display: inline-flex; align-items: center; gap: .25rem; font-size: .68rem; font-weight: 800; border-radius: 20px; padding: .08rem .5rem; margin-left: .25rem; vertical-align: middle; }
    .bk-tag.prot { background: #eef4fb; color: #2170a3; }
    .bk-tag.ok { background: #e9f8ef; color: #1e8e5a; }
    .bk-tag.mal { background: #fdecea; color: #c0392b; }
    .bk-tag.rest { background: #fff4e0; color: #b9770e; }
    .bk-btns { display: flex; gap: .3rem; flex-wrap: wrap; justify-content: flex-end; }
    .bk-btns form { display: inline; }
    .bk-nota-form { display: none; margin-top: .4rem; gap: .4rem; }
    .bk-nota-form.ver { display: flex; }
    .bk-nota-form input { max-width: 340px; border-radius: 8px; }
    @media (max-width: 767px) {
        .bk-fila { grid-template-columns: 38px minmax(0, 1fr); }
        .bk-btns { grid-column: 1 / -1; justify-content: flex-start; }
    }
    .bk-comparar { display: grid; grid-template-columns: auto 1fr 1fr; gap: .2rem .8rem; font-size: .84rem; margin: .6rem 0; }
    .bk-comparar b { text-align: right; }
    .bk-comparar .cab { font-size: .68rem; text-transform: uppercase; font-weight: 800; color: #8a8a9d; text-align: right; }
    .bk-comparar .menos { color: #c0392b; }
    #confirmarRestaurar { font-weight: 800; letter-spacing: .08em; text-transform: uppercase; }
</style>
@endpush

@section('content')
@php
    $T = \App\Support\Respaldos::TIPOS;
    $ult = $stats['ultimo'];
    $colorSalud = ['bien' => '#1e8e5a', 'atencion' => '#d4880f', 'mal' => '#c0392b'][$stats['salud']];
    $ahora = $stats['ahora'];
    $etiquetas = ['ventas' => 'ventas', 'compras' => 'compras', 'producciones' => 'producciones', 'productos' => 'productos', 'insumos' => 'insumos', 'clientes' => 'clientes', 'usuarios' => 'usuarios'];
@endphp

<div class="page-toolbar">
    <div>
        <h2><i class="fas fa-database mr-2 text-info"></i>Respaldos</h2>
        <p>Copias de seguridad de todos los datos del sistema, para no perder nada</p>
    </div>
</div>

<div class="inv-hero" style="--acento: {{ $colorSalud }}">
    <div class="ih-icono"><i class="fas {{ $stats['salud'] === 'bien' ? 'fa-shield-alt' : 'fa-exclamation-triangle' }}"></i></div>
    <div>
        <small>Último respaldo</small>
        <h3>{{ $ult ? ($stats['horas'] < 1 ? 'Hace minutos' : 'Hace ' . ($stats['horas'] < 48 ? $stats['horas'] . ' h' : intdiv($stats['horas'], 24) . ' días')) : 'Ninguno todavía' }}</h3>
        @if($ult)<small style="text-transform:none;letter-spacing:0;opacity:.85">{{ $ult['fecha']->format('d/m/Y H:i') }} · {{ $T[$ult['tipo']]['texto'] }}{{ $ult['usuario'] ? ' · ' . $ult['usuario'] : '' }}</small>@endif
    </div>
    <div class="ih-datos">
        <div><b>{{ $stats['total'] }}</b><span>respaldos · {{ $stats['espacio'] }}</span></div>
        <div><b>{{ $stats['seConservan'] }}</b><span>se conservan (los más viejos se borran solos)</span></div>
        <div><b>{{ $stats['protegidos'] }}</b><span>protegido(s) · nunca se borran</span></div>
    </div>
</div>

@if(!$stats['auto_ok'])
    <div class="alert alert-warning alert-dismissible fade show" id="avisoAutomatico" role="alert">
        <button type="button" class="close" data-dismiss="alert" aria-label="Cerrar aviso" title="Cerrar (vuelve a salir en 7 días si sigue sin hacerse)"><span aria-hidden="true">&times;</span></button>
        <i class="fas fa-robot mr-1"></i><b>El respaldo automático no se está haciendo.</b>
        {{ $stats['ultimo_auto'] ? 'El último automático fue el ' . $stats['ultimo_auto']['fecha']->format('d/m/Y H:i') . '.' : 'Todavía no hay ningún respaldo automático.' }}
        Para que se haga solo todos los días a las 3:00 a.m., el programador de Laravel tiene que estar corriendo:
        en la computadora del sistema deja abierta una ventana con <code>php artisan schedule:work</code>,
        o en Windows crea una tarea programada que ejecute <code>php artisan schedule:run</code> cada minuto.
        Mientras tanto, genera uno a mano con el botón de abajo.
    </div>
@endif

<div class="bk-acciones">
    <div class="bk-accion">
        <h5><i class="fas fa-plus"></i>Hacer un respaldo ahora</h5>
        <p>Útil antes de cambios grandes, de un conteo físico o de tu sustentación. La nota ayuda a reconocerlo después.</p>
        <form action="{{ route('backups.store') }}" method="POST" class="js-cargando">
            @csrf
            <input type="text" name="nota" maxlength="150" class="form-control" placeholder="Nota (opcional): ej. antes del inventario de fin de mes">
            <button type="submit" class="btn btn-primary"><i class="fas fa-play mr-1"></i>Respaldar</button>
        </form>
    </div>
    <div class="bk-accion">
        <h5><i class="fas fa-cloud-arrow-up"></i>Subir un respaldo</h5>
        <p>Un archivo descargado antes ({{ $stats['motorActivo'] === 'sqlite' ? '.zip' : '.sql.gz' }}), por ejemplo para pasar los datos a otra computadora. Se revisa antes de guardarlo.</p>
        <form action="{{ route('backups.subir') }}" method="POST" enctype="multipart/form-data" class="js-cargando">
            @csrf
            <input type="file" name="archivo" accept=".zip,.gz" class="form-control @error('archivo') is-invalid @enderror" required style="padding:.3rem .5rem">
            <button type="submit" class="btn btn-outline-primary"><i class="fas fa-upload mr-1"></i>Subir</button>
            @error('archivo')<div class="invalid-feedback d-block w-100">{{ $message }}</div>@enderror
        </form>
    </div>
</div>

<div class="bk-ahora">
    <span><i class="fas fa-database mr-1"></i>Ahora en el sistema ({{ strtoupper($stats['motorActivo']) }}):</span>
    @foreach($etiquetas as $k => $t)
        @if($ahora[$k] !== null)<span class="c"><b>{{ $ahora[$k] }}</b> {{ $t }}</span>@endif
    @endforeach
</div>

<div class="inv-chips">
    <a href="{{ route('backups.index') }}" class="inv-chip {{ $filtro ? '' : 'activo' }}">Todos ({{ $stats['total'] }})</a>
    @foreach($T as $clave => $t)
        @if($stats['porTipo'][$clave] ?? 0)
            <a href="{{ route('backups.index', ['tipo' => $clave]) }}" class="inv-chip {{ $filtro === $clave ? 'activo' : '' }}"><i class="fas {{ $t['icono'] }} mr-1"></i>{{ $t['texto'] }} ({{ $stats['porTipo'][$clave] }})</a>
        @endif
    @endforeach
</div>

@forelse($porDia as $fecha => $delDia)
    @php $f = \Carbon\Carbon::parse($fecha); @endphp
    <div class="bk-dia"><span><i class="far fa-calendar-alt mr-1"></i>{{ $f->isToday() ? 'Hoy · ' : ($f->isYesterday() ? 'Ayer · ' : '') }}{{ $f->locale('es')->translatedFormat('l d \d\e F') }}</span><span>{{ $delDia->count() }} respaldo(s)</span></div>
    @foreach($delDia as $b)
        @php
            $t = $T[$b['tipo']] ?? $T['manual'];
            $r = $b['resumen'];
            $otroMotor = $b['motor'] !== $stats['motorActivo'];
        @endphp
        <div class="bk-fila" style="--tc: {{ $t['color'] }}">
            <div class="bk-ic" title="{{ $t['texto'] }}"><i class="fas {{ $t['icono'] }}"></i></div>
            <div style="min-width:0">
                <div class="bk-tit">
                    {{ $b['fecha']->format('H:i') }} · {{ $t['texto'] }} <small>{{ $b['usuario'] ? '· ' . $b['usuario'] : '' }} · {{ $b['tamano'] }} · {{ strtoupper($b['motor']) }}</small>
                    @if($b['protegido'])<span class="bk-tag prot"><i class="fas fa-lock"></i>Protegido</span>@endif
                    @if($b['verificado'])<span class="bk-tag {{ $b['verificado']['ok'] ? 'ok' : 'mal' }}" title="{{ $b['verificado']['mensaje'] }} ({{ $b['verificado']['fecha'] }})"><i class="fas {{ $b['verificado']['ok'] ? 'fa-check' : 'fa-times' }}"></i>{{ $b['verificado']['ok'] ? 'Verificado' : 'Dañado' }}</span>@endif
                    @if($b['restaurado'])<span class="bk-tag rest" title="Restaurado el {{ $b['restaurado']['fecha'] }}{{ $b['restaurado']['usuario'] ? ' por ' . $b['restaurado']['usuario'] : '' }}"><i class="fas fa-clock-rotate-left"></i>Se restauró</span>@endif
                </div>
                <div class="bk-sub">
                    @if($r)
                        {{ $r['ventas'] ?? '?' }} ventas · {{ $r['compras'] ?? '?' }} compras · {{ $r['productos'] ?? '?' }} productos · {{ $r['insumos'] ?? '?' }} insumos · {{ $r['clientes'] ?? '?' }} clientes
                        @if(!empty($r['ultima_venta'])) · última venta {{ \Carbon\Carbon::parse($r['ultima_venta'])->format('d/m H:i') }}@endif
                    @else
                        <span title="{{ $b['nombre'] }}">{{ $b['nombre'] }}</span>
                    @endif
                </div>
                @if($b['nota'])<div class="bk-nota"><i class="fas fa-sticky-note mr-1"></i>{{ $b['nota'] }}</div>@endif
                <form action="{{ route('backups.nota', $b['nombre']) }}" method="POST" class="bk-nota-form" id="nota-{{ $loop->parent->index }}-{{ $loop->index }}">
                    @csrf
                    <input type="text" name="nota" maxlength="150" class="form-control form-control-sm" value="{{ $b['nota'] }}" placeholder="Nota para reconocerlo">
                    <button class="btn btn-sm btn-primary">Guardar</button>
                </form>
            </div>
            <div class="bk-btns">
                <a href="{{ route('backups.download', $b['nombre']) }}" class="btn btn-icon btn-info" title="Descargar (guárdalo también en un USB o en la nube)"><i class="fas fa-download"></i></a>
                <form action="{{ route('backups.verificar', $b['nombre']) }}" method="POST">@csrf<button class="btn btn-icon btn-light" title="Verificar que el archivo esté sano"><i class="fas fa-stethoscope"></i></button></form>
                <button type="button" class="btn btn-icon btn-light js-nota" data-form="nota-{{ $loop->parent->index }}-{{ $loop->index }}" title="Escribir una nota"><i class="fas fa-pen"></i></button>
                <form action="{{ route('backups.proteger', $b['nombre']) }}" method="POST">@csrf<button class="btn btn-icon {{ $b['protegido'] ? 'btn-primary' : 'btn-light' }}" title="{{ $b['protegido'] ? 'Quitar protección' : 'Proteger: no se borra en la limpieza automática' }}"><i class="fas {{ $b['protegido'] ? 'fa-lock' : 'fa-lock-open' }}"></i></button></form>
                @if($otroMotor)
                    <button type="button" class="btn btn-icon btn-secondary is-locked js-blocked" data-blocked-title="Es de otro motor" data-blocked-message="Este respaldo es de {{ strtoupper($b['motor']) }} y el sistema usa {{ strtoupper($stats['motorActivo']) }}: no se puede restaurar aquí."><i class="fas fa-clock-rotate-left"></i></button>
                @else
                    <button type="button" class="btn btn-icon btn-warning js-restaurar" title="Restaurar este respaldo"
                        data-url="{{ route('backups.restore', $b['nombre']) }}" data-fecha="{{ $b['fecha']->format('d/m/Y H:i') }}" data-tipo="{{ $t['texto'] }}"
                        data-resumen='@json($r)'><i class="fas fa-clock-rotate-left"></i></button>
                @endif
                @if($b['protegido'])
                    <button type="button" class="btn btn-icon btn-secondary is-locked js-blocked" data-blocked-title="Respaldo protegido" data-blocked-message="Quítale la protección (candado) para poder eliminarlo."><i class="fas fa-trash-alt"></i></button>
                @else
                    <form action="{{ route('backups.destroy', $b['nombre']) }}" method="POST" class="js-confirm" data-confirm-title="¿Eliminar este respaldo?" data-confirm="Se eliminará el respaldo del {{ $b['fecha']->format('d/m/Y H:i') }} para siempre.">
                        @csrf @method('DELETE')
                        <button class="btn btn-icon btn-danger" title="Eliminar"><i class="fas fa-trash-alt"></i></button>
                    </form>
                @endif
            </div>
        </div>
    @endforeach
@empty
    <div class="empty-state">
        <i class="fas fa-database"></i>
        <p>{{ $filtro ? 'No hay respaldos de este tipo.' : 'Todavía no hay respaldos. Haz el primero con el botón "Respaldar".' }}</p>
    </div>
@endforelse

<p class="small text-muted mt-3"><i class="fas fa-lightbulb mr-1"></i>Consejo: de vez en cuando descarga un respaldo y guárdalo fuera de esta computadora (USB, Google Drive). Si la computadora se daña, los respaldos de aquí también se pierden.</p>

{{-- Restaurar: hay que escribir RESTAURAR --}}
<div class="modal fade" id="modalRestaurar" tabindex="-1" role="dialog" aria-labelledby="tituloRestaurar" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <form class="modal-content" method="POST" id="formRestaurar">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title" id="tituloRestaurar"><i class="fas fa-clock-rotate-left text-warning mr-1"></i>Restaurar el respaldo del <span id="rFecha"></span></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <p class="mb-2"><b>Todos los datos de ahora se reemplazan</b> por los de ese respaldo. Lo registrado después de esa fecha (ventas, compras, producción…) desaparece.</p>
                <div class="bk-comparar" id="rComparar"></div>
                <div class="alert alert-info small py-2 mb-2"><i class="fas fa-life-ring mr-1"></i>Antes de restaurar se guarda solo un respaldo <b>"Antes de restaurar"</b>: si te equivocas, restauras ese y vuelves a como estaba.</div>
                <label for="confirmarRestaurar" class="small font-weight-bold">Para confirmar, escribe <b>{{ \App\Http\Controllers\BackupController::PALABRA }}</b></label>
                <input type="text" name="confirmar" id="confirmarRestaurar" class="form-control" autocomplete="off" placeholder="{{ \App\Http\Controllers\BackupController::PALABRA }}">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-warning" id="btnRestaurar" disabled><i class="fas fa-clock-rotate-left mr-1"></i>Restaurar</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    // Aviso del respaldo automático: al cerrarlo con la X no vuelve a salir por 7 días
    const aviso = document.getElementById('avisoAutomatico'), CLAVE = 'avisoRespaldoAutoCerrado';
    if (aviso) {
        try { if (Date.now() - Number(localStorage.getItem(CLAVE) || 0) < 7 * 864e5) aviso.remove(); } catch (e) {}
        const x = aviso.querySelector('.close');
        if (x) x.addEventListener('click', function () { try { localStorage.setItem(CLAVE, String(Date.now())); } catch (e) {} });
    }
    const AHORA = @json($ahora), PALABRA = @json(\App\Http\Controllers\BackupController::PALABRA);
    const NOMBRES = { ventas: 'Ventas', compras: 'Compras', producciones: 'Producciones', productos: 'Productos', insumos: 'Insumos', clientes: 'Clientes', usuarios: 'Usuarios' };
    document.querySelectorAll('.js-restaurar').forEach(function (b) {
        b.addEventListener('click', function () {
            const r = JSON.parse(b.dataset.resumen || 'null');
            document.getElementById('formRestaurar').action = b.dataset.url;
            document.getElementById('rFecha').textContent = b.dataset.fecha;
            document.getElementById('rComparar').innerHTML = r
                ? '<span></span><span class="cab">Ahora</span><span class="cab">En el respaldo</span>' + Object.keys(NOMBRES).filter(k => AHORA[k] !== null).map(function (k) {
                    const menos = r[k] !== null && r[k] !== undefined && r[k] < AHORA[k];
                    return '<span>' + NOMBRES[k] + '</span><b>' + AHORA[k] + '</b><b class="' + (menos ? 'menos' : '') + '">' + (r[k] ?? '?') + (menos ? ' (−' + (AHORA[k] - r[k]) + ')' : '') + '</b>';
                }).join('')
                : '<span class="text-muted" style="grid-column:1/-1">Respaldo antiguo: no se sabe cuántos datos tiene.</span>';
            document.getElementById('confirmarRestaurar').value = '';
            document.getElementById('btnRestaurar').disabled = true;
            if (window.$) $('#modalRestaurar').modal('show');
            setTimeout(function () { document.getElementById('confirmarRestaurar').focus(); }, 400);
        });
    });
    document.getElementById('confirmarRestaurar').addEventListener('input', function () {
        document.getElementById('btnRestaurar').disabled = this.value.trim().toUpperCase() !== PALABRA;
    });
    document.getElementById('formRestaurar').addEventListener('submit', function () {
        const b = document.getElementById('btnRestaurar'); b.disabled = true; b.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Restaurando…';
    });
    document.querySelectorAll('.js-nota').forEach(function (b) {
        b.addEventListener('click', function () { const f = document.getElementById(b.dataset.form); f.classList.toggle('ver'); if (f.classList.contains('ver')) f.querySelector('input').focus(); });
    });
    document.querySelectorAll('form.js-cargando').forEach(function (f) {
        f.addEventListener('submit', function () { const b = f.querySelector('button[type=submit]'); if (b) { b.disabled = true; b.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Un momento…'; } });
    });
})();
</script>
@endpush
