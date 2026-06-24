<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\HomepageLayout;
use App\Models\HomepageLayoutSection;
use App\Models\HomepageSection;

class LayoutSeeder extends Seeder
{
    public function run(): void
    {
        // Get all section IDs keyed by slug for easy lookup
        $sections = HomepageSection::pluck('id', 'slug');

        $layouts = [
            [
                'name' => 'Default Full Layout',
                'description' => 'Complete homepage with all major sections in standard order',
                'is_default' => true,
                'is_active' => true,
                'sections' => [
                    ['slug' => 'main-slider',         'order' => 1,  'cols' => 'col-sm-12'],
                    ['slug' => 'top-categories',      'order' => 2,  'cols' => 'col-sm-12'],
                    ['slug' => 'flash-sales',         'order' => 3,  'cols' => 'col-sm-12'],
                    ['slug' => 'hot-deals',           'order' => 4,  'cols' => 'col-sm-12'],
                    ['slug' => 'all-products',        'order' => 5,  'cols' => 'col-sm-12'],
                    ['slug' => 'slider-bottom-ads',   'order' => 6,  'cols' => 'col-sm-12'],
                    ['slug' => 'category-products',   'order' => 7,  'cols' => 'col-sm-12'],
                    ['slug' => 'campaign-ads',        'order' => 8,  'cols' => 'col-sm-12'],
                    ['slug' => 'brands',              'order' => 9,  'cols' => 'col-sm-12'],
                    ['slug' => 'latest-blogs',        'order' => 10, 'cols' => 'col-sm-12'],
                    ['slug' => 'customer-reviews',    'order' => 11, 'cols' => 'col-sm-12'],
                    ['slug' => 'footer-top-ads',      'order' => 12, 'cols' => 'col-sm-12'],
                ],
            ],
            [
                'name' => 'Marketing Focus',
                'description' => 'Emphasizes promotions, campaigns, and flash sales',
                'is_default' => false,
                'is_active' => false,
                'sections' => [
                    ['slug' => 'main-slider',         'order' => 1,  'cols' => 'col-sm-12'],
                    ['slug' => 'flash-sales',         'order' => 2,  'cols' => 'col-sm-12'],
                    ['slug' => 'hot-deals',           'order' => 3,  'cols' => 'col-sm-12'],
                    ['slug' => 'campaign-ads',        'order' => 4,  'cols' => 'col-sm-12'],
                    ['slug' => 'slider-bottom-ads',   'order' => 5,  'cols' => 'col-sm-12'],
                    ['slug' => 'extra-discount',      'order' => 6,  'cols' => 'col-sm-12'],
                    ['slug' => 'limited-offers',      'order' => 7,  'cols' => 'col-sm-12'],
                    ['slug' => 'all-products',        'order' => 8,  'cols' => 'col-sm-12'],
                    ['slug' => 'brands',              'order' => 9,  'cols' => 'col-sm-12'],
                    ['slug' => 'customer-reviews',    'order' => 10, 'cols' => 'col-sm-12'],
                ],
            ],
            [
                'name' => 'Store Front',
                'description' => 'Product-focused layout with categories and grid display',
                'is_default' => false,
                'is_active' => false,
                'sections' => [
                    ['slug' => 'main-slider',         'order' => 1,  'cols' => 'col-sm-12'],
                    ['slug' => 'top-categories',      'order' => 2,  'cols' => 'col-sm-12'],
                    ['slug' => 'category-products',   'order' => 3,  'cols' => 'col-sm-12'],
                    ['slug' => 'new-arrivals',        'order' => 4,  'cols' => 'col-sm-12'],
                    ['slug' => 'product-grid',        'order' => 5,  'cols' => 'col-sm-12'],
                    ['slug' => 'all-products',        'order' => 6,  'cols' => 'col-sm-12'],
                    ['slug' => 'brands',              'order' => 7,  'cols' => 'col-sm-12'],
                    ['slug' => 'latest-blogs',        'order' => 8,  'cols' => 'col-sm-12'],
                ],
            ],
            [
                'name' => 'Minimal Layout',
                'description' => 'Clean, minimal homepage with only essential sections',
                'is_default' => false,
                'is_active' => false,
                'sections' => [
                    ['slug' => 'main-slider',         'order' => 1,  'cols' => 'col-sm-12'],
                    ['slug' => 'flash-sales',         'order' => 2,  'cols' => 'col-sm-12'],
                    ['slug' => 'all-products',        'order' => 3,  'cols' => 'col-sm-12'],
                    ['slug' => 'brands',              'order' => 4,  'cols' => 'col-sm-12'],
                    ['slug' => 'latest-blogs',        'order' => 5,  'cols' => 'col-sm-12'],
                    ['slug' => 'footer-top-ads',      'order' => 6,  'cols' => 'col-sm-12'],
                ],
            ],
            [
                'name' => 'Blog & Content',
                'description' => 'Content-driven layout with blogs, reviews, and brand stories',
                'is_default' => false,
                'is_active' => false,
                'sections' => [
                    ['slug' => 'main-slider',         'order' => 1,  'cols' => 'col-sm-12'],
                    ['slug' => 'brand-intro',         'order' => 2,  'cols' => 'col-sm-12'],
                    ['slug' => 'top-categories',      'order' => 3,  'cols' => 'col-sm-12'],
                    ['slug' => 'latest-blogs',        'order' => 4,  'cols' => 'col-sm-12'],
                    ['slug' => 'video-section',       'order' => 5,  'cols' => 'col-sm-12'],
                    ['slug' => 'all-products',        'order' => 6,  'cols' => 'col-sm-12'],
                    ['slug' => 'customer-reviews',    'order' => 7,  'cols' => 'col-sm-12'],
                    ['slug' => 'client-logos',        'order' => 8,  'cols' => 'col-sm-12'],
                    ['slug' => 'work-with-us',        'order' => 9,  'cols' => 'col-sm-12'],
                ],
            ],
        ];

        foreach ($layouts as $layoutData) {
            $sectionList = $layoutData['sections'];
            unset($layoutData['sections']);

            $layoutData['created_by'] = 1;

            // Deactivate previous defaults if this is default
            if (!empty($layoutData['is_default'])) {
                HomepageLayout::where('is_default', true)->update(['is_default' => false]);
                HomepageLayout::where('is_active', true)->update(['is_active' => false]);
            }

            $layout = HomepageLayout::create($layoutData);

            foreach ($sectionList as $item) {
                $sectionId = $sections[$item['slug']] ?? null;
                if ($sectionId) {
                    HomepageLayoutSection::create([
                        'layout_id' => $layout->id,
                        'section_id' => $sectionId,
                        'sort_order' => $item['order'],
                        'is_visible' => true,
                        'columns_config' => $item['cols'] ?? 'col-sm-12',
                    ]);
                }
            }

            $this->command->info("Layout '{$layoutData['name']}' created with " . count($sectionList) . ' sections.');
        }
    }
}
