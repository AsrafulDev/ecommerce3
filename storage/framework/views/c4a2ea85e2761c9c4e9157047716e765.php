<?php
    $subtotal = Cart::instance('shopping')->subtotal();
    $subtotal=str_replace(',','',$subtotal);
    $subtotal=str_replace('.00', '',$subtotal);
    view()->share('subtotal',$subtotal);
?>
<a href="<?php echo e(route('customer.checkout')); ?>">
  <p class="margin-shopping">
  <i class="fa-solid fa-cart-shopping"></i>
  <span><?php echo e(Cart::instance('shopping')->count()); ?></span>
  
  </p></a>
  <div class="cshort-summary">
    <ul>
    <?php $__currentLoopData = Cart::instance('shopping')->content(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key=>$value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
      <li><a href=""><img src="<?php echo e(asset($value->options->image)); ?>" alt=""></a></li>
      <li><a href=""><?php echo e($value->name); ?></a></li>
      <li>Qty: <?php echo e($value->qty); ?></li>
      <li><p>৳<?php echo e($value->price); ?></p><button class="remove-cart cart_remove" data-id="<?php echo e($value->rowId); ?>"><i data-feather="x"></i></button></li>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </ul>
    <p><strong>সর্বমোট : ৳<?php echo e($subtotal); ?></strong></p>
    <a href="<?php echo e(route('customer.checkout')); ?>" class="go_cart">  অর্ডার করুন </a>
  </div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/feather-icons/4.29.0/feather.min.js"></script>
<script>
  feather.replace()
</script>
<!-- cart js start -->
    <script>

      $('.cart_remove').on('click',function(){
        var id = $(this).data('id');   
        $("#loading").show();
        if(id){
          $.ajax({
             type:"GET",
             data:{'id':id},
             url:"<?php echo e(route('cart.remove')); ?>",
             success:function(data){               
              if(data){
                $("#cartlist").html(data);
                $("#loading").hide();
                return cart_count()+cart_summary();
              }
             }
          });
         }  
       });

      function cart_count(){
          $.ajax({
             type:"GET",
             url:"<?php echo e(route('cart.count')); ?>",
             success:function(data){               
              if(data){
                $("#cart-qty").html(data);
              }else{
                 $("#cart-qty").empty();
              }
             }
          }); 
       };
      function cart_summary(){
          $.ajax({
             type:"GET",
             url:"<?php echo e(route('shipping.charge')); ?>",
             dataType: "html",
              success: function(response){
                  $('.cart-summary').html(response);
              }
          });
       };
    </script>
    <!-- cart js end --><?php /**PATH /home/creativedesignbd/ecommerce3.creativedesign.com.bd/resources/views/frontEnd/layouts/ajax/cart_count.blade.php ENDPATH**/ ?>