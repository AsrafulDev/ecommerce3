<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;

class SitemapController extends Controller
{
    public function index()
    {
        return view('backEnd.sitemap.index');
    }

    public function generate()
    {
        $baseUrl = config('app.url');
        $sitemap = Sitemap::create();

        // ─── Static Pages ──────────────────────────────
        $sitemap->add(Url::create($baseUrl . '/')
            ->setChangeFrequency(Url::CHANGE_FREQUENCY_DAILY)
            ->setPriority(1.0));

        $sitemap->add(Url::create($baseUrl . '/shop')
            ->setChangeFrequency(Url::CHANGE_FREQUENCY_DAILY)
            ->setPriority(0.9));

        $sitemap->add(Url::create($baseUrl . '/hot-deals')
            ->setChangeFrequency(Url::CHANGE_FREQUENCY_DAILY)
            ->setPriority(0.7));

        $sitemap->add(Url::create($baseUrl . '/flash-sales')
            ->setChangeFrequency(Url::CHANGE_FREQUENCY_DAILY)
            ->setPriority(0.7));

        $sitemap->add(Url::create($baseUrl . '/offer')
            ->setChangeFrequency(Url::CHANGE_FREQUENCY_DAILY)
            ->setPriority(0.7));

        $sitemap->add(Url::create($baseUrl . '/wholesale-products')
            ->setChangeFrequency(Url::CHANGE_FREQUENCY_DAILY)
            ->setPriority(0.6));

        $sitemap->add(Url::create($baseUrl . '/site/contact-us')
            ->setChangeFrequency(Url::CHANGE_FREQUENCY_YEARLY)
            ->setPriority(0.3));

        // ─── Custom Pages (CreatePage) ─────────────────
        $pages = \App\Models\CreatePage::where('status', 1)->get();
        foreach ($pages as $page) {
            $sitemap->add(Url::create($baseUrl . '/page/' . $page->slug)
                ->setChangeFrequency(Url::CHANGE_FREQUENCY_MONTHLY)
                ->setPriority(0.5)
                ->setLastModificationDate($page->updated_at));
        }

        // ─── Categories ────────────────────────────────
        $categories = \App\Models\Category::where('status', 1)->get();
        foreach ($categories as $category) {
            $sitemap->add(Url::create($baseUrl . '/category/' . $category->slug)
                ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
                ->setPriority(0.8)
                ->setLastModificationDate($category->updated_at));
        }

        // ─── Subcategories ─────────────────────────────
        $subcategories = \App\Models\Subcategory::where('status', 1)->get();
        foreach ($subcategories as $subcategory) {
            $sitemap->add(Url::create($baseUrl . '/subcategory/' . $subcategory->slug)
                ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
                ->setPriority(0.7)
                ->setLastModificationDate($subcategory->updated_at));
        }

        // ─── Brands ────────────────────────────────────
        $brands = \App\Models\Brand::where('status', 1)->get();
        foreach ($brands as $brand) {
            $sitemap->add(Url::create($baseUrl . '/brand/' . $brand->slug)
                ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
                ->setPriority(0.7)
                ->setLastModificationDate($brand->updated_at));
        }

        // ─── Products ──────────────────────────────────
        $products = \App\Models\Product::where('status', 1)->get();
        foreach ($products as $product) {
            $sitemap->add(Url::create($baseUrl . '/products/' . $product->slug)
                ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
                ->setPriority(0.9)
                ->setLastModificationDate($product->updated_at));
        }

        // ─── Campaigns ─────────────────────────────────
        $campaigns = \App\Models\Campaign::where('status', 1)->get();
        foreach ($campaigns as $campaign) {
            $sitemap->add(Url::create($baseUrl . '/campaign/' . $campaign->slug)
                ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
                ->setPriority(0.6)
                ->setLastModificationDate($campaign->updated_at));
        }

        // ─── Blog Posts ────────────────────────────────
        $blogs = \App\Models\Blog::where('status', 1)->get();
        foreach ($blogs as $blog) {
            $sitemap->add(Url::create($baseUrl . '/blog/' . $blog->slug)
                ->setChangeFrequency(Url::CHANGE_FREQUENCY_MONTHLY)
                ->setPriority(0.5)
                ->setLastModificationDate($blog->updated_at));
        }

        // ─── Write to file ─────────────────────────────
        $path = public_path('sitemap.xml');
        $sitemap->writeToFile($path);

        return redirect()->back()->with('success', '✅ Sitemap generated successfully at public/sitemap.xml!');
    }
}
