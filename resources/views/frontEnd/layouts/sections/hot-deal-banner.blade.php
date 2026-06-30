{{-- Hot Deal Banner Section --}}
@if(isset($hotDealBanner))
<section class="homeproduct">
    <div class="container">
        <div class="row">
            <div class="col-sm-12">
                <a href="{{ $hotDealBanner->link ?? '#' }}">
                    <img class="img-fluid w-100" src="{{ asset($hotDealBanner->image ?? '') }}" alt="Hot Deal Banner" />
                </a>
            </div>
        </div>
    </div>
</section>
@endif
