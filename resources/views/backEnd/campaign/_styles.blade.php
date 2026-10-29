<link href="{{asset('public/backEnd')}}/assets/libs/summernote/summernote-lite.min.css" rel="stylesheet" type="text/css" />
<link href="{{asset('public/backEnd')}}/assets/libs/select2/css/select2.min.css" rel="stylesheet" type="text/css" />
<link href="{{asset('public/backEnd')}}/assets/libs/flatpickr/flatpickr.min.css" rel="stylesheet" type="text/css" />
<style>
    .lp-section-card { border: none; border-radius: 14px; box-shadow: 0 2px 12px rgba(15,23,42,.06); margin-bottom: 1.25rem; overflow: hidden; }
    .lp-section-card .card-header { background: #fff; border-bottom: 1px solid #f1f5f9; font-weight: 700; font-size: .92rem; padding: .9rem 1.25rem; display: flex; align-items: center; gap: .5rem; }
    .lp-section-card .card-header i { color: #4f46e5; }
    .lp-section-card .card-body { padding: 1.25rem; }
    .lp-section-card .form-label { font-weight: 600; font-size: .82rem; color: #475569; }
    .lp-section-card .form-control, .lp-section-card .form-select { border-radius: 8px; border-color: #e2e8f0; }
    .lp-section-card .form-control:focus { border-color: #6366f1; box-shadow: 0 0 0 .2rem rgba(99,102,241,.12); }
    .img-preview-thumb { width: 100%; height: 90px; object-fit: cover; border-radius: 8px; border: 1px solid #e2e8f0; margin-top: .4rem; background: #f8fafc; }
    .field-hint { font-size: .72rem; color: #94a3b8; margin-top: .25rem; }
    .review-thumb-wrap { display: inline-block; position: relative; margin-right: .5rem; margin-bottom: .5rem; }
    .review-thumb-wrap img { width: 80px; height: 80px; object-fit: cover; border-radius: 8px; border: 1px solid #e2e8f0; }
    .review-thumb-wrap .del { position: absolute; top: -6px; right: -6px; width: 20px; height: 20px; border-radius: 50%; background: #ef4444; color: #fff; font-size: 11px; line-height: 20px; text-align: center; text-decoration: none; }
    /* Status switch */
    .switch { position: relative; display: inline-block; width: 48px; height: 26px; }
    .switch input { opacity: 0; width: 0; height: 0; }
    .switch .slider { position: absolute; cursor: pointer; inset: 0; background: #cbd5e1; transition: .3s; border-radius: 26px; }
    .switch .slider:before { content: ""; position: absolute; height: 20px; width: 20px; left: 3px; bottom: 3px; background: #fff; border-radius: 50%; transition: .3s; box-shadow: 0 1px 4px rgba(0,0,0,.2); }
    .switch input:checked + .slider { background: #22c55e; }
    .switch input:checked + .slider:before { transform: translateX(22px); }
    .sec-toggle-row { display: flex; justify-content: space-between; align-items: center; border: 1px solid #e2e8f0; border-radius: 10px; padding: .65rem .9rem; margin-bottom: .6rem; background: #f8fafc; }
    .sec-toggle-row .sec-label { font-weight: 600; font-size: .84rem; color: #334155; }
    /* Collapsible template section */
    .lp-collapsible .lp-collapsible-head { cursor: pointer; }
    .lp-collapsible .lp-collapsible-toggle { display: inline-flex; align-items: center; gap: .5rem; }
    .lp-collapsible .lp-collapsible-toggle i { color: #4f46e5; }
    .lp-collapsible .lp-collapse-arrow { transition: transform .25s ease; }
    .lp-collapsible.open .lp-collapse-arrow { transform: rotate(180deg); }
    .lp-collapsible .lp-section-switch { cursor: pointer; }
    .lp-collapsible .lp-section-switch input { pointer-events: none; }
    .lp-header-right { gap: .5rem; }
    .lp-number-badge { display: inline-flex; align-items: center; justify-content: center; width: 22px; height: 22px; border-radius: 50%; background: #4f46e5; color: #fff; font-size: .72rem; font-weight: 700; }
</style>
