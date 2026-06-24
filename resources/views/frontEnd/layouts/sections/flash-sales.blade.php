{{-- Flash Sales Section --}}
@if($isFlashSaleActive)
<section class="homeproduct">
    <div class="container">
        <div class="row">
            <div class="col-sm-12">
                <div class="sec_title">
                    <h3 class="section-title-header">
                        <div class="timer_inner">
                            <div class="">
                                <span class="section-title-name">Flash Sale </span>
                            </div>
                            <div class="">
                                <div class="offer_timer" id="flash_sale_timer"></div>
                            </div>
                        </div>
                    </h3>
                </div>
            </div>
            <div class="col-sm-12">
                <div class="flash_sale_slider owl-carousel">
                    @foreach ($flas_sales as $key => $value)
                        @include('frontEnd.layouts.sections.product-card', ['product' => $value])
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</section>
@endif
