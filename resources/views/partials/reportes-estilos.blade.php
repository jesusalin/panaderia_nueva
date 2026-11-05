{{-- Estilos compartidos de las pantallas de Reportes --}}
@include('partials.compras-estilos')
@once
@push('styles')
<style>
    .rp-tabla { width: 100%; font-size: .85rem; border-collapse: separate; border-spacing: 0; }
    .rp-tabla thead th { position: sticky; top: 0; z-index: 2; background: #faf7f4; font-size: .66rem; text-transform: uppercase; letter-spacing: .04em; color: #8a8a9d; font-weight: 800;
        padding: .6rem .7rem; border-bottom: 1px solid #ece5de; white-space: nowrap; }
    .rp-tabla td { padding: .55rem .7rem; border-bottom: 1px solid #f4f0ec; vertical-align: middle; }
    .rp-tabla td.num, .rp-tabla th.num { text-align: right; white-space: nowrap; font-variant-numeric: tabular-nums; }
    .rp-tabla tbody tr:hover td { background: #fffaf6; }
    .rp-tabla tfoot td { font-weight: 800; background: #faf7f4; border-top: 2px solid #ece5de; padding: .65rem .7rem; }
    .rp-tabla tr.quieto td { color: #9a9aad; }
    .rp-tabla tr.quieto td b { color: #6c757d; }
    body.dark-mode .rp-tabla thead th, body.dark-mode .rp-tabla tfoot td { background: #24243b; border-color: #33334d; color: #b0b0cc; }
    body.dark-mode .rp-tabla td { border-color: #2c2c44; }
    body.dark-mode .rp-tabla tbody tr:hover td { background: #26263e; }
    .rp-caja { background: #fff; border-radius: 14px; box-shadow: 0 2px 12px rgba(0,0,0,.05); overflow: hidden; }
    body.dark-mode .rp-caja { background: #1f1f33; }
    .rp-scroll { max-height: 70vh; overflow: auto; }
    .rp-chip { font-size: .68rem; font-weight: 800; border-radius: 10px; padding: .08rem .45rem; white-space: nowrap; }
    .rp-chip.bajo { background: rgba(231,76,60,.12); color: #c0392b; }
    .rp-chip.mal { background: #c0392b; color: #fff; }
    .rp-chip.ok { background: rgba(46,204,113,.14); color: #1e8e5a; }
    .rp-chip.gris { background: #eef0f3; color: #6c757d; }

    /* Barras horizontales (participación) */
    .rp-barra { height: 8px; background: #f1ece7; border-radius: 6px; overflow: hidden; min-width: 60px; }
    .rp-barra span { display: block; height: 100%; background: linear-gradient(90deg, #d9653a, #b5451b); border-radius: 6px; }
    body.dark-mode .rp-barra { background: #2c2c44; }

    /* Navegación por días */
    .rp-dias { display: flex; align-items: center; gap: .45rem; flex-wrap: wrap; margin-bottom: 1rem; }
    .rp-dias input[type=date] { width: auto; }

    /* Tarjetas de días del historial */
    .rp-hist { display: grid; grid-template-columns: repeat(auto-fill, minmax(118px, 1fr)); gap: .5rem; }
    .rp-hist a { display: block; background: #fff; border-radius: 10px; padding: .5rem .65rem; text-decoration: none; color: inherit; border: 1.5px solid #f1ece7; font-size: .78rem; }
    .rp-hist a:hover { border-color: #e2c9b8; text-decoration: none; color: inherit; }
    .rp-hist a.activo { border-color: #b5451b; }
    .rp-hist a b { display: block; font-size: .82rem; color: #1a1a2e; text-transform: capitalize; }
    .rp-hist .ok { color: #1e8e5a; font-weight: 700; } .rp-hist .no { color: #c0392b; font-weight: 700; } .rp-hist .nada { color: #adb5bd; }
    body.dark-mode .rp-hist a { background: #1f1f33; border-color: #33334d; }
    body.dark-mode .rp-hist a b { color: #f0f0f7; }

    .rp-acciones-exp { display: flex; gap: .4rem; }
    /* que el contenido ancho (gráficos, tablas) no estire la columna en celular */
    .inv-grid2 > * { min-width: 0; }
    @media (max-width: 767px) { .rp-ocultar-movil { display: none; } }
</style>
@endpush
@endonce
