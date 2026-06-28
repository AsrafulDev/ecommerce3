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
        $hComps = self::headerComponents();
        $fComps = self::footerComponents();
        
        $defaultH = ['topbar','logo','search','nav','cart'];
        $defaultF = ['about','links','support','newsletter','social','copyright'];
        
        $activeHeader = $setting->header_components ?: $defaultH;
        $activeFooter = $setting->footer_components ?: $defaultF;
        
        $availableHeader = array_values(array_diff(array_keys($hComps), $activeHeader));
        $availableFooter = array_values(array_diff(array_keys($fComps), $activeFooter));

        $headerStyles = ['classic'=>'Classic','modern'=>'Modern','minimal'=>'Minimal','centered'=>'Centered','mega'=>'Mega Menu','custom'=>'Custom'];
        $footerStyles = ['classic'=>'Classic','modern'=>'Modern','dark'=>'Dark','minimal'=>'Minimal','columns'=>'Columns','custom'=>'Custom'];

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
        $setting->save();

        Toastr::success('Header & Footer updated!', 'Success');
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

        // Preset style
        $validStyles = ['classic','modern','minimal','centered','mega'];
        if ($style && in_array($style, $validStyles)) {
            $view = 'frontEnd.layouts.headers.' . $style;
        } elseif ($style && in_array($style, ['classic','modern','dark','minimal','columns'])) {
            $view = 'frontEnd.layouts.footers.' . $style;
        } else {
            $view = null;
        }
        if ($view && view()->exists($view)) {
            return response()->json(['html' => view($view, compact('setting','contact','menucategories','socials','brands'))->render()]);
        }

        // Custom components
        $col = $type . '_components';
        $components = $setting->$col ?: [];
        $allComponents = $type === 'header' ? self::headerComponents() : self::footerComponents();
        $html = '<div style="font-family:sans-serif;">';
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
        $html .= '</div>';
        return response()->json(['html' => $html]);
    }
}
