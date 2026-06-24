{{-- Slider Bottom Ads --}}
<section>
    <div class="container">
        <div class="row">
            @foreach($sliderbottomads as $bottomAds)
            <div class="col-md-12">
                <a href="{{$bottomAds->link}}?sold=show">
                    <img class="img-fluid w-100" src="{{$bottomAds->image}}"/>
                </a>
            </div>
            @endforeach
        </div>
    </div>
</section>
