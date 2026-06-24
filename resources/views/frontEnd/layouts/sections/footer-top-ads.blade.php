{{-- Footer Top Ads Section --}}
<section>
    <div class="container">
        <div class="row">
            @foreach($footertopads as $footerAds)
            <div class="col-md-12">
                <a href="{{$footerAds->link}}?sold=show">
                    <img class="img-fluid w-100" src="{{$footerAds->image}}"/>
                </a>
            </div>
            @endforeach
        </div>
    </div>
</section>
