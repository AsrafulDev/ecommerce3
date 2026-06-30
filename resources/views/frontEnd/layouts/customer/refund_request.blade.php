@extends('frontEnd.layouts.master')
@section('title','Request Refund')

@section('content')
<section class="customer-section">
    <div class="container">
        <div class="row">

            <div class="col-sm-3">
                <div class="customer-sidebar">
                    @include('frontEnd.layouts.customer.sidebar')
                </div>
            </div>

            <div class="col-sm-9">
                <div class="customer-content">
                   <h5 class="account-title" style="color:#000;">{{ __('Request Refund') }}</h5>

                   <div class="card">
                       <div class="card-header bg-primary text-white">
                           <h6 class="mb-0">{{ __('Order Information') }}</h6>
                       </div>
                       <div class="card-body">
                           <div class="row">
                               <div class="col-md-6">
                                   <p><strong>{{ __('Order {{ __('{{ __('Inv') }}oice') }}') }}:</strong> #{{ $order->invoice_id }}</p>
                                   <p><strong>{{ __('Order {{ __('Date') }}') }}:</strong> {{ $order->created_at->format('d-m-Y h:i A') }}</p>
                                   <p><strong>{{ __('Order {{ __('Status') }}') }}:</strong> 
                                       <span class="badge bg-secondary">{{ $order->status ? $order->status->name : 'Pending' }}</span>
                                   </p>
                               </div>
                               <div class="col-md-6">
                                   <p><strong>{{ __('{{ __('Total') }} {{ __('Amount') }}') }}:</strong> ৳{{ number_format($order->amount, 2) }}</p>
                                   <p><strong>{{ __('{{ __('Shipping') }} Charge') }}:</strong> ৳{{ number_format($order->shipping_charge, 2) }}</p>
                                   <p><strong>{{ __('Grand {{ __('Total') }}') }}:</strong> 
                                       <strong class="text-primary">৳{{ number_format($order->amount + $order->shipping_charge, 2) }}</strong>
                                   </p>
                               </div>
                           </div>

                           <div class="mt-3">
                               <h6>{{ __('Order {{ __('{{ __('Item') }}s') }}') }}:</h6>
                               <table class="table table-sm table-bordered">
                                   <thead>
                                       <tr>
                                           <th>{{ __('Product') }}</th>
                                           <th>{{ __('Qty') }}</th>
                                           <th>{{ __('Price') }}</th>
                                       </tr>
                                   </thead>
                                   <tbody>
                                       @foreach($order->orderdetails as $item)
                                           <tr>
                                               <td>{{ $item->product_name }}</td>
                                               <td>{{ $item->qty }}</td>
                                               <td>৳{{ number_format($item->sale_price * $item->qty, 2) }}</td>
                                           </tr>
                                       @endforeach
                                   </tbody>
                               </table>
                           </div>
                       </div>
                   </div>

                   <form action="{{ route('customer.refunds.store') }}" method={{ __('"{{ __('POST') }}"') }} class="mt-4">
                       @csrf
                       <input type="hidden" name="order_id" value="{{ $order->id }}">

                       <div class="card">
                           <div class="card-header bg-warning text-dark">
                               <h6 class="mb-0">{{ __('Refund Details') }}</h6>
                           </div>
                           <div class="card-body">
                               <div class="row">
                                   <div class="col-md-6 mb-3">
                                       <label for="amount" class="form-label">{{ __('Refund {{ __('Amount') }}') }} <span class="text-danger">*</span></label>
                                       <input type="{{ __('number') }}" 
                                              class="form-control @error('amount') is-invalid @enderror" 
                                              id="amount" 
                                              name="amount" 
                                              value="{{ old('amount', $order->amount) }}" 
                                              min="1" 
                                              max="{{ $order->amount }}" 
                                              step="0.01" 
                                              required>
                                       <small class="text-muted">Maximum: ৳{{ number_format($order->amount, 2) }}</small>
                                       @error('amount')
                                           <div class="invalid-feedback">{{ $message }}</div>
                                       @enderror
                                   </div>

                                   <div class="col-md-6 mb-3">
                                       <label for="shipping_charge" class="form-label">{{ __('{{ __('{{ __('Shipping') }} Charge') }} Refund') }}</label>
                                       <input type="{{ __('number') }}" 
                                              class="form-control @error('shipping_charge') is-invalid @enderror" 
                                              id="shipping_charge" 
                                              name="shipping_charge" 
                                              value="{{ old('shipping_charge', $order->shipping_charge) }}" 
                                              min="0" 
                                              max="{{ $order->shipping_charge }}" 
                                              step="0.01">
                                       <small class="text-muted">Maximum: ৳{{ number_format($order->shipping_charge, 2) }}</small>
                                       @error('shipping_charge')
                                           <div class="invalid-feedback">{{ $message }}</div>
                                       @enderror
                                   </div>
                               </div>

                               <div class="mb-3">
                                   <label for="reason" class="form-label">{{ __('{{ __('Reason') }} for Refund') }} <span class="text-danger">*</span></label>
                                   <textarea class="form-control @error('reason') is-invalid @enderror" 
                                             id="reason" 
                                             name="reason" 
                                             rows="4" 
                                             required 
                                             placeholder="{{ __('Please explain why you want a refund...') }}">{{ old('reason') }}</textarea>
                                   @error('reason')
                                       <div class="invalid-feedback">{{ $message }}</div>
                                   @enderror
                               </div>

                               <div class="mb-3">
                                   <label for="refund_method" class="form-label">{{ __('Refund {{ __('Method') }}') }} <span class="text-danger">*</span></label>
                                   <select class="form-control @error('refund_method') is-invalid @enderror" 
                                           id="refund_method" 
                                           name="refund_method" 
                                           required>
                                       <option value="">{{ __('Select {{ __('Method') }}') }}</option>
                                       <option value="original_payment" {{ old('refund_method') == 'original_payment' ? 'selected' : '' }}>{{ __('{{ __('Original') }} {{ __('Payment {{ __('Method') }}') }}') }}</option>
                                       <option value="bkash" {{ old('refund_method') == 'bkash' ? 'selected' : '' }}>{{ __('bKash') }}</option>
                                       <option value="nagad" {{ old('refund_method') == 'nagad' ? 'selected' : '' }}>{{ __('Nagad') }}</option>
                                       <option value="bank" {{ old('refund_method') == 'bank' ? 'selected' : '' }}>{{ __('Bank Transfer') }}</option>
                                       <option value="manual" {{ old('refund_method') == 'manual' ? 'selected' : '' }}>{{ __('Manual/{{ __('Cash') }}') }}</option>
                                   </select>
                                   @error('refund_method')
                                       <div class="invalid-feedback">{{ $message }}</div>
                                   @enderror
                               </div>

                               <div class="mb-3">
                                   <label for="refund_account" class="form-label">{{ __('{{ __('Account Number') }}/{{ __('Phone') }}') }} <span class="text-danger">*</span></label>
                                   <input type="text" 
                                          class="form-control @error('refund_account') is-invalid @enderror" 
                                          id="refund_account" 
                                          name="refund_account" 
                                          value="{{ old('refund_account') }}" 
                                          required 
                                          placeholder="{{ __('Enter {{ __('bKash') }}/{{ __('Nagad') }} {{ __('number') }} or Bank account {{ __('number') }}') }}">
                                   @error('refund_account')
                                       <div class="invalid-feedback">{{ $message }}</div>
                                   @enderror
                               </div>

                               <div class="mb-3">
                                   <label for="refund_account_name" class="form-label">{{ __('{{ __('Account Holder') }} {{ __('Name') }}') }}</label>
                                   <input type="text" 
                                          class="form-control @error('refund_account_name') is-invalid @enderror" 
                                          id="refund_account_name" 
                                          name="refund_account_name" 
                                          value="{{ old('refund_account_name') }}" 
                                          placeholder="{{ __('Enter account holder name (if applicable)') }}">
                                   @error('refund_account_name')
                                       <div class="invalid-feedback">{{ $message }}</div>
                                   @enderror
                               </div>

                               <div class="alert alert-info">
                                   <i class="fa fa-info-circle"></i> 
                                   <strong>{{ __('Note') }}:</strong> Your refund request will be reviewed by our admin team. You will be notified once the refund is processed.
                               </div>

                               <div class="mt-4">
                                   <button type="{{ __('submit') }}" class="btn btn-primary">
                                       <i class="fa fa-paper-plane"></i> Submit Refund Request
                                   </button>
                                   <a href="{{ route('customer.orders') }}" class="btn btn-secondary">
                                       <i class="fa fa-arrow-left"></i> Back to Orders
                                   </a>
                               </div>
                           </div>
                       </div>
                   </form>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
