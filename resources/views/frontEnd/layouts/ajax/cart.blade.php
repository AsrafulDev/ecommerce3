@php
    $sub{{ __('total') }} = {{ __('Cart') }}::instance('shopping')->sub{{ __('total') }}();
    $sub{{ __('total') }}=str_replace(',','',$sub{{ __('total') }});
    $sub{{ __('total') }}=str_replace('.00', '',$sub{{ __('total') }});
    $shipping = {{ __('Session') }}::get('shipping')?{{ __('Session') }}::get('shipping'):0;
    $discount = {{ __('Session') }}::get('discount')?{{ __('Session') }}::get('discount'):0;
@endphp
<table class="cart_table table table-bordered table-striped text-center mb-0">
        <thead>
         <tr>
          <th style="width: 20%;">{{ __('Delete') }}</th>
          <th style="width: 40%;">{{ __('{{ __('Product') }}s') }}</th>
          <th style="width: 20%;">{{ __('Quantity') }}</th>
          <th style="width: 20%;">{{ __('Price') }}</th>
         </tr>
        </thead>

        <tbody>
         @foreach({{ __('Cart') }}::instance('shopping')->content() as $value)
         <tr>
          <td>
           <a class="cart_remove" data-id="{{$value->rowId}}"><i class="fas fa-trash text-danger"></i></a>
          </td>
          <td class="text-left">
           <a href="{{route('product',$value->options->slug)}}"> <img src="{{asset($value->options->image)}}" style="height:30px;width:30px" /> {{Str::limit($value->name,20)}}</a>
           @if($value->options->product_size)
            <p>Size: {{$value->options->product_size}}</p>
           @endif
           @if($value->options->product_color)
           <p>{{ __('Color') }}: {{ $value->options->product_color }}</p>
           @endif
          </td>
          <td class="cart_qty">
           <div class="qty-cart vcart-qty">
            <div class="quantity">
             <button class="minus cart_decrement" data-id="{{$value->rowId}}">-</button>
             <input type="text" value="{{$value->qty}}" readonly />
             <button class="plus cart_increment" data-id="{{$value->rowId}}">+</button>
            </div>
           </div>
          </td>
          <td><span class="alinur">৳ </span><strong>{{$value->price}}</strong></td>
         </tr>
         @endforeach
        </tbody>
        <tfoot>
         <tr>
          <th colspan="3" class="text-end px-4">{{ __('bn_70ac0f2d') }}</th>
          <td>
           <span id="net_{{ __('total') }}"><span class="alinur">৳ </span><strong>{{$sub{{ __('total') }}}}</strong></span>
          </td>
         </tr>
         <tr>
          <th colspan="3" class="text-end px-4">{{ __('bn_99838c8f') }}</th>
          <td>
           <span id="cart_shipping_cost"><span class="alinur">৳ </span><strong>{{$shipping}}</strong></span>
          </td>
         </tr>
         @if({{ __('Session') }}::get('discount', 0) > 0)
         <tr>
            <th colspan="3" class="text-end px-4">{{ __('bn_a13a244a') }}</th>
            <td>
                <span id="discount"><span class="alinur">৳ </span><strong>{{ {{ __('Session') }}::get('discount', 0) }}</strong></span>
            </td>
        </tr>
        @endif
         <tr>
          <th colspan="3" class="text-end px-4">{{ __('Total') }}</th>
          <td>
           <span id="grand_{{ __('total') }}"><span class="alinur">৳ </span><strong>{{$sub{{ __('total') }}+$shipping-{{ __('Session') }}::get('discount', 0)}}</strong></span>
          </td>
         </tr>
        </tfoot>
       </table>

<script src="{{asset('public/frontEnd/js/jquery-3.6.3.min.js')}}"></script>
<!-- cart js start -->
<script>
    $('.cart_store').on('click',function(){
    var id = $(this).data('id'); 
    var qty = $(this).parent().find('input').val();
    if({{ __('id)') }}{
        $.ajax({
           type:"{{ __('GET') }}",
           data:{'id':id,'qty':qty?qty:1},
           url:"{{route('cart.store')}}",
           success:function(data){               
            if(data){
                return cart_count();
            }
           }
        });
     }  
   });

    $('.cart_remove').on('click',function(){
    var id = $(this).data('id');   
    if({{ __('id)') }}{
        $.ajax({
           type:"{{ __('GET') }}",
           data:{'id':id},
           url:"{{route('cart.remove')}}",
           success:function(data){               
            if(data){
                $(".cartlist").html(data);
                return cart_count();
            }
           }
        });
     }  
   });
   

    $('.cart_increment').on('click',function(){
    var id = $(this).data('id');  
    if({{ __('id)') }}{
        $.ajax({
           type:"{{ __('GET') }}",
           data:{'id':id},
           url:"{{route('cart.increment')}}",
           success:function(data){               
            if(data){
                $(".cartlist").html(data);
                return cart_count();
            }
           }
        });
     }  
   });

    $('.cart_decrement').on('click',function(){
    var id = $(this).data('id');  
    if({{ __('id)') }}{
        $.ajax({
           type:"{{ __('GET') }}",
           data:{'id':id},
           url:"{{route('cart.decrement')}}",
           success:function(data){               
            if(data){
                $(".cartlist").html(data);
                return cart_count();
            }
           }
        });
     }  
   });
    
    function cart_count(){
        $.ajax({
           type:"{{ __('GET') }}",
           url:"{{route('cart.count')}}",
           success:function(data){               
            if(data){
                $("#cart-qty").html(data);
            }else{
               $("#cart-qty").empty();
            }
           }
        }); 
   };
   
</script>
<!-- cart js end -->