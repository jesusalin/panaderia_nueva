<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Panel') — Muruwasi</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- AdminLTE & Bootstrap -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6/css/all.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap">

    <style>
        :root { --main-sidebar-width: 270px; }
        body { font-family: 'Nunito', sans-serif; }
        .brand-link { background: #b5451b; display: flex; align-items: center; justify-content: center; gap: .5rem; }

        /* Sidebar */
        .main-sidebar { background: #1a1a2e; }
        .nav-sidebar { padding: 0 .5rem; }
        .nav-sidebar .nav-item { margin-bottom: 2px; }
        .nav-sidebar .nav-link {
            color: #c8c8d4; border-radius: 8px; padding: .65rem .8rem;
            display: flex; align-items: flex-start; gap: .3rem;
            transition: background .12s ease, color .12s ease;
        }
        .nav-sidebar .nav-link .nav-icon {
            width: 22px; text-align: center; margin-right: .5rem; flex-shrink: 0; margin-top: .1rem;
        }
        .nav-sidebar .nav-link p { margin: 0; line-height: 1.3; font-size: .92rem; white-space: normal; }

        /* Hover: sutil, distinto del estado activo */
        .nav-sidebar .nav-link:hover {
            background: rgba(255,255,255,.07) !important; color: #fff !important;
        }
        /* Activo: color de marca sólido + barra indicadora */
        .nav-sidebar .nav-link.active,
        .sidebar-dark-primary .nav-sidebar>.nav-item>.nav-link.active {
            background: #b5451b !important; color: #fff !important;
            box-shadow: inset 3px 0 0 #ffd8c2;
        }

        .nav-sidebar .nav-header {
            color: #7a7a9d; font-size: .68rem; letter-spacing: 1.2px; font-weight: 700;
            margin: 1.1rem .5rem .35rem; padding: 0;
        }
        .nav-sidebar .nav-item:first-child .nav-header,
        .nav-sidebar > .nav-header:first-child { margin-top: .4rem; }

        /* Secciones colapsables del sidebar */
        .nav-sidebar .nav-section { margin-top: .35rem; }
        .nav-header-toggle {
            display: flex; align-items: center; justify-content: space-between;
            color: #7a7a9d; font-size: .68rem; letter-spacing: 1.2px; font-weight: 700;
            padding: .6rem .8rem; border-radius: 8px; cursor: pointer;
            text-decoration: none; transition: background .12s ease, color .12s ease;
        }
        .nav-header-toggle:hover { background: rgba(255,255,255,.06); color: #c8c8d4; text-decoration: none; }
        .nav-header-toggle .chevron { font-size: .65rem; transition: transform .2s ease; }
        .nav-section.open .nav-header-toggle .chevron { transform: rotate(90deg); }
        .nav-section.open .nav-header-toggle { color: #c8c8d4; }
        .nav-section-body { list-style: none; margin: .15rem 0 0; padding: 0; }

        .card { border: none; box-shadow: 0 2px 10px rgba(0,0,0,.08); border-radius: 12px; }

        /* ===== Modal "Ver detalle": sin el borde duro de Bootstrap, con sombra suave
           y un fondo con leve contraste para que las tarjetas de adentro no se
           vean planas/vacías pegadas contra un fondo blanco idéntico. ===== */
        #detalleModal .modal-content { border: none; border-radius: 18px; box-shadow: 0 20px 60px rgba(20,20,30,.25); overflow: hidden; }
        #detalleModal .modal-header { background: #fff; border-bottom: 1px solid #eee; padding: 1.1rem 1.5rem; }
        #detalleModal .modal-title { font-weight: 800; color: #1a1a2e; }
        #detalleModal .modal-body { background: #f7f5f3; padding: 1.5rem; }
        body.dark-mode #detalleModal .modal-header { background: #1f1f33; }
        body.dark-mode #detalleModal .modal-title { color: #f0f0f7; }
        body.dark-mode #detalleModal .modal-body { background: #14141f; }
        .detalle-divider { border: none; border-top: 1px solid #f0ece6; margin: 1rem 0; }
        body.dark-mode .detalle-divider { border-top-color: #2c2c44; }
        .card-header { border-radius: 12px 12px 0 0 !important; font-weight: 700; }
        .btn { border-radius: 8px; font-weight: 600; }
        .badge { border-radius: 6px; }
        .small-box { border-radius: 12px; overflow: hidden; }
        .table thead th { border-top: none; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.5px; color: #6c757d; }
        .content-header h1 { font-weight: 700; }

        /* ===== Interruptor Modo oscuro ===== */
        .dark-switch { position: relative; display: inline-block; width: 40px; height: 22px; }
        .dark-switch input { opacity: 0; width: 0; height: 0; }
        .dark-switch-slider { position: absolute; cursor: pointer; inset: 0; background: #ccc; border-radius: 22px; transition: background .2s ease; }
        .dark-switch-slider::before { content: ""; position: absolute; height: 16px; width: 16px; left: 3px; top: 3px; background: #fff; border-radius: 50%; transition: transform .2s ease; box-shadow: 0 1px 3px rgba(0,0,0,.3); }
        .dark-switch input:checked + .dark-switch-slider { background: #b5451b; }
        .dark-switch input:checked + .dark-switch-slider::before { transform: translateX(18px); }
        #darkModeItem:hover { background: #f4f4f4; }

        /* ===== Modo oscuro ===== */
        body.dark-mode { color: #d5d5e2; }
        body.dark-mode .content-wrapper { background: #14141f; }
        body.dark-mode .card { background: #1f1f33; color: #e4e4ef; }
        body.dark-mode .card-header { background: #24243b; border-bottom: 1px solid #33334d; color: #e4e4ef; }
        body.dark-mode .card-body { color: #d5d5e2; }
        body.dark-mode .card-footer { background: #24243b; border-top: 1px solid #33334d; color: #d5d5e2; }
        body.dark-mode .table { color: #d5d5e2; background: transparent; }
        body.dark-mode .table thead th { color: #9a9ac0; border-bottom-color: #33334d; }
        body.dark-mode .table td, body.dark-mode .table th { border-color: #33334d; color: #d5d5e2; }
        body.dark-mode .table-hover tbody tr:hover { background: #24243b; color: #fff; }
        body.dark-mode .table-striped tbody tr:nth-of-type(odd) { background: #202032; }
        body.dark-mode .navbar-white { background: #1a1a2e !important; border-bottom: 1px solid #33334d; }
        body.dark-mode .navbar-light .nav-link { color: #e4e4ef !important; }
        body.dark-mode .content-header { background: transparent; }
        body.dark-mode .content-header h1,
        body.dark-mode h1, body.dark-mode h2, body.dark-mode h3,
        body.dark-mode h4, body.dark-mode h5, body.dark-mode h6 { color: #f0f0f7; }
        body.dark-mode p, body.dark-mode span, body.dark-mode label,
        body.dark-mode li, body.dark-mode td, body.dark-mode th,
        body.dark-mode strong, body.dark-mode small { color: #d5d5e2; }
        body.dark-mode .text-dark { color: #e4e4ef !important; }
        body.dark-mode .text-muted { color: #9a9ac0 !important; }
        body.dark-mode .text-secondary { color: #b0b0cc !important; }
        body.dark-mode a { color: #ff8f5e; }
        body.dark-mode a:hover { color: #ffab84; }
        body.dark-mode .breadcrumb-item a { color: #c8c8d4; }
        body.dark-mode .breadcrumb-item.active { color: #9a9ac0; }
        body.dark-mode .main-footer { background: #1a1a2e; color: #9a9ac0; border-top: 1px solid #33334d; }
        body.dark-mode .form-control { background: #24243b; border-color: #33334d; color: #e4e4ef; }
        body.dark-mode .form-control:focus { background: #24243b; color: #e4e4ef; }
        body.dark-mode .form-control::placeholder { color: #7a7a9d; }
        body.dark-mode select.form-control option { background: #24243b; color: #e4e4ef; }
        body.dark-mode .input-group-text { background: #24243b; border-color: #33334d; color: #d5d5e2; }
        body.dark-mode .modal-content { background: #1f1f33; color: #e4e4ef; }
        body.dark-mode .modal-header, body.dark-mode .modal-footer { border-color: #33334d; }
        body.dark-mode .close { color: #e4e4ef; text-shadow: none; }
        body.dark-mode .dropdown-menu { background: #1f1f33; border-color: #33334d; }
        body.dark-mode .dropdown-item { color: #e4e4ef; }
        body.dark-mode .dropdown-item:hover { background: #24243b; color: #fff; }
        body.dark-mode .dropdown-divider { border-color: #33334d; }
        body.dark-mode #darkModeItem:hover { background: #24243b; }
        body.dark-mode .small-box { background: #1f1f33; color: #e4e4ef; }
        body.dark-mode .small-box .small-box-footer { background: rgba(0,0,0,.2); color: #d5d5e2; }
        body.dark-mode .info-box { background: #1f1f33; color: #e4e4ef; box-shadow: 0 2px 10px rgba(0,0,0,.3); }
        body.dark-mode .info-box-text, body.dark-mode .info-box-number { color: #e4e4ef; }
        body.dark-mode .description-block .description-header { color: #f0f0f7; }
        body.dark-mode .description-block .description-text { color: #9a9ac0; }
        body.dark-mode hr { border-color: #33334d; }
        body.dark-mode .badge-light { background: #33334d; color: #e4e4ef; }
        body.dark-mode .pagination .page-link { background: #1f1f33; border-color: #33334d; color: #d5d5e2; }
        body.dark-mode .pagination .page-item.disabled .page-link { background: #191926; color: #6c6c8d; }
        body.dark-mode .pagination .page-item.active .page-link { background: #b5451b; border-color: #b5451b; color: #fff; }
        body.dark-mode .alert { color: #1a1a2e; }
        body.dark-mode .nav-tabs { border-color: #33334d; }
        body.dark-mode .nav-tabs .nav-link { color: #d5d5e2; }
        body.dark-mode .nav-tabs .nav-link.active { background: #1f1f33; border-color: #33334d #33334d #1f1f33; color: #fff; }

        /* Utilidades de Bootstrap que usan !important y ganaban al modo oscuro */
        body.dark-mode .bg-white { background-color: #1f1f33 !important; }
        body.dark-mode .bg-light { background-color: #24243b !important; color: #d5d5e2; }
        body.dark-mode .text-dark { color: #e4e4ef !important; }
        body.dark-mode .list-group-item { background: #1f1f33; border-color: #33334d; color: #d5d5e2; }

        /* ===== Impresión: solo el contenido, sin sidebar/navbar/botones ===== */
        @media print {
            .main-header, .main-sidebar, .content-header, .main-footer,
            .no-print, .alert, .cart-fab, .cart-overlay, .cart-drawer { display: none !important; }
            .wrapper { min-height: 0 !important; }
            .content-wrapper, .content, .container-fluid { padding: 0 !important; margin: 0 !important; }
            a[href]::after { content: "" !important; }

            /* Blanco y negro siempre, incluso si quedó en modo oscuro:
               un recibo con fondo oscuro y texto negro sería ilegible. */
            * { background: #fff !important; color: #000 !important; box-shadow: none !important; border-color: #ccc !important; }
            .badge-soft-success, .badge-soft-danger, .badge-soft-warning,
            .badge-soft-info, .badge-soft-secondary, .badge-soft-primary {
                background: #fff !important; border: 1px solid #999 !important;
            }
            .card, .table-card { border: 1px solid #ddd !important; }

            /* Si el detalle se abrió como modal (clic en "Ver detalle" desde un
               listado, sin recargar la página), la página de fondo NO debe
               imprimirse: solo el contenido del modal. */
            body.modal-open .wrapper { display: none !important; }
            .modal-backdrop { display: none !important; }
            .modal.show { position: static !important; }
            .modal-dialog { max-width: 100% !important; margin: 0 !important; }
            .modal-content { border: none !important; }
            #detalleModal .modal-header { display: none !important; }

            /* ===== Modo "Boleta / Ticket": para impresoras térmicas angostas
               (58-80mm), activado por JS (ver imprimirDetalle) antes de llamar a
               window.print(). Convierte cualquier detalle (venta, compra,
               producción) en un formato de una sola columna, compacto y sin
               decoración, en vez de ocupar una hoja A4 completa. ===== */
            html.modo-boleta .col-lg-8,
            html.modo-boleta .col-md-8,
            html.modo-boleta [class*="print-col"] {
                width: 100% !important; max-width: 100% !important; flex: 0 0 100% !important;
                padding: 0 !important; margin: 0 !important;
            }
            html.modo-boleta .prod-form-card { border: none !important; box-shadow: none !important; border-radius: 0 !important; margin: 0 !important; }
            html.modo-boleta [class$="-topbar"] { display: none !important; }
            html.modo-boleta [class$="-head"] {
                flex-direction: column !important; align-items: center !important; text-align: center !important;
                padding: .3rem 0 .5rem !important; gap: .2rem !important;
            }
            html.modo-boleta [class$="-head"] .ch-icon { display: none !important; }
            html.modo-boleta [class$="-head"] h5 { font-size: 13px !important; }
            html.modo-boleta [class$="-head"] p { font-size: 10px !important; }
            html.modo-boleta [class$="-head"] .badge-soft { font-size: 9px !important; margin-top: .2rem !important; }
            html.modo-boleta .info-strip {
                grid-template-columns: 1fr !important; gap: .15rem !important; padding: 0 .3rem .4rem !important;
            }
            html.modo-boleta .info-strip .is-item {
                border-left: none !important; padding-left: 0 !important; border-bottom: 1px dashed #ccc !important;
                display: flex !important; justify-content: space-between !important; align-items: baseline !important; padding-bottom: .1rem !important;
            }
            html.modo-boleta .info-strip .is-label { font-size: 9px !important; }
            html.modo-boleta .info-strip .is-value { font-size: 10px !important; }
            html.modo-boleta body, html.modo-boleta table { font-size: 10px !important; }
            html.modo-boleta .table-card { border: none !important; box-shadow: none !important; margin: 0 !important; }
            html.modo-boleta .table-modern th, html.modo-boleta .table-modern td { padding: .2rem .25rem !important; }
            html.modo-boleta .table-modern thead th { font-size: 8px !important; }
        }

        /* Componentes personalizados reutilizados en varias vistas (dashboard, catálogo, etc.) */
        body.dark-mode .dash-greeting h2,
        body.dark-mode .cat-toolbar h2,
        body.dark-mode .section-heading,
        body.dark-mode .cat-name,
        body.dark-mode .cat-count strong,
        body.dark-mode .stat-card .value,
        body.dark-mode .kpi-card .kpi-title { color: #f0f0f7; }

        body.dark-mode .dash-greeting p,
        body.dark-mode .cat-toolbar p,
        body.dark-mode .cat-desc,
        body.dark-mode .cat-count,
        body.dark-mode .stat-card .label,
        body.dark-mode .kpi-card .kpi-desc,
        body.dark-mode .section-heading small,
        body.dark-mode .empty-state { color: #9a9ac0; }

        body.dark-mode .stat-card,
        body.dark-mode .kpi-card,
        body.dark-mode .cat-card { background: #1f1f33; box-shadow: 0 2px 12px rgba(0,0,0,.3); }
        body.dark-mode .cat-card:hover { border-color: #b5451b; box-shadow: 0 6px 20px rgba(0,0,0,.4); }
        body.dark-mode .cat-meta { border-top-color: #33334d; }
        body.dark-mode .estado-switch .txt { color: #9a9ac0; }
        body.dark-mode .estado-switch.activa .txt { color: #6ee7a5; }
        body.dark-mode .kpi-card .kpi-ring { border-color: #33334d; }
        body.dark-mode .kpi-card .kpi-ring.neutral { color: #9a9ac0; }

        body.dark-mode .alert-banner.ok { background: rgba(46,204,113,.12); border-color: rgba(46,204,113,.3); color: #6ee7a5; }
        body.dark-mode .alert-banner.warn { background: rgba(243,156,18,.12); border-color: rgba(243,156,18,.3); color: #ffc673; }
        body.dark-mode .alert-banner .ab-text strong { color: inherit; }

        /* ══════════════════════════════════════════════════════
           KIT DE ESTILO COMPARTIDO — usado en todas las pantallas
           de listado (toolbar, badges suaves, botones de acción,
           tablas modernas, estados vacíos, barra de filtros)
           ══════════════════════════════════════════════════════ */

        /* Toolbar de cabecera de página */
        .page-toolbar { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: .75rem; margin-bottom: 1.5rem; }
        .page-toolbar h2 { font-weight: 800; margin: 0; color: #1a1a2e; font-size: 1.4rem; }
        .page-toolbar p { margin: .15rem 0 0; color: #8a8a9d; font-size: .88rem; }
        .page-toolbar .toolbar-actions { display: flex; gap: .5rem; flex-wrap: wrap; }
        body.dark-mode .page-toolbar h2 { color: #f0f0f7; }
        body.dark-mode .page-toolbar p { color: #9a9ac0; }

        /* Badges suaves (más legibles que los sólidos de Bootstrap) */
        .badge-soft { font-weight: 700; font-size: .74rem; padding: .35em .7em; border-radius: 20px; }
        .badge-soft-success { background: rgba(46,204,113,.14); color: #1e8e5a; }
        .badge-soft-danger  { background: rgba(231,76,60,.12);  color: #c0392b; }
        .badge-soft-warning { background: rgba(243,156,18,.14); color: #b9770e; }
        .badge-soft-info    { background: rgba(52,152,219,.14); color: #2170a3; }
        .badge-soft-secondary { background: #eef0f3; color: #6c757d; }
        .badge-soft-primary { background: rgba(181,69,27,.12); color: #b5451b; }
        body.dark-mode .badge-soft-success { background: rgba(46,204,113,.16); color: #6ee7a5; }
        body.dark-mode .badge-soft-danger  { background: rgba(231,76,60,.18);  color: #ff9b8f; }
        body.dark-mode .badge-soft-warning { background: rgba(243,156,18,.18); color: #ffc673; }
        body.dark-mode .badge-soft-info    { background: rgba(52,152,219,.18); color: #7ec3f5; }
        body.dark-mode .badge-soft-secondary { background: #2c2c44; color: #b0b0cc; }
        body.dark-mode .badge-soft-primary { background: rgba(181,69,27,.22); color: #ff9d6e; }

        /* Filas de lista (transaccional: compras, ventas, movimientos, kardex) */
        .list-rows { display: flex; flex-direction: column; gap: .6rem; }
        .list-row {
            background: #fff; border-radius: 12px; padding: .9rem 1.2rem; box-shadow: 0 2px 10px rgba(0,0,0,.04);
            display: flex; align-items: center; gap: 1rem; flex-wrap: wrap; transition: box-shadow .12s ease;
        }
        .list-row:hover { box-shadow: 0 4px 16px rgba(0,0,0,.08); }
        body.dark-mode .list-row { background: #1f1f33; box-shadow: 0 2px 10px rgba(0,0,0,.25); }
        body.dark-mode .list-row:hover { box-shadow: 0 4px 16px rgba(0,0,0,.4); }

        .lr-icon {
            width: 42px; height: 42px; border-radius: 11px; flex-shrink: 0;
            display: flex; align-items: center; justify-content: center; font-size: 1.05rem; color: #fff;
            background: linear-gradient(135deg, #1a1a2e, #b5451b);
        }
        .lr-icon.success { background: linear-gradient(135deg, #1e8e5a, #2ecc71); }
        .lr-icon.danger  { background: linear-gradient(135deg, #a93226, #e74c3c); }
        .lr-icon.warning { background: linear-gradient(135deg, #a1670c, #f39c12); }
        .lr-icon.info    { background: linear-gradient(135deg, #1c5d80, #3498db); }

        .lr-main { min-width: 180px; flex: 1 1 220px; }
        .lr-title { font-weight: 800; color: #1a1a2e; font-size: .92rem; }
        .lr-subtitle { font-size: .78rem; color: #8a8a9d; margin-top: .1rem; }
        body.dark-mode .lr-title { color: #f0f0f7; }
        body.dark-mode .lr-subtitle { color: #9a9ac0; }

        .lr-meta { display: flex; gap: 1.6rem; flex-wrap: wrap; }
        .lr-meta .lm-item { text-align: left; min-width: 80px; }
        .lr-meta .lm-label { font-size: .66rem; color: #adb5bd; text-transform: uppercase; letter-spacing: .03em; font-weight: 700; display: block; }
        .lr-meta .lm-value { font-size: .88rem; font-weight: 700; color: #1a1a2e; }
        body.dark-mode .lr-meta .lm-value { color: #e4e4ef; }

        .lr-side { margin-left: auto; display: flex; align-items: center; gap: .8rem; flex-shrink: 0; }
        .lr-amount { font-size: 1.05rem; font-weight: 800; color: #1a1a2e; }
        body.dark-mode .lr-amount { color: #f0f0f7; }

        @media (max-width: 767px) {
            .list-row { flex-direction: column; align-items: flex-start; }
            .lr-side { margin-left: 0; width: 100%; justify-content: space-between; }
        }

        /* Botones de acción circulares (editar / eliminar / ver / etc.) */
        .btn-icon {
            width: 32px; height: 32px; padding: 0; border-radius: 8px; border: none;
            display: inline-flex; align-items: center; justify-content: center;
            font-size: .82rem; transition: transform .12s ease, box-shadow .12s ease; flex-shrink: 0;
        }
        .btn-icon:hover { transform: translateY(-1px); }
        .btn-icon[disabled] { cursor: not-allowed; opacity: .4; transform: none; }
        .btn-icon.is-locked, .cat-actions .btn.is-locked { cursor: not-allowed; opacity: .45; }
        .btn-icon.is-locked:hover, .cat-actions .btn.is-locked:hover { transform: none; }
        .btn-icon-group { display: flex; gap: .35rem; align-items: center; justify-content: center; }

        /* Cuando el producto/categoría está inactivo, el botón Eliminar resalta en rojo */
        .is-inactive .btn-icon.btn-danger,
        .inactiva .btn-icon.btn-danger,
        .inactiva .cat-actions .btn-danger {
            background: #e74c3c; box-shadow: 0 0 0 3px rgba(231,76,60,.18);
        }
        .is-inactive .btn-icon.btn-danger:hover,
        .inactiva .cat-actions .btn-danger:hover { background: #c0392b; }

        /* Switch de estado activo/inactivo (usado en Categorías, Productos, etc.) */
        .estado-switch { display: flex; align-items: center; gap: .4rem; cursor: pointer; border: none; background: none; padding: 0; }
        .estado-switch .track {
            width: 34px; height: 18px; border-radius: 20px; background: #dee2e6; position: relative; transition: background .15s; flex-shrink: 0;
        }
        .estado-switch .track::after {
            content: ''; position: absolute; top: 2px; left: 2px; width: 14px; height: 14px; border-radius: 50%;
            background: #fff; transition: left .15s;
        }
        .estado-switch.activa .track { background: #2ecc71; }
        .estado-switch.activa .track::after { left: 18px; }
        .estado-switch .txt { font-size: .74rem; font-weight: 700; color: #8a8a9d; white-space: nowrap; }
        .estado-switch.activa .txt { color: #1e8e5a; }

        /* Tarjeta contenedora de tabla, con cabecera consistente */
        .table-card { background: #fff; border-radius: 14px; box-shadow: 0 2px 12px rgba(0,0,0,.05); overflow: hidden; }
        body.dark-mode .table-card { background: #1f1f33; box-shadow: 0 2px 12px rgba(0,0,0,.3); }
        .table-modern { margin-bottom: 0; }
        .table-modern thead th {
            background: #f7f5f3; border-top: none; border-bottom: 2px solid #efece8 !important;
            font-size: .72rem; text-transform: uppercase; letter-spacing: .04em; color: #8a8a9d; font-weight: 800;
            padding: .85rem 1rem;
        }
        .table-modern tbody td { padding: .8rem 1rem; vertical-align: middle; }
        .table-modern tbody tr { transition: background .1s ease; }
        .table-modern tbody tr:hover { background: #fbf7f4; }
        body.dark-mode .table-modern thead th { background: #24243b; border-bottom-color: #33334d !important; color: #9a9ac0; }
        body.dark-mode .table-modern tbody tr:hover { background: #24243b; }

        /* Icono/avatar circular al inicio de una fila (nombre de producto, insumo, etc.) */
        .row-icon {
            width: 38px; height: 38px; border-radius: 10px; flex-shrink: 0;
            background: linear-gradient(135deg, #1a1a2e, #b5451b); color: #fff;
            display: inline-flex; align-items: center; justify-content: center; font-size: 1rem;
        }
        .row-title { font-weight: 700; color: #1a1a2e; }
        .row-subtitle { font-size: .78rem; color: #8a8a9d; }
        body.dark-mode .row-title { color: #f0f0f7; }
        body.dark-mode .row-subtitle { color: #9a9ac0; }

        /* Estado vacío (reutilizable en toda pantalla de listado) */
        .empty-state { text-align: center; padding: 4rem 1rem; color: #adb5bd; }
        .empty-state i { font-size: 2.6rem; margin-bottom: .8rem; opacity: .5; display: block; }
        .empty-state p { margin: 0 0 1rem; }

        /* Barra de filtros/búsqueda */
        .filter-bar {
            background: #fff; border-radius: 14px; padding: 1rem 1.25rem; margin-bottom: 1.25rem;
            box-shadow: 0 2px 12px rgba(0,0,0,.05); display: flex; flex-wrap: wrap; gap: .6rem; align-items: center;
        }
        .filter-bar .form-control, .filter-bar select.form-control { border-radius: 8px; }
        .filter-bar label.fb-label { font-size: .76rem; font-weight: 700; color: #8a8a9d; text-transform: uppercase; letter-spacing: .03em; margin: 0 .2rem 0 0; }
        body.dark-mode .filter-bar { background: #1f1f33; box-shadow: 0 2px 12px rgba(0,0,0,.3); }
        body.dark-mode .filter-bar .fb-label { color: #9a9ac0; }

        /* Búsqueda con icono */
        .search-box { position: relative; }
        .search-box i { position: absolute; left: .8rem; top: 50%; transform: translateY(-50%); color: #adb5bd; font-size: .85rem; }
        .search-box input { padding-left: 2.1rem; border-radius: 8px; }

        /* ══════════════════════════════════════════════════════
           GRID DE TARJETAS — alternativa a table-modern para
           pantallas de catálogo (productos, insumos, etc.)
           ══════════════════════════════════════════════════════ */
        .card-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(255px, 1fr)); gap: 1.1rem; }

        .item-card {
            background: #fff; border-radius: 14px; overflow: hidden; box-shadow: 0 2px 12px rgba(0,0,0,.05);
            border: 1.5px solid transparent; transition: all .15s ease; display: flex; flex-direction: column;
        }
        .item-card:hover { box-shadow: 0 10px 26px rgba(0,0,0,.09); border-color: #f0dccd; transform: translateY(-2px); }
        .item-card.is-inactive { border-color: #f2d4d4; }
        .item-card.is-inactive .item-card-media { filter: grayscale(.3); }
        body.dark-mode .item-card { background: #1f1f33; box-shadow: 0 2px 12px rgba(0,0,0,.3); }
        body.dark-mode .item-card:hover { border-color: #45304f; }

        .item-card-media {
            height: 108px; background: linear-gradient(135deg, #1a1a2e, #b5451b); position: relative;
            display: flex; align-items: center; justify-content: center; color: rgba(255,255,255,.9); font-size: 2.1rem; overflow: hidden;
        }
        .item-card-media img { width: 100%; height: 100%; object-fit: cover; }
        .item-card-media .ic-badge { position: absolute; top: .55rem; right: .55rem; }
        /* El badge de "Stock bajo" va siempre sobre un fondo oscuro (foto o degradado del
           ícono), así que necesita colores propios y sólidos: los badge-soft normales usan
           fondo traslúcido + texto oscuro pensados para tarjetas blancas, y ahí quedaban
           casi invisibles tanto en modo claro como oscuro. */
        .ic-badge .badge-soft-danger {
            background: #e74c3c; color: #fff; box-shadow: 0 1px 5px rgba(0,0,0,.4);
        }
        .ic-badge .badge-soft-secondary {
            background: rgba(15,15,25,.65); color: #fff; box-shadow: 0 1px 5px rgba(0,0,0,.4);
        }
        body.dark-mode .ic-badge .badge-soft-danger { background: #ff5b48; color: #1a0a08; }
        body.dark-mode .ic-badge .badge-soft-secondary { background: rgba(0,0,0,.55); color: #f0f0f7; }

        .item-card-body { padding: 1rem 1.1rem .2rem; flex: 1; }
        .item-card-cat { font-size: .72rem; font-weight: 700; color: #b5451b; text-transform: uppercase; letter-spacing: .03em; }
        body.dark-mode .item-card-cat { color: #ff9d6e; }
        .item-card-title { font-weight: 800; color: #1a1a2e; font-size: 1rem; margin: .15rem 0 .5rem; line-height: 1.25; }
        body.dark-mode .item-card-title { color: #f0f0f7; }
        .item-card-price { font-size: 1.35rem; font-weight: 800; color: #1e8e5a; }
        body.dark-mode .item-card-price { color: #6ee7a5; }
        .item-card-price .ic-cost { font-size: .72rem; font-weight: 600; color: #adb5bd; margin-left: .4rem; }

        .item-card-stockrow { display: flex; align-items: center; justify-content: space-between; margin: .6rem 0 .9rem; font-size: .78rem; color: #6c757d; }
        body.dark-mode .item-card-stockrow { color: #9a9ac0; }

        .item-card-footer {
            padding: .7rem 1.1rem; border-top: 1px solid #f2f2f2; display: flex; align-items: center; justify-content: space-between;
        }
        body.dark-mode .item-card-footer { border-top-color: #33334d; }

        /* ══════════════════════════════════════════════════════
           PAGINACIÓN — reemplaza la vista Tailwind por defecto
           ══════════════════════════════════════════════════════ */
        .pg-nav { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: .8rem; margin-top: 1rem; }
        .pg-list { display: flex; align-items: center; gap: .3rem; list-style: none; margin: 0; padding: 0; }
        .pg-item .pg-link {
            display: flex; align-items: center; justify-content: center; min-width: 34px; height: 34px; padding: 0 .5rem;
            border-radius: 9px; font-size: .82rem; font-weight: 700; color: #495057; background: #fff;
            border: 1.5px solid #e9ecef; text-decoration: none; transition: all .12s ease;
        }
        .pg-item a.pg-link:hover { border-color: #b5451b; color: #b5451b; background: #fff7f3; }
        .pg-item.active .pg-link { background: #b5451b; border-color: #b5451b; color: #fff; }
        .pg-item.disabled .pg-link { color: #cfd4da; cursor: not-allowed; background: #f8f9fa; }
        .pg-item .pg-dots { border: none; background: transparent; }
        .pg-summary { font-size: .78rem; color: #8a8a9d; }
        body.dark-mode .pg-item .pg-link { background: #1f1f33; border-color: #33334d; color: #d5d5e2; }
        body.dark-mode .pg-item a.pg-link:hover { border-color: #ff9d6e; color: #ff9d6e; background: #24243b; }
        body.dark-mode .pg-item.active .pg-link { background: #b5451b; border-color: #b5451b; color: #fff; }
        body.dark-mode .pg-item.disabled .pg-link { background: #191927; color: #4a4a63; }
        body.dark-mode .pg-summary { color: #9a9ac0; }
        @media (max-width: 576px) { .pg-nav { justify-content: center; text-align: center; } }

        /* Medidor visual de nivel de stock (barra), usado en tarjetas de insumos */
        .stock-gauge { margin: .55rem 0 .8rem; }
        .stock-gauge .sg-track { height: 8px; border-radius: 6px; background: #eef0f3; overflow: hidden; }
        .stock-gauge .sg-fill { height: 100%; border-radius: 6px; transition: width .2s ease; }
        .stock-gauge .sg-fill.ok   { background: #2ecc71; }
        .stock-gauge .sg-fill.bajo { background: #e74c3c; }
        .stock-gauge .sg-labels { display: flex; justify-content: space-between; font-size: .72rem; color: #8a8a9d; margin-top: .35rem; }
        .stock-gauge .sg-labels strong { color: #1a1a2e; }
        body.dark-mode .stock-gauge .sg-track { background: #33334d; }
        body.dark-mode .stock-gauge .sg-labels { color: #9a9ac0; }
        body.dark-mode .stock-gauge .sg-labels strong { color: #f0f0f7; }
    </style>
    @stack('styles')
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">

    <!-- Navbar -->
    <nav class="main-header navbar navbar-expand navbar-white navbar-light">
        <ul class="navbar-nav">
            <li class="nav-item"><a class="nav-link" data-widget="pushmenu" href="#"><i class="fas fa-bars"></i></a></li>
        </ul>
        <ul class="navbar-nav ml-auto">
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" data-toggle="dropdown">
                    <i class="fas fa-user-circle mr-1"></i>
                    {{ auth()->user()->nombre ?? 'Usuario' }}
                </a>
                <div class="dropdown-menu dropdown-menu-right p-0" style="min-width: 230px; overflow: hidden;">
                    <div class="dropdown-item d-flex align-items-center justify-content-between py-2" id="darkModeItem" style="cursor: pointer;">
                        <span>
                            <i class="fas fa-moon mr-2" style="width: 16px;"></i>Modo oscuro
                        </span>
                        <label class="dark-switch mb-0">
                            <input type="checkbox" id="darkModeSwitch">
                            <span class="dark-switch-slider"></span>
                        </label>
                    </div>

                    <div class="dropdown-divider m-0"></div>

                    <form method="POST" action="{{ route('logout') }}" class="m-0">
                        @csrf
                        <button type="submit" class="dropdown-item text-danger py-2">
                            <i class="fas fa-sign-out-alt mr-2" style="width: 16px;"></i>Cerrar sesión
                        </button>
                    </form>
                </div>
            </li>
        </ul>
    </nav>

    <!-- Sidebar -->
    <aside class="main-sidebar sidebar-dark-primary elevation-4">
        <a href="{{ route('dashboard') }}" class="brand-link text-center">
            <i class="fas fa-bread-slice mr-2"></i>
            <span class="brand-text font-weight-bold">Muruwasi</span>
        </a>
        <div class="sidebar">
            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column" role="menu">

                    <li class="nav-item">
                        <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-tachometer-alt"></i>
                            <p>Dashboard</p>
                        </a>
                    </li>

                    @if(auth()->user()->hasModulo('catalogo'))
                    <li class="nav-item">
                        <a href="{{ route('categorias.index') }}" class="nav-link {{ request()->routeIs('categorias.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-tags"></i><p>Categorías</p>
                        </a>
                    </li>
                    @endif

                    @if(auth()->user()->hasModulo('catalogo') || auth()->user()->hasModulo('produccion'))
                    @php
                        // El módulo "Productos" agrupa Productos, Producción y Recetas.
                        // En vez de un desplegable con sub-ítems, es un único link: al entrar,
                        // la propia pantalla muestra pestañas para moverse entre esas secciones
                        // (ver partials/tabs-productos.blade.php).
                        $enProductos = request()->routeIs('productos.*', 'produccion.*');
                        $rutaProductos = auth()->user()->hasModulo('catalogo') ? 'productos.index' : 'produccion.index';
                    @endphp
                    <li class="nav-item">
                        <a href="{{ route($rutaProductos) }}" class="nav-link {{ $enProductos ? 'active' : '' }}">
                            <i class="nav-icon fas fa-bread-slice"></i><p>
                                Productos
                                @if($stockBajoProductosCount > 0)
                                    <span class="badge badge-danger right">{{ $stockBajoProductosCount }}</span>
                                @endif
                            </p>
                        </a>
                    </li>
                    @endif

                    @if(auth()->user()->hasModulo('inventario'))
                    @php $abierta = request()->routeIs('materia-prima.*') || request()->routeIs('movimientos.*') || request()->routeIs('kardex.*'); @endphp
                    <li class="nav-section {{ $abierta ? 'open' : '' }}">
                        <a href="#" class="nav-header-toggle">
                            <span>INVENTARIO</span>
                            <i class="fas fa-chevron-right chevron"></i>
                        </a>
                        <ul class="nav-section-body" style="{{ $abierta ? '' : 'display:none;' }}">
                            <li class="nav-item">
                                <a href="{{ route('materia-prima.index') }}" class="nav-link {{ request()->routeIs('materia-prima.*') ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-boxes"></i><p>
                                        Materia Prima
                                        @if($stockBajoMateriaPrimaCount > 0)
                                            <span class="badge badge-danger right">{{ $stockBajoMateriaPrimaCount }}</span>
                                        @endif
                                    </p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('movimientos.index') }}" class="nav-link {{ request()->routeIs('movimientos.*') ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-exchange-alt"></i><p>Movimientos de Materia Prima</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('kardex.index') }}" class="nav-link {{ request()->routeIs('kardex.*') ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-book-open"></i><p>Movimientos de Productos</p>
                                </a>
                            </li>
                        </ul>
                    </li>
                    @endif

                    @if(auth()->user()->hasModulo('compras'))
                    @php $abierta = request()->routeIs('proveedores.*') || request()->routeIs('compras.*') || request()->routeIs('ordenes-automaticas.*'); @endphp
                    <li class="nav-section {{ $abierta ? 'open' : '' }}">
                        <a href="#" class="nav-header-toggle">
                            <span>COMPRAS</span>
                            <i class="fas fa-chevron-right chevron"></i>
                        </a>
                        <ul class="nav-section-body" style="{{ $abierta ? '' : 'display:none;' }}">
                            <li class="nav-item">
                                <a href="{{ route('proveedores.index') }}" class="nav-link {{ request()->routeIs('proveedores.*') ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-truck"></i><p>Proveedores</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('compras.index') }}" class="nav-link {{ request()->routeIs('compras.*') ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-shopping-cart"></i><p>Compras</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('ordenes-automaticas.index') }}" class="nav-link {{ request()->routeIs('ordenes-automaticas.*') ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-robot"></i><p>Órdenes Automáticas</p>
                                </a>
                            </li>
                        </ul>
                    </li>
                    @endif

                    @if(auth()->user()->hasModulo('reportes'))
                    @php $abierta = request()->routeIs('tiempos-operacion.*'); @endphp
                    <li class="nav-section {{ $abierta ? 'open' : '' }}">
                        <a href="#" class="nav-header-toggle">
                            <span>REPORTES</span>
                            <i class="fas fa-chevron-right chevron"></i>
                        </a>
                        <ul class="nav-section-body" style="{{ $abierta ? '' : 'display:none;' }}">
                            <li class="nav-item">
                                <a href="{{ route('tiempos-operacion.index') }}" class="nav-link {{ request()->routeIs('tiempos-operacion.*') ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-stopwatch"></i><p>Tiempos por Operación</p>
                                </a>
                            </li>
                        </ul>
                    </li>
                    @endif

                    @if(auth()->user()->hasModulo('clientes'))
                    @php $abierta = request()->routeIs('clientes.*'); @endphp
                    <li class="nav-section {{ $abierta ? 'open' : '' }}">
                        <a href="#" class="nav-header-toggle">
                            <span>CLIENTES</span>
                            <i class="fas fa-chevron-right chevron"></i>
                        </a>
                        <ul class="nav-section-body" style="{{ $abierta ? '' : 'display:none;' }}">
                            <li class="nav-item">
                                <a href="{{ route('clientes.index') }}" class="nav-link {{ request()->routeIs('clientes.*') ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-users"></i><p>Clientes</p>
                                </a>
                            </li>
                        </ul>
                    </li>
                    @endif

                    @if(auth()->user()->hasModulo('ventas'))
                    @php $abierta = request()->routeIs('ventas.*'); @endphp
                    <li class="nav-section {{ $abierta ? 'open' : '' }}">
                        <a href="#" class="nav-header-toggle">
                            <span>VENTAS</span>
                            <i class="fas fa-chevron-right chevron"></i>
                        </a>
                        <ul class="nav-section-body" style="{{ $abierta ? '' : 'display:none;' }}">
                            <li class="nav-item">
                                <a href="{{ route('ventas.index') }}" class="nav-link {{ request()->routeIs('ventas.*') ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-cash-register"></i><p>Ventas</p>
                                </a>
                            </li>
                        </ul>
                    </li>
                    @endif

                    @if(auth()->user()->isAdmin())
                    @php $abierta = request()->routeIs('usuarios.*'); @endphp
                    <li class="nav-section {{ $abierta ? 'open' : '' }}">
                        <a href="#" class="nav-header-toggle">
                            <span>ADMINISTRACIÓN</span>
                            <i class="fas fa-chevron-right chevron"></i>
                        </a>
                        <ul class="nav-section-body" style="{{ $abierta ? '' : 'display:none;' }}">
                            <li class="nav-item">
                                <a href="{{ route('usuarios.index') }}" class="nav-link {{ request()->routeIs('usuarios.*') ? 'active' : '' }}">
                                    <i class="nav-icon fas fa-users"></i><p>Usuarios</p>
                                </a>
                            </li>
                        </ul>
                    </li>
                    @endif

                </ul>
            </nav>
        </div>
    </aside>

    <!-- Content -->
    <div class="content-wrapper">
        <section class="content" style="padding-top: 1.25rem;">
            <div class="container-fluid">

                {{-- Alertas --}}
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="fas fa-check-circle mr-2"></i>{{ session('success') }}
                        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                    </div>
                @endif
                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="fas fa-exclamation-triangle mr-2"></i>{{ session('error') }}
                        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                    </div>
                @endif
                @if($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                    </div>
                @endif

                @yield('content')
            </div>
        </section>
    </div>

    <footer class="main-footer text-center text-muted small">
        Sistema de Gestión &mdash; Muruwasi &copy; {{ date('Y') }}
    </footer>
</div>

{{-- Modal de confirmación reutilizable: cualquier <form class="js-confirm" data-confirm="mensaje">
     dispara este modal en vez del confirm() nativo del navegador. --}}
<div class="modal fade" id="confirmModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-body text-center pt-4 pb-2">
                <div class="mb-3" style="width:56px;height:56px;border-radius:50%;background:rgba(231,76,60,.12);display:flex;align-items:center;justify-content:center;margin:0 auto;">
                    <i class="fas fa-exclamation-triangle" style="color:#e74c3c;font-size:1.4rem;"></i>
                </div>
                <h5 class="font-weight-bold mb-2" id="confirmModalTitle">¿Estás seguro?</h5>
                <p class="text-muted mb-0" id="confirmModalText">Esta acción no se puede deshacer.</p>
            </div>
            <div class="modal-footer border-0 justify-content-center pb-4">
                <button type="button" class="btn btn-light px-4" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger px-4" id="confirmModalAccept">Sí, continuar</button>
            </div>
        </div>
    </div>
</div>

{{-- Modal informativo: para botones "bloqueados" (ej. Eliminar cuando el registro tiene
     historial). En vez de un <button disabled> mudo, el botón queda clickeable con la
     clase "js-blocked" y explica en este modal por qué no se puede hacer la acción. --}}
<div class="modal fade" id="blockedModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-body text-center pt-4 pb-2">
                <div class="mb-3" style="width:56px;height:56px;border-radius:50%;background:rgba(52,152,219,.12);display:flex;align-items:center;justify-content:center;margin:0 auto;">
                    <i class="fas fa-info-circle" style="color:#2170a3;font-size:1.4rem;"></i>
                </div>
                <h5 class="font-weight-bold mb-2" id="blockedModalTitle">No se puede eliminar</h5>
                <p class="text-muted mb-0" id="blockedModalText"></p>
            </div>
            <div class="modal-footer border-0 justify-content-center pb-4">
                <button type="button" class="btn btn-primary px-4" data-dismiss="modal">Entendido</button>
            </div>
        </div>
    </div>
</div>

{{-- Modal de "Ver detalle": se llena por AJAX con el fragmento que devuelve el
     controlador (compras, ventas, clientes, producción), así el usuario ve el
     detalle sin que la página se recargue. Los enlaces llevan la clase "js-ver-detalle". --}}
<div class="modal fade" id="detalleModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="detalleModalTitle">Detalle</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="detalleModalBody">
                <div class="text-center py-5 text-muted"><i class="fas fa-spinner fa-spin fa-2x"></i></div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.6/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
<script src="{{ asset('js/tiempos-operacion.js') }}"></script>

<script>
    (function () {
        document.querySelectorAll('.nav-header-toggle').forEach(function (toggle) {
            toggle.addEventListener('click', function (e) {
                e.preventDefault();
                const section = this.closest('.nav-section');
                const body = section.querySelector('.nav-section-body');
                const abrir = !section.classList.contains('open');
                section.classList.toggle('open', abrir);
                body.style.display = abrir ? 'block' : 'none';
            });
        });
    })();
</script>

<script>
    (function () {
        const STORAGE_KEY = 'panaderia_dark_mode';
        const body = document.body;
        const switchInput = document.getElementById('darkModeSwitch');
        const darkItem = document.getElementById('darkModeItem');

        // Aplicar preferencia guardada al cargar la página
        if (localStorage.getItem(STORAGE_KEY) === '1') {
            body.classList.add('dark-mode');
            if (switchInput) switchInput.checked = true;
        }

        function toggleDarkMode() {
            const activo = body.classList.toggle('dark-mode');
            localStorage.setItem(STORAGE_KEY, activo ? '1' : '0');
            if (switchInput) switchInput.checked = activo;
        }

        if (switchInput) {
            switchInput.addEventListener('click', function (e) {
                e.stopPropagation();
            });
            switchInput.addEventListener('change', function (e) {
                const activo = this.checked;
                body.classList.toggle('dark-mode', activo);
                localStorage.setItem(STORAGE_KEY, activo ? '1' : '0');
            });
        }

        // Permitir hacer clic en toda la fila (no solo en el switch) sin cerrar el menú
        if (darkItem) {
            darkItem.addEventListener('click', function (e) {
                if (e.target !== switchInput) {
                    e.preventDefault();
                    e.stopPropagation();
                    toggleDarkMode();
                }
            });
        }
    })();
</script>

<script>
    // Modal de confirmación reutilizable para formularios de eliminar/desactivar/etc.
    (function () {
        let formPendiente = null;
        document.addEventListener('submit', function (e) {
            const form = e.target;
            if (!form.classList || !form.classList.contains('js-confirm')) return;
            e.preventDefault();
            formPendiente = form;
            document.getElementById('confirmModalTitle').textContent = form.dataset.confirmTitle || '¿Estás seguro?';
            document.getElementById('confirmModalText').textContent = form.dataset.confirm || 'Esta acción no se puede deshacer.';
            $('#confirmModal').modal('show');
        });
        document.getElementById('confirmModalAccept').addEventListener('click', function () {
            $('#confirmModal').modal('hide');
            if (formPendiente) { formPendiente.submit(); formPendiente = null; }
        });
    })();
</script>

<script>
    // Botones "bloqueados" (ej. Eliminar cuando el registro tiene historial): en vez de un
    // <button disabled> que no responde al clic, este botón sí es clickeable y muestra
    // el motivo en el modal informativo.
    (function () {
        document.addEventListener('click', function (e) {
            const btn = e.target.closest('.js-blocked');
            if (!btn) return;
            e.preventDefault();
            document.getElementById('blockedModalTitle').textContent = btn.dataset.blockedTitle || 'No se puede realizar esta acción';
            document.getElementById('blockedModalText').textContent = btn.dataset.blockedMessage || '';
            $('#blockedModal').modal('show');
        });
    })();
</script>

<script>
    // "Ver detalle" por AJAX: en vez de navegar a la página de detalle (lo que
    // recarga todo el sitio), interceptamos el clic, pedimos el fragmento por
    // AJAX y lo mostramos en un modal. Si el enlace se abre en pestaña nueva,
    // con clic derecho, o si algo falla, sigue funcionando como enlace normal.
    // Usa fetch() nativo (no depende de que el bundle de Vite/axios haya
    // cargado) para que funcione siempre, incluso si "npm run build" no se
    // ejecutó todavía.
    (function () {
        const modalBody  = document.getElementById('detalleModalBody');
        const modalTitle = document.getElementById('detalleModalTitle');
        const LOADING_HTML = '<div class="text-center py-5 text-muted"><i class="fas fa-spinner fa-spin fa-2x"></i></div>';

        document.addEventListener('click', function (e) {
            const link = e.target.closest('.js-ver-detalle');
            if (!link) return;
            if (e.ctrlKey || e.metaKey || e.shiftKey) return; // dejar que abra en pestaña nueva si el usuario quiere

            e.preventDefault();
            modalTitle.textContent = link.dataset.tituloDetalle || 'Detalle';
            modalBody.innerHTML = LOADING_HTML;
            $('#detalleModal').modal('show');

            fetch(link.href, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'text/html',
                },
                credentials: 'same-origin',
            })
                .then(function (respuesta) {
                    if (!respuesta.ok) throw new Error('HTTP ' + respuesta.status);
                    return respuesta.text();
                })
                .then(function (html) {
                    modalBody.innerHTML = html;
                })
                .catch(function (error) {
                    modalBody.innerHTML = '<div class="alert alert-danger mb-0">No se pudo cargar el detalle (' + error.message + '). Intenta nuevamente.</div>';
                });
        });
    })();
</script>

<script>
    // Imprimir el detalle de una venta/compra/producción de dos formas:
    //  - 'a4': impresión normal, tal cual se ve en pantalla (impresora de oficina).
    //  - 'boleta': fuerza un ancho de página angosto (80mm, como una impresora
    //    térmica de tickets) y compacta el contenido a una sola columna.
    // Está en el layout (no en cada vista) porque el detalle a veces se carga
    // dentro del modal "Ver detalle" vía innerHTML, y las etiquetas <script>
    // inyectadas así no se ejecutan; una función global sí queda disponible.
    window.imprimirDetalle = function (modo) {
        document.documentElement.classList.remove('modo-boleta');
        const estiloPrevio = document.getElementById('boletaPrintStyle');
        if (estiloPrevio) estiloPrevio.remove();

        if (modo === 'boleta') {
            document.documentElement.classList.add('modo-boleta');
            const estilo = document.createElement('style');
            estilo.id = 'boletaPrintStyle';
            estilo.textContent = '@page { size: 80mm auto; margin: 3mm; }';
            document.head.appendChild(estilo);
        }

        window.print();
    };

    window.addEventListener('afterprint', function () {
        document.documentElement.classList.remove('modo-boleta');
        const estilo = document.getElementById('boletaPrintStyle');
        if (estilo) estilo.remove();
    });
</script>

@stack('scripts')
</body>
</html>
