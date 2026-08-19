@extends('layouts.app')
@section('title', 'Órdenes Automáticas')
@section('breadcrumb') <li class="breadcrumb-item active">Órdenes Automáticas</li> @endsection

@push('styles')
<style>
    .oa-tabs { display: flex; gap: .5rem; margin-bottom: 1.2rem; flex-wrap: wrap; }
    .oa-tab {
        display: flex; align-items: center; gap: .45rem; padding: .5rem 1rem; border-radius: 24px;
        background: #fff; border: 1.5px solid #e9ecef; color: #6c757d; font-weight: 700; font-size: .83rem; text-decoration: none;
        transition: all .12s;
    }
    .oa-tab:hover { border-color: #d4a98f; color: #b5451b; text-decoration: none; }
    .oa-tab.active { background: #b5451b; border-color: #b5451b; color: #fff; }
    .oa-tab .oa-count {
        background: rgba(0,0,0,.12); border-radius: 10px; padding: 0 .45rem; font-size: .74rem; min-width: 20px; text-align: center;
    }
    .oa-tab.active .oa-count { background: rgba(255,255,255,.25); }
    body.dark-mode .oa-tab { background: #1f1f33; border-color: #33334d; color: #9a9ac0; }
    body.dark-mode .oa-tab:hover { border-color: #b5451b; color: #ff9d6e; }
    body.dark-mode .oa-tab.active { background: #b5451b; border-color: #b5451b; color: #fff; }

    .oa-sinprov { color: #e74c3c; font-size: .76rem; font-weight: 700; }
    body.dark-mode .oa-sinprov { color: #ff9b8f; }

    #ordenesContainer { transition: opacity .15s ease; }
    #ordenesContainer.cargando { opacity: .4; pointer-events: none; }

    /* Botón + panel de info, igual patrón que el carrito flotante */
    .oa-info-wrap { position: relative; display: inline-block; }
    .oa-info-btn {
        display: inline-flex; align-items: center; gap: .4rem; background: rgba(52,152,219,.12); color: #2f7fb0;
        border: none; border-radius: 20px; padding: .4rem .9rem; font-weight: 700; font-size: .8rem; cursor: pointer;
    }
    .oa-info-btn:hover { background: rgba(52,152,219,.22); }
    body.dark-mode .oa-info-btn { background: rgba(52,152,219,.18); color: #7ec3f5; }
    body.dark-mode .oa-info-btn:hover { background: rgba(52,152,219,.28); }

    .oa-info-pop {
        position: absolute; top: calc(100% + 10px); left: 0; width: 340px; max-width: 85vw;
        background: #fff; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,.18); padding: 1rem 1.1rem;
        font-size: .82rem; color: #495057; line-height: 1.5; z-index: 1030;
        opacity: 0; transform: translateY(-6px); pointer-events: none; transition: all .15s ease;
    }
    .oa-info-pop.show { opacity: 1; transform: translateY(0); pointer-events: auto; }
    .oa-info-pop .oa-info-close {
        position: absolute; top: .5rem; right: .6rem; background: none; border: none; color: #adb5bd; font-size: .85rem;
    }
    body.dark-mode .oa-info-pop { background: #1f1f33; color: #d5d5e2; box-shadow: 0 10px 30px rgba(0,0,0,.45); }
</style>
@endpush

@section('content')

<div class="page-toolbar">
    <div>
        <h2><i class="fas fa-robot mr-2 text-primary"></i>Órdenes Automáticas de Reposición</h2>
        <div class="d-flex align-items-center flex-wrap" style="gap:.6rem;">
            <p class="mb-0">El sistema detecta insumos con stock bajo y sugiere cuánto reponer</p>
            <span class="oa-info-wrap">
                <button type="button" class="oa-info-btn" id="oaInfoBtn" onclick="toggleInfoOA()">
                    <i class="fas fa-circle-info"></i> ¿Cómo funciona?
                </button>
                <div class="oa-info-pop" id="oaInfoPop">
                    <button type="button" class="oa-info-close" onclick="toggleInfoOA()"><i class="fas fa-times"></i></button>
                    <i class="fas fa-info-circle mr-1"></i>
                    El sistema revisa todos los insumos activos y genera automáticamente una orden
                    cuando el stock llega o baja del mínimo configurado. Cada orden se puede
                    <strong>convertir en compra</strong> (requiere proveedor asignado al insumo) o
                    <strong>descartar</strong> si aún no se necesita reabastecer.
                </div>
            </span>
        </div>
    </div>
    <div class="toolbar-actions">
        <form action="{{ route('ordenes-automaticas.generar') }}" method="POST">
            @csrf
            <button class="btn btn-primary">
                <i class="fas fa-sync-alt mr-1"></i>Revisar Stock y Generar Órdenes
            </button>
        </form>
    </div>
</div>

<div class="oa-tabs" id="oaTabs">
    <a href="{{ route('ordenes-automaticas.index') }}" class="oa-tab {{ !request('estado') ? 'active' : '' }}">
        <i class="fas fa-layer-group"></i>Todas
        <span class="oa-count">{{ array_sum($conteos) }}</span>
    </a>
    <a href="{{ route('ordenes-automaticas.index', ['estado' => 'pendiente']) }}" class="oa-tab {{ request('estado') === 'pendiente' ? 'active' : '' }}">
        <i class="fas fa-clock"></i>Pendientes
        <span class="oa-count">{{ $conteos['pendiente'] }}</span>
    </a>
    <a href="{{ route('ordenes-automaticas.index', ['estado' => 'convertida']) }}" class="oa-tab {{ request('estado') === 'convertida' ? 'active' : '' }}">
        <i class="fas fa-check"></i>Convertidas
        <span class="oa-count">{{ $conteos['convertida'] }}</span>
    </a>
    <a href="{{ route('ordenes-automaticas.index', ['estado' => 'descartada']) }}" class="oa-tab {{ request('estado') === 'descartada' ? 'active' : '' }}">
        <i class="fas fa-ban"></i>Descartadas
        <span class="oa-count">{{ $conteos['descartada'] }}</span>
    </a>
</div>

<div id="ordenesContainer">
    @include('ordenes-automaticas._lista')
</div>

@endsection

@push('scripts')
<script>
    // ── Botón "¿Cómo funciona?" (mismo patrón que el panel del carrito) ──
    function toggleInfoOA() {
        document.getElementById('oaInfoPop').classList.toggle('show');
    }
    document.addEventListener('click', function (e) {
        const pop = document.getElementById('oaInfoPop');
        const btn = document.getElementById('oaInfoBtn');
        if (pop.classList.contains('show') && !pop.contains(e.target) && e.target !== btn && !btn.contains(e.target)) {
            pop.classList.remove('show');
        }
    });

    // ── Pestañas y paginación por AJAX (sin recargar la página) ──
    function cargarOrdenes(url, actualizarPestañas) {
        const cont = document.getElementById('ordenesContainer');
        cont.classList.add('cargando');

        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(res => res.text())
            .then(html => {
                cont.innerHTML = html;
                cont.classList.remove('cargando');
                history.pushState({ ordenesUrl: url }, '', url);
                if (actualizarPestañas) marcarPestañaActiva(url);
                cont.scrollIntoView({ behavior: 'smooth', block: 'start' });
            })
            .catch(() => { cont.classList.remove('cargando'); window.location.href = url; });
    }

    function marcarPestañaActiva(url) {
        const estado = new URL(url, window.location.origin).searchParams.get('estado') || '';
        document.querySelectorAll('.oa-tab').forEach(tab => {
            const tabEstado = new URL(tab.href, window.location.origin).searchParams.get('estado') || '';
            tab.classList.toggle('active', tabEstado === estado);
        });
    }

    // Pestañas
    document.getElementById('oaTabs').addEventListener('click', function (e) {
        const link = e.target.closest('.oa-tab');
        if (!link) return;
        e.preventDefault();
        cargarOrdenes(link.href, true);
    });

    // Paginación (los links se regeneran dentro del contenedor cada vez, por eso usamos delegación)
    document.getElementById('ordenesContainer').addEventListener('click', function (e) {
        const link = e.target.closest('a.pg-link');
        if (!link) return;
        e.preventDefault();
        cargarOrdenes(link.href, false);
    });

    // Botón "atrás/adelante" del navegador
    window.addEventListener('popstate', function () {
        cargarOrdenes(window.location.href, true);
    });
</script>
@endpush
