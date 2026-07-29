@foreach($cartinfo as $key=>$value)
<tr>
  <td><img height="30" src="{{asset($value->options->image)}}" onerror="this.src='{{ asset('public/assets/images/no-image.png') }}'"></td>
  <td>
      <div class="fw-semibold">{{$value->name}}</div>
        @php
            $pid = $value->options->product_id ?? $value->id;
            $product = \App\Models\Product::find($pid);
            $warrantyTiers = $product ? \App\Models\ProductWarrantyTier::where('product_id', $product->id)->where('is_active', true)->orderBy('sort_order')->get() : collect();
            $currentWarrantyId = $value->options->warranty_tier_id ?? '';
            $sizesList = collect();
            $colorsList = collect();
            if ($product) {
                $sizeIds = \App\Models\ProductVariantPrice::where('product_id', $product->id)->whereNotNull('size_id')->pluck('size_id')->unique()->filter();
                $colorIds = \App\Models\ProductVariantPrice::where('product_id', $product->id)->whereNotNull('color_id')->pluck('color_id')->unique()->filter();
                if ($sizeIds->isNotEmpty()) {
                    $sizesList = \App\Models\Size::whereIn('id', $sizeIds)->get();
                }
                if ($colorIds->isNotEmpty()) {
                    $colorsList = \App\Models\Color::whereIn('id', $colorIds)->get();
                }
                if ($sizesList->isEmpty() && $colorsList->isEmpty()) {
                    $sizesList = $product->sizes ?? collect();
                    $colorsList = $product->colors ?? collect();
                }
            }
            $hasSizes = $sizesList->isNotEmpty();
            $hasColors = $colorsList->isNotEmpty();
            $currentSizeId = $value->options->size_id ?? '';
            $currentColorId = $value->options->color_id ?? '';
        @endphp

       @if($hasSizes || $hasColors)
        <div class="d-flex flex-column gap-1 mt-2">
            @if($hasSizes)
            <div>
                <label class="form-label small text-muted mb-0" style="font-size:11px">{{ __('Size') }}</label>
                <select class="form-select form-select-sm cart-size-selector" data-id="{{ $value->rowId }}" data-product-id="{{ $pid }}" style="min-width:100px">
                    <option value="">{{ __('Select') }}</option>
                    @foreach($sizesList as $s)
                    <option value="{{ $s->id }}" {{ $currentSizeId == $s->id ? 'selected' : '' }}>{{ $s->sizeName ?? $s->size_name ?? 'N/A' }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            @if($hasColors)
            <div>
                <label class="form-label small text-muted mb-0" style="font-size:11px">{{ __('Color') }}</label>
                <select class="form-select form-select-sm cart-color-selector" data-id="{{ $value->rowId }}" data-product-id="{{ $pid }}" style="min-width:100px">
                    <option value="">{{ __('Select') }}</option>
                    @foreach($colorsList as $c)
                    <option value="{{ $c->id }}" {{ $currentColorId == $c->id ? 'selected' : '' }}>{{ $c->colorName ?? $c->color_name ?? 'N/A' }}</option>
                    @endforeach
                </select>
            </div>
            @endif
        </div>
        @endif

        {{-- 🛡️ Warranty --}}
        @if($warrantyTiers->isNotEmpty())
        <div class="mt-1">
            <label class="form-label small text-muted mb-0" style="font-size:11px">{{ __('Warranty') }}</label>
            <select class="form-select form-select-sm cart-warranty-selector" data-id="{{ $value->rowId }}" data-product-id="{{ $pid }}" style="min-width:130px;font-size:11px;">
                @foreach($warrantyTiers as $wt)
                    @php $adj = (float)($wt->additional_cost ?? 0); @endphp
                    <option value="{{ $wt->id }}" {{ $currentWarrantyId == $wt->id ? 'selected' : '' }}>
                        {{ $wt->warranty_days > 0 ? $wt->tier_name.' ('.$wt->warranty_days.'d)' : $wt->tier_name }}
                        {{ $adj != 0 ? ($adj > 0 ? '+'.$adj : $adj).' TK' : '' }}
                    </option>
                @endforeach
            </select>
        </div>
        @endif

        {{-- 📦 Batch --}}
        @php $batches = $product ? \App\Models\StockBatch::where('product_id', $product->id)->where('remaining_qty', '>', 0)->orderBy('id','asc')->get() : collect(); @endphp
        @if($batches->isNotEmpty())
        <div class="mt-1">
            <label class="form-label small text-muted mb-0" style="font-size:11px">{{ __('Batch') }} <small>({{ $batches->sum('remaining_qty') }} avail)</small></label>
            <select class="form-select form-select-sm cart-batch-selector" data-id="{{ $value->rowId }}" data-product-id="{{ $pid }}" style="min-width:120px;font-size:11px;">
                <option value="">{{ __('Auto') }}</option>
                @foreach($batches as $b)
                    <option value="{{ $b->id }}" {{ ($value->options->batch_id ?? '') == $b->id ? 'selected' : '' }}>
                        {{ $b->batch_no ?: 'Batch #'.$b->id }} ({{ $b->remaining_qty }} @ ৳{{ $b->unit_cost }})
                        {{ $b->expiry_date ? ' Exp: '.$b->expiry_date->format('Y-m-d') : '' }}
                        {{ $b->supplier_warranty_days ? ' 🛡️'.$b->supplier_warranty_days.'d' : '' }}
                    </option>
                @endforeach
            </select>
        </div>
        @endif

        {{-- 🔢 Serial Numbers — per-unit input with add/remove --}}
        <div class="mt-1 sn-inputs-container" data-id="{{ $value->rowId }}" data-product-id="{{ $pid }}">
            <label class="form-label small text-muted mb-1" style="font-size:11px">
                {{ __('Product SN') }} <small>(qty: {{ $value->qty }})</small>
            </label>
            @php
                $sns = is_array($value->options->serial_numbers ?? null) ? $value->options->serial_numbers : [];
                if (empty($sns)) $sns = [''];
            @endphp
            @foreach($sns as $i => $sn)
            <div class="input-group input-group-sm mb-1 sn-input-row">
                <input type="text"
                       class="form-control form-control-sm cart-sn-input"
                       data-id="{{ $value->rowId }}"
                       data-product-id="{{ $pid }}"
                       placeholder="Scan/type SN..."
                       value="{{ $sn }}"
                       style="font-size:11px;">
                <button type="button" class="btn btn-sm btn-outline-danger sn-remove-btn" title="Remove SN" tabindex="-1">×</button>
            </div>
            @endforeach
            <button type="button" class="btn btn-sm btn-outline-secondary sn-add-btn" style="font-size:10px;" tabindex="-1">＋ Add SN</button>
        </div>
  </td>
  <td>
    <div class="qty-cart vcart-qty">
      <div class="quantity">
          <button class="minus cart_decrement" value="{{$value->qty}}" data-id="{{$value->rowId}}">-</button>
          <input type="text" value="{{$value->qty}}" readonly />
          <button class="plus cart_increment" value="{{$value->qty}}" data-id="{{$value->rowId}}">+</button>
      </div>
  </div>
  </td>
  <td>
    @php
        $wa = $value->options->warranty_adjustment ?? 0;
        $bp = $value->options->base_price ?? $value->price;
    @endphp
    @if($wa != 0)
      <small class="text-muted text-decoration-line-through">{{$bp}}</small>
      <span class="d-block fw-semibold">{{$value->price}}</span>
      @if($wa > 0)<small class="text-success">+{{$wa}} warranty</small>@endif
    @else
      {{$value->price}}
    @endif
  </td>
  <td>{{$value->price * $value->qty}}</td>
  <td class="text-center">
    <button type="button" class="btn btn-light btn-sm cart_remove" data-id="{{$value->rowId}}">
        <i class="fa fa-times text-danger"></i>
    </button>
  </td>
</tr>
@endforeach
