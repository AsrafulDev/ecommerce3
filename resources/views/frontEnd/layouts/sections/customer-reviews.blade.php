{{-- Customer Reviews Section --}}
@if($reviews->count() > 0)
<section class="homeproduct">
    <div class="container">
        <div class="row">
            <div class="col-sm-12">
                <div class="sec_title">
                    <h5 class="text-center text-light py-2" style="background-color:var(--secondary-color)">
                        {{ __('Customer Reviews') }}
                    </h5>
                </div>
            </div>
            <div class="col-sm-12">
                <div class="customer-review owl-carousel">
                    @foreach ($reviews as $review)
                    <div class="border rounded">
                        <img class="img-fluid w-100" src="{{ asset($review->image) }}" />
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</section>
@endif
