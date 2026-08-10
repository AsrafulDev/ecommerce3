{{--
    ═══════════════════════════════════════════════════════════════════════
    ALL CATEGORY BUTTON  —  frontEnd/layouts/headers/parts/all_categories.blade.php

    Controlled from:  Admin → Header & Footer Builder → Header Style
      • header_all_category_button  (1 = show, 0 = hide)
      • header_all_category_type    (dropdown | mega | icon | shop)

    Include anywhere in a header, e.g.:
        @includeIf('frontEnd.layouts.headers.parts.all_categories', ['acbVariant' => 'light'])
        @includeIf('frontEnd.layouts.headers.parts.all_categories', ['acbVariant' => 'dark'])   // dark nav bar
    ═══════════════════════════════════════════════════════════════════════
--}}
@php
    $acbOn    = (int)($generalsetting->header_all_category_button ?? 1) === 1;
    $acbType  = $generalsetting->header_all_category_type ?? 'mega';
    $acbVariant = $acbVariant ?? 'light';

    // Top-level categories (with sub + child categories), cached like other menu caches
    $acbCats = \Illuminate\Support\Facades\Cache::remember('allcatbtn_categories', 1800, function () {
        return \App\Models\Category::where('status', 1)->where('parent_id', 0)
            ->with('subcategories.childcategories')->get();
    });
@endphp

@if($acbOn && $acbCats->isNotEmpty())
<style>
.all-cat-wrap{ position:relative; display:inline-block; }
.all-cat-btn{ display:inline-flex; align-items:center; gap:8px; border:none; cursor:pointer;
  background:var(--primary-color,#0d6efd); color:#fff; font-weight:600; font-size:14px;
  padding:10px 18px; border-radius:var(--border-radius,8px); line-height:1; white-space:nowrap;
  text-decoration:none; transition:filter .2s, background .2s, color .2s; }
.all-cat-btn:hover{ color:#fff; filter:brightness(1.08); }
.all-cat-btn .acb-caret{ transition:transform .2s; }
.all-cat-wrap.open .all-cat-btn .acb-caret{ transform:rotate(180deg); }

/* Dark variant — for nav bars that already use the primary color (button becomes white) */
.all-cat-wrap.acb-variant-dark .all-cat-btn{ background:#fff; color:var(--primary-color,#0d6efd); }
.all-cat-wrap.acb-variant-dark .all-cat-btn:hover{ color:var(--primary-color,#0d6efd); filter:brightness(.97); }

/* Panel (shared) */
.all-cat-panel{ position:absolute; top:calc(100% + 8px); left:0; z-index:1050; min-width:248px;
  background:#fff; color:#212529; border:1px solid #e5e7eb; border-radius:12px;
  box-shadow:0 12px 32px rgba(0,0,0,.14); padding:8px; display:none; text-align:left; }
.all-cat-wrap.open .all-cat-panel{ display:block; }
.all-cat-panel ul{ list-style:none; margin:0; padding:0; }
.all-cat-panel a{ text-decoration:none; }

/* Dropdown list */
.all-cat-panel .acb-top > a{ display:flex; align-items:center; justify-content:space-between; gap:8px;
  padding:8px 10px; border-radius:8px; color:#212529; font-weight:600; font-size:14px; }
.all-cat-panel .acb-top > a:hover{ background:var(--primary-color,#0d6efd); color:#fff; }
.all-cat-panel .acb-top.has-children{ position:relative; }
.all-cat-panel .acb-sub{ display:none; }
.all-cat-panel .acb-top.has-children:hover > .acb-sub,
.all-cat-panel .acb-top.has-children:focus-within > .acb-sub{ display:block; position:absolute; left:100%; top:-8px;
  background:#fff; border:1px solid #e5e7eb; border-radius:12px; box-shadow:0 12px 32px rgba(0,0,0,.14);
  min-width:230px; padding:8px; }
.all-cat-panel .acb-sub > li > a{ display:flex; align-items:center; justify-content:space-between; gap:8px;
  padding:7px 10px; border-radius:8px; color:#374151; font-size:13px; }
.all-cat-panel .acb-sub > li > a:hover{ background:#f3f4f6; color:var(--primary-color,#0d6efd); }
.all-cat-panel .acb-sub .acb-child{ padding-left:8px; }
.all-cat-panel .acb-foot{ margin-top:8px; padding-top:8px; border-top:1px solid #f3f4f6; text-align:center; }
.all-cat-panel .acb-foot a{ color:var(--primary-color,#0d6efd); font-weight:600; font-size:13px; }

/* Mega menu */
.all-cat-panel.acb-mega{ min-width:640px; width:max-content; max-width:86vw; }
.all-cat-panel.acb-mega .acb-mega-grid{ display:grid; grid-template-columns:repeat(3,1fr); gap:16px; }
.all-cat-panel.acb-mega .acb-mega-col{ border-left:1px solid #f3f4f6; padding-left:14px; }
.all-cat-panel.acb-mega .acb-mega-col:first-child{ border-left:none; padding-left:0; }
.all-cat-panel.acb-mega .acb-mega-col > a{ display:block; font-weight:700; color:#111827; font-size:14px; margin-bottom:6px; }
.all-cat-panel.acb-mega .acb-mega-col > a:hover{ color:var(--primary-color,#0d6efd); }
.all-cat-panel.acb-mega .acb-mega-col a.acb-mega-sub{ display:block; color:#6b7280; font-size:13px; padding:3px 0; }
.all-cat-panel.acb-mega .acb-mega-col a.acb-mega-sub:hover{ color:var(--primary-color,#0d6efd); }
.all-cat-panel.acb-mega .acb-mega-foot{ margin-top:12px; padding-top:10px; border-top:1px solid #f3f4f6; text-align:center; }
.all-cat-panel.acb-mega .acb-mega-foot a{ color:var(--primary-color,#0d6efd); font-weight:600; font-size:13px; }

/* Icon-only button */
.all-cat-btn.acb-icon{ padding:10px 12px; }
.all-cat-btn.acb-icon .acb-icon-label{ display:none; }

/* Shop link */
.all-cat-btn.acb-shop{ background:transparent; color:var(--primary-color,#0d6efd); border:2px solid var(--primary-color,#0d6efd); }
.all-cat-btn.acb-shop:hover{ background:var(--primary-color,#0d6efd); color:#fff; filter:none; }
.all-cat-wrap.acb-variant-dark .all-cat-btn.acb-shop{ background:transparent; color:#fff; border-color:#fff; }
.all-cat-wrap.acb-variant-dark .all-cat-btn.acb-shop:hover{ background:#fff; color:var(--primary-color,#0d6efd); }

/* Hide on mobile — headers render their own mobile menu below 768px */
@media (max-width: 767.98px){ .all-cat-wrap{ display:none; } }
</style>

@if($acbType === 'shop')
    {{-- Shop Link — simple link to the shop page --}}
    <div class="all-cat-wrap acb-variant-{{ $acbVariant }}">
        <a class="all-cat-btn acb-shop" href="{{ route('shop') }}">
            <i class="fa-solid fa-store"></i> {{ __('Shop All') }}
        </a>
    </div>

@elseif($acbType === 'icon')
    {{-- Icon Menu — compact icon button that opens the category dropdown --}}
    <div class="all-cat-wrap acb-variant-{{ $acbVariant }}">
        <button type="button" class="all-cat-btn acb-icon" data-acb-toggle aria-label="{{ __('All Categories') }}" title="{{ __('All Categories') }}">
            <i class="fa-solid fa-bars-staggered"></i><span class="acb-icon-label">{{ __('All Categories') }}</span>
        </button>
        <div class="all-cat-panel" data-acb-panel>
            <ul>
                @foreach($acbCats as $acbCat)
                <li class="acb-top {{ $acbCat->subcategories->isNotEmpty() ? 'has-children' : '' }}">
                    <a href="{{ url('category/' . $acbCat->slug) }}">
                        <span>{{ \Illuminate\Support\Str::limit($acbCat->name, 24) }}</span>
                        @if($acbCat->subcategories->isNotEmpty())<i class="fa-solid fa-chevron-right" style="font-size:11px;"></i>@endif
                    </a>
                    @if($acbCat->subcategories->isNotEmpty())
                    <ul class="acb-sub">
                        @foreach($acbCat->subcategories as $acbSub)
                        <li>
                            <a href="{{ url('subcategory/' . $acbSub->slug) }}">
                                <span>{{ \Illuminate\Support\Str::limit($acbSub->subcategoryName, 28) }}</span>
                                @if($acbSub->childcategories->isNotEmpty())<i class="fa-solid fa-chevron-right" style="font-size:11px;"></i>@endif
                            </a>
                            @if($acbSub->childcategories->isNotEmpty())
                            <ul class="acb-sub acb-child">
                                @foreach($acbSub->childcategories as $acbChild)
                                <li><a href="{{ url('products/' . $acbChild->slug) }}">{{ $acbChild->childcategoryName }}</a></li>
                                @endforeach
                            </ul>
                            @endif
                        </li>
                        @endforeach
                    </ul>
                    @endif
                </li>
                @endforeach
            </ul>
            <div class="acb-foot"><a href="{{ route('shop') }}">{{ __('View All Products') }} →</a></div>
        </div>
    </div>

@elseif($acbType === 'mega')
    {{-- Mega Menu — wide multi-column category panel --}}
    <div class="all-cat-wrap acb-variant-{{ $acbVariant }}">
        <button type="button" class="all-cat-btn" data-acb-toggle>
            <i class="fa-solid fa-bars-staggered"></i>{{ __('All Categories') }}<i class="fa-solid fa-angle-down acb-caret"></i>
        </button>
        <div class="all-cat-panel acb-mega" data-acb-panel>
            <div class="acb-mega-grid">
                @foreach($acbCats as $acbCat)
                <div class="acb-mega-col">
                    <a href="{{ url('category/' . $acbCat->slug) }}">{{ $acbCat->name }}</a>
                    @foreach($acbCat->subcategories->take(5) as $acbSub)
                        <a class="acb-mega-sub" href="{{ url('subcategory/' . $acbSub->slug) }}">{{ $acbSub->subcategoryName }}</a>
                    @endforeach
                </div>
                @endforeach
            </div>
            <div class="acb-mega-foot"><a href="{{ route('shop') }}">{{ __('View All Categories') }} →</a></div>
        </div>
    </div>

@else
    {{-- Dropdown Nav — default compact dropdown with flyout subcategories --}}
    <div class="all-cat-wrap acb-variant-{{ $acbVariant }}">
        <button type="button" class="all-cat-btn" data-acb-toggle>
            <i class="fa-solid fa-bars-staggered"></i>{{ __('All Categories') }}<i class="fa-solid fa-angle-down acb-caret"></i>
        </button>
        <div class="all-cat-panel" data-acb-panel>
            <ul>
                @foreach($acbCats as $acbCat)
                <li class="acb-top {{ $acbCat->subcategories->isNotEmpty() ? 'has-children' : '' }}">
                    <a href="{{ url('category/' . $acbCat->slug) }}">
                        <span>{{ \Illuminate\Support\Str::limit($acbCat->name, 24) }}</span>
                        @if($acbCat->subcategories->isNotEmpty())<i class="fa-solid fa-chevron-right" style="font-size:11px;"></i>@endif
                    </a>
                    @if($acbCat->subcategories->isNotEmpty())
                    <ul class="acb-sub">
                        @foreach($acbCat->subcategories as $acbSub)
                        <li>
                            <a href="{{ url('subcategory/' . $acbSub->slug) }}">
                                <span>{{ \Illuminate\Support\Str::limit($acbSub->subcategoryName, 28) }}</span>
                                @if($acbSub->childcategories->isNotEmpty())<i class="fa-solid fa-chevron-right" style="font-size:11px;"></i>@endif
                            </a>
                            @if($acbSub->childcategories->isNotEmpty())
                            <ul class="acb-sub acb-child">
                                @foreach($acbSub->childcategories as $acbChild)
                                <li><a href="{{ url('products/' . $acbChild->slug) }}">{{ $acbChild->childcategoryName }}</a></li>
                                @endforeach
                            </ul>
                            @endif
                        </li>
                        @endforeach
                    </ul>
                    @endif
                </li>
                @endforeach
            </ul>
            <div class="acb-foot"><a href="{{ route('shop') }}">{{ __('View All Products') }} →</a></div>
        </div>
    </div>
@endif

@push('script')
<script>
(function () {
    function closeAcbPanels(exclude) {
        document.querySelectorAll('.all-cat-wrap.open').forEach(function (w) {
            if (exclude && w === exclude) return;
            w.classList.remove('open');
        });
    }
    document.addEventListener('click', function (e) {
        var wrap = e.target.closest('.all-cat-wrap');
        if (wrap) {
            // Clicking a link inside the panel → let navigation happen, keep panel state as-is
            if (e.target.closest('.all-cat-panel a')) return;
            // Clicking the button toggles the panel
            if (e.target.closest('[data-acb-toggle]')) {
                closeAcbPanels(wrap);
                wrap.classList.toggle('open');
            }
            return;
        }
        closeAcbPanels();
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeAcbPanels();
    });
})();
</script>
@endpush
@endif
