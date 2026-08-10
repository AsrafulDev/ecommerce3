<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\GeneralSetting;
use Illuminate\Support\Facades\Cache;
use Toastr;

class HeaderFooterController extends Controller
{
    // Component definitions
    public static function headerComponents() {
        return [
            'topbar' => ['name' => 'Top Bar', 'icon' => 'mdi-page-layout-header'],
            'logo'   => ['name' => 'Logo Area', 'icon' => 'mdi-image'],
            'search' => ['name' => 'Search Bar', 'icon' => 'mdi-magnify'],
            'nav'    => ['name' => 'Navigation Menu', 'icon' => 'mdi-menu'],
            'cart'   => ['name' => 'Cart & Icons', 'icon' => 'mdi-cart'],
        ];
    }
    public static function footerComponents() {
        return [
            'about'      => ['name' => 'About Section', 'icon' => 'mdi-information'],
            'links'      => ['name' => 'Quick Links', 'icon' => 'mdi-link-variant'],
            'support'    => ['name' => 'Support Links', 'icon' => 'mdi-headset'],
            'newsletter' => ['name' => 'Newsletter', 'icon' => 'mdi-email'],
            'social'     => ['name' => 'Social Icons', 'icon' => 'mdi-share-variant'],
            'copyright'  => ['name' => 'Copyright Bar', 'icon' => 'mdi-copyright'],
        ];
    }
    public function index()
    {
        $setting = GeneralSetting::first();
        
        // Set defaults if not configured
        if (!$setting->header_style) $setting->header_style = 'custom';
        if (!$setting->footer_style) $setting->footer_style = 'custom';
        if (empty($setting->header_components)) $setting->header_components = array_keys(self::headerComponents());
        if (empty($setting->footer_components)) $setting->footer_components = array_keys(self::footerComponents());
        if (!in_array((int)$setting->header_all_category_button, [0,1], true)) $setting->header_all_category_button = 1;
        if (!in_array($setting->header_all_category_type, ['dropdown','mega','icon','shop'], true)) $setting->header_all_category_type = 'mega';
        $setting->save();
        
        $hComps = self::headerComponents();
        $fComps = self::footerComponents();
        
        $defaultH = ['topbar','logo','search','nav','cart'];
        $defaultF = ['about','links','support','newsletter','social','copyright'];
        
        $activeHeader = $setting->header_components ?: $defaultH;
        $activeFooter = $setting->footer_components ?: $defaultF;
        
        $availableHeader = array_values(array_diff(array_keys($hComps), $activeHeader));
        $availableFooter = array_values(array_diff(array_keys($fComps), $activeFooter));

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

        // If switching to custom, set default components if not already set
        if ($setting->header_style === 'custom' && empty($setting->header_components)) {
            $setting->header_components = array_keys(self::headerComponents());
        }
        if ($setting->footer_style === 'custom' && empty($setting->footer_components)) {
            $setting->footer_components = array_keys(self::footerComponents());
        }

        $setting->save();

        // Clear caches so frontend picks up changes immediately
        Cache::forget('general_setting');
        Cache::forget('frontend_homepage_v1');

        Toastr::success('Header & Footer updated! Changes are now live.', 'Success');
        return redirect()->back();
    }

    /**
    public function update(Request $request)
    {
        $setting = GeneralSetting::first();
        if (!$setting) {
            Toastr::error('Settings not found!', 'Error');
            return redirect()->back();
        }
        $setting->header_style = $request->header_style ?? 'custom';
        $setting->footer_style = $request->footer_style ?? 'custom';
        $setting->header_top_bar = $request->boolean('header_top_bar');
        $setting->header_sticky = $request->boolean('header_sticky');
        $setting->header_components = $request->header_components;
        $setting->footer_components = $request->footer_components;
        $setting->save();
        Cache::forget('frontend_homepage_v1');
        Toastr::success('Header & Footer saved!', 'Success');
        return redirect()->back();
    }

    /** AJAX: Add component */
    public function addComponent(Request $request)
    {
        $type = $request->type; $comp = $request->component;
        $all = $type === 'header' ? self::headerComponents() : self::footerComponents();
        if (!isset($all[$comp])) return response()->json(['error'=>'Invalid'],400);
        $setting = GeneralSetting::first();
        $col = $type . '_components';
        $current = $setting->$col ?: [];
        if (!in_array($comp, $current)) { $current[] = $comp; }
        $setting->$col = $current; $setting->save();
        return response()->json(['success'=>true, 'components'=>$current]);
    }

    /** AJAX: Remove component */
    public function removeComponent(Request $request)
    {
        $type = $request->type; $comp = $request->component;
        $setting = GeneralSetting::first();
        $col = $type . '_components';
        $current = $setting->$col ?: [];
        $current = array_values(array_diff($current, [$comp]));
        $setting->$col = $current; $setting->save();
        return response()->json(['success'=>true, 'components'=>$current]);
    }

    /** AJAX: Reorder components */
    public function reorderComponents(Request $request)
    {
        $type = $request->type; $order = $request->order;
        $setting = GeneralSetting::first();
        $col = $type . '_components';
        $setting->$col = $order; $setting->save();
        return response()->json(['success'=>true]);
    }

    /** AJAX: Preview header/footer */
    public function preview(Request $request)
    {
        $type = $request->type; $style = $request->style ?? null;
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
            return response()->json(['html' => $this->wrapPreviewHtml($bodyHtml, $activeTheme)]);
        }

        // Custom components — render individually for live builder
        $col = $type . '_components';
        $components = $setting->$col ?: [];
        $allComponents = $type === 'header' ? self::headerComponents() : self::footerComponents();
        $html = '';
        foreach ($components as $comp) {
            if (isset($allComponents[$comp])) {
                $view = 'frontEnd.layouts.' . $type . 's.parts.' . $comp;
                if (view()->exists($view)) {
                    $html .= view($view, compact('setting','contact','menucategories','socials','brands'))->render();
                } else {
                    $html .= '<div style="padding:8px;border:1px dashed #ccc;margin:4px;border-radius:4px;">'
                          . '<strong>' . $allComponents[$comp]['name'] . '</strong>'
                          . '<br><small class="text-muted">Create: resources/views/frontEnd/layouts/' . $type . 's/parts/' . $comp . '.blade.php</small>'
                          . '</div>';
                }
            }
        }
        return response()->json(['html' => $this->wrapPreviewHtml($html, $activeTheme)]);
    }

    /**
     * Wrap preview HTML in a complete document with all frontend CSS
     */
    private function wrapPreviewHtml(string $bodyHtml, $activeTheme = null): string
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
</head>
<body>' . $bodyHtml . '</body>
</html>';
    }
}
