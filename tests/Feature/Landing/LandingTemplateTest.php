<?php

namespace Tests\Feature\Landing;

use App\Models\LandingTemplate;
use App\Services\Landing\ContentValidator;
use App\Services\Landing\TemplateRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LandingTemplateTest extends TestCase
{
    use RefreshDatabase;

    public function test_table_has_the_planned_columns(): void
    {
        foreach (['id', 'name', 'slug', 'template_key', 'created_by', 'product_id', 'content', 'status',
                  'valid_from', 'valid_until', 'coupon_id', 'extra_discount_type', 'extra_discount_value'] as $column) {
            $this->assertTrue(Schema::hasColumn('landing_templates', $column), "missing {$column}");
        }
    }

    public function test_content_round_trips_as_json_keyed_by_element_id(): void
    {
        $content = ContentValidator::clean(TemplateRegistry::defaultContent(TemplateRegistry::LUXURY), TemplateRegistry::LUXURY);

        $landing = LandingTemplate::create([
            'name' => 'Round Trip',
            'slug' => LandingTemplate::uniqueSlug('Round Trip'),
            'template_key' => TemplateRegistry::LUXURY,
            'content' => $content,
        ]);

        $saved = $landing->fresh()->content;
        $this->assertSame($content, $saved);
        $this->assertSame('Limited time offer', $saved['elements']['hero.eyebrow']['value']);
    }

    public function test_slugs_are_made_unique_from_the_title(): void
    {
        LandingTemplate::create(['name' => 'Summer Sale', 'slug' => 'summer-sale', 'template_key' => TemplateRegistry::LUXURY]);
        LandingTemplate::create(['name' => 'Summer Sale', 'slug' => 'summer-sale-2', 'template_key' => TemplateRegistry::LUXURY]);

        $this->assertSame('summer-sale-3', LandingTemplate::uniqueSlug('Summer Sale'));
        $this->assertSame('bangla-title', LandingTemplate::uniqueSlug('Bangla Title'));
    }

    public function test_a_blank_title_falls_back_to_a_plain_slug(): void
    {
        $this->assertSame('landing', LandingTemplate::uniqueSlug('!!!'));
    }

    public function test_new_landing_is_a_draft_by_default(): void
    {
        $landing = LandingTemplate::create(['name' => 'Draft', 'slug' => 'draft', 'template_key' => TemplateRegistry::LUXURY]);

        $this->assertFalse($landing->fresh()->status);
    }
}
