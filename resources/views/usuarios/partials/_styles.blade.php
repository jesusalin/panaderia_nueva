@push('styles')
<style>
    .usuario-form-card { border: none; border-radius: 14px; box-shadow: 0 4px 24px rgba(0,0,0,.06); overflow: hidden; }
    .usuario-form-header {
        background: linear-gradient(135deg, #1a1a2e 0%, #16213e 60%, #b5451b 100%);
        color: #fff; padding: 1.75rem 2rem; display: flex; align-items: center; gap: 1rem;
    }
    .usuario-avatar {
        width: 56px; height: 56px; border-radius: 50%; background: rgba(255,255,255,.15);
        border: 2px solid rgba(255,255,255,.35); display: flex; align-items: center; justify-content: center;
        font-size: 1.4rem; font-weight: 800; flex-shrink: 0; transition: all .15s;
    }
    .usuario-form-header h5 { margin: 0; font-weight: 800; }
    .usuario-form-header p { margin: 0; opacity: .75; font-size: .85rem; }
    .usuario-form-body { padding: 1.5rem; }

    .section-label {
        display: block; font-weight: 800; font-size: .78rem; letter-spacing: .04em;
        text-transform: uppercase; color: #b5451b; margin: 1.75rem 0 .9rem;
        border-bottom: 2px solid #f4e9e3; padding-bottom: .5rem;
    }
    .section-label:first-child { margin-top: 0; }
    .section-label i { opacity: .8; }

    .form-control { border-radius: 10px; border: 1.5px solid #e9ecef; }
    .form-control:focus { border-color: #b5451b; box-shadow: 0 0 0 3px rgba(181,69,27,.12); }

    .field-warning {
        display: flex; gap: .6rem; align-items: flex-start; background: #fff8ec; border: 1px solid #f5deb3;
        color: #8a6215; border-radius: 10px; padding: .65rem .9rem; font-size: .8rem; margin-top: .4rem;
    }
    .field-warning i { margin-top: .15rem; }

    .password-wrap { position: relative; }
    .password-wrap .toggle-pass {
        position: absolute; right: .9rem; top: 50%; transform: translateY(-50%);
        cursor: pointer; color: #adb5bd;
    }
    .password-wrap .toggle-pass:hover { color: #495057; }

    .permisos-grid {
        display: grid; grid-template-columns: repeat(auto-fill, minmax(230px, 1fr)); gap: .75rem;
    }
    .permiso-card {
        position: relative; display: flex; align-items: center; gap: .75rem;
        border: 1.5px solid #e9ecef; border-radius: 12px; padding: .85rem 1rem;
        cursor: pointer; transition: all .15s; margin: 0; background: #fff;
    }
    .permiso-card:hover { border-color: #d4a98f; background: #fffaf7; }
    .permiso-card input { position: absolute; opacity: 0; pointer-events: none; }
    .permiso-icon {
        width: 36px; height: 36px; border-radius: 9px; background: #f1f1f4; color: #6c757d;
        display: flex; align-items: center; justify-content: center; flex-shrink: 0; transition: all .15s;
    }
    .permiso-texto { font-size: .87rem; font-weight: 600; color: #495057; flex: 1; }
    .permiso-check {
        width: 20px; height: 20px; border-radius: 50%; border: 1.5px solid #dee2e6;
        display: flex; align-items: center; justify-content: center; flex-shrink: 0; transition: all .15s;
    }
    .permiso-check i { font-size: .6rem; color: transparent; }
    .permiso-card input:checked ~ .permiso-check { background: #b5451b; border-color: #b5451b; }
    .permiso-card input:checked ~ .permiso-check i { color: #fff; }
    .permiso-card input:checked ~ .permiso-icon { background: #b5451b; color: #fff; }
    .permiso-card:has(input:checked) { border-color: #b5451b; background: #fff5f0; }

    .alert-admin-total {
        display: none; align-items: center; background: #eef2ff; color: #3b4fb8; border: 1px solid #d6ddfb;
        border-radius: 10px; padding: .7rem 1rem; font-size: .83rem; margin-top: .9rem;
    }

    .form-actions { display: flex; justify-content: flex-end; gap: .6rem; margin-top: 2rem; padding-top: 1.25rem; border-top: 1px solid #f0f0f0; }

    /* ===== Modo oscuro ===== */
    body.dark-mode .section-label { color: #ff9d6e; border-bottom-color: #33334d; }

    body.dark-mode .form-control { background: #24243b; border-color: #33334d; color: #e4e4ef; }
    body.dark-mode .form-control:focus { background: #24243b; }

    body.dark-mode .field-warning { background: rgba(243,156,18,.1); border-color: rgba(243,156,18,.3); color: #ffc673; }

    body.dark-mode .password-wrap .toggle-pass { color: #7a7a9d; }
    body.dark-mode .password-wrap .toggle-pass:hover { color: #d5d5e2; }

    body.dark-mode .permiso-card { background: #24243b; border-color: #33334d; }
    body.dark-mode .permiso-card:hover { border-color: #b5451b; background: #2a2438; }
    body.dark-mode .permiso-icon { background: #33334d; color: #b0b0cc; }
    body.dark-mode .permiso-texto { color: #d5d5e2; }
    body.dark-mode .permiso-check { border-color: #45455f; }
    body.dark-mode .permiso-card:has(input:checked) { background: rgba(181,69,27,.14); border-color: #b5451b; }

    body.dark-mode .alert-admin-total { background: rgba(59,79,184,.16); color: #a8b6ff; border-color: rgba(59,79,184,.35); }

    body.dark-mode .form-actions { border-top-color: #33334d; }
</style>
@endpush
