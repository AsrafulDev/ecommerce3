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
        .lp-form { background: var(--card); border: 1px solid var(--line); border-radius: 8px; padding: 22px; }
        .lp-field { margin-bottom: 14px; }
        .lp-field label { display: block; font-weight: 600; margin-bottom: 6px; font-size: 14px; }
        .lp-field input, .lp-field select { width: 100%; padding: 11px 13px; border: 1px solid var(--line); border-radius: 6px; font: inherit; background: var(--card); color: var(--text); }
        .lp-variants { border: 0; padding: 0; margin: 0 0 16px; display: grid; gap: 8px; }
        .lp-variants legend { font-weight: 600; margin-bottom: 6px; }
        .lp-variant { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; border: 1px solid var(--line); border-radius: 6px; padding: 10px 12px; cursor: pointer; }
        .lp-variant strong { margin-left: auto; color: var(--primary); }
        .lp-variant.is-out { opacity: .5; cursor: not-allowed; }
        .lp-total { font-size: 17px; margin: 14px 0; }
        .lp-closed { font-size: 16px; color: var(--muted); }
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
