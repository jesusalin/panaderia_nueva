@push('styles')
<style>
    .cat-form-card { border: none; border-radius: 14px; box-shadow: 0 4px 24px rgba(0,0,0,.06); overflow: hidden; }
    .cat-form-header {
        background: linear-gradient(135deg, #1a1a2e 0%, #16213e 60%, #b5451b 100%);
        color: #fff; padding: 1.75rem 2rem; display: flex; align-items: center; gap: 1rem;
    }
    .cat-form-icon {
        width: 56px; height: 56px; border-radius: 14px; background: rgba(255,255,255,.15);
        border: 2px solid rgba(255,255,255,.3); display: flex; align-items: center; justify-content: center;
        font-size: 1.4rem; flex-shrink: 0;
    }
    .cat-form-header h5 { margin: 0; font-weight: 800; }
    .cat-form-header p { margin: 0; opacity: .75; font-size: .85rem; }
    .cat-form-body { padding: 1.5rem; }
    .cat-form-body .form-control { border-radius: 10px; border: 1.5px solid #e9ecef; }
    .cat-form-body .form-control:focus { border-color: #b5451b; box-shadow: 0 0 0 3px rgba(181,69,27,.12); }
    .form-actions { display: flex; justify-content: flex-end; gap: .6rem; margin-top: 1.75rem; padding-top: 1.25rem; border-top: 1px solid #f0f0f0; }
</style>
@endpush
