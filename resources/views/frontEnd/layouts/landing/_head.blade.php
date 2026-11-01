{{-- Head of a landing page: fonts, the palette from the page's content, the shared layout styles,
     and the template's own styles when it has a _style file. --}}
<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $landing->name }}</title>
    @isset($fontsUrl)
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="{{ $fontsUrl }}" rel="stylesheet">
    @endisset
    <style>
        :root {
            --primary: {{ $page->colour('primary') }};
            --accent: {{ $page->colour('accent') }};
            --bg: {{ $page->colour('background') }};
            --text: {{ $page->colour('text') }};
            --card: #ffffff;
            --line: #e7dfd3;
            --muted: #6b6255;
            --font-head: Georgia, serif;
            --font-body: 'Hind Siliguri', system-ui, sans-serif;
        }
        * { box-sizing: border-box; }
        body { margin: 0; background: var(--bg); color: var(--text); font-family: var(--font-body); line-height: 1.7; }
        img { max-width: 100%; height: auto; display: block; }
        .lp-wrap { max-width: 1120px; margin: 0 auto; padding: 0 16px; }
        .lp-narrow { max-width: 720px; }
        .lp-section { padding: 64px 0; }
        .lp-alt { background: color-mix(in srgb, var(--bg) 92%, var(--accent) 8%); }
        .lp-h2, .lp-title { font-family: var(--font-head); font-weight: 700; color: var(--accent); margin: 0 0 16px; line-height: 1.2; }
        .lp-h2 { font-size: clamp(24px, 4vw, 36px); }
        .lp-title { font-size: clamp(32px, 5.5vw, 52px); }
        .lp-eyebrow { text-transform: uppercase; letter-spacing: .18em; font-size: 12px; color: var(--primary); margin: 0 0 10px; }
        .lp-lead, .lp-body { font-size: 16px; color: var(--muted); }
        .lp-hero { padding: 56px 0; }
        .lp-hero-grid { display: grid; gap: 28px; align-items: center; grid-template-columns: 1fr; }
        .lp-hero-img { border-radius: 8px; }
        .lp-img { position: relative; }
        .lp-grid { display: grid; gap: 18px; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); }
        .lp-card { background: var(--card); border: 1px solid var(--line); border-radius: 8px; padding: 20px; margin: 0; }
        .lp-card h3 { font-family: var(--font-head); font-size: 20px; margin: 0 0 6px; color: var(--accent); }
        .lp-card p, .lp-card blockquote { margin: 0; color: var(--muted); }
        .lp-card-img { width: 100%; border-radius: 6px; margin-bottom: 12px; aspect-ratio: 4 / 5; object-fit: cover; }
        .lp-img-empty { width: 100%; aspect-ratio: 4 / 5; border: 1px dashed var(--line); border-radius: 6px; display: grid; place-items: center; color: var(--muted); font-size: 13px; }
        .lp-stars { color: var(--primary); letter-spacing: 2px; margin-bottom: 8px; }
        .lp-review figcaption { margin-top: 10px; font-size: 14px; color: var(--primary); }
        .lp-faq { border-bottom: 1px solid var(--line); padding: 14px 0; }
        .lp-faq summary { cursor: pointer; font-weight: 600; font-size: 17px; color: var(--accent); }
        .lp-faq .lp-body { margin-top: 8px; }
        .lp-btn { display: inline-block; background: var(--primary); color: #fff; padding: 13px 26px; border-radius: 6px; text-decoration: none; font-weight: 600; border: 0; cursor: pointer; font-size: 16px; font-family: inherit; }
        .lp-btn[disabled] { opacity: .5; cursor: not-allowed; }
        .lp-table { width: 100%; border-collapse: collapse; background: var(--card); border: 1px solid var(--line); border-radius: 8px; overflow: hidden; }
        .lp-table th, .lp-table td { padding: 12px 14px; border-bottom: 1px solid var(--line); text-align: left; font-size: 15px; }
        .lp-table th { color: var(--accent); font-weight: 700; }
        .lp-table td:first-child { color: var(--accent); font-weight: 600; }
        .lp-note { font-size: 13px; color: var(--muted); margin: 10px 0 0; }
        /* Order form: the steps on the left, the summary on the right. Colours come from the theme. */
        .lp-checkout { background: color-mix(in srgb, var(--bg) 92%, var(--accent) 8%); }
        .lp-checkout-head { margin-bottom: 36px; }
        .lp-checkout-head .lp-h2 { margin-bottom: 10px; }
        .lp-order { display: grid; gap: 24px; align-items: start; }
        .lp-form { background: var(--card); border: 1px solid var(--line); border-radius: 18px; padding: 28px; }
        .lp-step { font-family: var(--font-body); font-size: 18px; font-weight: 600; color: var(--accent); margin: 0 0 16px; }
        .lp-step:not(:first-child) { margin-top: 30px; }
        .lp-field { margin-bottom: 16px; }
        .lp-field > label, .lp-label { display: block; font-weight: 600; margin-bottom: 8px; font-size: 14px; color: var(--text); }
        .lp-req { color: var(--primary); font-weight: 700; }
        .lp-field input[type=text], .lp-field input[type=tel], .lp-field input[type=number], .lp-field textarea {
            width: 100%; padding: 12px 14px; border: 1px solid var(--line); border-radius: 10px; font: inherit;
            background: var(--card); color: var(--text);
        }
        .lp-field textarea { min-height: 96px; resize: vertical; }
        .lp-hint { font-size: 12px; color: var(--muted); margin: 6px 0 0; }
        .lp-options { display: flex; flex-wrap: wrap; gap: 12px; }
        .lp-sizes { gap: 10px; }
        .lp-opt { position: relative; display: block; cursor: pointer; }
        .lp-opt[hidden] { display: none; }
        .lp-opt > input[type=radio], .lp-opt > input[type=hidden] { position: absolute; opacity: 0; width: 1px; height: 1px; pointer-events: none; }
        .lp-opt.is-out { opacity: .45; cursor: not-allowed; }
        .lp-opt-name { font-size: 14px; color: var(--text); }
        .lp-swatch-opt { display: flex; flex-direction: column; align-items: center; gap: 6px; width: 74px; }
        .lp-swatch { width: 40px; height: 40px; border-radius: 50%; border: 1px solid var(--line); box-shadow: inset 0 0 0 3px var(--card); }
        .lp-swatch-opt.is-picked .lp-swatch { outline: 2px solid var(--accent); outline-offset: 2px; }
        .lp-swatch-opt.is-picked .lp-opt-name { font-weight: 700; }
        .lp-size { min-width: 84px; text-align: center; border: 1px solid var(--line); border-radius: 10px; padding: 10px 14px; background: var(--card); }
        .lp-size.is-picked { border: 2px solid var(--accent); padding: 9px 13px; }
        .lp-size small { display: block; font-size: 12px; color: var(--muted); }
        .lp-qty { display: inline-flex; align-items: center; border: 1px solid var(--line); border-radius: 999px; background: var(--card); overflow: hidden; }
        .lp-qty-btn { width: 44px; height: 42px; background: none; border: 0; font-size: 18px; color: var(--muted); cursor: pointer; }
        .lp-qty input { width: 60px; text-align: center; border: 0; font: inherit; background: transparent; color: var(--text); }
        .lp-area { display: grid; grid-template-columns: auto 1fr auto; gap: 14px; align-items: center; border: 1px solid var(--line); border-radius: 14px; padding: 16px 18px; background: var(--card); margin-bottom: 12px; }
        .lp-area.is-picked { border: 2px solid var(--primary); background: color-mix(in srgb, var(--primary) 6%, var(--card)); padding: 15px 17px; }
        .lp-area-name { font-weight: 600; color: var(--text); }
        .lp-area strong { color: var(--text); }
        .lp-radio { width: 20px; height: 20px; border-radius: 50%; border: 1.5px solid var(--line); background: var(--card); }
        .lp-area.is-picked .lp-radio { border: 6px solid var(--primary); }
        .lp-summary { position: sticky; top: 24px; background: var(--card); border: 1px solid var(--line); border-radius: 18px; padding: 26px; box-shadow: 0 18px 40px rgba(0, 0, 0, .06); }
        .lp-summary-product { display: flex; gap: 14px; align-items: center; padding-bottom: 18px; border-bottom: 1px solid var(--line); }
        .lp-summary-product strong { display: block; color: var(--text); }
        .lp-summary-product small { display: block; color: var(--muted); font-size: 13px; }
        .lp-thumb { width: 72px; height: 88px; border-radius: 10px; overflow: hidden; background: var(--line); flex: 0 0 72px; }
        .lp-thumb img { width: 100%; height: 100%; object-fit: cover; }
        .lp-summary-rows { margin: 0; }
        .lp-summary-rows > div { display: flex; justify-content: space-between; gap: 12px; padding: 12px 0; border-bottom: 1px solid var(--line); font-size: 15px; }
        .lp-summary-rows dt { color: var(--muted); }
        .lp-summary-rows dd { margin: 0; color: var(--text); font-weight: 600; text-align: right; }
        .lp-summary-total { display: flex; justify-content: space-between; align-items: center; margin: 18px 0 20px; font-weight: 600; color: var(--text); }
        .lp-summary-total strong { font-size: 28px; color: var(--accent); }
        .lp-btn-wide { display: block; width: 100%; text-align: center; padding: 16px; }
        .lp-summary .lp-note { text-align: center; margin-top: 12px; }
        .lp-closed { font-size: 16px; color: var(--muted); }
        @media (min-width: 900px) {
            .lp-order { grid-template-columns: 1.35fr 1fr; }
        }
        @media (max-width: 899px) {
            .lp-summary { position: static; }
            .lp-form { padding: 22px; }
        }
        .lp-foot { padding: 24px 0; text-align: center; font-size: 13px; color: var(--muted); border-top: 1px solid var(--line); }
        .lp-off { opacity: .4; filter: grayscale(.7); }
        .lp-final { text-align: center; padding: 64px 0; }
        .lp-final .lp-h2 { margin-bottom: 8px; }
        @media (min-width: 900px) {
            .lp-hero-grid { grid-template-columns: 1.1fr .9fr; }
            .lp-hero { padding: 88px 0; }
        }
    </style>
    @if(view()->exists('frontEnd.layouts.landing.' . $landing->template_key . '._style'))
        @include('frontEnd.layouts.landing.' . $landing->template_key . '._style')
    @endif
</head>
<body>
