<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\LandingTemplate;
use App\Services\Landing\LandingPage;

/**
 * The public landing page. Only active landing pages are shown. Outside the validity window
 * the page is still shown, but its checkout is replaced by a closed notice (plan D6).
 */
class LandingController extends Controller
{
    public function show(string $slug)
    {
        $landing = LandingTemplate::where('slug', $slug)->where('status', true)->firstOrFail();

        return view('frontEnd.layouts.landing.' . $landing->template_key . '.page', LandingPage::viewData($landing));
    }
}
