{{-- Default Footer - Original complete working footer --}}
<footer style="background: var(--footer-bg) !important;">
    <div class="footer-top" style="background: var(--footer-bg) !important;">
        <div class="container">
            <div class="row">
                <div class="col-sm-4 mb-3 mb-sm-0">
                    <div class="footer-about">
                        <a href="{{ route('home') }}">
                            <img src="{{ asset($generalsetting->dark_logo ?? 'public/assets/images/CurlBazar.svg') }}" alt="" />
                        </a>
                        <p>{{ optional($generalsetting)->footer_about_text ?? '' }}</p>
                        <a href="tel:{{ $contact->hotline ?? '01XXX-XXXXXX' }}" class="footer-hotlint">{{ $contact->hotline ?? '01XXX-XXXXXX' }}</a>
                        <div class="app-badges mt-3">
                            <a href="{{ optional($generalsetting)->google_play_link ?? '#' }}" class="app-badge-btn" target="_blank" rel="noopener">
                                <img src="/public/assets/images/play.svg" alt="Get it on Google Play" style="height: 35px !important;">
                            </a>
                            <a href="{{ optional($generalsetting)->app_store_link ?? '#' }}" class="app-badge-btn" target="_blank" rel="noopener">
                                <img src="/public/assets/images/app.png" alt="Download on the App Store" style="height: 35px !important;">
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-sm-3 mb-3 mb-sm-0 col-6">
                    <div class="footer-menu">
                        <ul>
                            <li class="title"><a>Useful Link</a></li>
                            <li><a href="{{ route('shop') }}">All Products</a></li>
                            <li><a href="{{ route('complaint') }}">Complaints</a></li>
                            <li><a href="{{ route('contact') }}">Contact Us</a></li>
                            @foreach($pages as $page)
                            <li><a href="{{ route('page', ['slug' => $page->slug]) }}">{{ $page->name }}</a></li>
                            @endforeach
                        </ul>
                    </div>
                </div>
                <div class="col-sm-2 mb-3 mb-sm-0 col-6">
                    <div class="footer-menu">
                        <ul>
                            <li class="title"><a>Link</a></li>
                            <li><a href="{{ route('shop') }}">All Products</a></li>
                            @foreach($pagesright as $value)
                            <li><a href="{{ route('page', ['slug' => $value->slug]) }}">{{ $value->name }}</a></li>
                            @endforeach
                        </ul>
                    </div>
                </div>
                <div class="col-sm-3 mb-3 mb-sm-0">
                    <div class="footer-menu">
                        <ul>
                            <li class="title stay_conn"><a>Newsletter Subscribe</a></li>
                        </ul>
                        <div class="footer-newsletter" style="background: transparent; padding: 0;">
                            <form action="{{ route('frontend.newsletter.subscribe') }}" method="POST" class="modern-subscribe">
                                @csrf
                                <div class="input-group newsletter-input-group" style="background: transparent; border: none;">
                                    <input type="email" name="email" class="form-control newsletter-input" placeholder="Enter your email..." required style="background: var(--primary-color);">
                                    <button class="btn btn-primary newsletter-btn" type="submit" style="background: #dc3545;">
                                        <i class="fas fa-paper-plane"></i>
                                    </button>
                                </div>
                            </form>
                        </div>
                        <ul class="social_link">
                            @foreach($socialicons as $value)
                            <li class="social_list">
                                <a class="mobile-social-link" href="{{ $value->link }}"><i class="{{ $value->icon }}"></i></a>
                            </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="footer-bottom" style="background: var(--copyright-bg) !important;">
        <div class="container">
            <div class="row">
                <div class="col-sm-12">
                    <div class="copyright">
                        <p style="margin: 0; display: flex; align-items: center; justify-content: center; flex-wrap: wrap; gap: 5px;">
                            Copyright © {{ date('Y') }} {{ $generalsetting->name ?? '' }}. All rights reserved
                            <span style="margin: 0 5px;">|</span>
                            <span>Website Designed by:</span>
                            <a href="https://www.curlware.com" target="_blank" style="display: inline-flex; align-items: center; text-decoration: none; color: var(--copyright-text); margin-left: 5px;">
                                <img src="{{ asset('public/assets/images/curlware.svg') }}" alt="Curlware" style="height: 24px; margin-right: 5px;">
                                <strong>Curlware</strong>
                            </a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</footer>
