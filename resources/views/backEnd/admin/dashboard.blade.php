@extends('backEnd.layouts.master')
@section('title','Dashboard')

@section('css')
<link href="https://cdn.jsdelivr.net/npm/apexcharts@3.35.5/dist/apexcharts.css" rel="stylesheet">
<style>
:root {
    --card-shadow: 0 1px 3px rgba(0,0,0,0.04), 0 1px 2px rgba(0,0,0,0.03);
    --card-hover-shadow: 0 10px 40px rgba(0,0,0,0.08), 0 2px 8px rgba(0,0,0,0.04);
    --radius: 14px;
}
.stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(230px, 1fr)); gap: 1rem; margin-bottom: 1.5rem; }
.stat-grid.small { grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); }
.stat-card {
    background: #fff; border-radius: var(--radius); padding: 1.25rem 1.5rem;
    box-shadow: var(--card-shadow); transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    display: flex; align-items: center; gap: 1rem; border: 1px solid transparent;
}
.stat-card:hover { transform: translateY(-2px); box-shadow: var(--card-hover-shadow); border-color: #e2e8f0; }
.stat-card.compact { padding: 1rem 1.25rem; }
.stat-card .icon-box {
    width: 48px; height: 48px; border-radius: 12px;
    display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0;
}
.stat-card.compact .icon-box { width: 40px; height: 40px; font-size: 16px; }
.icon-blue   { background: #eff6ff; color: #3b82f6; }
.icon-green  { background: #f0fdf4; color: #22c55e; }
.icon-purple { background: #faf5ff; color: #a855f7; }
.icon-amber  { background: #fffbeb; color: #f59e0b; }
.icon-rose   { background: #fff1f2; color: #f43f5e; }
.icon-cyan   { background: #ecfeff; color: #06b6d4; }
.icon-indigo { background: #eef2ff; color: #6366f1; }
.icon-teal   { background: #f0fdfa; color: #14b8a6; }
.stat-card .stat-content { flex: 1; min-width: 0; }
.stat-card .stat-label { font-size: 0.75rem; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; }
.stat-card .stat-value { font-size: 1.5rem; font-weight: 800; color: #0f172a; line-height: 1.2; }
.stat-card.compact .stat-value { font-size: 1.2rem; }
.stat-card .stat-sub { font-size: 0.75rem; color: #64748b; margin-top: 2px; }
.stat-card .stat-sub.up { color: #16a34a; }
.welcome-section {
    display: flex; align-items: center; justify-content: space-between;
    flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem;
}
.welcome-section h3 { font-weight: 700; color: #1e293b; font-size: 1.4rem; margin: 0; }
.welcome-section p { color: #94a3b8; font-size: 0.85rem; margin: 2px 0 0 0; }
.date-badge { background: #f1f5f9; color: #64748b; padding: 0.45rem 1rem; border-radius: 50px; font-size: 0.8rem; font-weight: 600; }
.quick-actions { display: flex; gap: 0.5rem; flex-wrap: wrap; margin-bottom: 1.5rem; }
.quick-action-btn {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 0.5rem 1rem; border-radius: 10px; font-size: 0.8rem;
    font-weight: 600; text-decoration: none; transition: all 0.2s;
    border: 1px solid #e2e8f0; background: #fff; color: #475569;
}
.quick-action-btn:hover { background: #f8fafc; border-color: #cbd5e1; color: #1e293b; }
.chart-card {
    background: #fff; border-radius: var(--radius); padding: 1.25rem;
    box-shadow: var(--card-shadow); border: 1px solid #f1f5f9; height: 100%;
}
.chart-card .chart-header {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 1rem; padding-bottom: 0.75rem; border-bottom: 1px solid #f1f5f9;
}
.chart-card .chart-header h5 { font-size: 0.95rem; font-weight: 700; color: #1e293b; margin: 0; }
.dot { width: 8px; height: 8px; border-radius: 50%; display: inline-block; margin-right: 8px; }
.data-card {
    background: #fff; border-radius: var(--radius); padding: 1.25rem;
    box-shadow: var(--card-shadow); border: 1px solid #f1f5f9; height: 100%;
}
.data-card .data-header {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 1rem; padding-bottom: 0.75rem; border-bottom: 1px solid #f1f5f9;
}
.data-card .data-header h5 { font-size: 0.95rem; font-weight: 700; color: #1e293b; margin: 0; }
.table-modern { width: 100%; margin: 0; }
.table-modern th { background: #f8fafc; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b; padding: 0.6rem 0.75rem; border-bottom: 2px solid #e2e8f0; }
.table-modern td { padding: 0.6rem 0.75rem; vertical-align: middle; border-color: #f1f5f9; font-size: 0.85rem; color: #334155; }
.table-modern tbody tr { transition: background 0.15s; }
.table-modern tbody tr:hover { background: #f8fafc; }
.customer-mini { list-style: none; padding: 0; margin: 0; }
.customer-mini li { display: flex; align-items: center; gap: 10px; padding: 0.6rem 0; border-bottom: 1px solid #f1f5f9; }
.customer-mini li:last-child { border-bottom: none; }
.customer-mini .cust-avatar { width: 36px; height: 36px; border-radius: 50%; background: #eef2ff; color: #6366f1; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 14px; flex-shrink: 0; }
.customer-mini .cust-name { font-weight: 600; font-size: 0.85rem; color: #1e293b; }
.customer-mini .cust-phone { font-size: 0.75rem; color: #94a3b8; }
.badge-dot { display: inline-flex; align-items: center; gap: 5px; font-size: 0.75rem; font-weight: 600; padding: 0.3rem 0.75rem; border-radius: 50px; }
@media (max-width: 768px) { .stat-grid { grid-template-columns: repeat(2, 1fr); } .stat-card .stat-value { font-size: 1.2rem; } }
@media (max-width: 480px) { .stat-grid { grid-template-columns: 1fr; } }
</style>
@endsection

@section('content')
<div class="container-fluid py-3">

  <div class="welcome-section">
    <div>
      <h3>👋 {{ __('Welcome back') }}, {{ Auth::user()->name ?? 'Admin' }}</h3>
      <p>{{ __('Here\'s what\'s happening with your store today.') }}</p>
    </div>
    <div class="date-badge"><i class="far fa-calendar-alt me-1"></i> {{ now()->format('d M, Y') }}</div>
  </div>

  <div class="quick-actions">
    <a href="#" class="quick-action-btn"><i class="fas fa-plus-circle"></i> New Order</a>
    <a href="#" class="quick-action-btn"><i class="fas fa-box"></i> Add Product</a>
    <a href="#" class="quick-action-btn"><i class="fas fa-tag"></i> Add Coupon</a>
    <a href="#" class="quick-action-btn"><i class="fas fa-pen"></i> Write Blog</a>
    <a href="#" class="quick-action-btn"><i class="fas fa-coins"></i> Fund</a>
  </div>

  <div class="stat-grid">
    <div class="stat-card">
      <div class="icon-box icon-blue"><i class="fas fa-shopping-bag"></i></div>
      <div class="stat-content">
        <div class="stat-label">{{ __('Total Orders') }}</div>
        <div class="stat-value">{{ number_format($total_order ?? 0) }}</div>
        <div class="stat-sub up">+{{ $today_order ?? 0 }} today</div>
      </div>
    </div>
    <div class="stat-card">
      <div class="icon-box icon-green"><i class="fas fa-check-circle"></i></div>
      <div class="stat-content">
        <div class="stat-label">{{ __('Delivered') }}</div>
        <div class="stat-value">{{ number_format($total_delivery ?? 0) }}</div>
        <div class="stat-sub">{{ $last_week ?? 0 }} this week</div>
      </div>
    </div>
    <div class="stat-card">
      <div class="icon-box icon-amber"><i class="fas fa-coins"></i></div>
      <div class="stat-content">
        <div class="stat-label">{{ __('Today Profit') }}</div>
        <div class="stat-value">৳{{ number_format($today_profit ?? 0, 0) }}</div>
        <div class="stat-sub">Sales: ৳{{ number_format($today_sales ?? 0, 0) }}</div>
      </div>
    </div>
    <div class="stat-card">
      <div class="icon-box icon-rose"><i class="fas fa-chart-line"></i></div>
      <div class="stat-content">
        <div class="stat-label">{{ __('Expenses') }}</div>
        <div class="stat-value">৳{{ number_format($total_expenses ?? 0, 0) }}</div>
        <div class="stat-sub">Fund: ৳{{ number_format($fund_balance ?? 0, 0) }}</div>
      </div>
    </div>
  </div>

  <div class="stat-grid small">
    <div class="stat-card compact">
      <div class="icon-box icon-purple"><i class="fas fa-boxes"></i></div>
      <div class="stat-content">
        <div class="stat-label">{{ __('Products') }}</div>
        <div class="stat-value">{{ number_format($total_product ?? 0) }}</div>
      </div>
    </div>
    <div class="stat-card compact">
      <div class="icon-box icon-cyan"><i class="fas fa-users"></i></div>
      <div class="stat-content">
        <div class="stat-label">{{ __('Customers') }}</div>
        <div class="stat-value">{{ number_format($total_customer ?? 0) }}</div>
      </div>
    </div>
    <div class="stat-card compact">
      <div class="icon-box icon-indigo"><i class="fas fa-truck"></i></div>
      <div class="stat-content">
        <div class="stat-label">{{ __('Today Delivery') }}</div>
        <div class="stat-value">{{ $today_delivery ?? 0 }}</div>
      </div>
    </div>
    <div class="stat-card compact">
      <div class="icon-box icon-teal"><i class="fas fa-calendar-check"></i></div>
      <div class="stat-content">
        <div class="stat-label">{{ __('Last Month') }}</div>
        <div class="stat-value">{{ $last_month ?? 0 }}</div>
      </div>
    </div>
  </div>

  <div class="row g-3 mb-3">
    <div class="col-lg-8">
      <div class="chart-card">
        <div class="chart-header">
          <h5><span class="dot" style="background:#6366f1;"></span> {{ __('Sales Overview') }}</h5>
          <small class="text-muted">{{ __('Last 30 days (delivered)') }}</small>
        </div>
        <div id="salesChart" style="min-height: 300px;"></div>
      </div>
    </div>
    <div class="col-lg-4">
      <div class="chart-card">
        <div class="chart-header">
          <h5><span class="dot" style="background:#a855f7;"></span> {{ __('Categories') }}</h5>
        </div>
        <div id="categoryChart" style="min-height: 300px;"></div>
      </div>
    </div>
  </div>

  <div class="row g-3">
    <div class="col-lg-7">
      <div class="data-card">
        <div class="data-header">
          <h5><span class="dot" style="background:#3b82f6;"></span> {{ __('Recent Orders') }}</h5>
          <a href="#" class="text-decoration-none small fw-bold" style="color:#6366f1;">{{ __('View All') }} →</a>
        </div>
        <div class="table-responsive">
          <table class="table-modern">
            <thead>
              <tr><th>{{ __('Invoice') }}</th><th>{{ __('Customer') }}</th><th>{{ __('Amount') }}</th><th>{{ __('Status') }}</th><th>{{ __('Date') }}</th></tr>
            </thead>
            <tbody>
              @forelse($latest_order ?? [] as $order)
              <tr>
                <td class="fw-bold" style="color:#6366f1;">#{{ $order->invoice_id ?? $order->id }}</td>
                <td>{{ \Illuminate\Support\Str::limit($order->customer->name ?? 'Guest', 15) }}</td>
                <td class="fw-bold">৳{{ number_format($order->amount ?? 0, 0) }}</td>
                <td>
                  @php
                    $statusName = $order->status->name ?? ($order->order_status ?? 'Processing');
                    $statusSlug = $order->status->slug ?? '';
                    $badgeStyle = match(true) {
                      in_array($statusSlug, ['delivered','completed']) || $statusName === 'Delivered' => 'background:#dcfce7;color:#16a34a;',
                      in_array($statusSlug, ['pending','on-hold']) || $statusName === 'Pending' => 'background:#fef3c7;color:#d97706;',
                      in_array($statusSlug, ['shipped','processing','ready-to-ship']) => 'background:#dbeafe;color:#2563eb;',
                      in_array($statusSlug, ['cancelled','canceled','returned']) => 'background:#fee2e2;color:#dc2626;',
                      default => 'background:#f1f5f9;color:#64748b;',
                    };
                  @endphp
                  <span class="badge-dot" style="{{ $badgeStyle }}">{{ $statusName }}</span>
                </td>
                <td class="text-muted small">{{ optional($order->created_at)->format('d M') }}</td>
              </tr>
              @empty
              <tr><td colspan="5" class="text-center text-muted py-4">{{ __('No orders yet') }}</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <div class="col-lg-5">
      <div class="data-card">
        <div class="data-header">
          <h5><span class="dot" style="background:#a855f7;"></span> {{ __('Recent Customers') }}</h5>
          <a href="#" class="text-decoration-none small fw-bold" style="color:#a855f7;">{{ __('View All') }} →</a>
        </div>
        <ul class="customer-mini">
          @forelse($latest_customer ?? [] as $customer)
          <li>
            <div class="cust-avatar">{{ strtoupper(substr($customer->name ?? '?', 0, 1)) }}</div>
            <div>
              <div class="cust-name">{{ \Illuminate\Support\Str::limit($customer->name ?? 'Unknown', 20) }}</div>
              <div class="cust-phone">{{ $customer->phone ?? 'N/A' }}</div>
            </div>
          </li>
          @empty
          <li class="text-muted text-center py-3">{{ __('No customers yet') }}</li>
          @endforelse
        </ul>
      </div>
    </div>
  </div>

</div>
@endsection

@section('script')
<script src="https://cdn.jsdelivr.net/npm/apexcharts@3.35.5/dist/apexcharts.min.js"></script>
<script>
  (function() {
    var monthlyData = @json($monthly_sale ?? []);
    var categories = [], values = [];
    monthlyData.forEach(function(item) { categories.push(item.date); values.push(parseFloat(item.amount)); });
    categories.reverse(); values.reverse();
    new ApexCharts(document.querySelector('#salesChart'), {
      chart: { type: 'area', height: 320, toolbar: { show: false }, fontFamily: 'inherit' },
      series: [{ name: 'Sales (৳)', data: values }],
      xaxis: { categories: categories, labels: { style: { fontSize: '11px' } } },
      yaxis: { labels: { formatter: function(v) { return '৳'+(v/1000).toFixed(0)+'k'; } } },
      colors: ['#6366f1'],
      fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.3, opacityTo: 0.05 } },
      stroke: { curve: 'smooth', width: 2.5 },
      dataLabels: { enabled: false },
      grid: { borderColor: '#f1f5f9', strokeDashArray: 4 },
      tooltip: { y: { formatter: function(v) { return '৳'+v.toLocaleString(); } } }
    }).render();
  })();

  (function() {
    var catData = @json($categorySales ?? []);
    var labels = catData.map(function(c) { return c.category_name || 'Other'; });
    var series = catData.map(function(c) { return parseFloat(c.total_amount || 0); });
    new ApexCharts(document.querySelector('#categoryChart'), {
      chart: { type: 'donut', height: 320, fontFamily: 'inherit' },
      series: series, labels: labels,
      colors: ['#6366f1','#22c55e','#f59e0b','#f43f5e','#06b6d4','#a855f7','#14b8a6','#f97316'],
      legend: { position: 'bottom', fontSize: '12px' },
      plotOptions: { pie: { donut: { size: '55%' } } },
      dataLabels: { enabled: true, formatter: function(v, opts) { return opts.w.config.series[opts.seriesIndex] > 0 ? Math.round(v)+'%' : ''; } },
      tooltip: { y: { formatter: function(v) { return '৳'+v.toLocaleString(); } } },
      stroke: { width: 0 }
    }).render();
  })();
</script>
@endsection
