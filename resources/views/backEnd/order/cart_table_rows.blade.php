@foreach($cartinfo as $key=>$value)
<tr>
  <td><img height="30" src="{{asset($value->options->image)}}" onerror="this.src='{{ asset('public/assets/images/placeholder.webp') }}'"></td>
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
        <div class="d-flex flex-wrap gap-1 mt-2">
            @if($hasSizes)
            <div class="{{ $hasColors ? 'flex-fill' : 'w-100' }}">
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
            <div class="{{ $hasSizes ? 'flex-fill' : 'w-100' }}">
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
                        {{ $wt->warranty_days > 0 ? $wt->tier_name : $wt->tier_name }}
                        {{ $adj != 0 ? ($adj > 0 ? '+'.$adj : $adj).' TK' : '' }}
                    </option>
                @endforeach
            </select>
        </div>
        @endif

        {{-- 📦 Batch --}}
        @php
            $selectedBatchId = $value->options->batch_id ?? null;
            $batches = $product ? \App\Models\StockBatch::where('product_id', $product->id)
                ->where('pos_enabled', true)
                ->where(function ($q) use ($selectedBatchId) {
                    $q->where('remaining_qty', '>', 0);
                    // ✅ Always include the currently-assigned batch so it shows as selected
                    if ($selectedBatchId) {
                        $q->orWhere('id', $selectedBatchId);
                    }
                })
                ->orderBy('id', 'asc')
                ->get() : collect();
        @endphp
        @if($batches->isNotEmpty())
        <div class="mt-1">
            <label class="form-label small text-muted mb-0" style="font-size:11px">{{ __('Batch') }} <small>({{ $batches->sum('remaining_qty') }} avail)</small></label>
            <select class="form-select form-select-sm cart-batch-selector" data-id="{{ $value->rowId }}" data-product-id="{{ $pid }}" style="min-width:130px;font-size:11px;">
                <option value="">{{ __('Auto') }}</option>
                @foreach($batches as $b)
                    <option value="{{ $b->id }}" {{ (string) ($value->options->batch_id ?? '') === (string) $b->id ? 'selected' : '' }}>
                        {{ $b->batch_no ?: 'Batch #'.$b->id }} ({{ $b->remaining_qty }} @ ৳{{ number_format($b->selling_price ?? $b->unit_cost, 2) }})
                        {{ $b->exp_date ? ' Exp: '.$b->exp_date->format('Y-m-d') : '' }}
                        {{ $b->supplier_warranty_days ? ' 🛡️'.$b->supplier_warranty_days.'d' : '' }}
                    </option>
                @endforeach
            </select>
        </div>
        @endif

        {{-- 🔢 Serial Numbers —
             • Product/batch already has SN inventory recorded  → one SELECT per unit
               (qty), picked from that batch's in-stock SNs.
             • No SN inventory recorded yet → "Add SN" button reveals one free-text
               box per unit (qty), same style as the purchase-entry SN list. --}}
        @php
            $effectiveBatch = $selectedBatchId
                ? $batches->firstWhere('id', $selectedBatchId)
                : $batches->first();
            $availableSns = ($effectiveBatch && is_array($effectiveBatch->sn_stock)) ? array_values($effectiveBatch->sn_stock) : [];
            $chosenSns = is_array($value->options->serial_numbers ?? null) ? $value->options->serial_numbers : [];
        @endphp
        @if(!empty($availableSns))
            <div class="mt-1 sn-select-container" data-id="{{ $value->rowId }}" data-product-id="{{ $pid }}">
                <label class="form-label small text-muted mb-1" style="font-size:11px">
                    {{ __('Product SN') }} <small>(qty: {{ $value->qty }})</small>
                </label>
                @for($i = 0; $i < $value->qty; $i++)
                    <select class="form-select form-select-sm cart-sn-select mb-1" style="font-size:11px;">
                        <option value="">{{ __('Select SN') }} #{{ $i + 1 }}</option>
                        @foreach($availableSns as $sn)
                            <option value="{{ $sn }}" {{ ($chosenSns[$i] ?? null) === $sn ? 'selected' : '' }}>{{ $sn }}</option>
                        @endforeach
                    </select>
                @endfor
            </div>
        @else
            @php $hasTypedSns = !empty(array_filter($chosenSns)); @endphp
            <div class="mt-1 sn-manual-container" data-id="{{ $value->rowId }}" data-product-id="{{ $pid }}">
                <button type="button" class="btn btn-sm btn-outline-secondary toggle-cart-sn {{ $hasTypedSns ? 'active btn-dark' : '' }}" style="font-size:10px;">
                    <i class="fe-hash"></i> {{ __('Add SN') }} <small>({{ __('qty') }}: {{ $value->qty }})</small>
                </button>
                <div class="cart-sn-inputs mt-1" style="display:{{ $hasTypedSns ? 'block' : 'none' }};">
                    @for($i = 0; $i < $value->qty; $i++)
                        <div class="input-group input-group-sm mb-1 sn-input-row" style="flex-wrap:nowrap;">
                            <span class="input-group-text bg-light text-muted" style="font-size:10px;">{{ $i + 1 }}</span>
                            <input type="text"
                                   class="form-control form-control-sm cart-sn-input"
                                   placeholder="SN {{ $i + 1 }}"
                                   value="{{ $chosenSns[$i] ?? '' }}"
                                   style="font-size:11px;">
                        </div>
                    @endfor
                </div>
            </div>
        @endif
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
