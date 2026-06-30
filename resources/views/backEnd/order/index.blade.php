@extends('backEnd.layouts.master')
@section('title',$order_status->name.' Order')
@section('content')
<div class="container-fluid">
    
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <a href="{{ route('admin.order.create') }}" class="btn btn-danger rounded-pill"><i class="fe-shopping-cart"></i> {{ __('POS Create') }}</a>
                </div>
                <h4 class="page-title">{{ $order_status->name }} Order ({{ $order_status->orders_count }})</h4>
            </div>
        </div>
    </div>        
    <div class="row order_page">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-sm-8">
                            <ul class="action2-btn list-unstyled d-flex gap-2 p-0 m-0">
                                <li><a data-bs-toggle="modal" data-bs-target="#asign{{ __('{{ __('Use') }}r') }}" class="btn rounded-pill btn-success"><i class="fe-plus"></i> {{ __('Assign') }}</a></li>
                                <li><a data-bs-toggle="modal" data-bs-target="#change{{ __('Status') }}" class="btn rounded-pill btn-primary"><i class="fe-plus"></i>{{ __('{{ __('Status') }}') }}</a></li>
                                <li><a href="{{ route('admin.order.bulk_destroy') }}" class="btn rounded-pill btn-danger order_delete"><i class="fe-plus"></i>{{ __('Delete') }}</a></li>
                                <li><a href="{{ route('admin.order.order_print') }}" class="btn rounded-pill btn-info multi_order_print"><i class="fe-printer"></i>{{ __('Print') }}</a></li>
                                <li><a href="{{ route('admin.order.order_print') }}" class="btn rounded-pill btn-secondary multi_label_print"><i class="fe-tag"></i> {{ __('Label') }}</a></li>
                                @if($steadfast)
                                    <li><a href="{{ route('admin.bulk_courier', 'steadfast') }}?status=5" class="btn rounded-pill btn-info multi_order_courier"><i class="fe-truck"></i> {{ __('Steadfast') }}</a></li>
                                @endif
                                @if($pathao_info)
                                    <li><a data-bs-toggle="modal" data-bs-target="#pathao" class="btn rounded-pill btn-warning"><i class="fe-truck"></i> {{ __('Pathao') }}</a></li>
                                @endif
                                @if(isset($redx_info) && $redx_info)
                                    <li><a href="{{ route('admin.bulk_courier', 'redx') }}?status=5" class="btn rounded-pill btn-warning multi_order_courier" style="background-color: #f59e0b; border-color: #f59e0b;"><i class="fe-truck"></i> {{ __('RedX') }}</a></li>
                                @endif
                            </ul>
                        </div>
                        <div class="col-sm-4">
                            <form class="custom_form" method="{{ __('GET') }}">
                                <div class="form-group d-flex">
                                    <input type="text" name="keyword" placeholder="{{ __('Search') }}" class="form-control me-2" value="{{ request('keyword') }}">
                                    <button class="btn rounded-pill btn-info">{{ __('Search') }}</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table id="datatable-buttons" class="table table-striped w-100">
                            <thead>
                                <tr>
                                    <th style="width:2%;">
                                        <div class="form-check">
                                            <label class="form-check-label">
                                                <input type="checkbox" class="form-check-input checkall" value="">
                                            </label>
                                        </div>
                                    </th>
                                    <th style="width:2%;">{{ __('SL') }}</th>
                                    <th style="width:8%;">{{ __('Action') }}</th>
                                    <th style="width:8%;">{{ __('{{ __('Inv') }}oice') }}</th>
                                    <th style="width:10%;">{{ __('Date') }}</th>
                                    <th style="width:10%;">{{ __('{{ __('Name') }}') }}</th>
                                    <th style="width:8%;">{{ __('Type') }}</th>
                                    <th style="width:8%;">IP</th>
                                    <th style="width:10%;">{{ __('Order {{ __('Note') }}') }}</th>
                                    <th style="width:10%;">{{ __('Admin {{ __('Note') }}') }}</th>
                                    <th style="width:10%;">{{ __('{{ __('Amount') }}') }}</th>
                                    <th style="width:10%;">{{ __('{{ __('Status') }}') }}</th>
                                    <th style="width:12%;">{{ __('Courier') }}</th>
                                    <th>{{ __('Track') }}</th>
                                    <th>{{ __('Fraud {{ __('Check') }}') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($show_data as $key => $value)
                                    <tr>
                                        <td><input type="checkbox" class="checkbox form-check-input" value="{{ $value->id }}"></td>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>
                                            <div class="button-list custom-btn-list">
                                                <a href="{{ route('admin.order.invoice', ['invoice_id' => $value->invoice_id]) }}" title="{{ __('{{ __('Inv') }}oice') }}"><i class="fe-eye"></i></a>
                                                <a href="{{ route('admin.order.process', ['invoice_id' => $value->invoice_id]) }}" title="Process"><i class="fe-settings"></i></a>
                                                <a href="{{ route('admin.order.edit', ['invoice_id' => $value->invoice_id]) }}" title="{{ __('Edit') }}"><i class="fe-edit"></i></a>
                                                <form method="post" action="{{ route('admin.order.destroy') }}" class="d-inline">
                                                    @csrf
                                                    <input type="hidden" value="{{ $value->id }}" name="id">
                                                    <button type="{{ __('submit') }}" title="{{ __('Delete') }}" class="delete-confirm btn btn-link p-0" style="color:inherit;"><i class="fe-trash-2"></i></button>
                                                </form>
                                            </div>
                                        </td>
                                        <td>{{ $value->invoice_id }}</td>
                                        <td>
                                            {{ date('d-m-Y', strtotime($value->updated_at)) }}<br>
                                            {{ date('h:i:s a', strtotime($value->updated_at)) }}
                                        </td>
                                        <td>
                                            <strong>{{ $value->shipping ? $value->shipping->name : '' }}</strong>
                                            <p class="mb-0">{{ $value->shipping ? $value->shipping->{{ __('phone') }} : '' }}</p>
                                        </td>
                                        <td>
                                            @php
                                                $items = $value->orderDetails;
                                                $types = [];
                                                foreach ($items as $item) {
                                                    if ($item->product && $item->product->is_digital == 1) {
                                                        $types[] = 'Digital';
                                                    } else {
                                                        $types[] = 'Physical';
                                                    }
                                                }
                                                $types = array_unique($types);
                                                if (count($types) === 1) {
                                                    echo $types[0];
                                                } else {
                                                    echo "Mixed";
                                                }
                                            @endphp
                                        </td>

                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <span>{{ $value->ip_address }}</span>
                                                @if($value->ip_{{ __('address)') }}
                                                    @php
                                                        $is{{ __('Blocked') }} = in_array($value->ip_address, isset($blockedIps) ? $blockedIps : []);
                                                    @endphp
                                                    @if($is{{ __('Blocked') }})
                                                        <span class="badge bg-secondary" title="This IP is already blocked">
                                                            <i class="fe-shield"></i> {{ __('Blocked') }}
                                                        </span>
                                                    @else
                                                        <button type="button" 
                                                                class="btn btn-sm btn-danger block-ip-btn" 
                                                                data-ip="{{ $value->ip_address }}"
                                                                data-reason="ফেইক অর্ডার"
                                                                title="Block this IP - ফেইক অর্ডার">
                                                            <i class="fe-shield-off"></i> Block
                                                        </button>
                                                    @endif
                                                @endif
                                            </div>
                                        </td>
                                        {{-- {{ __('Order {{ __('Note') }}') }} (client) --}}
                                        <td>
                                            @php
                                                $order{{ __('Note') }} = isset($value->order_note) ? $value->order_note : (isset($value->note) ? $value->note : '');
                                            @endphp

                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-info note-modal-btn"
                                                data-type="order"
                                                data-id="{{ $value->id }}"
                                                data-note="{{ $order{{ __('Note') }} }}"
                                            >
                                                {{ $order{{ __('Note') }} ? 'View' : 'Add' }}
                                            </button>
                                        </td>

                                        {{-- {{ __('Admin {{ __('Note') }}') }} --}}
                                        <td>
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-warning note-modal-btn"
                                                data-type="admin"
                                                data-id="{{ $value->id }}"
                                                data-note="{{ isset($value->admin_note) ? $value->admin_note : '' }}"
                                            >
                                                {{ $value->admin_note ? 'View' : 'Add' }}
                                            </button>
                                        </td>

                                        {{-- {{ __('Amount') }} (show remaining if partial pa{{ __('id)') }} --}}
                                        <td>
                                            @php
                                                $payment = \App\Models\Payment::w{{ __('here') }}('order_id', $value->{{ __('id)') }}->first();
                                                $paid = $payment ? floatval($payment->amount) : 0;
                                                $total = floatval($value->amount);
                                                $show{{ __('Amount') }} = $total;
                                                if ($paid > 0 && $paid < $total) {
                                                    $show{{ __('Amount') }} = $total - $paid;
                                                }
                                            @endphp
                                            ৳{{ number_format($show{{ __('Amount') }}, 2) }}
                                        </td>

                                        <td>{{ $value->status ? $value->status->name : '' }}</td>

                                        {{-- {{ __('Courier') }} Information --}}
                                        <td>
                                            @php
                                                // Priority: courier_tracking_id > consignment_id
                                                $trackingId = isset($value->courier_tracking_{{ __('id)') }} ? $value->courier_tracking_id : $value->consignment_id;
                                                $courierType = $value->courier_type;
                                                
                                                // If no courier_type but has consignment_id, assume it's steadfast (backward compatibility)
                                                // This handles old orders that were sent via {{ __('Steadfast') }} but don't have courier_type
                                                if (!$courierType && $value->consignment_{{ __('id)') }} {
                                                    $courierType = 'steadfast';
                                                }
                                                
                                                // If still no courier_type but has tracking_id, assume steadfast
                                                if (!$courierType && $trackingId) {
                                                    $courierType = 'steadfast';
                                                }
                                            @endphp
                                            
                                            @if($trackingId)
                                                @php
                                                    $courier{{ __('Name') }} = ucfirst(isset($courierType) ? $courierType : '{{ __('Steadfast') }}');
                                                    $ct = isset($courierType) ? strtolower($courierType) : 'steadfast';
                                                    if ($ct === 'pathao') { $courier{{ __('Color') }} = 'info'; }
                                                    elseif ($ct === 'steadfast') { $courier{{ __('Color') }} = 'primary'; }
                                                    elseif ($ct === 'redx') { $courier{{ __('Color') }} = 'warning'; }
                                                    else { $courier{{ __('Color') }} = 'primary'; }
                                                @endphp
                                                <div>
                                                    <span class="badge bg-{{ $courier{{ __('Color') }} }} mb-1">
                                                        <i class="fe-truck"></i> {{ $courier{{ __('Name') }} }}
                                                    </span>
                                                    <br>
                                                    <small class="text-muted" style="font-size: 0.75rem;">
                                                        ID: {{ Str::limit($trackingId, 15) }}
                                                    </small>
                                                    @if($value->courier_sent_at)
                                                        <br>
                                                        <small class="text-muted" style="font-size: 0.7rem;">
                                                            {{ date('d-m-Y', strtotime($value->courier_sent_at)) }}
                                                        </small>
                                                    @endif
                                                </div>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>

                                        <td>
                                            @php
                                                // Get tracking ID (new field or fallback to old consignment_{{ __('id)') }}
                                                $trackingId = isset($value->courier_tracking_{{ __('id)') }} ? $value->courier_tracking_id : $value->consignment_id;
                                                $courierType = $value->courier_type;
                                                
                                                // If no courier_type but has consignment_id, assume it's steadfast
                                                if (!$courierType && $value->consignment_{{ __('id)') }} {
                                                    $courierType = 'steadfast';
                                                }
                                            @endphp
                                            
                                            @if(!empty($trackingId))
                                                @if($courierType == 'pathao')
                                                    <a href="{{ __('https://') }}merchant.pathao.com/public-tracking?consignment_id={{ $trackingId }}" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-info">
                                                        <i class="fe-truck"></i> {{ __('Track') }}
                                                    </a>
                                                @elseif($courierType == 'steadfast' || (!$courierType && $trackingId))
                                                    <a href="{{ __('https://') }}steadfast.com.bd/t/{{ $trackingId }}" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-primary">
                                                        <i class="fe-truck"></i> {{ __('Track') }}
                                                    </a>
                                                @elseif($courierType == 'redx')
                                                    <a href="{{ __('https://') }}redx.com.bd/track/{{ $trackingId }}" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-warning">
                                                        <i class="fe-truck"></i> {{ __('Track') }}
                                                    </a>
                                                @else
                                                    <span class="badge bg-secondary">{{ $trackingId }}</span>
                                                @endif
                                            @else
                                                <span class="text-muted">{{ __('bn_30bd0242') }}</span>
                                            @endif
                                        </td>

                                        <td>
                                            {{-- 
                                                LOGIC:
                                                - is_null() ব্যব{{ __('bn_f29420ce') }} করা হয়েছে কারণ 0 একটি ভ্যালিড রেট হতে পারে (ফ্রড)।
                                                - NULL হলে "যাচাই করুন" (হলুদ)।
                                                - অন্যথায় রেট দেখাবে (সবুজ/লাল)।
                                            --}}
                                            @if(is_null($value->fraud_rate))
                                                 <a href="javascript:void(0);" 
                                                class="btn btn-sm fraud-check"
                                                data-mobile="{{ $value->shipping ? $value->shipping->{{ __('phone') }} : '' }}"
                                                style="background:#fb8709; color:#fff; padding:5px 12px; border-radius:6px; font-size:13px;">
                                                চেকিং
                                            </a>
                                            @else
                                                <a href="javascript:void(0);" 
                                                   class="btn btn-sm fraud-check {{ $value->fraud_rate >= 80 ? 'btn-success' : 'btn-danger' }}"
                                                   data-mobile="{{ $value->shipping ? $value->shipping->{{ __('phone') }} : '' }}"
                                                   data-id="{{ $value->id }}"
                                                   style="padding:5px 12px; border-radius:6px; font-size:13px;">
                                                    {{ $value->fraud_rate }}% {{ $value->fraud_rate >= 80 ? '{{ __('bn_8704a028') }}' : 'ঝুঁকি' }}
                                                </a>
                                            @endif
                                        </td>

                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="custom-paginate mt-3">
                        {{ $show_data->links('pagination::bootstrap-4') }}
                    </div>
                </div> </div> </div></div>
</div>

<div class="modal fade" id="asign{{ __('{{ __('Use') }}r') }}" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">{{ __('{{ __('Assign') }} {{ __('{{ __('Use') }}r') }}') }}</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
      </div>
      <form action="{{ route('admin.order.assign') }}" id="order_assign">
        <div class="modal-body">
            <div class="form-group">
                <select name="user_id" id="user_id" class="form-control">
                    <option value="">{{ __('Select..') }}</option>
                    @foreach($users as $u)
                        <option value="{{ $u->id }}">{{ $u->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
            <button type="{{ __('submit') }}" class="btn btn-success">{{ __('Submit') }}</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="change{{ __('Status') }}" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">{{ __('{{ __('Change') }} {{ __('Status') }}') }}</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
      </div>
      <form action="{{ route('admin.order.status') }}" id="order_status_form" novalidate>
        <div class="modal-body">
            <div class="form-group">
                <label class="form-label">{{ __('Select {{ __('Status') }}') }}<span class="text-danger">*</span></label>
                <select name="order_status" id="order_status" class="form-control">
                    <option value="">{{ __('Select {{ __('Status') }}..') }}</option>
                    @if(isset($orderstatus) && $orderstatus->count() > 0)
                        @foreach($orderstatus as $s)
                            <option value="{{ $s->id }}">{{ $s->name }}</option>
                        @endforeach
                    @else
                        <option value="">{{ __('No status available') }}</option>
                    @endif
                </select>
                <small class="text-muted">{{ __('Select orders first, then choose status') }}</small>
                <div class="invalid-feedback" id="status_error" style="display: none;">{{ __('Please select a status') }}</div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
            <button type="{{ __('submit') }}" class="btn btn-success">{{ __('Update {{ __('Status') }}') }}</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="pathao" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">{{ __('{{ __('Pathao') }} {{ __('Courier') }}') }}</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
      </div>
      <form action="{{ route('admin.order.pathao') }}" id="order_sendto_pathao" method={{ __('"{{ __('POST') }}"') }}>
      @csrf
      <input type="hidden" name="order_ids" id="pathao_order_ids" value="">
      <div class="modal-body">
        <div class="form-group">
            <label for="pathaostore" class="form-label">{{ __('Store') }}</label>
           <select name="pathaostore" id="pathaostore" class="pathaostore form-control" >
             <option value="">{{ __('Select {{ __('Store') }}...') }}</option>
             @if(isset($pathaostore['data']['data']))
                 @foreach($pathaostore['data']['data'] as $store)
                     <option value="{{ $store['store_id'] }}">{{ $store['store_name'] }}</option>
                 @endforeach
             @endif
           </select>
        </div>

        <div class="form-group mt-3">
          <label for="pathaocity" class="form-label">{{ __('City') }}</label>
           <select name="pathaocity" id="pathaocity" class="chosen-select pathaocity form-control" style="width:100%" >
             <option value="">{{ __('Select City...') }}</option>
             @if(isset($pathaocities['data']['data']))
                 @foreach($pathaocities['data']['data'] as $city)
                     <option value="{{ $city['city_id'] }}">{{ $city['city_name'] }}</option>
                 @endforeach
             @endif
           </select>
        </div>

        <div class="form-group mt-3">
          <label class="form-label">{{ __('Zone') }}</label>
             <select name="pathaozone" id="pathaozone" class="pathaozone chosen-select form-control" style="width:100%"></select>
        </div>

        <div class="form-group mt-3">
          <label class="form-label">{{ __('Area') }}</label>
             <select name="pathaoarea" id="pathaoarea" class="pathaoarea chosen-select form-control" style="width:100%"></select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
        <button type="{{ __('submit') }}" class="btn btn-success">{{ __('Submit') }}</button>
      </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="noteModal" tabindex="-1" aria-labelledby="noteModal{{ __('Label') }}" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="noteModal{{ __('Label') }}">{{ __('{{ __('Note') }}') }}</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
      </div>

      <div class="modal-body">
        <input type="hidden" id="note_order_id">
        <input type="hidden" id="note_type">

        <div class="form-group">
            <label id="note_label">{{ __('{{ __('Note') }}') }}</label>
            <textarea id="note_modal_text" class="form-control" rows="5" placeholder="{{ __('Write note {{ __('here') }}...') }}"></textarea>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}</button>
        <button type="button" class="btn btn-success" id="save{{ __('Note') }}Btn">{{ __('Save') }}</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="fraud{{ __('Check') }}Modal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content" style="border-radius:12px;">
            <div class="modal-header" style="background:#10b981; color:#fff;">
                <h5 class="modal-title">
                    <i class="fe-shield"></i> ফ্রড চেকার রিপোর্ট
                </h5>
                <button type="button" class="btn-close btn-light" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body" id="fraudModalBody" style="min-height:250px;">
                <div class="text-center py-5">
                    <div class="spinner-border text-success" style="width:3rem;height:3rem;"></div>
                    <p class="mt-3 fw-bold">{{ __('bn_b1b4a7ec') }}</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Safe {{ __('number') }} helper
    function toNum(v) {
        if (v === null || v === undefined || v === '') return 0;
        var n = Number(v);
        return isNaN(n) ? 0 : n;
    }

    // build{{ __('Summary') }}: Updated to handle {{ __('New') }} API keys
    function build{{ __('Summary') }}(raw) {
        var pathao = raw.pathao || raw.{{ __('Pathao') }} || raw.pathao_data || raw.pathao || {};
        var redx = raw.redx || raw.{{ __('RedX') }} || raw.redx_data || raw.redx || {};
        var steadfast = raw.steadfast || raw.{{ __('Steadfast') }} || raw.steadfast_data || raw.steadfast || {};
        var parceldex = raw.parceldex || raw.ParcelDex || {};
        var paperfly = raw.paperfly || raw.PaperFly || {};

        function getStats(obj) {
            var t = toNum(obj.{{ __('total') }}_parcel || obj.{{ __('total') }} || obj.orders || obj.count);
            var s = toNum(obj.success_parcel || obj.success || obj.complete || obj.delivered);
            var c = toNum(obj.cancelled_parcel || obj.cancel || obj.cancelled || obj.failed);
            var r = (obj.success_ratio !== undefined) ? toNum(obj.success_ratio) : (t > 0 ? Math.round((s / t) * 100) : 0);
            return { {{ __('total') }}: t, success: s, cancel: c, rate: r };
        }

        var p = getStats(pathao);
        var r = getStats(redx);
        var s = getStats(steadfast);
        var pd = getStats(parceldex);
        var pf = getStats(paperfly);

        var {{ __('total') }} = p.{{ __('total') }} + r.{{ __('total') }} + s.{{ __('total') }} + pd.{{ __('total') }} + pf.{{ __('total') }};
        var success = p.success + r.success + s.success + pd.success + pf.success;
        var cancel = p.cancel + r.cancel + s.cancel + pd.cancel + pf.cancel;

        var rate = 0;
        if ({{ __('total') }} > 0) rate = Math.round((success / {{ __('total') }}) * 100);

        return {
            {{ __('total') }}: {{ __('total') }},
            success: success,
            cancel: cancel,
            rate: rate,
            couriers: {
                {{ __('Pathao') }}: p,
                {{ __('RedX') }}: r,
                {{ __('Steadfast') }}: s,
                ParcelDex: pd,
                PaperFly: pf
            }
        };
    }

    // Render HTML for modal from canonical summary (IN BANGLA)
    function loadFraudHtml(data, mobile) {
        if (data.{{ __('total') }} === 0) {
            return `
            <div class="container-fluid">
                <div class="p-3 mb-3" style="background:#f8f9fa;border-radius:8px;">
                    <h5><i class="fe-{{ __('phone') }}-call"></i> ${mobile}</h5>
                    <small>{{ __('bn_08d890f7') }}</small>
                    <span class="badge bg-secondary float-end">{{ __('bn_927a0c6c') }}</span>
                </div>
                <div class="alert alert-light text-center py-3" style="border:1px solid #ddd;">
                    <h5 class="text-muted mb-0">😕 No data found</h5>
                    <small>{{ __('bn_619f83cb') }}</small>
                </div>
            </div>`;
        }

        var rate{{ __('Text') }} = (data.rate || data.rate === 0) ? (data.rate + '%') : '{{ __('N/A') }}';
        
        // Bangla Risk Tags
        var riskTag = '<span class="badge bg-success">{{ __('bn_8704a028') }}</span>';
        var show{{ __('Warning') }} = (data.{{ __('total') }} > {{ __('0 && data.rate') }} < 80);
        if (show{{ __('Warning') }}) { riskTag = '<span class="badge bg-danger">{{ __('bn_8d38ebc7') }}</span>'; }

        var courierRows = '';
        Object.entries(data.couriers).forEach(function([name, c]) {
            if(c.{{ __('total') }} === 0) return;

            var c{{ __('Rate') }}Num = toNum(c.rate);
            var c{{ __('Rate') }} = (c.{{ __('total') }} === 0) ? '{{ __('N/A') }}' : (c{{ __('Rate') }}Num + '%');
            var badgeClass = 'bg-secondary';
            if (c.{{ __('total') }} === 0) { badgeClass = 'bg-secondary'; }
            else if (c{{ __('Rate') }}Num >= 90) { badgeClass = 'bg-success'; }
            else if (c{{ __('Rate') }}Num >= 70) { badgeClass = 'bg-warning text-dark'; }
            else { badgeClass = 'bg-danger'; }

            courierRows += `
                <tr>
                    <td>${name}</td>
                    <td>${c.{{ __('total') }}}</td>
                    <td class="text-success">${c.success}</td>
                    <td class="text-danger">${c.cancel}</td>
                    <td><span class="badge ${badgeClass}">${c{{ __('Rate') }}}</span></td>
                </tr>`;
        });

        var warningHtml = '';
        if (show{{ __('Warning') }}) {
            warningHtml = `<div class="alert alert-danger text-center py-2">{{ __('bn_a851f203') }}</div>`;
        } else {
            warningHtml = `<div class="text-start mb-3"><small class="text-success">{{ __('bn_39a620d4') }}</small></div>`;
        }

        return `
            <div class="container-fluid">
                <div class="p-3 mb-3" style="background:#e8fff3;border-radius:8px;">
                    <h5><i class="fe-{{ __('phone') }}-call"></i> ${mobile}</h5>
                    <small>{{ __('bn_fbbc3031') }}তার {{ __('bn_f29420ce') }}: ${rate{{ __('Text') }}}</small>
                    <span class="float-end">${riskTag}</span>
                </div>
                ${warningHtml}
                <div class="row text-center mb-4">
                    <div class="col-md-3 mb-2">
                        <div class="p-3 text-white" style="background:#6366f1;border-radius:10px;">
                            <h3>${data.{{ __('total') }}}</h3><span>{{ __('bn_fed88233') }}</span>
                        </div>
                    </div>
                    <div class="col-md-3 mb-2">
                        <div class="p-3 text-white" style="background:#10b981;border-radius:10px;">
                            <h3>${data.success}</h3><span>{{ __('bn_a62e1d5e') }}</span>
                        </div>
                    </div>
                    <div class="col-md-3 mb-2">
                        <div class="p-3 text-white" style="background:#ef4444;border-radius:10px;">
                            <h3>${data.cancel}</h3><span>{{ __('bn_7475e621') }}</span>
                        </div>
                    </div>
                    <div class="col-md-3 mb-2">
                        <div class="p-3 text-white" style="background:#f97316;border-radius:10px;">
                            <h3>${rate{{ __('Text') }}}</h3><span>{{ __('bn_f29420ce') }}</span>
                        </div>
                    </div>
                </div>
                <table class="table table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th>{{ __('bn_90c21c31') }}</th><th>{{ __('bn_70ac0f2d') }}</th><th>{{ __('bn_fbbc3031') }}</th><th>{{ __('Cancelled') }}</th><th>{{ __('bn_f29420ce') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${courierRows}
                    </tbody>
                </table>
            </div>
        `;
    }
</script>

<script src="{{ __('https://') }}code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function(){

    // {{ __('Order {{ __('Note') }}') }} / {{ __('Admin {{ __('Note') }}') }} popup open
    $(document).on('click', '.note-modal-btn', function (e) {
        e.preventDefault();
        let orderId = $(this).data('id');
        let type    = $(this).data('type');
        let note    = $(this).data('note') || '';

        $('#note_order_id').val(orderId);
        $('#note_type').val(type);
        $('#note_modal_text').val(note);

        if (type === 'admin') {
            $('#noteModal{{ __('Label') }}').text('{{ __('Admin {{ __('Note') }}') }}');
            $('#note_label').text('{{ __('Admin {{ __('Note') }}') }}');
        } else {
            $('#noteModal{{ __('Label') }}').text('{{ __('Order {{ __('Note') }}') }} ({{ __('Customer') }})');
            $('#note_label').text('{{ __('Order {{ __('Note') }}') }} ({{ __('Customer') }})');
        }

        $('#noteModal').modal('show');
    });

    // Save {{ __('Note') }} (AJAX)
    $('#save{{ __('Note') }}Btn').on('click', function () {
        let orderId = $('#note_order_id').val();
        let type    = $('#note_type').val();
        let note    = $('#note_modal_text').val();

        $.ajax({
            url: "{{ route('admin.order.update_note') }}",
            type: {{ __('"{{ __('POST') }}"') }},
            data: {
                _token: "{{ csrf_token() }}",
                order_id: orderId,
                note_type: type,
                note: note
            },
            success: function (res) {
                if (res.status === 'success') {
                    toastr.success('{{ __('Note') }} updated successfully');
                    let selector = '.note-modal-btn[data-id="' + orderId + '"][data-type="' + type + '"]';
                    let $btn = $(selector);
                    $btn.data('note', note);
                    $btn.text(note ? 'View' : 'Add');
                    $('#noteModal').modal('hide');
                } else {
                    toastr.error(res.{{ __('message') }} || 'Update failed');
                }
            },
            error: function () {
                toastr.error('Something went wrong');
            }
        });
    });

    // checkall
    $(".checkall").on('change',function(){
      $(".checkbox").prop('checked',$(this).is(":checked"));
    });

    // Fraud check → Popup Modal Open
    $(document).on('click', '.fraud-check', function(e){
        e.preventDefault();
        let mobile  = $(this).data('mobile');
        
        if (!mobile) { return toastr.error("No mobile {{ __('number') }} found"); }

        $("#fraudModalBody").html(`
            <div class="text-center py-5">
                <div class="spinner-border text-success" style="width:3rem;height:3rem;"></div>
                <p class="mt-3 fw-bold">{{ __('bn_14737c8e') }}</p>
            </div>
        `);

        $("#fraud{{ __('Check') }}Modal").modal("show");

        $.ajax({
            url: "{{ route('admin.fraud.check') }}",
            type: {{ __('"{{ __('POST') }}"') }},
            data: { 
                mobile: mobile,
                // আমরা এখানে order_id পাঠাচ্ছি না, কারণ কন্ট্রোলার মোবাইল নম্বর দিয়ে 
                // সব অর্ডার আপডেট করবে।
                _token: "{{ csrf_token() }}" 
            },
            timeout: 60000, // 60 seconds timeout
            beforeSend: function() {
                // Show loading state
                $("#fraudModalBody").html(`
                    <div class="text-center p-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">{{ __('Loading...') }}</span>
                        </div>
                        <p class="mt-3">{{ __('bn_8763f8fa') }}</p>
                    </div>
                `);
            },
            success: function(res) {
                
                if (res && res.status === "success") {
                    let apiData = {};
                    
                    if(res.data && res.data.data) {
                        apiData = res.data.data;
                    } else if (res.data) {
                        apiData = res.data;
                    }

                    // এখন আমরা পেইজে থাকা ওই {{ __('{{ __('Mobile') }} Number') }}ের *সব বাটন* খুঁজে বের করব
                    let allBtns = $('.fraud-check[data-mobile="'+mobile+'"]');

                    if(res.data && res.data.is_fraud === true) {
                         $("#fraudModalBody").html(`
                            <div class="alert alert-danger text-center p-5">
                                <h3>{{ __('bn_1b2f4505') }}</h3>
                                <p>{{ __('bn_8ec25fd5') }}</p>
                            </div>
                         `);
                         
                         // সব বাটন লাল করে দেওয়া
                         allBtns.removeClass('btn-warning text-dark btn-success').addClass('btn-danger').text('ফ্রড (ঝুঁকি)');
                         return;
                    }

                    // Build {{ __('Summary') }}
                    var summary = build{{ __('Summary') }}(apiData);
                    $("#fraudModalBody").html(loadFraudHtml(summary, mobile));

                    // ==========================================
                    // INSTANT BUTTON UPDATE LOGIC (ALL BUTTONS)
                    // ==========================================
                    let r = summary.rate;
                    
                    // আগের ক্লাস রিমুভ
                    allBtns.removeClass('btn-warning text-dark btn-success btn-danger');

                    if(r >= 80) {
                        // Safe
                        allBtns.addClass('btn-success');
                        allBtns.text(r + '% {{ __('bn_8704a028') }}');
                    } else {
                        // Risk
                        allBtns.addClass('btn-danger');
                        allBtns.text(r + '% ঝুঁকি');
                    }

                    toastr.success('{{ __('{{ __('Status') }}') }} {{ __('bn_fbbc3031') }}ভাবে সেভ হয়েছে!');

                } else {
                    var msg = (res && res.{{ __('message') }}) ? res.{{ __('message') }} : 'No data returned';
                    $("#fraudModalBody").html(`<div class="alert alert-danger text-center p-4">${msg}</div>`);
                }
            },

            error: function(xhr, status, error) {
                console.error('{{ __('Fraud {{ __('Check') }}') }} AJAX Error:', {
                    status: status,
                    error: error,
                    response: xhr.responseJSON,
                    statusCode: xhr.status
                });
                
                let error{{ __('Message') }} = 'অনুগ্রহ করে আবার চেষ্টা করুন।';
                
                if (xhr.responseJSON && xhr.responseJSON.{{ __('message') }}) {
                    error{{ __('Message') }} = xhr.responseJSON.{{ __('message') }};
                } else if (status === 'timeout') {
                    error{{ __('Message') }} = 'Request timeout! API server response নেওয়া যায়নি। অনুগ্রহ করে আবার চেষ্টা করুন।';
                } else if (status === 'error') {
                    error{{ __('Message') }} = 'Connection error! API server-এ connection করতে পারছে না।';
                } else if (xhr.status === 400) {
                    error{{ __('Message') }} = '{{ __('Inv') }}alid request! দয়া করে {{ __('{{ __('Mobile') }} Number') }} চেক করুন।';
                } else if (xhr.status === 500) {
                    error{{ __('Message') }} = 'Server error! দয়া করে admin-কে জানান।';
                } else if (xhr.status === 404) {
                    error{{ __('Message') }} = 'API endpoint not found!';
                }
                
                $("#fraudModalBody").html(`
                    <div class="alert alert-danger text-center p-4">
                        <h5>❌ {{ __('Error!') }}</h5>
                        <p>${error{{ __('Message') }}}</p>
                        ${xhr.responseJSON && xhr.responseJSON.{{ __('message') }} ? `<small>${xhr.responseJSON.{{ __('message') }}}</small>` : ''}
                    </div>
                `);
                
                // Reset button to original state
                let allBtns = $('.fraud-check[data-mobile="'+mobile+'"]');
                allBtns.removeClass('btn-success btn-danger').addClass('btn-warning').text('চেকিং');
                
                toastr.error('Fraud check failed: ' + error{{ __('Message') }});
            }
        });
    });

    // order assign
    $(document).on('{{ __('submit') }}', 'form#order_assign', function(e){
        e.preventDefault();
        var url = $(this).attr('action');
        let user_id = $('#user_id').val();

        var order = $('input.checkbox:checked').map(function(){
          return $(this).val();
        });
        var order_ids = order.get();

        if(order_ids.length == 0){
            toastr.error('Please Select An Order First !');
            return;
        }

        $.ajax({
           type: '{{ __('GET') }}',
           url: url,
           data: { user_id: user_id, order_ids: order_ids },
           success: function(res){
               if(res.status == 'success'){
                   toastr.success(res.{{ __('message') }});
                   window.location.reload();
               } else {
                   toastr.error(res.{{ __('message') }} || 'Failed something wrong');
               }
           },
           error: function(){
               toastr.error('Something went wrong');
           }
        });
    });

    // order status change
    $(document).on('{{ __('submit') }}', 'form#order_status_form', function(e){
        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation();
        
        var url = $(this).attr('action');
        let order_status = $('#order_status').val();
        var $statusSelect = $('#order_status');
        var $statusError = $('#status_error');
        
        // Clear any previous validation state
        $statusSelect.removeClass('is-invalid is-valid');
        $statusError.hide();

        var order = $('input.checkbox:checked').map(function(){
          return $(this).val();
        });
        var order_ids = order.get();

        // Validate orders selected FIRST
        if(order_ids.length == 0){
            toastr.error('Please Select An Order First !');
            return false;
        }
        
        // Validate status selected - check multiple conditions
        var status{{ __('Value') }} = {{ __('String') }}(order_status || '').trim();
        if(!status{{ __('Value') }} || status{{ __('Value') }} === '' || status{{ __('Value') }} === 'null' || status{{ __('Value') }} === 'undefined' || status{{ __('Value') }} === '0'){
            $statusSelect.addClass('is-invalid');
            $statusError.text('{{ __('Please select a status') }}').show();
            toastr.error('Please Select A {{ __('Status') }} First !');
            // Focus on select field and scroll to it
            $statusSelect.focus();
            $('html, body').animate({
                scrollTop: $statusSelect.offset().top - 100
            }, 300);
            return false;
        }
        
        // Additional check - make sure it's a valid {{ __('number') }}
        if(isNaN(parseInt(status{{ __('Value') }})) || parseInt(status{{ __('Value') }}) <= 0){
            $statusSelect.addClass('is-invalid');
            $statusError.text('Please select a valid status').show();
            toastr.error('Please Select A Valid {{ __('Status') }} !');
            $statusSelect.focus();
            return false;
        }

        // Show loading
        var $form = $(this);
        var $submitBtn = $form.find('button[type="{{ __('submit') }}"]');
        var originalHtml = $submitBtn.html();
        $submitBtn.prop('disabled', true).html('<i class="fe-loader"></i> Updating...');

        $.ajax({
           type: '{{ __('GET') }}',
           url: url,
           data: { order_status: order_status, order_ids: order_ids },
           success: function(res){
               if(res.status == 'success'){
                   toastr.success(res.{{ __('message') }});
                   $('#change{{ __('Status') }}').modal('hide');
                   setTimeout(function(){
                       window.location.reload();
                   }, 1000);
               } else {
                   toastr.error(res.{{ __('message') }} || 'Failed something wrong');
                   $submitBtn.prop('disabled', false).html(originalHtml);
               }
           },
           error: function(xhr){
               console.error('{{ __('Status') }} update error:', xhr);
               var errorMsg = 'Something went wrong';
               
               // Handle {{ __('Laravel') }} validation errors
               if(xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors){
                   var errors = xhr.responseJSON.errors;
                   if(errors.order_status){
                       $statusSelect.addClass('is-invalid');
                       $statusError.text(errors.order_status[0]).show();
                       errorMsg = errors.order_status[0];
                   } else if(errors.order_ids){
                       errorMsg = errors.order_ids[0];
                   }
               } else if(xhr.responseJSON && xhr.responseJSON.{{ __('message') }}){
                   errorMsg = xhr.responseJSON.{{ __('message') }};
               } else if(xhr.status === 400){
                   errorMsg = 'Bad request. Please check your selection.';
               }
               
               toastr.error(errorMsg);
               $submitBtn.prop('disabled', false).html(originalHtml);
           }
        });
        
        return false;
    });

    // order delete (bulk)
    $(document).on('click', '.order_delete', function(e){
        e.preventDefault();
        var url = $(this).attr('href');
        var order = $('input.checkbox:checked').map(function(){
          return $(this).val();
        });
        var order_ids = order.get();

        if(order_ids.length == 0){
            toastr.error('Please Select An Order First !');
            return;
        }

        $.ajax({
           type: '{{ __('GET') }}',
           url: url,
           data: { order_ids: order_ids },
           success: function(res){
               if(res.status == 'success'){
                   toastr.success(res.{{ __('message') }});
                   window.location.reload();
               } else {
                   toastr.error(res.{{ __('message') }} || 'Failed something wrong');
               }
           },
           error: function(){
               toastr.error('Something went wrong');
           }
        });
    });

    // multiple print
    $(document).on('click', '.multi_order_print', function(e){
        e.preventDefault();
        var url = $(this).attr('href');
        var order = $('input.checkbox:checked').map(function(){
          return $(this).val();
        });
        var order_ids = order.get();

        if(order_ids.length == 0){
            toastr.error('Please Select Atleast One Order!');
            return;
        }
        $.ajax({
           type: '{{ __('GET') }}',
           url: url,
           data: { order_ids: order_ids },
           success: function(res){
               if(res.status == 'success'){
                   var myWindow = window.open("", "_blank");
                   myWindow.document.write(res.view);
               } else {
                   toastr.error(res.{{ __('message') }} || 'Failed something wrong');
               }
           },
           error: function(){
               toastr.error('Something went wrong');
           }
        });
    });

    // label print
    $(document).on('click', '.multi_label_print', function(e){
        e.preventDefault();
        var order_ids = $('input.checkbox:checked').map(function(){ return $(this).val(); }).get();
        if(order_ids.length == 0){ toastr.error('Please Select Atleast One Order!'); return; }
        $.ajax({
            type: '{{ __('GET') }}',
            url: $(this).attr('href'),
            data: { order_ids: order_ids, type: 'label' },
            success: function(res){
                if(res.status == 'success'){
                    var w = window.open("","_blank");
                    w.document.write(res.view);
                } else { toastr.error(res.{{ __('message') }} || 'Failed'); }
            },
            error: function(){ toastr.error('Something went wrong'); }
        });
    });

    // multiple courier
    $(document).on('click', '.multi_order_courier', function(e){
        e.preventDefault();
        var url = $(this).attr('href');
        var order = $('input.checkbox:checked').map(function(){
          return $(this).val();
        });
        var order_ids = order.get();

        if(order_ids.length == 0){
            toastr.error('Please Select An Order First !');
            return;
        }
        
        // Show loading
        var $btn = $(this);
        var originalHtml = $btn.html();
        $btn.prop('disabled', true).html('<i class="fe-loader"></i> Sending...');

        $.ajax({
           type: '{{ __('GET') }}',
           url: url,
           data: { order_ids: order_ids },
           success: function(res){
               console.log('{{ __('Courier') }} Response:', res); // Debug log
               
               if(res.status == 'success'){
                    if(res.success && res.success.length > 0){
                        toastr.success('Orders sent to courier successfully!');
                    }
                    if(res.failed && res.failed.length > 0){
                        res.failed.forEach(function(fail){
                            console.error('Failed order:', fail);
                            toastr.warning('Order ' + fail.order_id + ': ' + fail.{{ __('message') }});
                        });
                    }
                    // Reload page to show courier information
                    setTimeout(function(){
                        window.location.reload();
                    }, 1000);
               } else {
                    toastr.error(res.{{ __('message') }} || 'Failed something wrong');
                    $btn.prop('disabled', false).html(originalHtml);
               }
           },
           error: function(xhr){
               console.error('{{ __('Courier') }} Error:', xhr);
               var errorMsg = 'Something went wrong';
               
               if(xhr.responseJSON){
                   // {{ __('Check') }} for failed orders with detailed {{ __('message') }}s
                   if(xhr.responseJSON.failed && xhr.responseJSON.failed.length > 0){
                       xhr.responseJSON.failed.forEach(function(fail){
                           var msg = fail.{{ __('message') }} || 'Failed to send order';
                           if(fail.status_code === 401){
                               msg = 'Account is not active! Please check your {{ __('Steadfast') }} account status and API credentials.';
                           } else if(fail.status_code === 403){
                               msg = 'Access forbidden! Please check your API credentials.';
                           } else if(fail.status_code === 404){
                               msg = 'API endpoint not found! Please check the API URL.';
                           }
                           toastr.error('Order ' + fail.order_id + ': ' + msg);
                       });
                   } else if(xhr.responseJSON.{{ __('message') }}){
                       errorMsg = xhr.responseJSON.{{ __('message') }};
                   }
               } else if(xhr.status === 401){
                   errorMsg = 'Account is not active! Please check your {{ __('Steadfast') }} account status and API credentials.';
               } else if(xhr.status === 403){
                   errorMsg = 'Access forbidden! Please check your API credentials.';
               } else if(xhr.status === 404){
                   errorMsg = 'API endpoint not found! Please check the API URL.';
               }
               
               toastr.error(errorMsg);
               $btn.prop('disabled', false).html(originalHtml);
           }
        });
    });

    // Quick {{ __('IP Block') }} from order page
    $(document).on('click', '.block-ip-btn', function(e){
        e.preventDefault();
        var $btn = $(this);
        var ip = $btn.data('ip');
        var reason = $btn.data('reason') || 'ফেইক অর্ডার';
        
        if(!ip){
            toastr.error('IP address not found');
            return;
        }
        
        // Disable button and show loading
        $btn.prop('disabled', true);
        var originalHtml = $btn.html();
        $btn.html('<i class="fe-loader"></i> Blocking...');
        
        $.ajax({
            url: "{{ route('customers.ipblock.quick') }}",
            type: {{ __('"{{ __('POST') }}"') }},
            data: {
                _token: "{{ csrf_token() }}",
                ip: ip,
                reason: reason
            },
            success: function(res){
                if(res.status === 'success'){
                    toastr.success(res.{{ __('message') }} || 'IP blocked successfully');
                    // {{ __('Change') }} button to show blocked state (badge style)
                    $btn.replaceWith('<span class="badge bg-secondary" title="This IP is already blocked"><i class="fe-shield"></i> {{ __('Blocked') }}</span>');
                } else {
                    toastr.error(res.{{ __('message') }} || 'Failed to block IP');
                    $btn.prop('disabled', false);
                    $btn.html(originalHtml);
                }
            },
            error: function(xhr){
                var errorMsg = 'Failed to block IP';
                if(xhr.responseJSON && xhr.responseJSON.{{ __('message') }}){
                    errorMsg = xhr.responseJSON.{{ __('message') }};
                }
                toastr.error(errorMsg);
                $btn.prop('disabled', false);
                $btn.html(originalHtml);
            }
        });
    });

    // {{ __('Pathao') }} Modal Open - Set selected order IDs
    $(document).on('click', '[data-bs-target="#pathao"]', function(e){
        var order = $('input.checkbox:checked').map(function(){
            return $(this).val();
        });
        var order_ids = order.get();
        
        if(order_ids.length == 0){
            toastr.error('Please Select Atleast One Order First!');
            e.preventDefault();
            return false;
        }
        
        $('#pathao_order_ids').val(order_ids.join(','));
    });

    // {{ __('Pathao') }} City {{ __('Change') }} - Load {{ __('Zone') }}s
    $(document).on('change', '#pathaocity', function(){
        var cityId = $(this).val();
        if(!cityId){
            $('#pathaozone').html('<option value="">{{ __('Select {{ __('Zone') }}...') }}</option>');
            $('#pathaoarea').html('<option value="">{{ __('Select {{ __('Area') }}...') }}</option>');
            return;
        }
        
        $.ajax({
            url: "{{ route('pathaocity') }}",
            type: "{{ __('GET') }}",
            data: { city_id: cityId },
            success: function(res){
                var options = '<option value="">{{ __('Select {{ __('Zone') }}...') }}</option>';
                if(res && res.data && res.data.data && res.data.data.length > 0){
                    $.each(res.data.data, function(key, zone){
                        options += '<option value="' + zone.zone_id + '">' + zone.zone_name + '</option>';
                    });
                } else {
                    toastr.warning('No zones found for this city');
                }
                $('#pathaozone').html(options);
                $('#pathaoarea').html('<option value="">{{ __('Select {{ __('Area') }}...') }}</option>');
            },
            error: function(xhr){
                var errorMsg = 'Failed to load zones';
                if(xhr.responseJSON && xhr.responseJSON.{{ __('message') }}){
                    errorMsg = xhr.responseJSON.{{ __('message') }};
                }
                toastr.error(errorMsg);
                $('#pathaozone').html('<option value="">{{ __('Select {{ __('Zone') }}...') }}</option>');
                $('#pathaoarea').html('<option value="">{{ __('Select {{ __('Area') }}...') }}</option>');
            }
        });
    });

    // {{ __('Pathao') }} {{ __('Zone') }} {{ __('Change') }} - Load {{ __('Area') }}s
    $(document).on('change', '#pathaozone', function(){
        var zoneId = $(this).val();
        if(!zoneId){
            $('#pathaoarea').html('<option value="">{{ __('Select {{ __('Area') }}...') }}</option>');
            return;
        }
        
        $.ajax({
            url: "{{ route('pathaozone') }}",
            type: "{{ __('GET') }}",
            data: { zone_id: zoneId },
            success: function(res){
                var options = '<option value="">{{ __('Select {{ __('Area') }}...') }}</option>';
                if(res && res.data && res.data.data && res.data.data.length > 0){
                    $.each(res.data.data, function(key, area){
                        options += '<option value="' + area.area_id + '">' + area.area_name + '</option>';
                    });
                } else {
                    toastr.warning('No areas found for this zone');
                }
                $('#pathaoarea').html(options);
            },
            error: function(xhr){
                var errorMsg = 'Failed to load areas';
                if(xhr.responseJSON && xhr.responseJSON.{{ __('message') }}){
                    errorMsg = xhr.responseJSON.{{ __('message') }};
                }
                toastr.error(errorMsg);
                $('#pathaoarea').html('<option value="">{{ __('Select {{ __('Area') }}...') }}</option>');
            }
        });
    });

    // {{ __('Pathao') }} Form Submit
    $(document).on('{{ __('submit') }}', '#order_sendto_pathao', function(e){
        e.preventDefault();
        
        var orderIds = $('#pathao_order_ids').val();
        if(!orderIds){
            toastr.error('Please select orders first');
            return;
        }
        
        var formData = $(this).serialize();
        formData += '&order_ids=' + orderIds.split(',').map(function({{ __('id)') }}{ return id.trim(); }).join(',');
        
        // Validate required fields
        if(!$('#pathaostore').val() || !$('#pathaocity').val() || !$('#pathaozone').val() || !$('#pathaoarea').val()){
            toastr.error('Please fill all required fields ({{ __('Store') }}, City, {{ __('Zone') }}, {{ __('Area') }})');
            return;
        }
        
        $.ajax({
            url: $(this).attr('action'),
            type: {{ __('"{{ __('POST') }}"') }},
            data: formData,
            success: function(res){
                if(res.status === 'success'){
                    var successCount = res.result.success ? res.result.success.length : 0;
                    var failedCount = res.result.failed ? res.result.failed.length : 0;
                    
                    if(successCount > 0){
                        toastr.success(successCount + ' order(s) sent to {{ __('Pathao') }} successfully');
                    }
                    if(failedCount > 0){
                        toastr.warning(failedCount + ' order(s) failed to send');
                    }
                    
                    $('#pathao').modal('hide');
                    setTimeout(function(){
                        window.location.reload();
                    }, 1500);
                } else {
                    toastr.error(res.{{ __('message') }} || 'Failed to send orders');
                }
            },
            error: function(xhr){
                var errorMsg = 'Failed to send orders';
                if(xhr.responseJSON && xhr.responseJSON.{{ __('message') }}){
                    errorMsg = xhr.responseJSON.{{ __('message') }};
                }
                toastr.error(errorMsg);
            }
        });
    });

});
</script>
@endsection