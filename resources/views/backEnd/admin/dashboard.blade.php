@extends('backEnd.layouts.master')
@section('title','Sales Dashboard')

@section('css')
<link href="https://cdn.jsdelivr.net/npm/apexcharts@3.35.5/dist/apexcharts.css" rel="stylesheet">
<style>
.dashboard-banner{
  background:linear-gradient(90deg,#5e72e4 0%,#825ee4 100%);
  color:#fff;border-radius:15px;padding:25px;
  display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap
}
.dashboard-card{
  background:#fff;border-radius:15px;padding:25px;
  box-shadow:0 2px 10px rgba(0,0,0,.06);transition:.3s
}
.dashboard-card:hover{transform:translateY(-3px)}
.metric-title{font-size:14px;color:#9da5b4}
.metric-value{font-size:26px;font-weight:700;color:#273444}
.chart-box{
  background:#fff;border-radius:15px;padding:20px;
  box-shadow:0 2px 10px rgba(0,0,0,.06)
}
.table td,.table th{vertical-align:middle}
</style>
@endsection

@section('content')
<div class="container-fluid py-3">

  {{-- Header --}}
  <div class="mb-3">
    <h4 class="fw-bold">Hi! Welcome To Dashboard</h4>
    <small class="text-muted">Home → Sales Dashboard</small>
  </div>

  {{-- Banner --}}
  <div class="dashboard-banner mb-4">
    <div>
      <h3>Congratulations {{ Auth::user()->name ?? 'Admin' }} 🎉</h3>
      <p>You have reached your sales milestone! Keep going strong 💪</p>
    </div>
    <div class="text-end">
      <h2 class="mb-0">
        TK {{ number_format($today_profit ?? 0,2) }}
      </h2>
      <p class="mb-0">Today's Profit</p>
    </div>
  </div>

  {{-- Cards --}}
  <div class="row g-3 mb-3">
    <div class="col-md-3">
      <div class="dashboard-card">
        <div class="metric-title">Total Orders</div>
        <div class="metric-value">{{ number_format($total_order ?? 0) }}</div>
      </div>
    </div>

    <div class="col-md-3">
      <div class="dashboard-card">
        <div class="metric-title">Fund Balance</div>
        <div class="metric-value">
          TK {{ number_format($fund_balance ?? 0,2) }}
        </div>
      </div>
    </div>

    <div class="col-md-3">
      <div class="dashboard-card">
        <div class="metric-title">Total Expenses</div>
        <div class="metric-value">
          TK {{ number_format($total_expenses ?? 0,2) }}
        </div>
      </div>
    </div>

    <div class="col-md-3">
      <div class="dashboard-card">
        <div class="metric-title">Delivered Orders</div>
        <div class="metric-value">{{ number_format($total_delivery ?? 0) }}</div>
      </div>
    </div>
  </div>

  {{-- Charts --}}
  <div class="row g-3">
    <div class="col-lg-4">
      <div class="chart-box">
        <h5 class="fw-bold mb-3">Sales By Category</h5>
        <div id="categoryChart"></div>
      </div>
    </div>

    <div class="col-lg-8">
      <div class="chart-box">
        <h5 class="fw-bold mb-3">Monthly Sales Statistics</h5>
        <div id="salesChart"></div>
      </div>
    </div>
  </div>

  {{-- Recent Orders & Customers --}}
  <div class="row g-3 mt-3">
    <div class="col-lg-8">
      <div class="chart-box">
        <h5 class="fw-bold mb-3">Recent Orders</h5>
        <div class="table-responsive">
          <table class="table table-borderless">
            <thead class="table-light">
              <tr>
                <th>Customer</th>
                <th>Invoice</th>
                <th>Status</th>
                <th>Date</th>
              </tr>
            </thead>
            <tbody>
            @forelse($latest_order ?? [] as $order)
              <tr>
                <td>{{ $order->customer->name ?? 'Guest' }}</td>
                <td>#{{ $order->invoice_id ?? '-' }}</td>
                <td>
                  @if(($order->order_status ?? 0) == 5)
                    <span class="badge bg-success">Delivered</span>
                  @elseif(($order->order_status ?? 0) == 1)
                    <span class="badge bg-info">Pending</span>
                  @else
                    <span class="badge bg-warning">Processing</span>
                  @endif
                </td>
                <td>{{ optional($order->created_at)->format('d M Y') }}</td>
              </tr>
            @empty
              <tr>
                <td colspan="4" class="text-center text-muted">
                  No recent orders found
                </td>
              </tr>
            @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="chart-box">
        <h5 class="fw-bold mb-3">Recent Customers</h5>
        <ul class="list-unstyled mb-0">
          @forelse($latest_customer ?? [] as $cust)
            <li class="py-2 border-bottom">
              <strong>{{ $cust->name }}</strong><br>
              <small class="text-muted">{{ $cust->phone ?? 'N/A' }}</small>
            </li>
          @empty
            <li class="text-muted">No customers found</li>
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
// Category Chart - Dynamic Data from Database
new ApexCharts(document.querySelector("#categoryChart"),{
  chart:{type:'donut',height:260},
  labels:@json($categoryLabels ?? ['No Sales']),
  series:@json($categorySeries ?? [0]),
  legend:{position:'bottom'},
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
        labels:{
          show:true,
          total:{
            show:true,
            label:'Total Sales',
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

// Monthly Sales Chart
new ApexCharts(document.querySelector("#salesChart"),{
  chart:{type:'area',height:300,toolbar:{show:false}},
  series:[{
    name:'Sales',
    data:@json(($monthly_sale ?? collect())->pluck('amount'))
  }],
  xaxis:{
    categories:@json(($monthly_sale ?? collect())->pluck('date'))
  },
  stroke:{curve:'smooth'},
}).render();
</script>
@endsection
