{{-- Copyright Bar (Footer Component) --}}
<div class="footer-part-copyright" style="background:var(--copyright-bg); color:var(--copyright-text); padding:12px 0; font-size:13px;">
    <div class="container text-center">
        <p class="mb-0" style="color:var(--copyright-text);">
            {{ $generalsetting->copyright ?? '© '.date('Y').' '.($generalsetting->name ?? 'All rights reserved.').'. All rights reserved.' }}
        </p>
    </div>
</div>
