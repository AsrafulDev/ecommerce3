<?php

namespace App\View\Composers;

use Illuminate\View\View;
use Illuminate\Support\Facades\Cache;
use App\Models\EcomPixel;
use App\Models\GoogleTagManager;
use App\Models\TiktokPixel;

class TrackingComposer
{
    /**
     * Share tracking pixels (Facebook, TikTok) and GTM with frontend views.
     * Ensures dataLayer-ready data for GTM, Facebook Pixel, TikTok Pixel.
     */
    public function compose(View $view)
    {
        // Only compose for frontend master layout
        if (!str_contains($view->getName(), 'frontEnd.layouts.master')) {
            return;
        }

        // Facebook Pixels - fallback if not already set
        if (!$view->offsetExists('pixels') || $view->getData()['pixels'] === null) {
            $view->with('pixels', Cache::remember('pixels_list', 3600, fn() => EcomPixel::where('status', 1)->get()));
        }

        // GTM - fallback if not already set
        if (!$view->offsetExists('gtm_code') || $view->getData()['gtm_code'] === null) {
            $view->with('gtm_code', Cache::remember('gtm_code_list', 3600, fn() => GoogleTagManager::where('status', 1)->get()));
        }

        // TikTok Pixels
        $view->with('tiktok_pixels', Cache::remember('tiktok_pixels_list', 3600, fn() => TiktokPixel::where('status', 1)->get()));
    }
}
