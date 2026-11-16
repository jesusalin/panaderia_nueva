{{--
    Tema visual de Muruwasi (estilo limpio tipo Shopify / Zoho Inventory con los colores de la panadería).
    Va DESPUÉS de los estilos del layout y de cada pantalla, así que manda sobre ellos.

    Todo sale de variables (tokens): para cambiar un color se cambia aquí y se aplica en todo
    el sistema, en modo claro y en modo oscuro (body.dark-mode).
--}}
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap">
<style>
    :root {
        --m-bg: #f0eee9;            /* fondo de la página (un poco más oscuro que las tarjetas, para que se distingan) */
        --m-surface: #ffffff;       /* tarjetas, tablas, menú */
        --m-surface-2: #faf9f7;     /* cabeceras de tabla, zonas secundarias */
        --m-surface-3: #efebe5;     /* hover, chips */
        --m-border: #e7e3dd;
        --m-border-strong: #d8d3cb;
        --m-text: #1d1b19;
        --m-text-2: #4b4741;
        --m-muted: #7b756d;
        --m-muted-2: #8f897f;     /* textos de apoyo más suaves (fechas, unidades) */
        --m-brand: #b5451b;
        --m-brand-hover: #983812;
        --m-brand-soft: #fbefe8;
        --m-brand-text: #a33d15;
        --m-success: #1a7f4b;  --m-success-soft: #e5f4ec; --m-success-text: #166c40;
        --m-danger: #c23a2b;   --m-danger-soft: #fdeceA;  --m-danger-text: #b03224;
        --m-warning: #b26a00;  --m-warning-soft: #fdf2dd; --m-warning-text: #8f5500;
        --m-info: #1f6fa8;     --m-info-soft: #e5f1fa;    --m-info-text: #1b6197;
        --m-shadow: 0 1px 2px rgba(29, 27, 25, .07), 0 1px 3px rgba(29, 27, 25, .05);
        --m-shadow-hover: 0 10px 28px rgba(29, 27, 25, .14), 0 2px 6px rgba(29, 27, 25, .06);
        --m-radius: 12px;
        --m-focus: 0 0 0 3px rgba(181, 69, 27, .18);
    }
:root body.dark-mode{
        --m-bg: #14141f;
        --m-surface: #1f1f33;
        --m-surface-2: #24243b;
        --m-surface-3: #2c2c44;
        --m-border: #33334d;
        --m-border-strong: #41415f;
        --m-text: #f0f0f6;
        --m-text-2: #cfcfe0;
        --m-muted: #a2a2c0;
        --m-muted-2: #8b8baa;
        --m-brand: #d6582a;
        --m-brand-hover: #e86a3b;
        --m-brand-soft: rgba(214, 88, 42, .18);
        --m-brand-text: #ffa47a;
        --m-success: #2f9e66;  --m-success-soft: rgba(76, 195, 138, .15); --m-success-text: #7be0ab;
        --m-danger: #d8483a;   --m-danger-soft: rgba(255, 110, 95, .15);  --m-danger-text: #ffa093;
        --m-warning: #c98411;  --m-warning-soft: rgba(240, 170, 60, .16); --m-warning-text: #ffcd7a;
        --m-info: #2f86c4;     --m-info-soft: rgba(110, 182, 238, .15);   --m-info-text: #93cdf6;
        --m-shadow: 0 1px 2px rgba(0, 0, 0, .35);
        --m-shadow-hover: 0 8px 24px rgba(0, 0, 0, .45);
        --m-focus: 0 0 0 3px rgba(255, 140, 90, .25);
        color-scheme: dark;
    }

:root /* ═════════════ Base ═════════════ */
    body, :root .tooltip, :root .popover, :root .modal, :root .dropdown-menu, :root input, :root select, :root textarea, :root button{
        font-family: 'Inter', 'Nunito', system-ui, -apple-system, 'Segoe UI', sans-serif;
    }
:root body{ color: var(--m-text); background: var(--m-bg); -webkit-font-smoothing: antialiased; font-size: .935rem; }
:root .content-wrapper, :root body.dark-mode .content-wrapper{ background: var(--m-bg); }
:root .content-wrapper > .content > .container-fluid{ max-width: 1480px; padding-left: 1.5rem; padding-right: 1.5rem; }
    @media (max-width: 576px) { .content-wrapper > .content > .container-fluid { padding-left: .9rem; padding-right: .9rem; } }
:root h1, :root h2, :root h3, :root h4, :root h5, :root h6{ letter-spacing: -.01em; }
:root a{ color: var(--m-brand-text); }
:root a:hover{ color: var(--m-brand-hover); }
:root hr{ border-color: var(--m-border); }
::selection{ background: rgba(181, 69, 27, .2); }
:root .text-muted{ color: var(--m-muted) !important; }
:root .text-body{ color: var(--m-text) !important; }
:root .text-dark{ color: var(--m-text) !important; }
:root .text-secondary{ color: var(--m-text-2) !important; }
:root .text-primary{ color: var(--m-brand-text) !important; }
:root .text-success{ color: var(--m-success-text) !important; }
:root .text-danger{ color: var(--m-danger-text) !important; }
:root .text-warning{ color: var(--m-warning-text) !important; }
:root .text-info{ color: var(--m-info-text) !important; }
:root .bg-white{ background-color: var(--m-surface) !important; }
:root .bg-light{ background-color: var(--m-surface-2) !important; color: var(--m-text); }
:root code{ color: var(--m-brand-text); background: var(--m-surface-3); padding: .1rem .35rem; border-radius: 5px; }

:root /* ═════════════ Menú lateral claro ═════════════ */
    .main-sidebar, :root .main-sidebar::before{ background: var(--m-surface) !important; }
:root .main-sidebar{ border-right: 1px solid var(--m-border); box-shadow: none !important; }
:root .layout-fixed .main-sidebar{ scrollbar-color: var(--m-border-strong) transparent; }
:root .layout-fixed .main-sidebar::-webkit-scrollbar-thumb{ background: var(--m-border-strong); }
:root .brand-link, :root .brand-link:hover{
        background: var(--m-surface) !important; color: var(--m-text) !important; border-bottom: 1px solid var(--m-border) !important;
        justify-content: flex-start !important; padding: .85rem 1.1rem !important; height: 57px;
    }
:root .brand-link > i.fa-bread-slice{
        width: 34px; height: 34px; border-radius: 10px; display: inline-flex; align-items: center; justify-content: center;
        background: linear-gradient(135deg, #c8562a, #8e3210); color: #fff; font-size: .95rem; margin-right: .15rem !important;
        box-shadow: 0 2px 6px rgba(181, 69, 27, .35);
    }
:root .brand-link .brand-text{ font-weight: 800 !important; font-size: 1.12rem; letter-spacing: -.02em; color: var(--m-text); }
:root .brand-link .brand-text::after{ content: 'Panadería'; display: block; font-size: .66rem; font-weight: 600; letter-spacing: .06em; text-transform: uppercase; color: var(--m-muted); margin-top: -.15rem; }

:root .nav-sidebar{ padding: .25rem .65rem !important; }
:root .nav-sidebar .nav-link, :root .sidebar-dark-primary .nav-sidebar .nav-link{
        color: var(--m-text-2) !important; font-weight: 500; padding: .5rem .7rem !important; border-radius: 9px; font-size: .9rem;
    }
:root .nav-sidebar .nav-link .nav-icon{ color: var(--m-muted); font-size: .95rem; }
:root .nav-sidebar .nav-link p{ font-size: .9rem !important; }
:root .nav-sidebar .nav-link:hover, :root .sidebar-dark-primary .nav-sidebar .nav-link:hover{
        background: var(--m-surface-3) !important; color: var(--m-text) !important;
    }
:root .nav-sidebar .nav-link:hover .nav-icon{ color: var(--m-text-2); }
:root .nav-sidebar .nav-link.active, :root .sidebar-dark-primary .nav-sidebar > .nav-item > .nav-link.active, :root .sidebar-dark-primary .nav-sidebar .nav-link.active{
        background: var(--m-brand-soft) !important; color: var(--m-brand-text) !important; font-weight: 700;
        box-shadow: inset 3px 0 0 var(--m-brand) !important;
    }
:root .nav-sidebar .nav-link.active .nav-icon{ color: var(--m-brand) !important; }
:root .nav-header-toggle, :root .nav-sidebar .nav-header{ color: var(--m-muted) !important; font-size: .68rem !important; letter-spacing: .08em !important; }
:root .nav-header-toggle:hover, :root .nav-section.open .nav-header-toggle{ background: transparent !important; color: var(--m-text) !important; }
:root .nav-sidebar .badge{ font-weight: 700; }
    @media (min-width: 992px) {
:root body.sidebar-collapse #menuLateral .nav-header-toggle{ background: var(--m-border) !important; }
:root body.sidebar-collapse .main-sidebar .brand-link{ justify-content: center !important; padding: .85rem 0 !important; }
:root body.sidebar-collapse .main-sidebar .brand-link > i.fa-bread-slice{ margin: 0 !important; font-size: .95rem; }
:root body.sidebar-collapse #menuLateral .nav-link.active{ box-shadow: none !important; }
        /* Menú contraído (botón ☰): solo íconos centrados, sin textos */
:root body.sidebar-collapse #menuLateral .nav-link{ padding: .62rem 0 !important; justify-content: center; }
:root body.sidebar-collapse #menuLateral .nav-link p{ font-size: 0 !important; }
:root body.sidebar-collapse #menuLateral .nav-link p .badge{ font-size: .58rem !important; }
:root body.sidebar-collapse #menuLateral .nav-link .nav-icon{ font-size: 1.08rem; color: var(--m-text-2); }
:root body.sidebar-collapse #menuLateral .nav-link.active .nav-icon{ color: var(--m-brand) !important; }
:root body.sidebar-collapse #menuLateral .nav-header-toggle{ font-size: 0 !important; padding: 0 !important; height: 1px; margin: .5rem .35rem !important; }
:root body.sidebar-collapse .nav-sidebar{ padding: .25rem 0 !important; }
:root body.sidebar-collapse .main-sidebar .brand-link .brand-text{ display: none !important; }
    }
:root .tooltip.menu-tip .tooltip-inner{ background: var(--m-text); color: var(--m-surface); }
:root .tooltip.menu-tip .arrow::before{ border-right-color: var(--m-text) !important; }
:root /* Ordenar menú (solo admin) sobre fondo claro */
    .menu-orden-btn{ border-color: var(--m-border-strong); color: var(--m-text-2); background: var(--m-surface); }
:root .menu-orden-btn:hover{ background: var(--m-surface-3); color: var(--m-text); }
:root .menu-orden-btn-ok, :root .menu-orden-btn-ok:hover{ background: var(--m-brand); border-color: var(--m-brand); color: #fff; }
:root .menu-orden-ayuda{ color: var(--m-brand-text); }
:root .menu-orden-check{ color: var(--m-text-2); }
:root body.menu-editando .menu-orden-bar{ background: var(--m-surface); box-shadow: 0 6px 10px -8px rgba(0, 0, 0, .25); }
:root body.menu-editando #menuLateral .nav-item > .nav-link, :root body.menu-editando #menuLateral .nav-header-toggle{ outline-color: var(--m-border-strong); }
:root .menu-grip{ color: var(--m-muted); }
:root .menu-arrastrando-clon > .nav-link, :root .menu-arrastrando-clon > .nav-header-toggle{ background: var(--m-surface) !important; color: var(--m-text) !important; }

:root /* ═════════════ Barra superior ═════════════ */
    .main-header.navbar, :root body.dark-mode .navbar-white{
        background: var(--m-surface) !important; border-bottom: 1px solid var(--m-border) !important; min-height: 57px; box-shadow: none;
    }
:root .main-header .nav-link, :root body.dark-mode .navbar-light .nav-link{ color: var(--m-text-2) !important; }
:root .main-header .nav-link:hover{ color: var(--m-text) !important; }
:root .nav-usuario{ border-radius: 10px; padding-left: .5rem !important; padding-right: .6rem !important; }
:root .nav-usuario:hover{ background: var(--m-surface-3); }
:root .nav-usuario-txt .nu-nombre{ color: var(--m-text); font-weight: 600; }
:root .nav-usuario-txt .nu-apodo{ color: var(--m-muted); }
:root .m-migas{ display: flex; align-items: center; gap: .35rem; margin: 0; padding: 0 .4rem; list-style: none; font-size: .86rem; min-width: 0; }
:root .m-migas li{ color: var(--m-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
:root .m-migas li + li::before{ content: '/'; margin-right: .35rem; color: var(--m-border-strong); }
:root .m-migas li.active{ color: var(--m-text); font-weight: 600; }
:root .m-migas a{ color: var(--m-muted) !important; }
:root .m-migas a:hover{ color: var(--m-text) !important; }
    @media (max-width: 767px) { .m-migas li:not(.active) { display: none; } .m-migas li + li::before { display: none; } }

:root /* ═════════════ Superficies: tarjetas, :root filas, :root tablas ═════════════ */
    .card, :root .list-row, :root .table-card, :root .filter-bar, :root .item-card, :root .rp-stat, :root .stat-card, :root .kpi-card, :root .cat-card, :root .rp-filter, :root .rp-caja, :root .inv-box, :root .cd-hist, :root .cd-cerrar, :root .cd-dia > *, :root .kd-motivo, :root .info-box, :root .small-box{
        background: var(--m-surface) !important; border: 1px solid var(--m-border) !important;
        box-shadow: var(--m-shadow) !important; color: var(--m-text);
    }
:root .card{ border-radius: var(--m-radius); }
:root .card-header{ background: var(--m-surface) !important; border-bottom: 1px solid var(--m-border) !important; color: var(--m-text) !important; }
:root .card-footer{ background: var(--m-surface-2) !important; border-top: 1px solid var(--m-border) !important; }
:root .card-body{ color: inherit; }
:root .list-row:hover, :root .item-card:hover, :root .clic-tarjeta:hover{ box-shadow: var(--m-shadow-hover) !important; }
:root .item-card:hover{ border-color: var(--m-border-strong) !important; }
:root .cd-cerrar{ border-left: 4px solid var(--m-success) !important; }
:root .rp-stat.acc-info{ border-left: 4px solid var(--m-info) !important; }
:root .rp-stat.acc-warn{ border-left: 4px solid var(--m-warning) !important; }
:root .rp-stat.acc-danger{ border-left: 4px solid var(--m-danger) !important; }
:root .rp-stat.acc-success{ border-left: 4px solid var(--m-success) !important; }
:root .rp-stat .rp-label, :root .lr-meta .lm-label, :root .filter-bar label.fb-label, :root .stat-card .label{ color: var(--m-muted) !important; }
:root .rp-stat .rp-value, :root .lr-title, :root .lr-amount, :root .lr-meta .lm-value, :root .row-title, :root .item-card-title, :root .page-toolbar h2, :root .stat-card .value, :root .cat-name, :root .kpi-card .kpi-title, :root .section-heading, :root .dash-greeting h2, :root .cat-toolbar h2{ color: var(--m-text) !important; }
:root .lr-subtitle, :root .row-subtitle, :root .page-toolbar p, :root .rp-stat .rp-sub, :root .cat-desc, :root .dash-greeting p, :root .cat-toolbar p, :root .empty-state, :root .item-card-stockrow, :root .pg-summary, :root .section-heading small{ color: var(--m-muted) !important; }
:root .page-toolbar h2{ font-size: 1.45rem; font-weight: 800; letter-spacing: -.02em; }
:root .item-card-footer{ border-top: 1px solid var(--m-border) !important; }
:root .item-card-cat{ color: var(--m-brand-text) !important; }
:root .item-card-price{ color: var(--m-success-text) !important; }
:root .stock-gauge .sg-track{ background: var(--m-surface-3) !important; }
:root .stock-gauge .sg-labels{ color: var(--m-muted) !important; }
:root .stock-gauge .sg-labels strong{ color: var(--m-text) !important; }

:root /* Tablas */
    .table{ color: var(--m-text); }
:root .table td, :root .table th{ border-color: var(--m-border) !important; }
:root .table thead th, :root .table-modern thead th, :root .rp-tabla thead th, :root .inv-tabla thead th{
        background: var(--m-surface-2) !important; color: var(--m-muted) !important; border-bottom: 1px solid var(--m-border) !important;
        font-size: .72rem; font-weight: 700; letter-spacing: .04em;
    }
:root .table-modern tbody tr:hover, :root .table-hover tbody tr:hover{ background: var(--m-surface-2) !important; color: inherit; }
:root .table-striped tbody tr:nth-of-type(odd){ background: var(--m-surface-2); }
:root .clic-fila:hover{ background-color: var(--m-surface-2) !important; }

:root /* ═════════════ Botones ═════════════ */
    .btn{ border-radius: 9px; font-weight: 600; letter-spacing: -.005em; transition: background-color .12s, border-color .12s, box-shadow .12s, color .12s; }
:root .btn:focus, :root .btn.focus{ box-shadow: var(--m-focus) !important; }
:root .btn-primary, :root .btn-primary:not(:disabled):not(.disabled):active{ background: var(--m-brand); border-color: var(--m-brand); color: #fff; }
:root .btn-primary:hover, :root .btn-primary:focus{ background: var(--m-brand-hover); border-color: var(--m-brand-hover); color: #fff; }
:root .btn-success, :root .btn-success:not(:disabled):not(.disabled):active{ background: var(--m-success); border-color: var(--m-success); color: #fff; }
:root .btn-success:hover, :root .btn-success:focus{ background: #156a3e; border-color: #156a3e; color: #fff; }
:root body.dark-mode .btn-success:hover{ background: #38b376; border-color: #38b376; }
:root .btn-danger{ background: var(--m-danger); border-color: var(--m-danger); color: #fff; }
:root .btn-danger:hover{ filter: brightness(.94); color: #fff; }
:root .btn-warning{ background: #f2a51a; border-color: #f2a51a; color: #2b1d00; }
:root .btn-warning:hover{ background: #e0950c; border-color: #e0950c; color: #2b1d00; }
:root .btn-info{ background: var(--m-info); border-color: var(--m-info); color: #fff; }
:root .btn-info:hover{ filter: brightness(.94); color: #fff; }
:root .btn-secondary{ background: var(--m-surface-3); border-color: var(--m-border-strong); color: var(--m-text-2); }
:root .btn-light, :root .btn-default, :root summary.btn-light{
        background: var(--m-surface) !important; border: 1px solid var(--m-border-strong) !important; color: var(--m-text) !important;
        box-shadow: 0 1px 0 rgba(0, 0, 0, .03);
    }
:root .btn-light:hover, :root .btn-default:hover, :root summary.btn-light:hover{ background: var(--m-surface-3) !important; color: var(--m-text) !important; }
:root .btn-outline-primary{ color: var(--m-brand-text); border-color: var(--m-brand); background: transparent; }
:root .btn-outline-primary:hover, :root .btn-outline-primary:not(:disabled):not(.disabled).active{ background: var(--m-brand); border-color: var(--m-brand); color: #fff; }
:root .btn-outline-secondary{ color: var(--m-text-2); border-color: var(--m-border-strong); }
:root .btn-outline-secondary:hover{ background: var(--m-surface-3); color: var(--m-text); border-color: var(--m-border-strong); }
:root .btn-outline-success{ color: var(--m-success-text); border-color: var(--m-success); }
:root .btn-outline-success:hover{ background: var(--m-success); color: #fff; }
:root .btn-outline-danger{ color: var(--m-danger-text); border-color: var(--m-danger); }
:root .btn-outline-danger:hover{ background: var(--m-danger); color: #fff; }
:root .btn-outline-warning{ color: var(--m-warning-text); border-color: #e0950c; }
:root .btn-outline-warning:hover{ background: #f2a51a; color: #2b1d00; }
:root .btn-outline-info{ color: var(--m-info-text); border-color: var(--m-info); }
:root .btn-outline-info:hover{ background: var(--m-info); color: #fff; }
:root .btn-link{ color: var(--m-brand-text); }
:root .btn-icon.btn-info, :root .btn-icon.btn-warning, :root .btn-icon.btn-danger, :root .btn-icon.btn-success, :root .btn-icon.btn-primary, :root .btn-icon.btn-secondary{ border: none; }
:root .btn-icon{ border-radius: 9px; }
:root .btn-icon.btn-info{ background: var(--m-info-soft); color: var(--m-info-text); }
:root .btn-icon.btn-warning{ background: var(--m-warning-soft); color: var(--m-warning-text); }
:root .btn-icon.btn-danger{ background: var(--m-danger-soft); color: var(--m-danger-text); }
:root .btn-icon.btn-success{ background: var(--m-success-soft); color: var(--m-success-text); }
:root .btn-icon.btn-primary{ background: var(--m-brand-soft); color: var(--m-brand-text); }
:root .btn-icon.btn-secondary{ background: var(--m-surface-3); color: var(--m-muted); }
:root .btn-icon.btn-info:hover{ background: var(--m-info); color: #fff; }
:root .btn-icon.btn-warning:hover{ background: #f2a51a; color: #2b1d00; }
:root .btn-icon.btn-danger:hover{ background: var(--m-danger); color: #fff; }
:root .btn-icon.btn-success:hover{ background: var(--m-success); color: #fff; }
:root .btn-icon.btn-primary:hover{ background: var(--m-brand); color: #fff; }

:root /* ═════════════ Formularios ═════════════ */
    .form-control, :root .custom-select, :root body.dark-mode .form-control{
        background-color: var(--m-surface); border: 1px solid var(--m-border-strong); color: var(--m-text); border-radius: 9px;
    }
:root .form-control:focus, :root .custom-select:focus, :root body.dark-mode .form-control:focus{
        background-color: var(--m-surface); border-color: var(--m-brand); box-shadow: var(--m-focus); color: var(--m-text);
    }
:root .form-control::placeholder, :root body.dark-mode .form-control::placeholder{ color: var(--m-muted); opacity: .85; }
:root .form-control:disabled, :root .form-control[readonly]{ background-color: var(--m-surface-2); }
:root body.dark-mode select.form-control:not([multiple]):not([size]){ background-color: var(--m-surface); }
:root .input-group-text, :root body.dark-mode .input-group-text{ background: var(--m-surface-2); border-color: var(--m-border-strong); color: var(--m-text-2); }
:root .custom-control-input:checked ~ .custom-control-label::before{ background-color: var(--m-brand); border-color: var(--m-brand); }
:root input[type=checkbox], :root input[type=radio]{ accent-color: var(--m-brand); }
:root label{ color: inherit; }

:root /* ═════════════ Etiquetas y estados ═════════════ */
    .badge-soft-success{ background: var(--m-success-soft) !important; color: var(--m-success-text) !important; }
:root .badge-soft-danger{ background: var(--m-danger-soft) !important;  color: var(--m-danger-text) !important; }
:root .badge-soft-warning{ background: var(--m-warning-soft) !important; color: var(--m-warning-text) !important; }
:root .badge-soft-info{ background: var(--m-info-soft) !important;    color: var(--m-info-text) !important; }
:root .badge-soft-primary{ background: var(--m-brand-soft) !important;   color: var(--m-brand-text) !important; }
:root .badge-soft-secondary{ background: var(--m-surface-3) !important; color: var(--m-text-2) !important; }
:root .badge-success{ background: var(--m-success); color: #fff; }
:root .badge-danger{ background: var(--m-danger); color: #fff; }
:root .badge-warning{ background: #f2a51a; color: #2b1d00; }
:root .badge-info{ background: var(--m-info); color: #fff; }
:root .badge-primary{ background: var(--m-brand); color: #fff; }
:root .badge-secondary{ background: var(--m-surface-3); color: var(--m-text-2); }
:root .badge-light, :root body.dark-mode .badge-light{ background: var(--m-surface-3); color: var(--m-text-2); }
:root .ic-badge .badge-soft-danger{ background: #e74c3c !important; color: #fff !important; }
:root .ic-badge .badge-soft-secondary{ background: rgba(15, 15, 25, .65) !important; color: #fff !important; }

:root /* ═════════════ Avisos ═════════════ */
    .alert{ border-radius: var(--m-radius); border: 1px solid transparent; }
:root .alert-success, :root body.dark-mode .alert-success{ background: var(--m-success-soft); border-color: rgba(26, 127, 75, .25); color: var(--m-success-text); }
:root .alert-danger, :root body.dark-mode .alert-danger{ background: var(--m-danger-soft);  border-color: rgba(194, 58, 43, .25); color: var(--m-danger-text); }
:root .alert-warning, :root body.dark-mode .alert-warning{ background: var(--m-warning-soft); border-color: rgba(178, 106, 0, .25); color: var(--m-warning-text); }
:root .alert-info, :root body.dark-mode .alert-info{ background: var(--m-info-soft);    border-color: rgba(31, 111, 168, .25); color: var(--m-info-text); }
:root .alert-secondary, :root .alert-light{ background: var(--m-surface-2); border-color: var(--m-border); color: var(--m-text-2); }
:root .alert .alert-link, :root .alert a{ color: inherit; text-decoration: underline; }
:root .alert code{ background: rgba(0, 0, 0, .06); color: inherit; }
:root body.dark-mode .alert code{ background: rgba(255, 255, 255, .1); }
:root .alert .close, :root body.dark-mode .close{ color: inherit; opacity: .6; text-shadow: none; }
:root .alert .close:hover, :root .close:hover{ opacity: 1; }
:root .clave-temporal .ct-clave{ background: var(--m-surface); color: var(--m-text); }

:root /* ═════════════ Modales, :root menús, :root pestañas, :root paginación ═════════════ */
    .modal-content, :root body.dark-mode .modal-content{ background: var(--m-surface); color: var(--m-text); border: 1px solid var(--m-border); border-radius: 16px; }
:root .modal-header, :root .modal-footer, :root body.dark-mode .modal-header, :root body.dark-mode .modal-footer{ border-color: var(--m-border); }
:root #detalleModal .modal-header, :root body.dark-mode #detalleModal .modal-header{ background: var(--m-surface); border-bottom-color: var(--m-border); }
:root #detalleModal .modal-title, :root body.dark-mode #detalleModal .modal-title{ color: var(--m-text); }
:root #detalleModal .modal-body, :root body.dark-mode #detalleModal .modal-body{ background: var(--m-bg); }
:root .modal-backdrop.show{ opacity: .45; }
:root .dropdown-menu, :root body.dark-mode .dropdown-menu{ background: var(--m-surface); border: 1px solid var(--m-border); border-radius: 12px; box-shadow: var(--m-shadow-hover); }
:root .dropdown-item, :root body.dark-mode .dropdown-item{ color: var(--m-text); }
:root .dropdown-item:hover, :root .dropdown-item:focus, :root body.dark-mode .dropdown-item:hover, :root #darkModeItem:hover, :root body.dark-mode #darkModeItem:hover{ background: var(--m-surface-3); color: var(--m-text); }
:root .dropdown-item.text-danger, :root .dropdown-item.text-danger:hover{ color: var(--m-danger-text) !important; }
:root .dropdown-divider, :root body.dark-mode .dropdown-divider{ border-color: var(--m-border); }
:root .nav-tabs, :root body.dark-mode .nav-tabs{ border-color: var(--m-border); }
:root .nav-tabs .nav-link, :root body.dark-mode .nav-tabs .nav-link{ color: var(--m-text-2); }
:root .nav-tabs .nav-link.active, :root body.dark-mode .nav-tabs .nav-link.active{ background: var(--m-surface); border-color: var(--m-border) var(--m-border) var(--m-surface); color: var(--m-text); }
:root .list-group-item, :root body.dark-mode .list-group-item{ background: var(--m-surface); border-color: var(--m-border); color: var(--m-text); }
:root .pg-item .pg-link, :root body.dark-mode .pg-item .pg-link{ background: var(--m-surface); border: 1px solid var(--m-border-strong); color: var(--m-text-2); }
:root .pg-item a.pg-link:hover, :root body.dark-mode .pg-item a.pg-link:hover{ border-color: var(--m-brand); color: var(--m-brand-text); background: var(--m-brand-soft); }
:root .pg-item.active .pg-link, :root body.dark-mode .pg-item.active .pg-link{ background: var(--m-brand); border-color: var(--m-brand); color: #fff; }
:root .pg-item.disabled .pg-link, :root body.dark-mode .pg-item.disabled .pg-link{ background: var(--m-surface-2); color: var(--m-muted); opacity: .7; }
:root .pg-item .pg-dots, :root body.dark-mode .pg-item .pg-link.pg-dots{ background: transparent; border: none; color: var(--m-muted); }
:root .pagination .page-link{ background: var(--m-surface); border-color: var(--m-border); color: var(--m-text-2); }
:root .pagination .page-item.active .page-link{ background: var(--m-brand); border-color: var(--m-brand); color: #fff; }

:root /* Buscadores con sugerencias */
    .sug-lista, :root body.dark-mode .sug-lista{ background: var(--m-surface); color: var(--m-text); border-color: var(--m-border); }
:root .sug-cab, :root .sug-pie, :root body.dark-mode .sug-cab, :root body.dark-mode .sug-pie{ background: var(--m-surface-2); border-color: var(--m-border); color: var(--m-muted); }
:root .sug-item .si-t, :root body.dark-mode .sug-item .si-t{ color: var(--m-text); }
:root .sug-item .si-s{ color: var(--m-muted); }
:root .sug-item:hover, :root .sug-item.activo, :root body.dark-mode .sug-item:hover, :root body.dark-mode .sug-item.activo{ background: var(--m-brand-soft); }
:root .sug-item .si-ic, :root body.dark-mode .sug-item .si-ic{ background: var(--m-surface-3); color: var(--m-brand-text); }

:root /* Pie de página */
    .main-footer, :root body.dark-mode .main-footer{ background: transparent; border-top: 1px solid var(--m-border); color: var(--m-muted); }

:root /* Interruptor de estado y del modo oscuro */
    .estado-switch .track{ background: var(--m-border-strong); }
:root .estado-switch .txt{ color: var(--m-muted) !important; }
:root .estado-switch.activa .track{ background: var(--m-success); }
:root .estado-switch.activa .txt{ color: var(--m-success-text) !important; }
:root .dark-switch input:checked + .dark-switch-slider{ background: var(--m-brand); }

:root /* Barra de paginación "Ver 12 / Todos", :root aviso del stock, :root tablas interactivas */
    .pp-ver{ background: var(--m-surface-3) !important; }
:root .pp-ver span{ color: var(--m-muted) !important; }
:root .pp-ver a{ color: var(--m-text-2) !important; }
:root .pp-ver a.activo{ background: var(--m-brand) !important; color: #fff !important; }
:root .pp-ver a:hover:not(.activo){ background: var(--m-surface) !important; }
:root .tb-barra .tb-buscar input, :root .tb-barra select{ background-color: var(--m-surface); border-color: var(--m-border-strong); color: var(--m-text); }
:root .empty-state i{ color: var(--m-border-strong); opacity: 1; }

    /* ═════════════ Pie de página: mismo color que la barra de arriba ═════════════ */
    :root .main-footer, :root body.dark-mode .main-footer {
        background: var(--m-surface) !important; border-top: 1px solid var(--m-border) !important; color: var(--m-text-2) !important;
    }

    /* ═════════════ Ordenar menú (modo edición) ═════════════ */
    :root body.menu-editando #menuLateral .nav-item > .nav-link { padding-left: 1.9rem !important; }
    :root body.menu-editando #menuLateral .nav-header-toggle { padding-left: 1.9rem !important; }
    :root body.menu-editando .menu-grip { left: .65rem; top: 50%; transform: translateY(-50%); }
    :root body.menu-editando .nav-section > .menu-grip { top: 1.05rem; }

    /* ═════════════ Se nota lo que se presiona ═════════════
       Al pasar el mouse: se levanta y marca el borde. Al presionar: baja y se oscurece un poco. */
    :root .btn:not(:disabled):not(.disabled):active, :root .btn-icon:not(:disabled):active { transform: translateY(1px) scale(.97); filter: brightness(.92); }
    :root .btn-light:hover, :root .btn-default:hover { border-color: var(--m-muted-2) !important; box-shadow: 0 2px 6px rgba(29, 27, 25, .1); }
    :root .btn-light:active, :root .btn-default:active { background: var(--m-border) !important; }
    :root .btn-icon:hover { box-shadow: 0 3px 8px rgba(29, 27, 25, .15); }
    :root .list-row:hover, :root .item-card:hover, :root .cat-card:hover, :root .stat-card:hover, :root .clic-tarjeta:hover,
    :root [data-href]:hover:not(tr):not(.clic-fila) {
        border-color: var(--m-border-strong) !important; box-shadow: var(--m-shadow-hover) !important;
    }
    :root .clic-tarjeta:active, :root [data-href]:active:not(tr):not(.clic-fila) { transform: translateY(0) scale(.99) !important; box-shadow: var(--m-shadow) !important; }
    :root .clic-fila:hover, :root tr[data-href]:hover { background-color: var(--m-surface-3) !important; box-shadow: inset 3px 0 0 var(--m-brand) !important; }
    :root .clic-fila:active, :root tr[data-href]:active { background-color: var(--m-border) !important; }
    :root .table-modern tbody tr:hover, :root .table-hover tbody tr:hover, :root .rp-tabla tbody tr:hover { background: var(--m-surface-3) !important; }
    :root a.inv-chip:hover, :root .kd-chip:hover, :root .sk-orden-op:hover, :root .cd-tabs a:hover, :root .perm-chip:hover { filter: brightness(.96); box-shadow: 0 2px 6px rgba(29, 27, 25, .1); }
    :root a.inv-chip:active, :root .kd-chip:active, :root .sk-orden-op:active, :root .cd-tabs a:active { transform: scale(.97); }
    :root .nav-sidebar .nav-link:active { transform: scale(.98); background: var(--m-border) !important; }
    :root .dropdown-item:active { background: var(--m-border) !important; color: var(--m-text) !important; }
    :root .form-control:hover:not(:focus):not(:disabled) { border-color: var(--m-muted-2); }
    /* Al usar el teclado (Tab), un anillo naranja muestra dónde estás */
    :root a:focus-visible, :root button:focus-visible, :root [tabindex]:focus-visible, :root summary:focus-visible { outline: 2px solid var(--m-brand); outline-offset: 2px; }
    body.dark-mode .btn-light:active, body.dark-mode .btn-default:active { background: var(--m-surface-3) !important; }
</style>
