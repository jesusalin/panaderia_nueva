@extends('layouts.app')
@section('title', 'Respaldos')
@section('breadcrumb') <li class="breadcrumb-item active">Respaldos</li> @endsection

@push('styles')
<style>
    /* ── Aviso "¿Cómo funciona?" (mismo patrón que Tiempos por Operación / Órdenes Automáticas) ── */
    .bk-info-wrap { position: relative; display: inline-block; }
    .bk-info-btn {
        display: inline-flex; align-items: center; gap: .4rem; background: rgba(52,152,219,.12); color: #2f7fb0;
        border: none; border-radius: 20px; padding: .4rem .9rem; font-weight: 700; font-size: .8rem; cursor: pointer;
    }
    .bk-info-btn:hover { background: rgba(52,152,219,.22); }
    body.dark-mode .bk-info-btn { background: rgba(52,152,219,.18); color: #7ec3f5; }
    body.dark-mode .bk-info-btn:hover { background: rgba(52,152,219,.28); }

    .bk-info-pop {
        position: absolute; top: calc(100% + 10px); left: 0; width: 380px; max-width: 85vw;
        background: #fff; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,.18); padding: 1rem 1.1rem;
        font-size: .82rem; color: #495057; line-height: 1.5; z-index: 1030;
        opacity: 0; transform: translateY(-6px); pointer-events: none; transition: all .15s ease;
    }
    .bk-info-pop.show { opacity: 1; transform: translateY(0); pointer-events: auto; }
    .bk-info-pop .bk-info-close {
        position: absolute; top: .5rem; right: .6rem; background: none; border: none; color: #adb5bd; font-size: .85rem;
    }
    .bk-info-pop code { background: #f4e9e3; color: #b5451b; padding: .1em .4em; border-radius: 5px; }
    body.dark-mode .bk-info-pop { background: #1f1f33; color: #d5d5e2; box-shadow: 0 10px 30px rgba(0,0,0,.45); }
    body.dark-mode .bk-info-pop code { background: rgba(181,69,27,.22); color: #ff9d6e; }

    /* ── Tarjetas de estadísticas (mismo estilo "to-card" con hover) ── */
    .bk-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1.1rem; margin-bottom: 1.5rem; }
    @media (max-width: 767px) { .bk-stats { grid-template-columns: repeat(2, 1fr); } }
    .bk-stat {
        background: #fff; border-radius: 14px; padding: 1.1rem 1.3rem; box-shadow: 0 2px 12px rgba(0,0,0,.05);
        border: 1.5px solid transparent; transition: box-shadow .15s; display: flex; align-items: center; gap: .8rem;
    }
    .bk-stat:hover { box-shadow: 0 6px 20px rgba(0,0,0,.08); }
    body.dark-mode .bk-stat { background: #1e1e33; box-shadow: 0 2px 12px rgba(0,0,0,.3); }
    .bk-stat .bs-icon {
        width: 42px; height: 42px; border-radius: 11px; flex-shrink: 0;
        display: flex; align-items: center; justify-content: center; font-size: 1.05rem; color: #fff;
    }
    .bk-stat .bs-value { font-size: 1.25rem; font-weight: 800; color: #1a1a2e; line-height: 1.1; }
    .bk-stat .bs-label { font-size: .72rem; color: #8a8a9d; font-weight: 700; text-transform: uppercase; letter-spacing: .03em; }
    body.dark-mode .bk-stat .bs-value { color: #f0f0f7; }
    body.dark-mode .bk-stat .bs-label { color: #9a9ac0; }

    /* ── Tarjeta de acción "Generar respaldo" (mismo patrón que el resto del sistema:
       cabecera con degradado de marca + ícono, cuerpo con el contenido) ── */
    .prod-form-card { border: none; border-radius: 14px; box-shadow: 0 4px 24px rgba(0,0,0,.06); overflow: hidden; }
    .prod-form-header {
        background: linear-gradient(135deg, #1a1a2e 0%, #16213e 60%, #b5451b 100%);
        color: #fff; padding: 1.75rem 2rem; display: flex; align-items: center; gap: 1rem;
    }
    .prod-form-icon {
        width: 56px; height: 56px; border-radius: 14px; background: rgba(255,255,255,.15);
        border: 2px solid rgba(255,255,255,.3); display: flex; align-items: center; justify-content: center;
        font-size: 1.4rem; flex-shrink: 0; color: #fff;
    }
    .prod-form-header h5 { margin: 0; font-weight: 800; }
    .prod-form-header p { margin: 0; opacity: .75; font-size: .85rem; }
    .prod-form-body { padding: 1.5rem; }

    .bk-action-body { display: flex; align-items: center; justify-content: space-between; gap: 1.2rem; flex-wrap: wrap; }
    .bk-action-body p { margin: 0; color: #8a8a9d; font-size: .85rem; max-width: 520px; }

    /* ── Lista de respaldos ── */
    .bk-file-name { display: flex; align-items: center; gap: .6rem; }
    .bk-file-icon {
        width: 34px; height: 34px; border-radius: 9px; flex-shrink: 0; display: flex; align-items: center;
        justify-content: center; background: rgba(52,152,219,.12); color: #2170a3; font-size: .9rem;
    }
</style>
@endpush

@section('content')

<div class="page-toolbar">
    <div>
        <h2><i class="fas fa-database mr-2 text-info"></i>Respaldos de Base de Datos</h2>
        <div class="d-flex align-items-center flex-wrap" style="gap:.6rem;">
            <p class="mb-0">Copias de seguridad automáticas y manuales del sistema</p>
            <span class="bk-info-wrap">
                <button type="button" class="bk-info-btn" id="bkInfoBtn" onclick="toggleInfoBK()">
                    <i class="fas fa-circle-info"></i> ¿Cómo funciona?
                </button>
                <div class="bk-info-pop" id="bkInfoPop">
                    <button type="button" class="bk-info-close" onclick="toggleInfoBK()"><i class="fas fa-times"></i></button>
                    <i class="fas fa-info-circle mr-1"></i>
                    Todos los días a las <strong>3:00 a.m.</strong> se genera un respaldo automático
                    (<code>backup:run</code>) y se conservan los últimos <strong>{{ $stats['seConservan'] }}</strong>;
                    los más antiguos se borran solos. Para restaurar uno, un administrador debe correr por consola
                    <code>php artisan backup:restore &lt;archivo&gt;</code> — es una operación destructiva, por eso
                    no está disponible desde esta pantalla.
                </div>
            </span>
        </div>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="alert alert-danger">{{ session('error') }}</div>
@endif

{{-- Tarjetas de estadísticas --}}
<div class="bk-stats">
    <div class="bk-stat">
        <div class="bs-icon" style="background:linear-gradient(135deg,#1a1a2e,#2170a3);"><i class="fas fa-box-archive"></i></div>
        <div><div class="bs-value">{{ $stats['total'] }}</div><div class="bs-label">Respaldos guardados</div></div>
    </div>
    <div class="bk-stat">
        <div class="bs-icon" style="background:#3498db;"><i class="fas fa-hdd"></i></div>
        <div><div class="bs-value">{{ $stats['espacio'] }}</div><div class="bs-label">Espacio ocupado</div></div>
    </div>
    <div class="bk-stat">
        <div class="bs-icon" style="background:#2ecc71;"><i class="fas fa-clock"></i></div>
        <div><div class="bs-value" style="font-size:1rem;">{{ $stats['ultimo']?->diffForHumans() ?? 'Sin respaldos aún' }}</div><div class="bs-label">Último respaldo</div></div>
    </div>
    <div class="bk-stat">
        <div class="bs-icon" style="background:#8e44ad;"><i class="fas fa-server"></i></div>
        <div><div class="bs-value" style="font-size:1rem;">{{ strtoupper($stats['motorActivo']) }}</div><div class="bs-label">Motor de base de datos</div></div>
    </div>
</div>

{{-- Tarjeta de acción: generar respaldo manual --}}
<div class="card prod-form-card mb-3">
    <div class="prod-form-header">
        <div class="prod-form-icon"><i class="fas fa-plus"></i></div>
        <div>
            <h5>Generar respaldo manual</h5>
            <p>Además del respaldo automático diario, puedes generar uno al vuelo — útil antes de una actualización o de tu sustentación.</p>
        </div>
    </div>
    <div class="prod-form-body">
        <div class="bk-action-body">
            <p><i class="fas fa-circle-info mr-1 text-muted"></i>Se guardará en <code>storage/app/backups</code> y aparecerá en la lista de abajo al instante.</p>
            <form action="{{ route('backups.store') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-primary"><i class="fas fa-play mr-1"></i>Generar respaldo ahora</button>
            </form>
        </div>
    </div>
</div>

{{-- Lista de respaldos --}}
@if($backups->count() > 0)
<div class="table-card">
    <table class="table table-hover table-modern">
        <thead>
            <tr>
                <th>Archivo</th>
                <th>Motor</th>
                <th>Fecha</th>
                <th class="text-center">Tamaño</th>
                <th class="text-center">Acciones</th>
            </tr>
        </thead>
        <tbody>
            @foreach($backups as $backup)
            <tr>
                <td>
                    <div class="bk-file-name">
                        <div class="bk-file-icon"><i class="fas fa-file-zipper"></i></div>
                        <span class="row-title">{{ $backup['nombre'] }}</span>
                    </div>
                </td>
                <td><span class="badge-soft badge-soft-info">{{ $backup['motor'] }}</span></td>
                <td>{{ $backup['fecha']->format('d/m/Y H:i') }}</td>
                <td class="text-center">{{ $backup['tamano'] }}</td>
                <td class="text-center">
                    <div class="btn-icon-group">
                        <a href="{{ route('backups.download', $backup['nombre']) }}" class="btn btn-icon btn-info" title="Descargar">
                            <i class="fas fa-download"></i>
                        </a>
                        <form action="{{ route('backups.destroy', $backup['nombre']) }}" method="POST" class="js-confirm"
                            data-confirm-title="¿Eliminar este respaldo?"
                            data-confirm="Se eliminará &quot;{{ $backup['nombre'] }}&quot; permanentemente. Esta acción no se puede deshacer.">
                            @csrf @method('DELETE')
                            <button class="btn btn-icon btn-danger" title="Eliminar"><i class="fas fa-trash-alt"></i></button>
                        </form>
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@else
<div class="empty-state">
    <i class="fas fa-database"></i>
    <p>Todavía no se ha generado ningún respaldo</p>
    <form action="{{ route('backups.store') }}" method="POST" class="d-inline">
        @csrf
        <button type="submit" class="btn btn-primary"><i class="fas fa-plus mr-1"></i>Generar el primero</button>
    </form>
</div>
@endif

@endsection

@push('scripts')
<script>
    // ── Botón "¿Cómo funciona?" (mismo patrón que Tiempos por Operación) ──
    function toggleInfoBK() {
        document.getElementById('bkInfoPop').classList.toggle('show');
    }
    document.addEventListener('click', function (e) {
        const pop = document.getElementById('bkInfoPop');
        const btn = document.getElementById('bkInfoBtn');
        if (pop.classList.contains('show') && !pop.contains(e.target) && e.target !== btn && !btn.contains(e.target)) {
            pop.classList.remove('show');
        }
    });
</script>
@endpush
