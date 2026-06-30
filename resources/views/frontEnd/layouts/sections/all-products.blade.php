{{-- {{ __('All {{ __('Product') }}s') }} Grid Section --}}
@if($generalsetting->show_all_products)
<section class="homeproduct">
    <div class="container">
        <div class="row">
            <div class="col-sm-12">
                <div class="sec_title">
                    <h3 class="section-title-header">
                        <div class="timer_inner">
                            <div class="">
                                <span class="section-title-name">{{ __('All {{ __('Product') }}s') }}</span>
                            </div>
                        </div>
                    </h3>
                </div>
            </div>
            <div class="col-sm-12">
                <div class="category-product main_product_inner">
                    @foreach($all_products as $key => $value)
                        @include('frontEnd.layouts.sections.product-card', ['product' => $value])
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</section>
@endif
