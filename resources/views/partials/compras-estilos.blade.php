{{-- Estilos compartidos de las pantallas de Compras (compras, proveedores y órdenes automáticas) --}}
@include('partials.inv-estilos')
@once
@push('styles')
<style>
    /* En celular los filtros no deben salirse de la pantalla (nombres de proveedor largos) */
    .filter-bar select { max-width: 100%; }
    @media (max-width: 576px) {
        .filter-bar .fb-par { width: 100%; }
        .filter-bar .fb-par select { flex: 1 1 auto; min-width: 0; width: 100% !important; }
        .filter-bar details { max-width: 100%; }
        .filter-bar details input[type=date] { min-width: 0; }
    }

    /* Estado de una compra */
    .cp-estado { display: inline-flex; align-items: center; gap: .3rem; border-radius: 20px; padding: .2rem .7rem; font-size: .74rem; font-weight: 800; white-space: nowrap; }
    .cp-estado.pendiente { background: rgba(243,156,18,.15); color: #a5680d; }
    .cp-estado.atrasada  { background: rgba(231,76,60,.13); color: #c0392b; }
    .cp-estado.recibida  { background: rgba(46,204,113,.15); color: #1e8e5a; }
    .cp-estado.anulada   { background: #eef0f3; color: #6c757d; text-decoration: line-through; }
    body.dark-mode .cp-estado.anulada { background: #2c2c44; color: #9a9ac0; }

    /* Franja "Por recibir" */
    .cp-porrecibir { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: .75rem; margin-bottom: 1.2rem; }
    .cp-pr { background: #fff; border-radius: 14px; padding: .85rem 1rem; box-shadow: 0 2px 12px rgba(0,0,0,.05); border-left: 4px solid #f39c12; display: flex; flex-direction: column; gap: .35rem; }
    .cp-pr.atrasada { border-left-color: #e74c3c; }
    .cp-pr .pr-top { display: flex; justify-content: space-between; gap: .5rem; align-items: flex-start; }
    .cp-pr b { color: #1a1a2e; font-size: .92rem; }
    .cp-pr small { color: #8a8a9d; font-size: .75rem; }
    .cp-pr .pr-total { font-weight: 800; color: #b5451b; white-space: nowrap; }
    .inv-hero .cp-estado { background: rgba(255,255,255,.92); }
    .cp-pr .pr-acc { display: flex; gap: .4rem; margin-top: .2rem; }
    body.dark-mode .cp-pr { background: #1f1f33; }
    body.dark-mode .cp-pr b { color: #f0f0f7; }
    .cp-titulo { font-size: .76rem; font-weight: 800; letter-spacing: .05em; text-transform: uppercase; color: #8a8a9d; margin: 0 0 .55rem; display: flex; align-items: center; gap: .4rem; }

    /* Filas del listado */
    .cp-fila { display: grid; grid-template-columns: 44px minmax(200px, 1.6fr) minmax(110px, .8fr) minmax(120px, .8fr) 130px auto; gap: .9rem; align-items: center;
        background: #fff; border-radius: 12px; padding: .75rem 1rem; box-shadow: 0 1px 6px rgba(0,0,0,.05); text-decoration: none !important; color: inherit; }
    .cp-fila:hover { box-shadow: 0 4px 16px rgba(0,0,0,.09); color: inherit; }
    .cp-fila + .cp-fila { margin-top: .55rem; }
    .cp-fila.anulada { opacity: .65; }
    body.dark-mode .cp-fila { background: #1f1f33; }
    .cp-fila .cf-ic { width: 42px; height: 42px; border-radius: 11px; display: flex; align-items: center; justify-content: center; color: #fff; font-size: .95rem; }
    .cf-ic.pendiente { background: linear-gradient(135deg, #a1670c, #f39c12); } .cf-ic.atrasada { background: linear-gradient(135deg, #a93226, #e74c3c); }
    .cf-ic.recibida { background: linear-gradient(135deg, #1e8e5a, #2ecc71); } .cf-ic.anulada { background: #adb5bd; }
    .cp-fila .cf-t { font-weight: 800; color: #1a1a2e; font-size: .92rem; }
    .cp-fila .cf-s { font-size: .78rem; color: #8a8a9d; margin-top: .1rem; overflow: hidden; text-overflow: ellipsis; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; }
    body.dark-mode .cp-fila .cf-t { color: #f0f0f7; }
    .cp-fila .cf-dato small { display: block; font-size: .64rem; color: #adb5bd; text-transform: uppercase; letter-spacing: .03em; font-weight: 700; }
    .cp-fila .cf-dato span { font-size: .86rem; font-weight: 700; color: #1a1a2e; }
    body.dark-mode .cp-fila .cf-dato span { color: #e4e4ef; }
    .cp-fila .cf-total { text-align: right; font-weight: 800; font-size: 1rem; color: #1a1a2e; }
    body.dark-mode .cp-fila .cf-total { color: #f0f0f7; }
    .cp-fila .cf-acc { display: flex; gap: .4rem; align-items: center; justify-content: flex-end; }
    @media (max-width: 991px) {
        .cp-fila { grid-template-columns: 42px 1fr auto; }
        .cp-fila .cf-dato { display: none; }
        .cp-fila .cf-acc { grid-column: 1 / -1; justify-content: flex-start; }
    }

    /* Avatar de proveedor */
    .pv-avatar { width: 46px; height: 46px; border-radius: 12px; background: linear-gradient(135deg, #7a2c10, #b5451b); color: #fff; font-weight: 800;
        display: flex; align-items: center; justify-content: center; font-size: 1rem; flex-shrink: 0; letter-spacing: .02em; }
    .pv-avatar.inactivo { background: #adb5bd; }
    .pv-contacto { display: flex; gap: .35rem; flex-wrap: wrap; }
    .pv-contacto a { display: inline-flex; align-items: center; gap: .3rem; border-radius: 20px; padding: .22rem .65rem; font-size: .76rem; font-weight: 700;
        background: #f6f1ec; color: #7a2c10; text-decoration: none; }
    .pv-contacto a:hover { background: #fde9dd; }
    .pv-contacto a.wa { background: rgba(37,211,102,.14); color: #128c4a; }
    body.dark-mode .pv-contacto a { background: #2c2c44; color: #ffb38f; }
    body.dark-mode .pv-contacto a.wa { background: rgba(37,211,102,.15); color: #6ee7a5; }

    /* Variación de precio */
    .cp-var { font-size: .72rem; font-weight: 800; border-radius: 10px; padding: .05rem .4rem; white-space: nowrap; }
    .cp-var.sube { background: rgba(231,76,60,.12); color: #c0392b; }
    .cp-var.baja { background: rgba(46,204,113,.14); color: #1e8e5a; }
    .cp-var.igual { background: #eef0f3; color: #6c757d; }

    /* Línea de tiempo de una compra */
    .cp-tiempo { list-style: none; padding: 0; margin: 0; position: relative; }
    .cp-tiempo::before { content: ''; position: absolute; left: 15px; top: 6px; bottom: 6px; width: 2px; background: #eee5dd; }
    .cp-tiempo li { position: relative; padding: 0 0 1rem 2.6rem; }
    .cp-tiempo li:last-child { padding-bottom: 0; }
    .cp-tiempo .tp-ic { position: absolute; left: 0; top: 0; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: .78rem; background: #f6f1ec; color: #b5451b; border: 3px solid #fff; }
    .cp-tiempo .tp-ic.ok { background: #e3f6ec; color: #1e8e5a; } .cp-tiempo .tp-ic.mal { background: #fdecea; color: #c0392b; } .cp-tiempo .tp-ic.espera { background: #fff4e0; color: #a5680d; }
    .cp-tiempo b { display: block; font-size: .88rem; color: #1a1a2e; }
    .cp-tiempo small { color: #8a8a9d; font-size: .78rem; }
    body.dark-mode .cp-tiempo::before { background: #33334d; }
    body.dark-mode .cp-tiempo .tp-ic { border-color: #1f1f33; }
    body.dark-mode .cp-tiempo b { color: #f0f0f7; }
</style>
@endpush
@endonce
