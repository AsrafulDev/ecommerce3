{{-- Luxury look: serif headings, a soft hero background and square corners. --}}
<style>
    :root { --font-head: 'Cormorant Garamond', Georgia, serif; --font-body: 'Hind Siliguri', 'Cormorant Garamond', Georgia, serif; --line: #e7dfd3; --muted: #4d463d; }
    .lp-section { padding: 72px 0; }
    .lp-hero { padding: 64px 0; background: linear-gradient(180deg, #ffffff 0%, var(--bg) 100%); }
    .lp-hero-img { border-radius: 4px; box-shadow: 0 18px 40px rgba(28, 25, 23, .12); }
    .lp-title { font-size: clamp(34px, 6vw, 56px); letter-spacing: .01em; }
    .lp-eyebrow { letter-spacing: .22em; }
    .lp-card, .lp-form, .lp-variant, .lp-table { border-radius: 6px; }
    .lp-btn { border-radius: 2px; letter-spacing: .04em; }
    .lx-nav { background: #fff; border-bottom: 1px solid var(--line); }
    .lx-nav-inner { max-width: 1200px; margin: 0 auto; padding: 18px 24px; display: flex; align-items: center; justify-content: space-between; gap: 16px; }
    .lx-brand { display: flex; flex-direction: column; line-height: 1.2; }
    .lx-brand span { font-family: var(--font-head); font-size: 22px; letter-spacing: .08em; color: var(--accent); }
    .lx-brand small { font-size: 11px; letter-spacing: .22em; color: var(--muted); margin-top: 2px; }
    .lx-logo { max-height: 44px; width: auto; }
    .lx-nav-cta { background: var(--accent); color: #fff; padding: 9px 20px; border-radius: 2px; text-decoration: none; font-size: 14px; letter-spacing: .04em; }
    @media (min-width: 900px) {
        .lp-hero { padding: 96px 0; }
    }
</style>
