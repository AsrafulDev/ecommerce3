{{-- Logo Component --}}
<div class="header-logo-area" style="background:#fff; padding:12px 0; text-align:center;">
    <div class="container">
        <a href="{{ route('home') }}">
            <img src="{{ asset($generalsetting->dark_logo ?: 'public/assets/images/CurlBazar.svg') }}" 
                 alt="Logo" style="max-height:45px;">
        </a>
    </div>
</div>
