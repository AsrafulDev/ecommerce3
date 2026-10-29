{{-- Luxury template: the page skeleton. Section order is fixed here; each section can be switched off
     from the landing's content, and the checkout is always shown. --}}
<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $landing->name }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;700&family=Hind+Siliguri:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: {{ $page->colour('primary') }};
            --accent: {{ $page->colour('accent') }};
            --bg: {{ $page->colour('background') }};
            --text: {{ $page->colour('text') }};
        }
        * { box-sizing: border-box; }
        body { margin: 0; background: var(--bg); color: var(--text); font-family: 'Hind Siliguri', 'Cormorant Garamond', Georgia, serif; line-height: 1.7; }
        img { max-width: 100%; height: auto; display: block; }
        .lp-wrap { max-width: 1120px; margin: 0 auto; padding: 0 16px; }
        .lp-narrow { max-width: 720px; }
        .lp-section { padding: 72px 0; }
        .lp-alt { background: #f1ebe2; }
        .lp-h2, .lp-title { font-family: 'Cormorant Garamond', Georgia, serif; font-weight: 700; letter-spacing: .01em; color: var(--accent); margin: 0 0 18px; line-height: 1.2; }
        .lp-h2 { font-size: clamp(26px, 4vw, 38px); }
        .lp-title { font-size: clamp(34px, 6vw, 56px); }
        .lp-eyebrow { text-transform: uppercase; letter-spacing: .22em; font-size: 12px; color: var(--primary); margin: 0 0 12px; }
        .lp-lead, .lp-body { font-size: 17px; color: #3f3a33; }
        .lp-hero { padding: 64px 0; background: linear-gradient(180deg, #ffffff 0%, var(--bg) 100%); }
        .lp-hero-grid { display: grid; gap: 32px; align-items: center; grid-template-columns: 1fr; }
        .lp-hero-img { border-radius: 4px; box-shadow: 0 18px 40px rgba(28, 25, 23, .12); }
        .lp-grid { display: grid; gap: 20px; grid-template-columns: repeat(auto-fit, minmax(230px, 1fr)); }
        .lp-card { background: #fff; border: 1px solid #e7dfd3; border-radius: 6px; padding: 22px; margin: 0; }
        .lp-card h3 { font-family: 'Cormorant Garamond', Georgia, serif; font-size: 22px; margin: 0 0 8px; color: var(--accent); }
        .lp-card p, .lp-card blockquote { margin: 0; color: #4d463d; }
        .lp-card-img { width: 100%; border-radius: 4px; margin-bottom: 14px; }
        .lp-stars { color: var(--primary); letter-spacing: 2px; margin-bottom: 8px; }
        .lp-review figcaption { margin-top: 12px; font-size: 14px; color: var(--primary); }
        .lp-faq { border-bottom: 1px solid #e7dfd3; padding: 16px 0; }
        .lp-faq summary { cursor: pointer; font-weight: 600; font-size: 18px; color: var(--accent); }
        .lp-faq .lp-body { margin-top: 10px; }
        .lp-btn { display: inline-block; background: var(--primary); color: #fff; padding: 14px 30px; border-radius: 2px; text-decoration: none; font-weight: 600; letter-spacing: .04em; border: 0; cursor: pointer; font-size: 16px; }
        .lp-btn[disabled] { opacity: .5; cursor: not-allowed; }
        .lp-form { background: #fff; border: 1px solid #e7dfd3; border-radius: 6px; padding: 26px; }
        .lp-field { margin-bottom: 16px; }
        .lp-field label { display: block; font-weight: 600; margin-bottom: 6px; font-size: 15px; }
        .lp-field input, .lp-field select { width: 100%; padding: 12px 14px; border: 1px solid #d8cfc1; border-radius: 3px; font: inherit; background: #fff; }
        .lp-variants { border: 0; padding: 0; margin: 0 0 18px; display: grid; gap: 10px; }
        .lp-variants legend { font-weight: 600; margin-bottom: 8px; }
        .lp-variant { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; border: 1px solid #d8cfc1; border-radius: 3px; padding: 12px 14px; cursor: pointer; }
        .lp-variant input:checked + span { font-weight: 700; }
        .lp-variant.is-out { opacity: .5; cursor: not-allowed; }
        .lp-variant strong { margin-left: auto; color: var(--primary); }
        .lp-total { font-size: 18px; margin: 18px 0; }
        .lp-note { font-size: 14px; color: #6b6255; margin: 14px 0 0; }
        .lp-closed { font-size: 17px; color: #4d463d; }
        .lp-foot { padding: 28px 0; text-align: center; font-size: 13px; color: #6b6255; border-top: 1px solid #e7dfd3; }
        @media (min-width: 900px) {
            .lp-hero-grid { grid-template-columns: 1.1fr .9fr; }
            .lp-hero { padding: 96px 0; }
        }
    </style>
</head>
<body>
    {{-- A template's own section wins when it has one; otherwise the shared section is used. --}}
    @php
        $sectionView = function (string $section) use ($landing) {
            $own = 'frontEnd.layouts.landing.' . $landing->template_key . '.sections.' . $section;

            return view()->exists($own) ? $own : 'frontEnd.layouts.landing.sections.' . $section;
        };
    @endphp
    @foreach(['hero', 'problem', 'solution', 'features', 'reviews', 'faq'] as $section)
        @if($page->enabled($section) || $page->isEditable())
            <div class="{{ $page->enabled($section) ? '' : 'lp-off' }}"{!! $page->sectionAttr($section) !!}>
                @include($sectionView($section), ['page' => $page, 'product' => $product, 'open' => $open])
            </div>
        @endif
    @endforeach

    @include('frontEnd.layouts.partials.landing_checkout', [
        'page' => $page,
        'product' => $product,
        'variants' => $variants,
        'areas' => $areas,
        'open' => $open,
    ])

    <footer class="lp-foot">{{ $generalsetting?->copyright_text ?? '' }}</footer>
    @if($page->isEditable())
        @include('frontEnd.layouts.landing._editor')
    @endif
</body>
</html>
