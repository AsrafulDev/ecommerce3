{{-- Top Categories Slider --}}
<section class="homeproduct">
    <div class="container">
        <div class="row">
            <div class="col-sm-12">
                <div class="sec_title">
                    <h3 class="section-title-header">
                        <div class="timer_inner">
                            <div class="">
                                <span class="section-title-name"> Top Categories </span>
                            </div>
                        </div>
                    </h3>
                </div>
            </div>
            <div class="col-sm-12">
                <div class="category-sliger owl-carousel">
                    @foreach ($menucategories as $key => $value)
                        <div>
                            <div class="text-center ">
                                <a href="{{ route('category', $value->slug) }}">
                                    <img class="" src="{{ asset($value->image) }}" alt="" style="border: 2px solid #3c7d17; border-radius: 50%; width: 100%; height: auto;" />
                                </a>
                            </div>
                            <div class="cat_name_style">
                                <a href="{{ route('category', $value->slug) }}">
                                    <p>{{ $value->name }}</p>
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</section>
