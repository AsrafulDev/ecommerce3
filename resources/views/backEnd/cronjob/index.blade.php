@extends('backEnd.layouts.master')
@section('title', 'Cron Job ম্যানেজমেন্ট')

@section('css')
<style>
    .cron-card {
        border: none;
        border-radius: 16px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.06);
        background: #fff;
        overflow: hidden;
        border: 1px solid #f1f5f9;
        transition: box-shadow .3s;
    }
    .cron-card:hover { box-shadow: 0 8px 32px rgba(0,0,0,0.10); }

    .cron-header {
        padding: 20px 24px 16px;
        border-bottom: 1px solid #f1f5f9;
        display: flex;
        align-items: center;
        gap: 16px;
    }
    .cron-icon {
        width: 52px; height: 52px;
        border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        font-size: 22px;
        flex-shrink: 0;
    }
    .cron-icon.courier { background: linear-gradient(135deg,#667eea,#764ba2); color:#fff; }
    .cron-body  { padding: 20px 24px; }
    .cron-footer{ padding: 14px 24px; background: #f8fafc; border-top: 1px solid #f1f5f9; }

    /* Toggle Switch */
    .switch { position:relative; display:inline-block; width:52px; height:28px; }
    .switch input { opacity:0; width:0; height:0; }
    .slider {
        position:absolute; cursor:pointer; inset:0;
        background:#ccc; border-radius:28px;
        transition:.3s;
    }
    .slider:before {
        content:""; position:absolute;
        height:22px; width:22px;
        left:3px; bottom:3px;
        background:#fff; border-radius:50%;
        transition:.3s;
        box-shadow: 0 1px 4px rgba(0,0,0,.2);
    }
    input:checked + .slider { background: #28a745; }
    input:checked + .slider:before { transform: translateX(24px); }

    /* Status badge */
    .run-badge {
        display:inline-flex; align-items:center; gap:5px;
        padding:4px 12px; border-radius:20px;
        font-size:12px; font-weight:600;
    }
    .run-badge.success { background:#d1fae5; color:#065f46; }
    .run-badge.failed  { background:#fee2e2; color:#991b1b; }
    .run-badge.running { background:#fef3c7; color:#92400e; }
    .run-badge.none    { background:#f1f5f9; color:#64748b; }

    /* Stat box */
    .stat-box {
        background:#f8fafc;
        border:1px solid #e8edf3;
        border-radius:10px;
        padding:12px 16px;
        text-align:center;
    }
    .stat-box .stat-num { font-size:22px; font-weight:700; line-height:1; }
    .stat-box .stat-label { font-size:11px; color:#64748b; margin-top:4px; }

    /* Frequency select */
    .freq-select {
        border:1px solid #e2e8f0; border-radius:8px;
        padding:6px 10px; font-size:13px;
        background:#fff; cursor:pointer;
    }
    .freq-select:focus { outline:none; border-color:#667eea; box-shadow:0 0 0 2px rgba(102,126,234,.2); }

    /* Run Now btn */
    .btn-run-now {
        background:linear-gradient(135deg,#667eea,#764ba2);
        color:#fff; border:none;
        padding:8px 20px; border-radius:8px;
        font-size:13px; font-weight:600;
        cursor:pointer; transition:.2s;
        display:inline-flex; align-items:center; gap:6px;
    }
    .btn-run-now:hover { transform:translateY(-1px); box-shadow:0 4px 12px rgba(102,126,234,.4); }
    .btn-run-now:disabled { opacity:.6; cursor:not-allowed; transform:none; box-shadow:none; }

    /* Server cron info box */
    .cron-info-box {
        background:linear-gradient(135deg,#1e293b,#334155);
        border-radius:12px; padding:20px 24px; color:#e2e8f0;
    }
    .cron-info-box code {
        background:#0f172a; color:#38bdf8;
        padding:10px 16px; border-radius:8px;
        display:block; font-size:13px; margin:10px 0;
        border-left:3px solid #38bdf8;
        word-break: break-all;
    }
    .cron-info-box .note { font-size:12px; color:#94a3b8; }

    .spinner-border-sm { width:14px; height:14px; border-width:2px; }

    @keyframes pulse-dot { 0%,100%{opacity:1} 50%{opacity:.4} }
    .pulse-dot { animation: pulse-dot 1.2s ease-in-out infinite; }
</style>
@endsection

@section('content')
<div class="page-title-area">
    <div class="row align-items-center">
        <div class="col-sm-6">
            <div class="breadcrumbs-area clearfix">
                <h4 class="page-title pull-left">Cron Job ম্যানেজমেন্ট</h4>
                <ul class="breadcrumbs pull-left">
                    <li><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li><span>Cron Jobs</span></li>
                </ul>
            </div>
        </div>
    </div>
</div>

<div class="main-content-inner">
    <div class="row">

        @foreach($jobs as $job)
        <div class="col-lg-8 col-xl-7 mb-4">
            <div class="cron-card" id="card-job-{{ $job->id }}">

                {{-- Header --}}
                <div class="cron-header">
                    <div class="cron-icon courier">
                        <i class="fa fa-truck"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h5 class="mb-1 font-weight-bold" style="font-size:16px;">{{ $job->job_title }}</h5>
                        <p class="mb-0 text-muted" style="font-size:12px;">{{ $job->job_description }}</p>
                    </div>
                    {{-- Toggle --}}
                    <label class="switch mb-0" title="{{ $job->is_enabled ? 'বন্ধ করুন' : 'চালু করুন' }}">
                        <input type="checkbox" class="toggle-cron"
                               data-id="{{ $job->id }}"
                               {{ $job->is_enabled ? 'checked' : '' }}>
                        <span class="slider"></span>
                    </label>
                </div>

                {{-- Body --}}
                <div class="cron-body">

                    {{-- Stats row --}}
                    <div class="row mb-4">
                        <div class="col-4">
                            <div class="stat-box">
                                <div class="stat-num text-success" id="updated-{{ $job->id }}">{{ $job->last_updated_count ?? 0 }}</div>
                                <div class="stat-label">আপডেট হয়েছে</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="stat-box">
                                <div class="stat-num text-danger" id="failed-{{ $job->id }}">{{ $job->last_failed_count ?? 0 }}</div>
                                <div class="stat-label">ব্যর্থ হয়েছে</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="stat-box">
                                <div class="stat-num text-primary">{{ $job->frequency_minutes }}</div>
                                <div class="stat-label">মিনিট পর পর</div>
                            </div>
                        </div>
                    </div>

                    {{-- Last run info --}}
                    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                        <div>
                            <span class="text-muted" style="font-size:12px;">শেষ রান:</span>
                            <strong style="font-size:13px;" id="last-run-at-{{ $job->id }}">
                                {{ $job->last_run_at ? $job->last_run_at->format('d M Y, h:i A') : 'এখনও রান হয়নি' }}
                            </strong>
                        </div>
                        <span class="run-badge {{ $job->last_run_status ?? 'none' }}" id="status-badge-{{ $job->id }}">
                            @if($job->last_run_status === 'success')
                                <i class="fa fa-check-circle"></i> সফল
                            @elseif($job->last_run_status === 'failed')
                                <i class="fa fa-times-circle"></i> ব্যর্থ
                            @elseif($job->last_run_status === 'running')
                                <i class="fa fa-circle pulse-dot"></i> চলছে
                            @else
                                <i class="fa fa-clock-o"></i> কখনও চলেনি
                            @endif
                        </span>
                    </div>

                    {{-- Last result text --}}
                    @if($job->last_run_result)
                    <div class="alert alert-light py-2 px-3 mb-3" id="result-text-{{ $job->id }}" style="font-size:12px;border-radius:8px;">
                        <i class="fa fa-info-circle text-muted mr-1"></i>
                        {{ $job->last_run_result }}
                    </div>
                    @else
                    <div id="result-text-{{ $job->id }}" style="display:none;" class="alert alert-light py-2 px-3 mb-3" style="font-size:12px;border-radius:8px;"></div>
                    @endif

                    {{-- Settings row --}}
                    <div class="row align-items-end">
                        <div class="col-sm-4 mb-3 mb-sm-0">
                            <label class="d-block mb-1" style="font-size:12px;font-weight:600;">ফ্রিকোয়েন্সি (মিনিট)</label>
                            <select class="freq-select w-100" id="freq-{{ $job->id }}">
                                @foreach([1,2,5,10,15,30,60,120] as $min)
                                <option value="{{ $min }}" {{ $job->frequency_minutes == $min ? 'selected' : '' }}>
                                    {{ $min >= 60 ? ($min/60).' ঘণ্টা' : $min.' মিনিট' }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-sm-4 mb-3 mb-sm-0">
                            <label class="d-block mb-1" style="font-size:12px;font-weight:600;">অর্ডার লিমিট (প্রতিবার)</label>
                            <select class="freq-select w-100" id="limit-{{ $job->id }}">
                                @foreach([10,25,50,100,200,500] as $lim)
                                <option value="{{ $lim }}" {{ $job->order_limit == $lim ? 'selected' : '' }}>
                                    {{ $lim }} টি অর্ডার
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-sm-4 d-flex gap-2">
                            <button class="btn btn-sm btn-outline-secondary btn-save-settings flex-grow-1"
                                    data-id="{{ $job->id }}"
                                    style="border-radius:8px;font-size:13px;">
                                <i class="fa fa-save mr-1"></i> সেভ
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="cron-footer d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <span style="font-size:12px;color:#64748b;">
                        <i class="fa fa-clock-o mr-1"></i>
                        প্রতি <strong id="freq-display-{{ $job->id }}">{{ $job->frequency_minutes >= 60 ? ($job->frequency_minutes/60).' ঘণ্টা' : $job->frequency_minutes.' মিনিট' }}</strong> পর স্বয়ংক্রিয় রান
                    </span>
                    <button class="btn-run-now" id="btn-run-{{ $job->id }}" data-id="{{ $job->id }}">
                        <i class="fa fa-play-circle"></i>
                        এখনই রান করুন
                    </button>
                </div>
            </div>
        </div>
        @endforeach

        {{-- Server Cron Setup Guide --}}
        <div class="col-lg-4 col-xl-5 mb-4">
            <div class="cron-info-box">
                <h6 class="mb-2" style="color:#f8fafc;font-weight:700;">
                    <i class="fa fa-server mr-2"></i>Server Cron Setup
                </h6>
                <p class="note mb-2">cPanel / Hosting-এ নিচের কমান্ডটি যোগ করুন:</p>
                <code>* * * * * cd {{ base_path() }} && php artisan schedule:run >> /dev/null 2>&1</code>
                <p class="note mt-2 mb-0">
                    <i class="fa fa-info-circle mr-1"></i>
                    এই একটি cron লাইনই যথেষ্ট — বাকি সব schedule Laravel নিজেই পরিচালনা করবে।
                </p>

                <hr style="border-color:#334155;margin:16px 0;">

                <h6 class="mb-2" style="color:#f8fafc;font-weight:700;">
                    <i class="fa fa-question-circle mr-2"></i>কীভাবে কাজ করে?
                </h6>
                <ul class="note pl-3 mb-0" style="line-height:1.8;">
                    <li>অর্ডার courier-এ পাঠানো হলে status = <strong style="color:#38bdf8;">5</strong> হয়</li>
                    <li>Cron চললে courier API থেকে update নেওয়া হয়</li>
                    <li>Delivered → status = <strong style="color:#4ade80;">6</strong></li>
                    <li>Cancelled → status = <strong style="color:#f87171;">11</strong></li>
                    <li>"এখনই রান করুন" দিয়ে manual sync করা যাবে</li>
                </ul>

                <hr style="border-color:#334155;margin:16px 0;">

                <h6 class="mb-2" style="color:#f8fafc;font-weight:700;">
                    <i class="fa fa-terminal mr-2"></i>Manual Run (Terminal)
                </h6>
                <code>php artisan courier:check-status --limit=50</code>
                <code style="margin-top:6px;">php artisan courier:check-status --force</code>
            </div>
        </div>

    </div>
</div>
@endsection

@section('js')
<script>
(function() {
    const csrfToken = '{{ csrf_token() }}';

    // ── Toggle enable/disable ─────────────────────────────────
    document.querySelectorAll('.toggle-cron').forEach(function(el) {
        el.addEventListener('change', function() {
            const id = this.dataset.id;
            fetch('{{ url("admin/cron-jobs") }}/' + id + '/toggle', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
            })
            .then(r => r.json())
            .then(function(data) {
                if (data.success) {
                    showToast(data.message, 'success');
                }
            })
            .catch(function() { showToast('সমস্যা হয়েছে, আবার চেষ্টা করুন।', 'error'); });
        });
    });

    // ── Save settings ─────────────────────────────────────────
    document.querySelectorAll('.btn-save-settings').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const id   = this.dataset.id;
            const freq = document.getElementById('freq-'  + id).value;
            const lim  = document.getElementById('limit-' + id).value;

            btn.disabled = true;
            btn.innerHTML = '<i class="fa fa-spinner fa-spin mr-1"></i> সেভ হচ্ছে...';

            fetch('{{ url("admin/cron-jobs") }}/' + id + '/settings', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ frequency_minutes: freq, order_limit: lim })
            })
            .then(r => r.json())
            .then(function(data) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa fa-save mr-1"></i> সেভ';
                if (data.success) {
                    showToast(data.message, 'success');
                    // Update display
                    var dispEl = document.getElementById('freq-display-' + id);
                    if (dispEl) {
                        dispEl.textContent = freq >= 60 ? (freq/60)+' ঘণ্টা' : freq+' মিনিট';
                    }
                    // Update stat box
                    var statBox = document.querySelector('#card-job-' + id + ' .stat-num.text-primary');
                    if (statBox) statBox.textContent = freq;
                } else {
                    showToast('সেভ ব্যর্থ হয়েছে।', 'error');
                }
            })
            .catch(function() {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa fa-save mr-1"></i> সেভ';
                showToast('নেটওয়ার্ক সমস্যা।', 'error');
            });
        });
    });

    // ── Run Now ───────────────────────────────────────────────
    document.querySelectorAll('.btn-run-now').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm mr-1"></span> চলছে...';

            setBadge(id, 'running', '<i class="fa fa-circle pulse-dot"></i> চলছে');

            fetch('{{ url("admin/cron-jobs") }}/' + id + '/run-now', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
            })
            .then(r => r.json())
            .then(function(data) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa fa-play-circle"></i> এখনই রান করুন';

                if (data.success) {
                    showToast(data.message, 'success');
                    setBadge(id, data.last_run_status, getBadgeHtml(data.last_run_status));
                    if (data.last_run_at) {
                        var el = document.getElementById('last-run-at-' + id);
                        if (el) el.textContent = data.last_run_at;
                    }
                    if (data.last_run_result) {
                        var res = document.getElementById('result-text-' + id);
                        if (res) {
                            res.style.display = '';
                            res.innerHTML = '<i class="fa fa-info-circle text-muted mr-1"></i>' + data.last_run_result;
                        }
                    }
                    if (typeof data.updated_count !== 'undefined') {
                        var u = document.getElementById('updated-' + id);
                        if (u) u.textContent = data.updated_count;
                    }
                    if (typeof data.failed_count !== 'undefined') {
                        var f = document.getElementById('failed-' + id);
                        if (f) f.textContent = data.failed_count;
                    }
                } else {
                    showToast(data.message || 'রান ব্যর্থ হয়েছে।', 'error');
                    setBadge(id, 'failed', '<i class="fa fa-times-circle"></i> ব্যর্থ');
                }
            })
            .catch(function() {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa fa-play-circle"></i> এখনই রান করুন';
                setBadge(id, 'failed', '<i class="fa fa-times-circle"></i> ব্যর্থ');
                showToast('নেটওয়ার্ক সমস্যা।', 'error');
            });
        });
    });

    function setBadge(id, status, html) {
        var el = document.getElementById('status-badge-' + id);
        if (!el) return;
        el.className = 'run-badge ' + (status || 'none');
        el.innerHTML = html;
    }

    function getBadgeHtml(status) {
        if (status === 'success') return '<i class="fa fa-check-circle"></i> সফল';
        if (status === 'failed')  return '<i class="fa fa-times-circle"></i> ব্যর্থ';
        if (status === 'running') return '<i class="fa fa-circle pulse-dot"></i> চলছে';
        return '<i class="fa fa-clock-o"></i> কখনও চলেনি';
    }

    function showToast(msg, type) {
        if (typeof toastr !== 'undefined') {
            toastr[type === 'success' ? 'success' : 'error'](msg);
        } else {
            alert(msg);
        }
    }
})();
</script>
@endsection
