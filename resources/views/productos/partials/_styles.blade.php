@push('styles')
<style>
    .prod-form-card { border: none; border-radius: 14px; box-shadow: 0 4px 24px rgba(0,0,0,.06); overflow: hidden; }
    .prod-form-header {
        background: linear-gradient(135deg, #1a1a2e 0%, #16213e 60%, #b5451b 100%);
        color: #fff; padding: 1.75rem 2rem; display: flex; align-items: center; gap: 1rem;
    }
    .prod-form-icon {
        width: 56px; height: 56px; border-radius: 14px; background: rgba(255,255,255,.15);
        border: 2px solid rgba(255,255,255,.3); display: flex; align-items: center; justify-content: center;
        font-size: 1.4rem; flex-shrink: 0;
    }
    .prod-form-header h5 { margin: 0; font-weight: 800; }
    .prod-form-header p { margin: 0; opacity: .75; font-size: .85rem; }
    .prod-form-body { padding: 2rem; }
    .prod-form-body .form-control { border-radius: 10px; border: 1.5px solid #e9ecef; }
    .prod-form-body .form-control:focus { border-color: #b5451b; box-shadow: 0 0 0 3px rgba(181,69,27,.12); }
    body.dark-mode .prod-form-body .form-control { background: #24243b; border-color: #33334d; color: #e4e4ef; }

    .section-label {
        display: block; font-weight: 800; font-size: .78rem; letter-spacing: .04em;
        text-transform: uppercase; color: #b5451b; margin: 1.75rem 0 .9rem;
        border-bottom: 2px solid #f4e9e3; padding-bottom: .5rem;
    }
    .section-label:first-child { margin-top: 0; }
    body.dark-mode .section-label { color: #ff9d6e; border-bottom-color: #33334d; }

    .ganancia-box { background: #f7f5f3; border-radius: 10px; padding: .8rem 1rem; display: flex; justify-content: space-between; align-items: center; margin-top: .3rem; }
    body.dark-mode .ganancia-box { background: #24243b; }
    .ganancia-box .g-label { font-size: .78rem; color: #8a8a9d; font-weight: 700; }
    .ganancia-box .g-value { font-weight: 800; font-size: 1.1rem; }
    .ganancia-box .g-value.positivo { color: #1e8e5a; }
    .ganancia-box .g-value.negativo { color: #c0392b; }
    body.dark-mode .ganancia-box .g-value.positivo { color: #6ee7a5; }
    body.dark-mode .ganancia-box .g-value.negativo { color: #ff9b8f; }

    .img-upload { border: 2px dashed #e0d5cc; border-radius: 12px; padding: 1.1rem; text-align: center; cursor: pointer; transition: .15s; position: relative; }
    .img-upload:hover { border-color: #b5451b; background: #fffaf7; }
    .img-upload input[type=file] { position: absolute; inset: 0; opacity: 0; cursor: pointer; }
    .img-upload i { font-size: 1.5rem; color: #b5451b; }
    .img-upload .iu-text { font-size: .8rem; color: #8a8a9d; margin-top: .35rem; }
    body.dark-mode .img-upload { border-color: #33334d; }
    body.dark-mode .img-upload:hover { background: #24243b; }

    .preview-wrap { position: sticky; top: 1rem; }
    .preview-label { font-size: .76rem; font-weight: 800; color: #8a8a9d; text-transform: uppercase; letter-spacing: .03em; margin-bottom: .7rem; display: block; text-align: center; }

    .form-actions { display: flex; justify-content: flex-end; gap: .6rem; margin-top: 1.75rem; padding-top: 1.25rem; border-top: 1px solid #f0f0f0; }
    body.dark-mode .form-actions { border-top-color: #33334d; }
</style>
@endpush
