<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\GeneralSetting;
use Toastr;

class HeaderFooterController extends Controller
{
    public function index()
    {
        $setting = GeneralSetting::first();
        $headerStyles = [
            'classic'  => ['name' => 'Classic', 'desc' => 'Traditional top bar + nav with search', 'icon' => 'mdi-page-layout-header'],
            'modern'   => ['name' => 'Modern', 'desc' => 'Clean search-focused with rounded elements', 'icon' => 'mdi-page-layout-header-footer'],
            'minimal'  => ['name' => 'Minimal', 'desc' => 'Simple logo + nav, minimal chrome', 'icon' => 'mdi-page-layout-body'],
            'centered' => ['name' => 'Centered', 'desc' => 'Logo centered, nav below', 'icon' => 'mdi-align-horizontal-center'],
            'mega'     => ['name' => 'Mega Menu', 'desc' => 'Dark nav with category mega dropdown', 'icon' => 'mdi-menu-open'],
        ];
        $footerStyles = [
            'classic'  => ['name' => 'Classic', 'desc' => '4-column dark footer with social icons', 'icon' => 'mdi-page-layout-footer'],
            'modern'   => ['name' => 'Modern', 'desc' => 'Light footer with organized link columns', 'icon' => 'mdi-view-dashboard'],
            'dark'     => ['name' => 'Dark', 'desc' => 'Full-width dark footer with newsletter', 'icon' => 'mdi-invert-colors'],
            'minimal'  => ['name' => 'Minimal', 'desc' => 'Simple centered text with privacy links', 'icon' => 'mdi-minimize'],
            'columns'  => ['name' => 'Columns', 'desc' => '5-column layout with app store badges', 'icon' => 'mdi-view-column'],
        ];

        return view('backEnd.headerfooter.index', compact('setting', 'headerStyles', 'footerStyles'));
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
     * AJAX: Preview a header/footer style in iframe
     */
    public function preview(Request $request)
    {
        $type = $request->type; // 'header' or 'footer'
        $style = $request->style;

        $validStyles = ['classic','modern','minimal','centered','mega'];
        if (!in_array($style, $validStyles)) {
            return response()->json(['error' => 'Invalid style'], 400);
        }

        $setting = GeneralSetting::first();
        $contact = \App\Models\Contact::first();
        $menucategories = \App\Models\Category::where('status',1)->where('parent_id',0)
            ->with(['subcategories.childcategories'])->get();
        $socials = \App\Models\SocialMedia::where('status',1)->get();

        if ($type === 'header') {
            $html = view('frontEnd.layouts.headers.' . $style, compact('setting', 'contact', 'menucategories', 'socials'))->render();
        } else {
            $html = view('frontEnd.layouts.footers.' . $style, compact('setting', 'contact', 'menucategories', 'socials'))->render();
        }

        return response()->json(['html' => $html]);
    }
}
