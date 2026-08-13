@php
    $settings = \App\Models\GeneralSetting::first();
    $theme = null;
    if ($settings && $settings->theme_id) {
        $theme = \App\Models\Theme::find($settings->theme_id);
    }
    if (!$theme) {
        $theme = \App\Models\Theme::where('is_default', true)->first();
    }
@endphp
/* ================================================================
   🎨 Dynamic Theme Stylesheet
   Generated from active theme — loaded via /dynamic-theme.css
   ================================================================ */

:root {
    --primary-color: {{ $theme->primary_color ?? '#0d6efd' }};
    --secondary-color: {{ $theme->secondary_color ?? '#198754' }};
    --accent-color: {{ $theme->accent_color ?? '#ff6a00' }};
    --text-color: {{ $theme->text_color ?? '#212529' }};
    --heading-color: {{ $theme->heading_color ?? '#111111' }};
    --body-bg: {{ $theme->body_bg_color ?? '#ffffff' }};
    --header-bg: {{ $theme->header_bg_color ?? '#ffffff' }};
    --header-text: {{ $theme->header_text_color ?? '#212529' }};
    --footer-bg: {{ $theme->footer_bg_color ?? '#1a1a1a' }};
    --footer-text: {{ $theme->footer_text_color ?? '#cccccc' }};
    --copyright-bg: {{ $theme->copyright_bg_color ?? '#000000' }};
    --copyright-text: {{ $theme->copyright_text_color ?? '#ffffff' }};
    --button-bg: {{ $theme->button_bg_color ?? '#0d6efd' }};
    --button-text: {{ $theme->button_text_color ?? '#ffffff' }};
    --button-hover-bg: {{ $theme->button_hover_bg_color ?? '#0b5ed7' }};
    --border-color: {{ $theme->border_color ?? '#dee2e6' }};
    --sale-badge-bg: {{ $theme->sale_badge_bg ?? '#dc3545' }};
    --sale-badge-text: {{ $theme->sale_badge_text ?? '#ffffff' }};
    --font-family: {{ $theme->font_family ?? "'Roboto', sans-serif" }};
    --heading-font: {{ $theme->heading_font ?? "'Jost', sans-serif" }};
    --body-font-size: {{ $theme->body_font_size ?? '14px' }};
    --border-radius: {{ $theme->border_radius ?? '8px' }};
    --card-shadow: {{ $theme->card_shadow ?? '0 2px 8px rgba(0,0,0,0.08)' }};
    --layout-style: {{ $theme->layout_style ?? 'contained' }};
}

/* ================================================================
   Theme-specific utility classes
   ================================================================ */

.bg-primary-theme { background-color: var(--primary-color) !important; }
.text-primary-theme { color: var(--primary-color) !important; }
.border-primary-theme { border-color: var(--primary-color) !important; }

.bg-secondary-theme { background-color: var(--secondary-color) !important; }
.text-secondary-theme { color: var(--secondary-color) !important; }

.bg-accent-theme { background-color: var(--accent-color) !important; }
.text-accent-theme { color: var(--accent-color) !important; }

.bg-footer-theme { background-color: var(--footer-bg) !important; }
.bg-copyright-theme { background-color: var(--copyright-bg) !important; }

.btn-theme {
    background-color: var(--button-bg) !important;
    color: var(--button-text) !important;
    border: none;
    border-radius: var(--border-radius);
}
.btn-theme:hover {
    background-color: var(--button-hover-bg) !important;
    color: var(--button-text) !important;
}

/* Font family helpers */
.font-body { font-family: var(--font-family); }
.font-heading { font-family: var(--heading-font); }

/* Layout style */
@if(($theme->layout_style ?? 'contained') === 'full-width')
.container { max-width: 100% !important; padding-left: 15px; padding-right: 15px; }
@endif

/* Custom CSS from theme */
@if(!empty($theme->custom_css))
{{ $theme->custom_css }}
@endif

/* ================================================================
   Page Custom CSS — target specific pages via body page classes
   (e.g. body.page-home, body.page-category, body.pageurl-shop, ...)
   ================================================================ */
@if(!empty($theme->page_custom_css))
{{ $theme->page_custom_css }}
@endif
