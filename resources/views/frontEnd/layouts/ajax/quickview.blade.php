@php
    // ⭐ Batch-aware price label (attached by controller; falls back to static columns)
    $qHasRange = !is_null($data->price_min ?? null) && !is_null($data->price_max ?? null);
    $qSaleMin  = $qHasRange ? (float) $data->price_min : (float) ($data->new_price ?? 0);
    $qSaleMax  = $qHasRange ? (float) $data->price_max : $qSaleMin;
    $qMrpMin   = ($qHasRange ? ($data->mrp_min ?? null) : null) ?? (($data->old_price ?? 0) ? (float) $data->old_price : null);
    $qMrpMax   = $qHasRange ? ($data->mrp_max ?? $qMrpMin) : $qMrpMin;
    $qSale     = ($qSaleMax > $qSaleMin) ? number_format($qSaleMin, 0) . ' - ' . number_format($qSaleMax, 0) : number_format($qSaleMax, 0);
    $qMrp      = ($qMrpMin !== null && $qMrpMax !== null && $qMrpMax > $qMrpMin)
                ? number_format($qMrpMin, 0) . ' - ' . number_format($qMrpMax, 0)
                : ($qMrpMax !== null ? number_format($qMrpMax, 0) : null);
@endphp
<div class="modal-view quick-product">
	<button class="close-modal">x</button>
	<div class="quick-product-img">
		<img src="{{asset($data->image->image)}}" alt="">
	</div>
	<div class="quick-product-content">
		<div class="product-details-cart">
            <p class="name">{{$data->name}}</p>
             <p style="display: none;" class="product_star"><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i> ({{$data->reviews_count}} customer review)</p>
            <p class="details-price">৳{{$qSale}} @if($qMrp)<del>৳{{$qMrp}}</del>@endif</p>
            <div class="details_short">
                {!! $data->short_description !!}
            </div>
            <form action="{{route('cart.store')}}" method="POST" class="ajax-cart-form">
                @csrf
                <input type="hidden" name="id" value="{{$data->id}}">                
                
                <div class="qty-cart">
                    <div class="quantity">
                        <span class="minus">-</span>
                        <input type="text" name="qty" value="1"/>
                        <span class="plus">+</span>
                    </div>
                    <button type="submit" class="add-to-cart cart_store" data-id="{{$data->id}}" data-name="{{ addslashes($data->name) }}" data-price="{{ $qSaleMax }}" data-category="{{ addslashes($data->category->name ?? '') }}">add to cart</button>
                </div>
            </form>
            <a href="{{route('product',['id'=>$data->id])}}" style="display: none;" class="details-wishlist">Go To Details</a>
            <div class="col-12 mt-3 delivery_details">
                <table class="table">
                    <tbody>                                    
                        <tr>
                            <td class="potro_font">
                               Category: {{ $data->category->name }}
                            </td>                                        
                        </tr>
                        <tr>
                            <td class="potro_font">
                               Brand: {{ $data->brand ? $data->brand->name : '' }}
                            </td>                                        
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
	</div>
</div>
<script src="{{asset('public/frontEnd/js/jquery-3.6.3.min.js')}}"></script>
<script>
	$('.close-modal').on('click',function(){
        $("#custom-modal").hide();
        $("#page-overlay").hide();
     });
</script>
<script>
    $(document).ready(function() {
        $('.minus').click(function () {
            var $input = $(this).parent().find('input');
            var count = parseInt($input.val()) - 1;
            count = count < 1 ? 1 : count;
            $input.val(count);
            $input.change();
            return false;
        });
        $('.plus').click(function () {
            var $input = $(this).parent().find('input');
            $input.val(parseInt($input.val()) + 1);
            $input.change();
            return false;
        });
    });
</script>
<!-- cart js start -->