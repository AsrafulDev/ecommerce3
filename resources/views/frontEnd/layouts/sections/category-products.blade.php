{{-- {{ __('Category') }}-wise {{ __('Product') }}s Section --}}
@if($generalsetting->show_category_wise_products)
    @foreach ($homeproducts as $homecat)
        <section class="homeproduct">
            <div class="container">
                <div class="row">
                    <div class="col-sm-12">
                        <div class="sec_title">
                            <h3 class="section-title-header">
                                <span class="section-title-name">{{ $homecat->name }}</span>
                            </h3>
                        </div>
                    </div>
                    <div class="col-sm-12">
                        <div class="product_sliders">
                            @foreach ($homecat->products as $key => $value)
                                @include('frontEnd.layouts.sections.product-card', ['product' => $value])
                            @endforeach
                        </div>
                    </div>
                    <div class="col-sm-12">
                        <div class="show_more_btn">
                            <a href="{{ route('category', $homecat->slug) }}" class="view_more_btn">{{ __('View More') }}</a>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    @endforeach
@endif
