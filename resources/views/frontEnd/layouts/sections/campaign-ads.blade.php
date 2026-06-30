{{-- {{ __('Campaign') }} Ads Section --}}
<section>
    <div class="container">
        <div class="row">
          @foreach($campaognads as $campaignAds)
            <div class="col-md-12">
                <a href="{{$campaignAds->link}}?sold=show">
                    <img class="img-fluid w-100" src="{{$campaignAds->image}}"/>
                </a>
            </div>
            @endforeach
        </div>
    </div>
</section>
