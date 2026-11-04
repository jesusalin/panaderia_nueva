{{-- Estilos compartidos de las pantallas de Inventario (resúmenes, cabeceras, cajas y tablas) --}}
@once
@push('styles')
<style>
    .inv-resumen { display: flex; flex-wrap: wrap; gap: .6rem; margin-bottom: 1rem; }
    .inv-resumen .ir { background: #fff; border-radius: 12px; padding: .55rem .95rem; font-size: .84rem; color: #6c757d; box-shadow: 0 1px 6px rgba(0,0,0,.05);
        border: 1.5px solid transparent; text-decoration: none; display: inline-flex; align-items: center; gap: .4rem; }
    .inv-resumen .ir b { color: #1a1a2e; font-size: 1.02rem; }
    .inv-resumen a.ir:hover { border-color: #e2c9b8; text-decoration: none; color: #6c757d; }
    .inv-resumen .ir.activo { border-color: #b5451b; }
    .inv-resumen .ir.bajo i { color: #e67e22; } .inv-resumen .ir.agotado i { color: #c0392b; } .inv-resumen .ir.valor i { color: #1e8e5a; }
    body.dark-mode .inv-resumen .ir { background: #1f1f33; color: #b0b0cc; }
    body.dark-mode .inv-resumen .ir b { color: #f0f0f7; }

    .inv-hero { display: flex; align-items: center; gap: 1rem; flex-wrap: wrap; padding: 1.2rem 1.4rem; border-radius: 14px; color: #fff; margin-bottom: 1.2rem;
        background: linear-gradient(135deg, #1a1a2e 0%, #23233d 55%, var(--acento, #b5451b) 150%); }
    .inv-hero .ih-icono { width: 62px; height: 62px; border-radius: 14px; background: rgba(255,255,255,.13); border: 2px solid rgba(255,255,255,.25);
        display: flex; align-items: center; justify-content: center; font-size: 1.6rem; flex-shrink: 0; }
    .inv-hero small { opacity: .75; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; font-size: .72rem; }
    .inv-hero h3 { margin: 0; font-weight: 800; font-size: 1.4rem; }
    .inv-hero .ih-datos { margin-left: auto; display: flex; gap: 1.5rem; flex-wrap: wrap; }
    .inv-hero .ih-datos div { text-align: right; }
    .inv-hero .ih-datos b { display: block; font-size: 1.25rem; }
    .inv-hero .ih-datos span { font-size: .72rem; opacity: .75; }
    .inv-hero .ih-acciones { display: flex; gap: .45rem; flex-wrap: wrap; width: 100%; }

    .inv-grid2 { display: grid; grid-template-columns: 1fr 1fr; gap: 1.1rem; }
    @media (max-width: 991px) { .inv-grid2 { grid-template-columns: 1fr; } }
    .inv-box { background: #fff; border-radius: 14px; box-shadow: 0 2px 12px rgba(0,0,0,.05); padding: 1.05rem 1.25rem; }
    .inv-box h5 { font-size: .95rem; font-weight: 800; color: #1a1a2e; margin-bottom: .85rem; }
    .inv-box h5 i { color: #b5451b; margin-right: .35rem; }
    body.dark-mode .inv-box { background: #1f1f33; }
    body.dark-mode .inv-box h5 { color: #f0f0f7; }
    .inv-tabla { width: 100%; font-size: .85rem; }
    .inv-tabla th { font-size: .68rem; text-transform: uppercase; letter-spacing: .03em; color: #8a8a9d; font-weight: 800; padding: .35rem .5rem .35rem 0; border-bottom: 1px solid #f0ebe6; }
    .inv-tabla td { padding: .45rem .5rem .45rem 0; border-bottom: 1px solid #f6f3f0; vertical-align: middle; }
    .inv-tabla tr:last-child td { border-bottom: none; }
    body.dark-mode .inv-tabla th, body.dark-mode .inv-tabla td { border-color: #33334d; }
    .inv-mas { color: #1e8e5a; font-weight: 800; } .inv-menos { color: #c0392b; font-weight: 800; } .inv-aj { color: #a5680d; font-weight: 800; }

    .inv-barras { display: flex; align-items: flex-end; gap: 4px; height: 110px; }
    .inv-barras .b { flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: flex-end; height: 100%; }
    .inv-barras .b span { width: 100%; background: #b5451b; border-radius: 4px 4px 0 0; min-height: 2px; opacity: .8; }
    .inv-barras .b em { font-style: normal; font-size: .62rem; color: #adb5bd; margin-top: .2rem; }

    .inv-dia { font-size: .74rem; font-weight: 800; color: #8a8a9d; text-transform: uppercase; letter-spacing: .05em; margin: 1.1rem 0 .45rem; display: flex; justify-content: space-between; }
    .inv-dia:first-child { margin-top: 0; }
    .inv-lista { background: #fff; border-radius: 14px; box-shadow: 0 2px 12px rgba(0,0,0,.05); overflow: hidden; }
    body.dark-mode .inv-lista { background: #1f1f33; }
    .inv-mov { display: grid; grid-template-columns: 50px 34px minmax(150px, 1.4fr) minmax(110px, .9fr) 110px 150px minmax(120px, 1fr); gap: .7rem; align-items: center;
        padding: .6rem 1.1rem; border-bottom: 1px solid #f3f0ec; font-size: .86rem; }
    .inv-mov:last-child { border-bottom: none; }
    body.dark-mode .inv-mov { border-bottom-color: #2c2c44; }
    .inv-mov .im-hora { color: #8a8a9d; font-weight: 700; font-variant-numeric: tabular-nums; }
    .inv-mov .im-ic { width: 30px; height: 30px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: .8rem; }
    .im-ic.entrada { background: rgba(46,204,113,.14); color: #1e8e5a; } .im-ic.salida { background: rgba(231,76,60,.12); color: #c0392b; } .im-ic.ajuste { background: rgba(243,156,18,.16); color: #a5680d; }
    .inv-mov .im-nombre a { font-weight: 700; color: #1a1a2e; }
    body.dark-mode .inv-mov .im-nombre a { color: #f0f0f7; }
    .inv-mov .im-nombre small, .inv-mov .im-motivo small { display: block; color: #adb5bd; font-size: .72rem; }
    .inv-mov .im-cant { display: flex; align-items: center; justify-content: flex-end; gap: .45rem; white-space: nowrap; }
    .im-flecha { width: 22px; height: 22px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: .66rem; flex-shrink: 0; }
    .im-flecha.sube { background: rgba(46,204,113,.16); color: #1e8e5a; }
    .im-flecha.baja { background: rgba(231,76,60,.13); color: #c0392b; }
    .im-flecha.ajuste { background: rgba(243,156,18,.18); color: #a5680d; }
    .im-paso { margin: 0 .2rem; font-size: .85rem; }
    .im-paso.sube { color: #1e8e5a; } .im-paso.baja { color: #c0392b; } .im-paso.ajuste { color: #a5680d; }
    .inv-dia .id-flechas { display: flex; gap: .7rem; align-items: center; }
    .inv-dia .id-flechas i { margin-right: .2rem; }
    .inv-dia .sube { color: #1e8e5a; } .inv-dia .baja { color: #c0392b; }
    .inv-mov .im-saldo { color: #6c757d; font-size: .8rem; text-align: right; }
    .inv-mov .im-saldo b { color: #1a1a2e; }
    body.dark-mode .inv-mov .im-saldo b { color: #f0f0f7; }
    .inv-mov .im-obs { color: #8a8a9d; font-size: .78rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    @media (max-width: 991px) {
        .inv-mov { grid-template-columns: 46px 30px 1fr auto; }
        .inv-mov .im-motivo, .inv-mov .im-obs { grid-column: 3 / -1; }
        .inv-mov .im-saldo { grid-column: 3 / -1; text-align: left; }
        .inv-mov .im-hora { grid-row: 1; grid-column: 1; }
        .inv-mov .im-ic { grid-row: 1; grid-column: 2; }
        .inv-mov .im-nombre { grid-row: 1; grid-column: 3; min-width: 0; }
        .inv-mov .im-cant { grid-row: 1; grid-column: 4; }
        .inv-dia { flex-wrap: wrap; gap: .3rem; }
        .inv-dia .id-flechas span:last-child { display: none; }
    }
    .inv-chips { display: flex; flex-wrap: wrap; gap: .4rem; margin-bottom: 1rem; }
    .inv-chip { padding: .3rem .8rem; border-radius: 20px; background: #fff; border: 1.5px solid #eee3da; font-size: .78rem; font-weight: 700; color: #6c757d; text-decoration: none; }
    .inv-chip:hover { border-color: #b5451b; color: #b5451b; text-decoration: none; }
    .inv-chip.activo { background: #b5451b; border-color: #b5451b; color: #fff; }
    body.dark-mode .inv-chip { background: #1f1f33; border-color: #33334d; color: #b0b0cc; }
</style>
@endpush
@endonce
