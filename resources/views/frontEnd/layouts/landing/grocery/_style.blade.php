{{-- Grocery & organic look, following the Matir Ghran design: warm cream, deep green, gold offer accents. --}}
<link rel="stylesheet" href="{{ asset('public/frontEnd/css/all.min.css') }}">
<style>
    :root { --font-head: 'Hind Siliguri', system-ui, sans-serif; --font-body: 'Hind Siliguri', system-ui, sans-serif; --gr-card: #fffdf8; --gr-line: #e2d9c6; --gr-muted: #5f6b58; --gr-tint: #e1ead6; --gr-beige: #f3ead9; --gr-green: #2f6b3f; --gr-dark: #1d3a25; --gr-gold: #e8b04a; --gr-rust: #8a4b2a; --gr-ink: #1d2b20; }
    body { background: #f8f4ea; }
    .gr-wrap { max-width: 1200px; margin: 0 auto; padding: 0 24px; }
    .gr-section { padding: 88px 0; }
    .gr-tint { background: var(--gr-tint); }
    .gr-beige { background: var(--gr-beige); }
    .gr-eyebrow { font-size: 13px; color: var(--gr-rust); letter-spacing: .04em; margin: 0 0 12px; font-weight: 500; }
    .gr-h2 { font-size: clamp(28px, 4vw, 42px); font-weight: 700; line-height: 1.3; color: var(--gr-ink); margin: 0 0 12px; }
    .gr-sub { font-size: 16px; color: var(--gr-muted); margin: 0 0 40px; line-height: 1.7; }
    .gr-center { text-align: center; }
    .gr-center .gr-sub { margin-left: auto; margin-right: auto; }
    .gr-body { font-size: 15px; color: var(--gr-muted); line-height: 1.8; }
    .gr-small { font-size: 13px; line-height: 1.7; }

    /* Notice bar and navigation */
    .gr-notice { background: #fde7b0; color: #5a4310; text-align: center; font-size: 13px; padding: 8px 16px; }
    .gr-nav { background: #faf6ec; border-bottom: 1px solid var(--gr-line); }
    .gr-nav-inner { max-width: 1200px; margin: 0 auto; padding: 16px 24px; display: flex; align-items: center; justify-content: space-between; gap: 16px; }
    .gr-brand { display: flex; align-items: baseline; gap: 8px; }
    .gr-brand span { font-size: 22px; font-weight: 700; color: var(--gr-dark); }
    .gr-brand small { font-size: 11px; letter-spacing: .12em; color: var(--gr-muted); }
    .gr-logo { max-height: 44px; width: auto; }
    .gr-nav-cta { background: var(--gr-green); color: #fff; padding: 10px 22px; border-radius: 10px; text-decoration: none; font-size: 15px; }

    /* Hero */
    .gr-hero { padding: 72px 0 64px; background: linear-gradient(180deg, #fbf3e0 0%, #f4ead8 100%); }
    .gr-hero-grid { display: grid; grid-template-columns: 1fr; gap: 48px; align-items: center; }
    .gr-title { font-size: clamp(34px, 4.6vw, 54px); line-height: 1.3; font-weight: 700; color: var(--gr-ink); margin: 0; }
    .gr-tagline { font-size: 18px; color: var(--gr-rust); margin: 10px 0 18px; }
    .gr-lead { font-size: 16px; color: var(--gr-muted); line-height: 1.8; max-width: 540px; }
    .gr-price-row { display: flex; align-items: center; gap: 14px; flex-wrap: wrap; margin: 22px 0 6px; }
    .gr-price { font-size: 40px; font-weight: 700; color: var(--gr-green); }
    .gr-old { font-size: 18px; color: var(--gr-muted); text-decoration: line-through; }
    .gr-badge { background: #dfeed8; color: var(--gr-green); padding: 4px 12px; border-radius: 999px; font-size: 13px; }
    .gr-size { font-size: 14px; color: var(--gr-muted); margin: 0 0 24px; }
    .gr-actions { display: flex; gap: 14px; flex-wrap: wrap; }
    .gr-btn { display: inline-flex; align-items: center; gap: 8px; background: var(--gr-green); color: #fff; padding: 14px 30px; border-radius: 999px; text-decoration: none; font-weight: 600; font-size: 16px; box-shadow: 0 10px 24px rgba(47, 107, 63, .25); border: 0; cursor: pointer; font-family: inherit; }
    .gr-btn-wide { width: 100%; justify-content: center; }
    .gr-btn-ghost { display: inline-flex; align-items: center; gap: 8px; border: 1.5px solid var(--gr-green); color: var(--gr-green); padding: 13px 26px; border-radius: 999px; text-decoration: none; font-size: 16px; }
    .gr-checks { list-style: none; padding: 0; margin: 24px 0 0; display: flex; flex-wrap: wrap; gap: 10px 22px; color: var(--gr-muted); font-size: 14px; }
    .gr-checks li::before { content: "✓"; color: var(--gr-green); font-weight: 700; margin-right: 6px; }
    .gr-hero-media { border-radius: 24px; overflow: hidden; aspect-ratio: 4 / 5; background: #e9e0cc; box-shadow: 0 30px 60px rgba(29, 58, 37, .12); position: relative; }
    .gr-hero-media img, .gr-tile-img img, .gr-final-bg img { width: 100%; height: 100%; object-fit: cover; }
    .gr-tile-img { position: relative; width: 100%; height: 100%; border-radius: 16px; overflow: hidden; background: #e9e0cc; }
    .gr-empty { display: grid; place-items: center; height: 100%; color: var(--gr-muted); font-size: 13px; border: 1px dashed var(--gr-line); border-radius: 16px; }

    /* Benefit and trust cards */
    .gr-cards { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; }
    .gr-card { position: relative; background: var(--gr-card); border: 1px solid var(--gr-line); border-radius: 18px; padding: 26px 24px; }
    .gr-card h3 { font-size: 18px; font-weight: 600; color: var(--gr-ink); margin: 16px 0 8px; }
    .gr-card p { font-size: 14px; color: var(--gr-muted); line-height: 1.7; margin: 0; }
    .gr-dot { display: inline-flex; align-items: center; justify-content: center; width: 46px; height: 46px; border-radius: 12px; background: #e3ebd6; color: var(--gr-dark); font-size: 18px; }

    /* Product details */
    .gr-details-grid { display: grid; grid-template-columns: 1.3fr 1fr; gap: 24px; align-items: start; }
    .gr-stack { display: grid; gap: 24px; }
    .gr-panel { position: relative; background: var(--gr-card); border: 1px solid var(--gr-line); border-radius: 18px; padding: 28px 30px; }
    .gr-panel h3 { font-size: 18px; font-weight: 600; color: var(--gr-ink); margin: 0 0 14px; }
    .gr-ingredient { font-weight: 600; color: var(--gr-ink); padding-bottom: 14px; border-bottom: 1px solid var(--gr-line); margin-bottom: 4px; }
    .gr-row { position: relative; display: grid; grid-template-columns: 40% 1fr; gap: 16px; padding: 13px 0; border-bottom: 1px solid #e6eddc; font-size: 15px; }
    .gr-row > span:first-child { color: var(--gr-muted); }
    .gr-row > span:last-child { color: var(--gr-ink); font-weight: 600; }
    .gr-label-rows .gr-row { grid-template-columns: 1fr 1fr; }
    .gr-label-rows .gr-row > span:last-child { text-align: right; }
    .gr-tips { list-style: none; padding: 0; margin: 0; display: grid; gap: 10px; }
    .gr-tips li { position: relative; padding-left: 18px; font-size: 14px; color: var(--gr-muted); line-height: 1.7; }
    .gr-tips li::before { content: "•"; position: absolute; left: 4px; color: var(--gr-rust); }

    /* Concerns */
    .gr-concerns { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    .gr-concern { position: relative; display: flex; align-items: center; gap: 14px; background: var(--gr-card); border: 1px solid var(--gr-line); border-radius: 16px; padding: 18px 22px; }
    .gr-concern-q { flex: 1; font-size: 13px; color: var(--gr-muted); line-height: 1.6; }
    .gr-concern-a { flex: 1.2; font-size: 15px; font-weight: 600; color: var(--gr-ink); line-height: 1.6; }
    .gr-arrow { flex: 0 0 32px; width: 32px; height: 32px; border-radius: 50%; background: #f4e3b5; color: var(--gr-ink); display: grid; place-items: center; font-size: 13px; }

    /* Gallery */
    .gr-mosaic { display: grid; grid-template-columns: repeat(3, 1fr); grid-auto-rows: 240px; gap: 16px; margin-top: 40px; }
    .gr-tile { position: relative; margin: 0; }
    .gr-tile-1 { grid-row: span 2; }

    /* Reviews */
    .gr-empty-card { border: 1px dashed #cfc3a8; border-radius: 18px; padding: 44px 40px; text-align: center; background: #fcfaf3; }
    .gr-empty-icon { display: inline-flex; align-items: center; justify-content: center; width: 56px; height: 56px; border-radius: 50%; background: #e3ebd6; color: var(--gr-dark); font-size: 20px; margin-bottom: 14px; }
    .gr-empty-card h3 { font-size: 19px; font-weight: 600; color: var(--gr-ink); margin: 0 0 8px; }
    .gr-empty-card p { margin: 0 auto; max-width: 560px; color: var(--gr-muted); font-size: 15px; line-height: 1.7; }
    .gr-reviews { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-top: 24px; }
    .gr-review { position: relative; background: var(--gr-card); border: 1px solid var(--gr-line); border-radius: 18px; padding: 22px; }
    .gr-review blockquote { margin: 10px 0; font-size: 14px; color: var(--gr-muted); line-height: 1.7; }
    .gr-review figcaption { font-size: 13px; color: var(--gr-rust); }
    .gr-stars { color: var(--gr-gold); letter-spacing: 2px; }

    /* Offer */
    .gr-tiers { display: grid; grid-template-columns: repeat(3, 1fr); gap: 18px; }
    .gr-tier { position: relative; background: var(--gr-card); border: 1.5px solid var(--gr-line); border-radius: 18px; padding: 24px 22px; text-align: left; }
    .gr-tier.is-picked { border-color: var(--gr-green); box-shadow: 0 12px 30px rgba(47, 107, 63, .12); }
    .gr-tier-badge { position: absolute; top: -11px; right: 18px; background: var(--gr-gold); color: var(--gr-ink); font-size: 12px; font-weight: 600; padding: 3px 12px; border-radius: 999px; }
    .gr-tier-name { font-size: 13px; color: var(--gr-muted); }
    .gr-tier-size { font-size: 24px; font-weight: 700; color: var(--gr-ink); margin: 4px 0 10px; }
    .gr-tier-price { display: flex; align-items: baseline; gap: 10px; }
    .gr-tier-price strong { font-size: 26px; color: var(--gr-green); }
    .gr-tier-price s { font-size: 14px; color: var(--gr-muted); }
    .gr-tier-unit { font-size: 13px; color: var(--gr-muted); margin: 2px 0 12px; }
    .gr-tier-save { display: inline-block; background: #dfeed8; color: var(--gr-green); padding: 4px 12px; border-radius: 999px; font-size: 13px; }
    .gr-bundle { margin-top: 28px; background: var(--gr-dark); color: #f4efe2; border-radius: 22px; padding: 30px 34px; display: flex; justify-content: space-between; align-items: center; gap: 24px; flex-wrap: wrap; }
    .gr-bundle-title { font-size: 18px; font-weight: 600; color: var(--gr-gold); margin: 0 0 12px; }
    .gr-pill { display: inline-block; border: 1px solid rgba(255, 255, 255, .25); border-radius: 999px; padding: 6px 14px; font-size: 14px; margin: 0 8px 8px 0; }
    .gr-bundle-note { font-size: 12px; color: #c9d3c4; line-height: 1.8; }
    .gr-bundle .gr-btn { background: var(--gr-gold); color: var(--gr-ink); box-shadow: none; }

    /* Policy */
    .gr-policy-two { display: grid; grid-template-columns: 1fr 1fr; gap: 22px; }
    .gr-zone { position: relative; display: flex; justify-content: space-between; align-items: center; gap: 12px; padding: 14px 0; border-bottom: 1px solid #e0d6c0; }
    .gr-zone strong { display: block; font-weight: 600; color: var(--gr-ink); font-size: 15px; }
    .gr-zone small { display: block; color: var(--gr-muted); font-size: 13px; }
    .gr-zone > span:last-child { font-weight: 700; color: var(--gr-green); }
    .gr-pay { position: relative; display: flex; justify-content: space-between; align-items: center; gap: 12px; background: var(--gr-card); border: 1px solid var(--gr-line); border-radius: 14px; padding: 14px 16px; font-size: 14px; color: var(--gr-muted); margin-bottom: 12px; }
    .gr-pay.is-on { border: 2px solid var(--gr-green); background: #eaf3e2; color: var(--gr-ink); }
    .gr-tag { font-size: 12px; padding: 3px 10px; border-radius: 999px; background: #efe8d8; color: var(--gr-muted); white-space: nowrap; }
    .gr-pay.is-on .gr-tag { background: var(--gr-green); color: #fff; }
    .gr-policy-two { margin-top: 40px; }
    .gr-policy-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-top: 22px; }
    .gr-policy-cell { position: relative; background: var(--gr-card); border: 1px solid var(--gr-line); border-radius: 16px; padding: 22px 24px; }
    .gr-policy-cell h3 { font-size: 16px; font-weight: 600; color: var(--gr-ink); margin: 0 0 10px; }
    .gr-policy-cell ul { margin: 0; padding-left: 18px; color: var(--gr-muted); font-size: 14px; line-height: 1.75; }
    .gr-policy-cell li { margin-bottom: 4px; }

    /* FAQ */
    .gr-split-faq { display: grid; grid-template-columns: 1fr 1.5fr; gap: 56px; align-items: start; }
    .gr-faq-list { background: var(--gr-card); border: 1px solid var(--gr-line); border-radius: 18px; padding: 6px 26px; }
    .gr-faq { position: relative; border-bottom: 1px solid var(--gr-line); }
    .gr-faq:last-child { border-bottom: 0; }
    .gr-faq summary { cursor: pointer; list-style: none; padding: 18px 40px 18px 0; font-size: 16px; font-weight: 600; color: var(--gr-ink); }
    .gr-faq summary::-webkit-details-marker { display: none; }
    .gr-chev { position: absolute; right: 2px; top: 22px; color: var(--gr-rust); font-size: 13px; }
    .gr-faq .gr-body { padding-bottom: 18px; }

    /* Closing banner */
    .gr-final-wrap { padding: 88px 0; }
    .gr-final { position: relative; background: var(--gr-dark); color: #f4efe2; border-radius: 26px; padding: 48px 54px; display: flex; justify-content: space-between; align-items: center; gap: 32px; flex-wrap: wrap; overflow: hidden; }
    .gr-final .gr-eyebrow { color: var(--gr-gold); text-transform: uppercase; letter-spacing: .12em; font-size: 12px; font-weight: 600; }
    .gr-final .gr-h2 { color: #fff; font-size: clamp(26px, 3.4vw, 36px); margin-bottom: 18px; }
    .gr-final .gr-checks { margin-top: 0; color: #d9e0d2; }
    .gr-final .gr-checks li::before { color: var(--gr-gold); }
    .gr-final-cta { text-align: right; }
    .gr-final-cta .gr-btn { background: var(--gr-gold); color: var(--gr-ink); box-shadow: none; }
    .gr-final-cta small { display: block; margin-top: 10px; font-size: 13px; color: #c9d3c4; }

    /* Order form: the shared form, dressed for this template */
    #checkout { background: var(--gr-tint); padding: 88px 0; }
    #checkout .lp-h2 { text-align: center; font-size: clamp(26px, 3.6vw, 36px); color: var(--gr-ink); font-weight: 700; }
    #checkout .lp-form { border-radius: 18px; border-color: var(--gr-line); }
    #checkout .lp-btn { background: var(--gr-green); border-radius: 999px; font-weight: 700; }

    /* Layout at narrower widths */
    @media (min-width: 900px) {
        .gr-hero-grid { grid-template-columns: 1fr 1fr; }
    }
    @media (max-width: 900px) {
        .gr-cards, .gr-tiers, .gr-reviews, .gr-policy-grid { grid-template-columns: 1fr 1fr; }
        .gr-details-grid, .gr-policy-two, .gr-split-faq { grid-template-columns: 1fr; }
        .gr-mosaic { grid-template-columns: 1fr 1fr; grid-auto-rows: 220px; }
        .gr-section { padding: 60px 0; }
    }
    @media (max-width: 560px) {
        .gr-cards, .gr-tiers, .gr-reviews, .gr-policy-grid, .gr-concerns, .gr-mosaic { grid-template-columns: 1fr; }
        .gr-tile-1 { grid-row: auto; }
        .gr-final { padding: 32px 26px; }
        .gr-final-cta { text-align: left; }
        .gr-panel { padding: 22px 20px; }
        .gr-bundle { padding: 24px; }
    }
</style>
