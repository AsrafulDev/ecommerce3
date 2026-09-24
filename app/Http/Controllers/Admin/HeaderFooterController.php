<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\HeaderFooterComponents;
use Illuminate\Http\Request;
use App\Models\GeneralSetting;
use Illuminate\Support\Facades\Cache;
use Toastr;

class HeaderFooterController extends Controller
{
    // Component definitions live in the shared support class (storefront uses them too).
    public static function headerComponents() {
        return HeaderFooterComponents::headerComponents();
    }
    public static function footerComponents() {
        return HeaderFooterComponents::footerComponents();
    }

    /** Read one component column (JSON cast) as a normalized row structure. */
    private static function stored(GeneralSetting $setting, string $type): array
    {
        $col = $type . '_components';
        $normalized = HeaderFooterComponents::normalize($setting->$col, $type);

        return HeaderFooterComponents::hasWidgets($normalized)
            ? $normalized
            : HeaderFooterComponents::defaults($type);
    }

    public function index()
    {
        $setting = GeneralSetting::first();

        // Set defaults if not configured
        if (!$setting->header_style) $setting->header_style = 'custom';
        if (!$setting->footer_style) $setting->footer_style = 'custom';
        if (!in_array((int)$setting->header_all_category_button, [0,1], true)) $setting->header_all_category_button = 1;
        if (!in_array($setting->header_all_category_type, ['dropdown','mega','icon','shop'], true)) $setting->header_all_category_type = 'mega';
        $setting->save();

        $hComps = self::headerComponents();
        $fComps = self::footerComponents();

        $activeHeader = self::stored($setting, 'header');
        $activeFooter = self::stored($setting, 'footer');

        $availableHeader = array_values(array_diff(array_keys($hComps), HeaderFooterComponents::ids($activeHeader)));
        $availableFooter = array_values(array_diff(array_keys($fComps), HeaderFooterComponents::ids($activeFooter)));

        $headerStyles = ['default'=>'Default','classic'=>'Classic','modern'=>'Modern','minimal'=>'Minimal','centered'=>'Centered','mega'=>'Mega Menu','custom'=>'Custom'];
        $footerStyles = ['default'=>'Default','classic'=>'Classic','modern'=>'Modern','dark'=>'Dark','minimal'=>'Minimal','columns'=>'Columns','custom'=>'Custom'];

        return view('backEnd.headerfooter.index', compact(
            'setting','activeHeader','activeFooter','availableHeader','availableFooter','headerStyles','footerStyles','hComps','fComps'
        ));
    }

    public function update(Request $request)
    {
        $setting = GeneralSetting::first();
        if (!$setting) {
            Toastr::error('Settings not found!', 'Error');
            return redirect()->back();
        }

        $setting->header_style = $request->header_style ?? $setting->header_style;
        $setting->footer_style = $request->footer_style ?? $setting->footer_style;
        $setting->header_top_bar = $request->boolean('header_top_bar');
        $setting->header_sticky = $request->boolean('header_sticky');

        // All Category Button — on/off + presentation type (dropdown nav / mega menu / icon menu / shop link)
        $setting->header_all_category_button = $request->boolean('header_all_category_button') ? 1 : 0;
        $allowedTypes = ['dropdown','mega','icon','shop'];
        $setting->header_all_category_type = in_array($request->header_all_category_type, $allowedTypes, true)
            ? $request->header_all_category_type
            : 'mega';

        // Builder canvas (draft) arrives as JSON hidden inputs; normalize + validate ids.
        $provided = [];
        foreach (['header', 'footer'] as $type) {
            $input = $request->input($type . '_components');
            if ($input === null) {
                continue;
            }
            if (is_string($input)) {
                $input = json_decode($input, true);
            }
            if (is_array($input)) {
                $provided[$type] = true;
                $setting->{$type . '_components'} = HeaderFooterComponents::normalize($input, $type);
            }
        }

        // If switching to custom, set default components if not already set
        // (an explicitly emptied canvas from the builder must stay empty — note
        // the normalizer ALWAYS returns 3 row keys, so hasWidgets() is the check,
        // not empty()).
        if ($setting->header_style === 'custom'
            && ! HeaderFooterComponents::hasWidgets($setting->header_components)
            && ! isset($provided['header'])) {
            $setting->header_components = HeaderFooterComponents::defaults('header');
        }
        if ($setting->footer_style === 'custom'
            && ! HeaderFooterComponents::hasWidgets($setting->footer_components)
            && ! isset($provided['footer'])) {
            $setting->footer_components = HeaderFooterComponents::defaults('footer');
        }

        $setting->save();

        // Clear caches so frontend picks up changes immediately
        Cache::forget('general_setting');
        Cache::forget('frontend_homepage_v1');

        Toastr::success('Header & Footer updated! Changes are now live.', 'Success');
        return redirect()->back();
    }

    /** AJAX: Preview header/footer (style preset or unsaved builder draft) */
    public function preview(Request $request)
    {
        $type = $request->type; $style = $request->style ?? null;
        if (!in_array($type, ['header', 'footer'], true)) {
            return response()->json(['error' => 'Invalid preview type.'], 422);
        }
        $setting = GeneralSetting::first();
        $contact = \App\Models\Contact::first();
        $menucategories = \App\Models\Category::where('status',1)->where('parent_id',0)
            ->with(['subcategories.childcategories'])->get();
        $socials = \App\Models\SocialMedia::where('status',1)->get();
        $brands = \App\Models\Brand::where('status',1)->limit(12)->get();
        $activeTheme = \App\Models\Theme::where('is_default', true)->first() ?? \App\Models\Theme::first();

        // Preset style — 'custom' skips this to use component-based rendering below
        $validHeaderStyles = ['default','classic','modern','minimal','centered','mega'];
        $validFooterStyles = ['default','classic','modern','dark','minimal','columns'];
        if ($type === 'header' && $style && in_array($style, $validHeaderStyles)) {
            $view = 'frontEnd.layouts.headers.' . $style;
        } elseif ($type === 'footer' && $style && in_array($style, $validFooterStyles)) {
            $view = 'frontEnd.layouts.footers.' . $style;
        } else {
            $view = null;
        }

        if ($view && view()->exists($view)) {
            $bodyHtml = view($view, compact('setting','contact','menucategories','socials','brands','activeTheme'))->render();
            return response()->json(['html' => $this->wrapPreviewHtml($bodyHtml, $activeTheme, $this->pullPushedScripts())]);
        }

        // Custom components — render the unsaved draft when provided, else the stored layout.
        // Uses the SAME partial as the storefront so the preview can't drift.
        $draft = $request->input('components');
        if (is_string($draft)) {
            $draft = json_decode($draft, true);
        }

        if (is_array($draft)) {
            // An explicitly emptied canvas must preview as empty, not as defaults.
            $normalized = HeaderFooterComponents::normalize($draft, $type);
            $rows = HeaderFooterComponents::hasWidgets($normalized) ? $normalized['rows'] : [];
        } else {
            $rows = self::stored($setting, $type)['rows'];
        }

        $bodyHtml = view('frontEnd.layouts.partials.hf-builder', compact('type', 'rows'))
            ->render();

        return response()->json([
            'html' => $this->wrapPreviewHtml($bodyHtml, $activeTheme, $this->pullPushedScripts()),
        ]);
    }

    /**
     * Collect Blade @push('script') output produced while rendering the preview
     * body, so widgets that ship behaviour in a push (e.g. all_categories'
     * dropdown toggle) actually work inside the preview iframe.
     */
    private function pullPushedScripts(): string
    {
        try {
            return app('view')->yieldPushContent('script');
        } catch (\Throwable $e) {
            return '';
        }
    }

    /**
     * Wrap preview HTML in a complete document with all frontend CSS
     */
    private function wrapPreviewHtml(string $bodyHtml, $activeTheme = null, string $pushedScripts = ''): string
    {
        $themeVars = '';
        if ($activeTheme) {
            $themeVars = ':root {
                --primary-color: ' . ($activeTheme->primary_color ?? '#0d6efd') . ';
                --secondary-color: ' . ($activeTheme->secondary_color ?? '#198754') . ';
                --accent-color: ' . ($activeTheme->accent_color ?? '#ff6a00') . ';
                --text-color: ' . ($activeTheme->text_color ?? '#212529') . ';
                --heading-color: ' . ($activeTheme->heading_color ?? '#111111') . ';
                --body-bg: ' . ($activeTheme->body_bg_color ?? '#ffffff') . ';
                --header-bg: ' . ($activeTheme->header_bg_color ?? '#ffffff') . ';
                --header-text: ' . ($activeTheme->header_text_color ?? '#212529') . ';
                --footer-bg: ' . ($activeTheme->footer_bg_color ?? '#1a1a1a') . ';
                --footer-text: ' . ($activeTheme->footer_text_color ?? '#ffffff') . ';
                --copyright-bg: ' . ($activeTheme->copyright_bg_color ?? '#000000') . ';
                --copyright-text: ' . ($activeTheme->copyright_text_color ?? '#ffffff') . ';
                --button-bg: ' . ($activeTheme->button_bg_color ?? '#0d6efd') . ';
                --button-text: ' . ($activeTheme->button_text_color ?? '#ffffff') . ';
                --button-hover-bg: ' . ($activeTheme->button_hover_bg_color ?? '#0b5ed7') . ';
                --border-color: ' . ($activeTheme->border_color ?? '#dee2e6') . ';
                --sale-badge-bg: ' . ($activeTheme->sale_badge_bg ?? '#dc3545') . ';
                --sale-badge-text: ' . ($activeTheme->sale_badge_text ?? '#ffffff') . ';
                --font-family: ' . ($activeTheme->font_family ?? "'Roboto', sans-serif") . ';
                --heading-font: ' . ($activeTheme->heading_font ?? "'Jost', sans-serif") . ';
                --body-font-size: ' . ($activeTheme->body_font_size ?? '14px') . ';
                --border-radius: ' . ($activeTheme->border_radius ?? '8px') . ';
            }';
        }

        $assetBase = asset('public/frontEnd/css');

        return '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="' . $assetBase . '/bootstrap.min.css">
    <link rel="stylesheet" href="' . $assetBase . '/all.min.css">
    <link rel="stylesheet" href="' . $assetBase . '/mobile-menu.css">
    <link rel="stylesheet" href="' . $assetBase . '/wsit-menu.css">
    <link rel="stylesheet" href="' . url('/style.css') . '">
    <link rel="stylesheet" href="' . url('/dynamic-theme.css') . '">
    <link rel="stylesheet" href="' . url('/responsive.css') . '">
    <link rel="stylesheet" href="' . $assetBase . '/main.css">
    <style>
        ' . $themeVars . '
        html, body {
            margin:0 !important; padding:0 !important;
            width:100% !important;
            font-family: var(--font-family);
            font-size: var(--body-font-size);
            color: var(--text-color);
            background: #fff;
            -webkit-font-smoothing: antialiased;
            overflow-x: hidden;
        }
        a { text-decoration: none; }
        img { max-width: 100%; height: auto; }
        .container { max-width: 100% !important; padding-left: 10px !important; padding-right: 10px !important; }
    </style>
    <script src="' . asset('public/frontEnd/js/jquery-3.6.3.min.js') . '"></script>
</head>
<body>' . $bodyHtml . ($pushedScripts !== '' ? "\n" . $pushedScripts : '') . '</body>
</html>';
    }
}
