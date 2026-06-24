{{-- Main Slider + Sidebar Categories --}}
<section class="slider-section">
    <div class="container">
        <div class="row">
            <div class="col-sm-3 hidetosm">
                @include('frontEnd.layouts.sections.sidebar-categories')
            </div>
            <div class="col-sm-9">
                <div class="main_slider owl-carousel">
                    @foreach ($sliders as $key => $value)
                    <div class="slider_item">
                        <div class="slider_img">
                            <a href="{{ $value->link ?? '#' }}">
                                <img src="{{ asset($value->image) }}" class="img-fluid w-100" alt="Slider" />
                            </a>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</section>
