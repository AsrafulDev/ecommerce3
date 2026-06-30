@extends('backEnd.layouts.master')
@section('title','Sales Dashboard')

@section('css')
<link href="https://cdn.jsdelivr.net/npm/apexcharts@3.35.5/dist/apexcharts.css" rel="stylesheet">
<style>
/* ── Dashboard Hero Banner ── */
.dash-hero {
    background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
    border-radius: 16px;
    padding: 1.75rem 2rem;
    position: relative;
    overflow: hidden;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
}
.dash-hero::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -30%;
    width: 60%;
    height: 200%;
    background: radial-gradient(circle, rgba(255,255,255,0.04) 0%, transparent 70%);
    pointer-events: none;
}
.dash-hero::after {
    content: '';
    position: absolute;
    bottom: -40%;
    left: -10%;
    width: 40%;
    height: 150%;
    background: radial-gradient(circle, rgba(99,102,241,0.08) 0%, transparent 70%);
    pointer-events: none;
}
.dash-hero > * { position: relative; z-index: 1; }
.dash-hero h3 { color: #fff; font-weight: 700; font-size: 1.35rem; margin-bottom: 0.25rem; }
.dash-hero p { color: rgba(255,255,255,0.6); margin: 0; font-size: 0.85rem; }
.dash-hero .hero-profit { text-align: right; }
.dash-hero .hero-profit .profit-value {
    font-size: 1.8rem; font-weight: 800; color: #fff; line-height: 1.2;
}
.dash-hero .hero-profit .profit-label {
    font-size: 0.8rem; color: rgba(255,255,255,0.6);
    text-transform: uppercase; letter-spacing: 0.5px;
}

/* ── Dashboard Stat Cards ── */
.dash-card {
    background: #fff;
    border: none;
    border-radius: 16px;
    padding: 1.25rem 1.5rem;
    box-shadow: 0 2px 12px rgba(0,0,0,0.05), 0 1px 3px rgba(0,0,0,0.03);
    transition: all 0.3s cubic-bezier(0.4,0,0.2,1);
    position: relative;
    overflow: hidden;
    display: flex;
    align-items: center;
    gap: 1rem;
}
.dash-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 30px rgba(0,0,0,0.08), 0 2px 8px rgba(0,0,0,0.04);
}
.dash-card .card-icon {
    width: 52px; height: 52px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    flex-shrink: 0;
}
.dash-card .card-icon.orders { background: #ede9fe; color: #7c3aed; }
.dash-card .card-icon.fund { background: #dbeafe; color: #2563eb; }
.dash-card .card-icon.expenses { background: #fce4ec; color: #dc2626; }
.dash-card .card-icon.delivery { background: #dcfce7; color: #16a34a; }
.dash-card .card-info { flex: 1; min-width: 0; }
.dash-card .card-info .card-label {
    font-size: 0.8rem; color: #94a3b8;
    font-weight: 500; text-transform: uppercase; letter-spacing: 0.4px;
}
.dash-card .card-info .card-value {
    font-size: 1.6rem; font-weight: 800; color: #1a1a2e;
    line-height: 1.3;
}

/* ── Chart Box ── */
.chart-modern {
    background: #fff;
    border: none;
    border-radius: 16px;
    padding: 1.5rem;
    box-shadow: 0 2px 12px rgba(0,0,0,0.05), 0 1px 3px rgba(0,0,0,0.03);
    transition: all 0.3s ease;
}
.chart-modern:hover {
    box-shadow: 0 8px 25px rgba(0,0,0,0.07);
}
.chart-modern .chart-title {
    font-size: 1rem; font-weight: 700; color: #1a1a2e;
    margin-bottom: 1rem; padding-bottom: 0.75rem;
    border-bottom: 2px solid #f1f5f9;
    display: flex; align-items: center; gap: 8px;
}
.chart-modern .chart-title .title-dot {
    width: 8px; height: 8px; border-radius: 50%;
    flex-shrink: 0;
}
.chart-modern .chart-title .title-dot.purple { background: #7c3aed; }
.chart-modern .chart-title .title-dot.blue { background: #2563eb; }

/* ── Table Enhancements ── */
.table-modern { margin-bottom: 0; }
.table-modern thead th {
    background: #f8fafc;
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #64748b;
    border-bottom: 2px solid #e2e8f0;
    padding: 0.75rem 1rem;
}
.table-modern tbody td {
    padding: 0.75rem 1rem;
    vertical-align: middle;
    border-color: #f1f5f9;
    font-size: 0.875rem;
}
.table-modern tbody tr {
    transition: background 0.2s ease;
}
.table-modern tbody tr:hover {
    background: #f8fafc;
}

/* ── Customer List ── */
.customer-list { margin: 0; }
.customer-list .customer-item {
    padding: 0.7rem 0;
    border-bottom: 1px solid #f1f5f9;
    transition: all 0.2s ease;
}
.customer-list .customer-item:last-child { border-bottom: none; }
.customer-list .customer-item:hover {
    padding-left: 6px;
}
.customer-list .customer-item .cust-name {
    font-weight: 600; color: #1a1a2e; font-size: 0.9rem;
}
.customer-list .customer-item .cust-phone {
    font-size: 0.8rem; color: #94a3b8;
}

/* ── Badge refinements ── */
.badge-dash {
    font-size: 0.75rem;
    font-weight: 600;
    padding: 0.3rem 0.75rem;
    border-radius: 50px;
}
</style>
@endsection

@section('content')
<div class="container-fluid py-3">

  {{-- Header --}}
  <div class="page-header-modern mb-3">
    <h4 class="fw-bold">Hi! Welcome To Dashboard</h4>
    <p class="text-muted">Home → Sales Dashboard</p>
  </div>

  {{-- Hero Banner --}}
  <div class="dash-hero mb-4">
    <div>
      <h3>Congratulations {{ Auth::user()->name ?? 'Admin' }} 🎉</h3>
      <p>You have reached your sales milestone! Keep going strong 💪</p>
    </div>
    <div class="hero-profit">
      <div class="profit-value">TK {{ number_format($today_profit ?? 0,2) }}</div>
      <div class="profit-label">Today's Profit</div>
    </div>
  </div>

  {{-- Stat Cards --}}
  <div class="row g-3 mb-3">
    <div class="col-md-3">
      <div class="dash-card">
        <div class="card-icon orders" data-feather="shopping-cart"></div>
        <div class="card-info">
          <div class="card-label">Total Orders</div>
          <div class="card-value">{{ number_format($total_order ?? 0) }}</div>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="dash-card">
        <div class="card-icon fund" data-feather="dollar-sign"></div>
        <div class="card-info">
          <div class="card-label">Fund Balance</div>
          <div class="card-value">TK {{ number_format($fund_balance ?? 0,2) }}</div>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="dash-card">
        <div class="card-icon expenses" data-feather="trending-down"></div>
        <div class="card-info">
          <div class="card-label">Total Expenses</div>
          <div class="card-value">TK {{ number_format($total_expenses ?? 0,2) }}</div>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="dash-card">
        <div class="card-icon delivery" data-feather="truck"></div>
        <div class="card-info">
          <div class="card-label">Delivered Orders</div>
          <div class="card-value">{{ number_format($total_delivery ?? 0) }}</div>
        </div>
      </div>
    </div>
  </div>

  {{-- Charts --}}
  <div class="row g-3">
    <div class="col-lg-4">
      <div class="chart-modern">
        <div class="chart-title">
          <span class="title-dot purple"></span>
          Sales By Category
        </div>
        <div id="categoryChart"></div>
      </div>
    </div>
    <div class="col-lg-8">
      <div class="chart-modern">
        <div class="chart-title">
          <span class="title-dot blue"></span>
          Monthly Sales Statistics
        </div>
        <div id="salesChart"></div>
      </div>
    </div>
  </div>

  {{-- Recent Orders & Customers --}}
  <div class="row g-3 mt-3">
    <div class="col-lg-8">
      <div class="chart-modern">
        <div class="chart-title">
          <span class="title-dot blue"></span>
          Recent Orders
        </div>
        <div class="table-responsive">
          <table class="table table-modern">
            <thead>
              <tr>
                <th>Customer</th>
                <th>{{ __('Invoice') }}</th>
                <th>{{ __('Status') }}</th>
                <th>{{ __('Date') }}</th>
              </tr>
            </thead>
            <tbody>
            @forelse($latest_order ?? [] as $order)
              <tr>
                <td>{{ $order->customer->name ?? 'Guest' }}</td>
                <td>#{{ $order->invoice_id ?? '-' }}</td>
                <td>
                  @if(($order->order_status ?? 0) == 5)
                    <span class="badge bg-success badge-dash">Delivered</span>
                  @elseif(($order->order_status ?? 0) == 1)
                    <span class="badge bg-info badge-dash">{{ __('Pending') }}</span>
                  @else
                    <span class="badge bg-warning badge-dash">{{ __('Processing') }}</span>
                  @endif
                </td>
                <td class="text-muted">{{ optional($order->created_at)->format('d M Y') }}</td>
              </tr>
            @empty
              <tr>
                <td colspan="4" class="text-center text-muted py-3">No recent orders found</td>
              </tr>
            @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <div class="col-lg-4">
      <div class="chart-modern">
        <div class="chart-title">
          <span class="title-dot purple"></span>
          Recent Customers
        </div>
        <ul class="customer-list">
          @forelse($latest_customer ?? [] as $cust)
            <li class="customer-item">
              <div class="cust-name">{{ $cust->name }}</div>
              <div class="cust-phone">{{ $cust->phone ?? 'N/A' }}</div>
            </li>
          @empty
            <li class="text-muted text-center py-3">No customers found</li>
          @endforelse
        </ul>
      </div>
    </div>
  </div>

</div>
@endsection

@section('script')
<script src="https://cdn.jsdelivr.net/npm/apexcharts@3.35.5"></script>
<script>
// ── Category Chart (Donut) ──
new ApexCharts(document.querySelector("#categoryChart"),{
  chart:{type:'donut',height:280},
  labels:@json($categoryLabels ?? ['No Sales']),
  series:@json($categorySeries ?? [0]),
  legend:{position:'bottom',fontSize:'13px',fontFamily:'inherit'},
  colors:['#7c3aed','#2563eb','#16a34a','#d97706','#dc2626','#0891b2'],
  dataLabels:{enabled:false},
  tooltip:{
    y:{
      formatter:function(val){
        return '৳ ' + val.toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2});
      }
    }
  },
  plotOptions:{
    pie:{
      donut:{
        size:'70%',
        labels:{
          show:true,
          name:{show:true,fontSize:'13px',fontWeight:600,color:'#1a1a2e'},
          value:{show:true,fontSize:'14px',fontWeight:700,color:'#273444',
            formatter:function(val){
              return '৳ ' + Number(val).toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2});
            }
          },
          total:{
            show:true,
            label:'Total Sales',
            fontSize:'12px',
            fontWeight:600,
            color:'#94a3b8',
            formatter:function(){
              var total = @json(array_sum($categorySeries ?? [0]));
              return '৳ ' + total.toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2});
            }
          }
        }
      }
    }
  }
}).render();

// ── Monthly Sales Chart (Area) ──
new ApexCharts(document.querySelector("#salesChart"),{
  chart:{type:'area',height:300,toolbar:{show:false},fontFamily:'inherit'},
  series:[{
    name:'Sales',
    data:@json(($monthly_sale ?? collect())->pluck('amount'))
  }],
  xaxis:{
    categories:@json(($monthly_sale ?? collect())->pluck('date')),
    labels:{style:{fontSize:'12px',fontWeight:500,colors:'#94a3b8'}}
  },
  yaxis:{
    labels:{
      style:{fontSize:'12px',fontWeight:500,colors:'#94a3b8'},
      formatter:function(val){ return '৳ ' + val.toLocaleString('en-US'); }
    }
  },
  stroke:{curve:'smooth',width:3,colors:['#2563eb']},
  fill:{
    type:'gradient',
    gradient:{
      shadeIntensity:1,
      opacityFrom:0.4,
      opacityTo:0.1,
      colorStops:[{
        offset:0,color:'#2563eb',opacity:0.4
      },{
        offset:100,color:'#2563eb',opacity:0.05
      }]
    }
  },
  dataLabels:{enabled:false},
  markers:{size:0},
  tooltip:{
    y:{
      formatter:function(val){ return '৳ ' + val.toLocaleString('en-US',{minimumFractionDigits:2}); }
    }
  },
  grid:{borderColor:'#f1f5f9'}
}).render();

// ── Feather Icons ──
if(typeof feather !== 'undefined'){ feather.replace(); }
</script>
@endsection
