{{-- About Section (Footer Component) --}}
<div class="footer-part-about" style="background:var(--footer-bg); color:var(--footer-text); padding:20px 0;">
    <div class="container">
        <div class="text-center text-md-start">
            <a href="{{ route('home') }}">
                <img src="{{ asset($generalsetting->dark_logo ?? 'public/assets/images/CurlBazar.svg') }}" 
                     alt="Logo" style="max-height:40px;" class="mb-2">
            </a>
            <p class="small opacity-75" style="max-width:400px;">{{ $generalsetting->footer_about_text ?? 'Your trusted online store.' }}</p>
            <a href="tel:{{ $contact->hotline ?? '' }}" class="text-decoration-none" style="color:var(--footer-text);opacity:0.8;">
                <i class="fa-solid fa-phone"></i> {{ $contact->hotline ?? '' }}
            </a>
        </div>
    </div>
</div>
