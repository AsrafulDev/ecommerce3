{{-- Fashion look, following the ELAN design: cream and rose palette, arch photos, dark band, mosaic. --}}
<style>
    :root { --font-head: 'Cormorant Garamond', Georgia, serif; --font-body: 'Hind Siliguri', system-ui, sans-serif; --card: #fffdf9; --line: #e6dccd; --muted: #6f655a; --soft: #f1e8da; --rose: #a86a5f; --ink: #2b2622; }
    body { background: #faf7f2; }
    .fx-wrap { max-width: 1200px; margin: 0 auto; padding: 0 24px; }
    .fx-section { padding: 88px 0; }
    .fx-alt { background: #f2e9dc; }
    .fx-eyebrow { display: flex; align-items: center; gap: 12px; font-size: 13px; color: var(--rose); letter-spacing: .04em; margin: 0 0 14px; }
    .fx-eyebrow::before { content: ""; width: 28px; height: 1px; background: var(--rose); }
    .fx-h2 { font-family: var(--font-body); font-weight: 600; font-size: clamp(30px, 4vw, 46px); line-height: 1.25; color: var(--ink); margin: 0 0 24px; }
    .fx-h3 { font-family: var(--font-body); font-weight: 600; font-size: 24px; color: var(--ink); margin: 0 0 18px; }
    .fx-h4 { font-family: var(--font-body); font-weight: 600; font-size: 18px; color: var(--ink); margin: 0 0 14px; }
    .fx-sub { font-family: var(--font-head); font-style: italic; font-size: 20px; color: var(--rose); margin: 0; }
    .fx-body { color: var(--muted); font-size: 16px; line-height: 1.8; }
    .fx-small { font-size: 13px; line-height: 1.7; }
    .fx-fine { font-size: 12px; color: var(--muted); margin: 14px 0 0; }

    /* Navigation */
    .fx-nav { background: #faf7f2; border-bottom: 1px solid var(--line); }
    .fx-nav-inner { max-width: 1200px; margin: 0 auto; padding: 18px 24px; display: flex; align-items: center; justify-content: space-between; }
    .fx-brand span { font-family: var(--font-head); font-size: 24px; letter-spacing: .3em; color: var(--ink); }
    .fx-brand small { display: block; font-size: 10px; letter-spacing: .3em; color: var(--muted); }
    .fx-nav-cta { background: var(--ink); color: #fff; padding: 9px 20px; border-radius: 999px; text-decoration: none; font-size: 14px; }

    /* Hero */
    .fx-hero { padding: 72px 0 56px; }
    .fx-hero-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 56px; align-items: center; }
    .fx-title { font-family: var(--font-body); font-weight: 700; font-size: clamp(40px, 5vw, 62px); line-height: 1.2; color: var(--ink); margin: 0; }
    .fx-tagline { font-family: var(--font-head); font-style: italic; font-size: 30px; color: var(--rose); margin: 6px 0 18px; }
    .fx-lead { font-size: 17px; color: var(--muted); line-height: 1.8; max-width: 520px; }
    .fx-price-row { display: flex; align-items: center; gap: 14px; flex-wrap: wrap; margin: 22px 0 6px; }
    .fx-price { font-size: 38px; font-weight: 700; color: var(--ink); }
    .fx-old { font-size: 18px; color: var(--muted); text-decoration: line-through; }
    .fx-badge { background: #fde4df; color: #9b4a40; padding: 4px 12px; border-radius: 999px; font-size: 13px; }
    .fx-note { font-size: 14px; color: var(--muted); margin: 0 0 22px; }
    .fx-actions { display: flex; gap: 14px; flex-wrap: wrap; }
    .fx-btn { display: inline-flex; align-items: center; gap: 8px; background: var(--rose); color: #fff; padding: 15px 30px; border-radius: 999px; text-decoration: none; font-weight: 600; font-size: 16px; box-shadow: 0 10px 24px rgba(168, 106, 95, .25); border: 0; cursor: pointer; font-family: inherit; }
    .fx-btn-wide { width: 100%; justify-content: center; margin-top: 18px; }
    .fx-btn-ghost { display: inline-flex; align-items: center; gap: 8px; border: 1px solid var(--line); color: var(--ink); padding: 15px 26px; border-radius: 999px; text-decoration: none; font-size: 16px; background: transparent; }
    .fx-perks { list-style: none; padding: 0; margin: 26px 0 0; display: grid; grid-template-columns: 1fr 1fr; gap: 10px 24px; color: #4b443c; font-size: 14px; }
    .fx-hero-media { position: relative; }
    .fx-arch { position: relative; border-radius: 220px 220px 28px 28px; overflow: hidden; aspect-ratio: 4 / 5; background: #e9dccb; box-shadow: 0 30px 60px rgba(43, 38, 34, .12); }
    .fx-arch img { width: 100%; height: 100%; object-fit: cover; }

    /* Strip of connected cards (benefits, reviews) */
    .fx-strip { display: grid; grid-template-columns: repeat(4, 1fr); border: 1px solid var(--line); border-radius: 18px; background: var(--card); overflow: hidden; margin-top: 8px; }
    .fx-strip-cell { padding: 28px 24px; border-right: 1px solid var(--line); position: relative; margin: 0; }
    .fx-strip-cell:last-child { border-right: 0; }
    .fx-strip-cell::after { content: ""; position: absolute; left: 0; right: 0; bottom: 0; height: 10px; background: #e6d9c8; }
    .fx-strip-cell h3 { font-family: var(--font-body); font-size: 18px; font-weight: 600; color: var(--ink); margin: 14px 0 8px; }
    .fx-strip-cell p { font-size: 14px; color: var(--muted); margin: 0; line-height: 1.7; }
    .fx-dot { display: inline-block; width: 40px; height: 40px; border-radius: 50%; background: #f7dcd4; }
    .fx-stars { color: var(--rose); letter-spacing: 2px; }
    .fx-photo-reviews { display: grid; grid-template-columns: repeat(3, 1fr); gap: 18px; }
    .fx-photo-review { margin: 0; background: var(--card); border: 1px solid var(--line); border-radius: 16px; padding: 12px; position: relative; }
    .fx-photo-review .fx-tile-img { aspect-ratio: 4 / 5; height: auto; }
    .fx-photo-review figcaption { font-size: 13px; color: var(--rose); margin-top: 6px; }
    @media (max-width: 900px) { .fx-photo-reviews { grid-template-columns: 1fr 1fr; } }
    @media (max-width: 560px) { .fx-photo-reviews { grid-template-columns: 1fr; } }
    .fx-strip-cell blockquote { margin: 10px 0; font-size: 14px; color: var(--muted); }
    .fx-strip-cell figcaption { font-size: 13px; color: var(--rose); }

    /* Two columns */
    .fx-split { display: grid; grid-template-columns: 1fr 1fr; gap: 64px; align-items: start; }
    .fx-photo { border-radius: 18px; overflow: hidden; position: relative; aspect-ratio: 4 / 5; background: #e9dccb; }
    .fx-photo img { width: 100%; height: 100%; object-fit: cover; }
    .fx-photo-wide { aspect-ratio: 16 / 11; margin: 28px 0 0; }
    .fx-spec { width: 100%; border-collapse: collapse; margin: 26px 0 22px; }
    .fx-spec th, .fx-spec td { text-align: left; padding: 14px 0; border-bottom: 1px solid var(--line); font-size: 15px; vertical-align: top; }
    .fx-spec th { width: 36%; font-weight: 500; color: var(--muted); }
    .fx-spec td { color: var(--ink); font-weight: 500; }
    .fx-colours { display: flex; gap: 12px; flex-wrap: wrap; }
    .fx-chip { display: inline-flex; align-items: center; gap: 8px; border: 1px solid var(--line); border-radius: 999px; padding: 6px 14px 6px 6px; background: var(--card); font-size: 14px; }
    .fx-chip i { width: 24px; height: 24px; border-radius: 50%; border: 1px solid var(--line); display: inline-block; }
    .fx-chip b { font-weight: 500; }

    /* Size card and care list */
    .fx-card-soft { background: var(--soft); border-radius: 22px; padding: 34px 36px; }
    #sizes .fx-card-soft { background: #f1e8da; }
    .fx-tablewrap { overflow-x: auto; }
    .fx-size { width: 100%; border-collapse: collapse; font-size: 14px; }
    .fx-size th, .fx-size td { padding: 12px 8px; text-align: left; border-bottom: 1px solid #e0d3bf; }
    .fx-size th { color: var(--muted); font-weight: 500; }
    .fx-size td strong { color: var(--ink); }
    .fx-care { padding: 56px 0 88px; }
    .fx-steps { list-style: none; padding: 0; margin: 0; display: grid; grid-template-columns: 1fr 1fr; gap: 16px 40px; }
    .fx-steps li { display: flex; gap: 14px; color: var(--muted); font-size: 15px; }
    .fx-num { color: var(--rose); font-weight: 600; }

    /* Dark band */
    .fx-dark { background: var(--ink); color: #f4ede4; padding: 92px 0; }
    .fx-dark .fx-h2 { color: #fff; }
    .fx-dark .fx-body { color: #cfc5b8; }
    .fx-dark .fx-eyebrow { color: #d8998e; }
    .fx-dark .fx-eyebrow::before { background: #d8998e; }
    .fx-points { display: grid; grid-template-columns: 1fr 1fr; gap: 0 48px; }
    .fx-point { border-top: 1px solid #4a423b; padding: 28px 0 36px; position: relative; }
    .fx-point h3 { font-family: var(--font-body); font-size: 19px; font-weight: 600; color: #fff; margin: 0 0 10px; }
    .fx-point p { font-size: 15px; color: #cfc5b8; margin: 0; line-height: 1.7; }

    /* Mosaic gallery */
    .fx-mosaic { display: grid; grid-template-columns: repeat(4, 1fr); grid-auto-rows: 260px; gap: 16px; }
    .fx-tile { margin: 0; position: relative; }
    .fx-tile-img { position: relative; width: 100%; height: 100%; border-radius: 16px; overflow: hidden; background: #e9dccb; }
    .fx-tile-img img { width: 100%; height: 100%; object-fit: cover; }
    .fx-tile-1 { grid-row: span 2; }
    .fx-tile-3 { grid-column: span 2; }
    .fx-empty { display: grid; place-items: center; height: 100%; color: var(--muted); font-size: 13px; border: 1px dashed var(--line); border-radius: 16px; }

    /* Reviews empty state */
    .fx-empty-card { border: 1px dashed #d8cbb7; border-radius: 18px; padding: 40px 44px; display: flex; gap: 22px; align-items: flex-start; background: #fcfaf6; }
    .fx-empty-card h3 { font-family: var(--font-body); font-size: 19px; font-weight: 600; color: var(--ink); margin: 0 0 8px; }
    .fx-empty-card p { margin: 0; color: var(--muted); font-size: 15px; line-height: 1.7; }
    .fx-quote { font-family: var(--font-head); font-size: 64px; line-height: 1; color: #d8bfb6; }

    /* Offer card */
    .fx-offer { display: grid; grid-template-columns: 1fr 1.1fr; background: #fcfaf6; border-radius: 26px; overflow: hidden; box-shadow: 0 20px 50px rgba(43, 38, 34, .08); }
    .fx-offer-photo { min-height: 520px; background: #e9dccb; position: relative; }
    .fx-offer-photo img { width: 100%; height: 100%; object-fit: cover; }
    .fx-offer-body { padding: 44px 46px; }
    .fx-tiers { display: grid; gap: 12px; margin: 22px 0 6px; }
    .fx-tier { border: 1px solid var(--line); border-radius: 14px; padding: 16px 18px; display: grid; gap: 4px; background: var(--card); }
    .fx-tier strong { font-size: 16px; color: var(--ink); }
    .fx-tier span { font-size: 14px; color: var(--muted); }
    .fx-tier.is-picked { border: 2px solid var(--rose); background: #fbeee9; }

    /* Policy */
    .fx-policy-two { display: grid; grid-template-columns: 1fr 1fr; gap: 22px; margin: 8px 0 22px; }
    .fx-zones { display: grid; gap: 0; }
    .fx-zone { display: flex; justify-content: space-between; align-items: center; gap: 12px; padding: 14px 0; border-bottom: 1px solid #e0d3bf; }
    .fx-zone strong { display: block; font-weight: 500; color: var(--ink); font-size: 15px; }
    .fx-zone small { display: block; color: var(--muted); font-size: 13px; }
    .fx-zone span { font-weight: 600; color: var(--ink); }
    .fx-payments { display: grid; gap: 12px; }
    .fx-pay { background: var(--card); border-radius: 12px; padding: 14px 16px; font-size: 14px; color: var(--muted); }
    .fx-policy-four { display: grid; grid-template-columns: repeat(4, 1fr); gap: 18px; }
    .fx-policy-cell { background: var(--soft); border-radius: 18px; padding: 24px 22px; }
    .fx-policy-cell h3 { font-family: var(--font-body); font-size: 17px; font-weight: 600; color: var(--ink); margin: 0 0 10px; }
    .fx-policy-cell p { margin: 0; font-size: 14px; color: var(--muted); line-height: 1.7; }

    /* FAQ */
    .fx-split-faq { grid-template-columns: 1fr 1.4fr; }
    .fx-faq { border-bottom: 1px solid var(--line); padding: 20px 0; }
    .fx-faq summary { cursor: pointer; font-size: 16px; color: var(--ink); font-weight: 500; list-style: none; }
    .fx-faq .fx-body { margin-top: 10px; }

    /* Final banner */
    .fx-final { position: relative; background: #d9c4a8 center/cover no-repeat; padding: 120px 0; overflow: hidden; }
    .fx-final-overlay { position: absolute; inset: 0; background: linear-gradient(90deg, rgba(250, 247, 242, .92) 0%, rgba(250, 247, 242, .6) 45%, rgba(250, 247, 242, 0) 75%); }
    .fx-final-copy { position: relative; max-width: 1200px; }
    .fx-final .fx-h2 { max-width: 520px; }

    /* Responsive */
    @media (max-width: 900px) {
        .fx-hero-grid, .fx-split, .fx-split-faq, .fx-offer, .fx-policy-two { grid-template-columns: 1fr; }
        .fx-strip, .fx-policy-four, .fx-steps, .fx-points { grid-template-columns: 1fr 1fr; }
        .fx-mosaic { grid-template-columns: 1fr 1fr; grid-auto-rows: 220px; }
        .fx-tile-3 { grid-column: span 2; }
        .fx-offer-photo { min-height: 320px; }
        .fx-section { padding: 60px 0; }
    }
    @media (max-width: 560px) {
        .fx-strip, .fx-policy-four, .fx-steps, .fx-points, .fx-mosaic { grid-template-columns: 1fr; }
        .fx-tile-3, .fx-tile-1 { grid-column: auto; grid-row: auto; }
        .fx-perks { grid-template-columns: 1fr; }
        .fx-card-soft, .fx-offer-body { padding: 24px; }
    }
</style>
