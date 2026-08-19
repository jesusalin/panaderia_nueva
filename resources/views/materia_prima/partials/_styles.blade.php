@push('styles')
<style>
    .mp-form-card { border: none; border-radius: 14px; box-shadow: 0 4px 24px rgba(0,0,0,.06); overflow: hidden; }
    .mp-form-header {
        background: linear-gradient(135deg, #1a1a2e 0%, #16213e 60%, #b5451b 100%);
        color: #fff; padding: 1.75rem 2rem; display: flex; align-items: center; gap: 1rem;
    }
    .mp-form-icon {
        width: 56px; height: 56px; border-radius: 14px; background: rgba(255,255,255,.15);
        border: 2px solid rgba(255,255,255,.3); display: flex; align-items: center; justify-content: center;
        font-size: 1.4rem; flex-shrink: 0;
    }
    .mp-form-header h5 { margin: 0; font-weight: 800; }
    .mp-form-header p { margin: 0; opacity: .75; font-size: .85rem; }
    .mp-form-body { padding: 1.5rem; }
    .mp-form-body .form-control { border-radius: 10px; border: 1.5px solid #e9ecef; }
    .mp-form-body .form-control:focus { border-color: #b5451b; box-shadow: 0 0 0 3px rgba(181,69,27,.12); }
    body.dark-mode .mp-form-body .form-control { background: #24243b; border-color: #33334d; color: #e4e4ef; }

    .section-label {
        display: block; font-weight: 800; font-size: .78rem; letter-spacing: .04em;
        text-transform: uppercase; color: #b5451b; margin: 1.75rem 0 .9rem;
        border-bottom: 2px solid #f4e9e3; padding-bottom: .5rem;
    }
    .section-label:first-child { margin-top: 0; }
    body.dark-mode .section-label { color: #ff9d6e; border-bottom-color: #33334d; }

    .repo-hint { font-size: .78rem; color: #8a8a9d; margin-top: .3rem; }
    body.dark-mode .repo-hint { color: #9a9ac0; }

    .preview-wrap { position: sticky; top: 1rem; }
    .preview-label { font-size: .76rem; font-weight: 800; color: #8a8a9d; text-transform: uppercase; letter-spacing: .03em; margin-bottom: .7rem; display: block; text-align: center; }

    .form-actions { display: flex; justify-content: flex-end; gap: .6rem; margin-top: 1.75rem; padding-top: 1.25rem; border-top: 1px solid #f0f0f0; }
    body.dark-mode .form-actions { border-top-color: #33334d; }
</style>
@endpush
