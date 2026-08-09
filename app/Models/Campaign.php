<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Campaign extends Model
{
    use HasFactory;
    protected $guarded = [];

    /**
     * Landing page sections that can be toggled from the admin.
     */
    public const SECTIONS = [
        'hero'     => 'Hero',
        'problem'  => 'Problem',
        'solution' => 'Solution',
        'features' => 'Features',
        'benefits' => 'Benefits',
        'media'    => 'Product Images / Videos',
        'offer'    => 'Offer',
        'review'   => 'Customer Review',
        'trust'    => 'Trust Badges',
        'faq'      => 'FAQ',
        'order'    => 'Checkout / Order Form',
        'cta'      => 'CTA',
    ];

    /**
     * Default per-section config (label, position, visibility).
     */
    public const SECTION_DEFAULTS = [
        'hero'     => ['label' => 'Hero',                   'position' => 1],
        'problem'  => ['label' => 'Problem',                'position' => 2],
        'solution' => ['label' => 'Solution',               'position' => 3],
        'features' => ['label' => 'Features',               'position' => 4],
        'benefits' => ['label' => 'Benefits',               'position' => 5],
        'media'    => ['label' => 'Product Images / Videos','position' => 6],
        'offer'    => ['label' => 'Offer',                  'position' => 7],
        'review'   => ['label' => 'Customer Review',        'position' => 8],
        'trust'    => ['label' => 'Trust Badges',           'position' => 9],
        'faq'      => ['label' => 'FAQ',                    'position' => 10],
        'order'    => ['label' => 'Checkout / Order Form',  'position' => 11],
        'cta'      => ['label' => 'CTA',                    'position' => 12],
    ];

    /**
     * Dynamic heading/label texts for the landing page.
     * Defaults are used ONLY to pre-fill the admin form on first load.
     * An empty stored value means the heading is hidden on the page.
     */
    public const LABELS = [
        'nav_features'     => 'Features',
        'nav_reviews'      => 'Reviews',
        'nav_faq'          => 'FAQ',
        'nav_order'        => 'Order',
        'nav_cta'          => 'Order Now',
        'hero_eyebrow'     => 'Limited time offer',
        'hero_cta_order'   => 'Order Now',
        'hero_cta_details' => 'How It Works',
        'hero_trust_ends'  => 'Offer ends soon',
        'hero_trust_cod'   => 'Cash on Delivery',
        'problem_eyebrow'  => 'The problem',
        'problem_heading'  => 'The real problem',
        'solution_eyebrow' => 'The solution',
        'features_eyebrow' => 'What\'s inside',
        'features_card'    => 'Feature',
        'benefits_eyebrow' => 'How life changes',
        'benefits_heading' => 'How it performs',
        'media_eyebrow'    => 'A closer look',
        'media_heading'    => 'A closer look',
        'offer_eyebrow'    => 'Limited time',
        'offer_heading'    => 'অর্ডার করতে চাইলে নিচের ফর্মটি পূরণ করুন',
        'review_eyebrow'   => 'What people say',
        'reviews_heading'  => 'What our customers say',
        'trust_eyebrow'    => 'Why shop with us',
        'faq_eyebrow'      => 'Questions',
        'faq_heading'      => 'Everything you need to know',
        'order_eyebrow'    => 'Checkout',
        'cta_eyebrow'      => 'Last call',
        'cta_heading'      => 'Ready when you are',
        'form_select'      => 'Select your product',
        'form_info'        => 'Your information',
        'form_submit'      => 'অর্ডার কনফার্ম করুন',
        'form_summary'     => 'Order summary',
        'form_delivery'    => 'Delivery Charge',
        'form_total'       => 'Total',
        'form_warranty'    => 'Warranty',
        'sticky_order'     => 'অর্ডার করুন',
        'sticky_cod'       => 'Cash on Delivery',
        'footer_rights'    => 'All rights reserved',
    ];

    protected function casts(): array
    {
        return [
            'sections' => 'array',
            'labels'   => 'array',
            'features' => 'array',
            'problem'  => 'array',
            'solution' => 'array',
            'benefits' => 'array',
            'trust'    => 'array',
            'faq'      => 'array',
            'reviews'  => 'array',
            'cta'      => 'array',
        ];
    }

    /**
     * Problem / pain points — array of { num, title, text }.
     */
    public function problem(): array
    {
        return $this->cleanRows($this->problem, ['num', 'title', 'text']);
    }

    /**
     * Solution — array of { icon, title, text } benefits (before/after handled separately in blade).
     */
    public function solution(): array
    {
        return $this->cleanRows($this->solution, ['icon', 'title', 'text']);
    }

    /**
     * Benefits — array of { icon, title, text }.
     */
    public function benefits(): array
    {
        return $this->cleanRows($this->benefits, ['icon', 'title', 'text']);
    }

    /**
     * Trust badges — array of { icon, text }.
     */
    public function trust(): array
    {
        return $this->cleanRows($this->trust, ['icon', 'text']);
    }

    /**
     * FAQ — array of { q, a }.
     */
    public function faq(): array
    {
        return $this->cleanRows($this->faq, ['q', 'a']);
    }

    /**
     * CTA — array of { icon, title, text }.
     */
    public function cta(): array
    {
        return $this->cleanRows($this->cta, ['icon', 'title', 'text']);
    }

    /**
     * Customer reviews — array of { name, text, rating }.
     */
    public function reviews(): array
    {
        return $this->cleanRows($this->reviews, ['name', 'text', 'rating']);
    }

    /**
     * Filter a stored array of rows, keeping only rows with at least one value,
     * and normalizing each row to the given keys.
     */
    protected function cleanRows($rows, array $keys): array
    {
        $rows = is_array($rows) ? $rows : [];
        $out = [];
        foreach ($rows as $row) {
            $row = is_array($row) ? $row : [];
            $normalized = [];
            $hasValue = false;
            foreach ($keys as $k) {
                $v = trim((string) ($row[$k] ?? ''));
                $normalized[$k] = $v;
                if ($v !== '') { $hasValue = true; }
            }
            if ($hasValue) {
                $out[] = $normalized;
            }
        }
        return $out;
    }

    /**
     * Read a dynamic heading/label. Returns '' when not set (=> hide on page).
     */
    public function label(string $key): string
    {
        $labels = is_array($this->labels) ? $this->labels : [];
        return trim((string) ($labels[$key] ?? ''));
    }

    /**
     * Feature grid items — each: { icon, image, title, text }.
     * Filters out completely empty rows.
     */
    public function features(): array
    {
        $list = is_array($this->features) ? $this->features : [];
        $out = [];
        foreach ($list as $f) {
            $f = is_array($f) ? $f : [];
            $icon  = trim((string) ($f['icon'] ?? ''));
            $image = trim((string) ($f['image'] ?? ''));
            $title = trim((string) ($f['title'] ?? ''));
            $text  = trim((string) ($f['text'] ?? ''));
            if ($icon === '' && $image === '' && $title === '' && $text === '') {
                continue;
            }
            $out[] = ['icon' => $icon, 'image' => $image, 'title' => $title, 'text' => $text];
        }
        return $out;
    }

    /**
     * Full per-section config (stored merged over defaults).
     * Each entry: label, position, visible, title, text, feature.
     */
    public function sectionConfig(): array
    {
        $stored = is_array($this->sections) ? $this->sections : [];
        $out = [];
        foreach (self::SECTIONS as $key => $label) {
            $d = self::SECTION_DEFAULTS[$key] ?? ['label' => $label, 'position' => count($out) + 1];
            $s = is_array($stored[$key] ?? null) ? $stored[$key] : [];
            $out[$key] = [
                'label'    => (string) ($s['label'] ?? $d['label']),
                'position' => (int) ($s['position'] ?? $d['position']),
                'visible'  => array_key_exists('visible', $s) ? (bool) $s['visible'] : true,
                'title'    => (string) ($s['title'] ?? ''),
                'text'     => (string) ($s['text'] ?? ''),
                'feature'  => (string) ($s['feature'] ?? ''),
            ];
        }
        return $out;
    }

    /**
     * Section keys ordered by their configured position.
     */
    public function orderedSectionKeys(): array
    {
        $cfg = $this->sectionConfig();
        uasort($cfg, fn($a, $b) => $a['position'] <=> $b['position']);
        return array_keys($cfg);
    }

    /**
     * Whether a landing-page section is visible (defaults to true).
     */
    public function sectionVisible(string $key): bool
    {
        $cfg = $this->sectionConfig();
        return array_key_exists($key, $cfg) ? (bool) $cfg[$key]['visible'] : true;
    }

    public function sectionLabel(string $key): string
    {
        $cfg = $this->sectionConfig();
        return $cfg[$key]['label'] ?? (self::SECTIONS[$key] ?? $key);
    }

    /**
     * Read an editable per-section field (title|text|feature).
     */
    public function sectionField(string $key, string $field): string
    {
        $cfg = $this->sectionConfig();
        return $cfg[$key][$field] ?? '';
    }

    public function product(){
        return $this->hasOne(Product::class, 'id','product_id')->select('id','name','slug','old_price','new_price');
    }
    public function products()
    {
        return $this->belongsToMany(Product::class, 'campaign_product', 'campaign_id', 'product_id')
            ->select('products.id','products.name','products.slug','products.old_price','products.new_price');
    }
    public function images(){
        return $this->hasMany(CampaignReview::class, 'campaign_id')->select('id','image','campaign_id');
    }
}
