@foreach($cartinfo as $key=>$value)
<tr>
  <td><img height="30" src="{{asset($value->options->image)}}"></td>
  <td>
      <div class="fw-semibold">{{$value->name}}</div>
        @php
            $product = \App\Models\{{ __('Product') }}::find($value->{{ __('id)') }};
            $sizesList = collect();
            $colorsList = collect();
            if ($product) {
                $sizeIds = \App\Models\{{ __('Product') }}VariantPrice::w{{ __('here') }}('product_id', $product->{{ __('id)') }}->w{{ __('here') }}NotNull('size_id')->pluck('size_id')->unique()->{{ __('filter') }}();
                $colorIds = \App\Models\{{ __('Product') }}VariantPrice::w{{ __('here') }}('product_id', $product->{{ __('id)') }}->w{{ __('here') }}NotNull('color_id')->pluck('color_id')->unique()->{{ __('filter') }}();
                if ($sizeIds->isNotEmpty()) {
                    $sizesList = \App\Models\Size::w{{ __('here') }}In('id', $sizeIds)->get();
                }
                if ($colorIds->isNotEmpty()) {
                    $colorsList = \App\Models\{{ __('Color') }}::w{{ __('here') }}In('id', $colorIds)->get();
                }
                if ($sizesList->isEmpty() && $colorsList->isEmpty()) {
                    $sizesList = $product->sizes ?? collect();
                    $colorsList = $product->colors ?? collect();
                }
            }
            $has{{ __('Sizes') }} = $sizesList->isNotEmpty();
            $has{{ __('{{ __('Color') }}s') }} = $colorsList->isNotEmpty();
            $currentSizeId = $value->options->size_id ?? '';
            $current{{ __('Color') }}Id = $value->options->color_id ?? '';
        @endphp

       @if($has{{ __('Sizes') }} || $has{{ __('{{ __('Color') }}s') }})
        <div class="d-flex flex-column gap-1 mt-2">
            @if($has{{ __('Sizes') }})
            <div>
                <label class="form-label small text-muted mb-0" style="font-size:11px">{{ __('Size') }}</label>
                <select class="form-select form-select-sm cart-size-selector" data-id="{{ $value->rowId }}" data-product-id="{{ $value->id }}" style="min-width:100px">
                    <option value="">{{ __('Select') }}</option>
                    @foreach($sizesList as $s)
                    <option value="{{ $s->id }}" {{ $currentSizeId == $s->id ? 'selected' : '' }}>{{ $s->size{{ __('Name') }} ?? $s->size_name ?? '{{ __('N/A') }}' }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            @if($has{{ __('{{ __('Color') }}s') }})
            <div>
                <label class="form-label small text-muted mb-0" style="font-size:11px">{{ __('{{ __('Color') }}') }}</label>
                <select class="form-select form-select-sm cart-color-selector" data-id="{{ $value->rowId }}" data-product-id="{{ $value->id }}" style="min-width:100px">
                    <option value="">{{ __('Select') }}</option>
                    @foreach($colorsList as $c)
                    <option value="{{ $c->id }}" {{ $current{{ __('Color') }}Id == $c->id ? 'selected' : '' }}>{{ $c->color{{ __('Name') }} ?? $c->color_name ?? '{{ __('N/A') }}' }}</option>
                    @endforeach
                </select>
            </div>
            @endif
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
  <td>{{$value->price}}</td>
  <td>{{$value->price * $value->qty}}</td>
  <td class="text-center">
    <button type="button" class="btn btn-light btn-sm cart_remove" data-id="{{$value->rowId}}">
        <i class="fa fa-times text-danger"></i>
    </button>
  </td>
</tr>
@endforeach
