{{-- Estilos de los formularios por secciones (Producto, Insumo): tarjetas, dinero, ganancia, interruptor --}}
@once
@push('styles')
<style>
    .pfm-hero { display: flex; align-items: center; gap: 1rem; flex-wrap: wrap; padding: 1.2rem 1.4rem; border-radius: 14px; color: #fff; margin-bottom: 1.2rem;
        background: linear-gradient(135deg, #1a1a2e 0%, #23233d 55%, var(--cat, #b5451b) 150%); }
    .pfm-hero .ph-foto { width: 64px; height: 64px; border-radius: 14px; background: rgba(255,255,255,.13); border: 2px solid rgba(255,255,255,.25);
        display: flex; align-items: center; justify-content: center; font-size: 1.6rem; overflow: hidden; flex-shrink: 0; }
    .pfm-hero .ph-foto img { width: 100%; height: 100%; object-fit: cover; }
    .pfm-hero small { opacity: .75; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; font-size: .72rem; }
    .pfm-hero h3 { margin: 0; font-weight: 800; font-size: 1.4rem; }
    .pfm-hero .ph-datos { margin-left: auto; display: flex; gap: 1.4rem; flex-wrap: wrap; }
    .pfm-hero .ph-datos div { text-align: right; }
    .pfm-hero .ph-datos b { display: block; font-size: 1.25rem; }
    .pfm-hero .ph-datos span { font-size: .72rem; opacity: .75; }

    .pfm-grid { display: grid; grid-template-columns: minmax(0, 1.7fr) minmax(0, 1fr); gap: 1.2rem; align-items: start; }
    @media (max-width: 991px) { .pfm-grid { grid-template-columns: 1fr; } }
    .pfm-card { background: #fff; border-radius: 14px; box-shadow: 0 2px 12px rgba(0,0,0,.05); margin-bottom: 1.1rem; }
    body.dark-mode .pfm-card { background: #1f1f33; }
    .pfm-card-head { display: flex; align-items: center; gap: .6rem; padding: .9rem 1.3rem; border-bottom: 1px solid #f3eee9; }
    .pfm-card-head .hi { width: 30px; height: 30px; border-radius: 9px; background: rgba(181,69,27,.1); color: #b5451b; display: flex; align-items: center; justify-content: center; font-size: .85rem; }
    .pfm-card-head h5 { margin: 0; font-weight: 800; font-size: .98rem; color: #1a1a2e; }
    .pfm-card-head small { margin-left: auto; color: #8a8a9d; }
    body.dark-mode .pfm-card-head { border-bottom-color: #2c2c44; }
    body.dark-mode .pfm-card-head h5 { color: #f0f0f7; }
    .pfm-card-body { padding: 1.1rem 1.3rem; }
    .pfm-card-body label { font-size: .84rem; font-weight: 700; color: #495057; }
    body.dark-mode .pfm-card-body label { color: #d5d5e2; }
    .pfm-card-body .form-control { border-radius: 10px; border: 1.5px solid #e9ecef; }
    .pfm-card-body .form-control:focus { border-color: #b5451b; box-shadow: 0 0 0 3px rgba(181,69,27,.12); }
    body.dark-mode .pfm-card-body .form-control { background: #24243b; border-color: #33334d; color: #e4e4ef; }

    .pfm-foto { display: grid; grid-template-columns: 170px 1fr; gap: 1rem; align-items: center; }
    @media (max-width: 576px) { .pfm-foto { grid-template-columns: 1fr; } }
    .pfm-foto-vista { width: 170px; height: 130px; border-radius: 12px; overflow: hidden; display: flex; align-items: center; justify-content: center;
        background: linear-gradient(135deg, #1a1a2e, var(--cat, #b5451b)); color: #fff; font-size: 2.4rem; }
    .pfm-foto-vista img { width: 100%; height: 100%; object-fit: cover; }
    .pfm-drop { position: relative; border: 2px dashed #e0d5cc; border-radius: 12px; padding: 1rem; text-align: center; cursor: pointer; transition: .15s; color: #8a8a9d; font-size: .82rem; }
    .pfm-drop:hover, .pfm-drop.sobre { border-color: #b5451b; background: #fffaf7; }
    .pfm-drop input { position: absolute; inset: 0; opacity: 0; cursor: pointer; }
    .pfm-drop i { font-size: 1.4rem; color: #b5451b; display: block; margin-bottom: .3rem; }
    body.dark-mode .pfm-drop { border-color: #33334d; }

    .pfm-precios { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
    @media (max-width: 576px) { .pfm-precios { grid-template-columns: 1fr; } }
    .pfm-money { position: relative; }
    .pfm-money span { position: absolute; left: .9rem; top: 50%; transform: translateY(-50%); font-weight: 700; color: #8a8a9d; }
    .pfm-money input { padding-left: 2.3rem; font-size: 1.15rem; font-weight: 800; height: 46px; }
    .pfm-receta-link { font-size: .78rem; margin-top: .35rem; }
    .pfm-ganancia { margin-top: 1rem; padding: .85rem 1rem; border-radius: 12px; background: rgba(46,204,113,.1); }
    .pfm-ganancia .g-top { display: flex; justify-content: space-between; align-items: baseline; flex-wrap: wrap; gap: .3rem; }
    .pfm-ganancia .g-top span { font-size: .78rem; font-weight: 800; letter-spacing: .04em; text-transform: uppercase; color: #1e8e5a; }
    .pfm-ganancia .g-top b { font-size: 1.35rem; color: #1e8e5a; }
    .pfm-ganancia .g-barra { height: 8px; border-radius: 6px; background: rgba(0,0,0,.07); overflow: hidden; margin-top: .45rem; }
    .pfm-ganancia .g-barra span { display: block; height: 100%; border-radius: 6px; background: #2ecc71; transition: width .2s; }
    .pfm-ganancia.baja { background: rgba(243,156,18,.12); } .pfm-ganancia.baja .g-top span, .pfm-ganancia.baja .g-top b { color: #a5680d; } .pfm-ganancia.baja .g-barra span { background: #f39c12; }
    .pfm-ganancia.perdida { background: rgba(231,76,60,.1); } .pfm-ganancia.perdida .g-top span, .pfm-ganancia.perdida .g-top b { color: #c0392b; } .pfm-ganancia.perdida .g-barra span { background: #e74c3c; }
    .pfm-ganancia.vacia { background: #f7f5f3; } .pfm-ganancia.vacia .g-top span, .pfm-ganancia.vacia .g-top b { color: #8a8a9d; }
    body.dark-mode .pfm-ganancia.vacia { background: #24243b; }

    .pfm-stock { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
    @media (max-width: 576px) { .pfm-stock { grid-template-columns: 1fr; } }
    .pfm-stock-actual { display: flex; align-items: center; justify-content: space-between; gap: .6rem; background: #f7f5f3; border-radius: 10px; padding: .55rem .8rem; }
    .pfm-stock-actual b { font-size: 1.35rem; color: #1a1a2e; }
    body.dark-mode .pfm-stock-actual { background: #24243b; }
    body.dark-mode .pfm-stock-actual b { color: #f0f0f7; }

    .pfm-switch { display: flex; align-items: center; justify-content: space-between; gap: 1rem; }
    .pfm-switch .sw-txt b { display: block; color: #1a1a2e; }
    .pfm-switch .sw-txt span { font-size: .8rem; color: #8a8a9d; }
    body.dark-mode .pfm-switch .sw-txt b { color: #f0f0f7; }
    .pfm-toggle { position: relative; width: 52px; height: 28px; flex-shrink: 0; margin: 0; }
    .pfm-toggle input { opacity: 0; width: 0; height: 0; }
    .pfm-toggle span { position: absolute; inset: 0; border-radius: 20px; background: #dee2e6; cursor: pointer; transition: .15s; }
    .pfm-toggle span::after { content: ''; position: absolute; top: 3px; left: 3px; width: 22px; height: 22px; border-radius: 50%; background: #fff; transition: .15s; box-shadow: 0 1px 3px rgba(0,0,0,.2); }
    .pfm-toggle input:checked + span { background: #2ecc71; }
    .pfm-toggle input:checked + span::after { left: 27px; }
    .pfm-toggle input:focus-visible + span { box-shadow: 0 0 0 3px rgba(46,204,113,.3); }

    .pfm-lado { position: sticky; top: 1rem; }
    .pfm-lado .preview-label { font-size: .76rem; font-weight: 800; color: #8a8a9d; text-transform: uppercase; letter-spacing: .03em; margin-bottom: .7rem; display: block; text-align: center; }
    .pfm-lado .item-card { margin-bottom: 1rem; }
    .pfm-check { list-style: none; margin: 0; padding: 0; font-size: .84rem; }
    .pfm-check li { display: flex; gap: .55rem; align-items: flex-start; padding: .4rem 0; color: #6c757d; }
    .pfm-check li i { margin-top: .2rem; width: 14px; }
    .pfm-check li.ok i { color: #2ecc71; } .pfm-check li.no i { color: #f39c12; }
    .pfm-guardar { width: 100%; padding: .7rem; font-weight: 800; }
</style>
@endpush
@endonce
