<?php

namespace Tests\Unit\Landing;

use App\Services\Landing\ContentValidator;
use App\Services\Landing\InvalidLandingContent;
use App\Services\Landing\TemplateRegistry;
use PHPUnit\Framework\TestCase;

class ContentValidatorTest extends TestCase
{
    private function content(array $override = []): array
    {
        return array_replace_recursive(TemplateRegistry::defaultContent(TemplateRegistry::LUXURY), $override);
    }

    private function refusal(array $content): array
    {
        try {
            ContentValidator::clean($content, TemplateRegistry::LUXURY);
        } catch (InvalidLandingContent $e) {
            return $e->errors;
        }

        $this->fail('Expected the content to be refused.');
    }

    public function test_default_content_is_valid(): void
    {
        $clean = ContentValidator::clean(TemplateRegistry::defaultContent(TemplateRegistry::LUXURY), TemplateRegistry::LUXURY);

        $this->assertSame('Limited time offer', $clean['elements']['hero.eyebrow']['value']);
        $this->assertTrue($clean['sections']['checkout']['enabled']);
    }

    public function test_unknown_element_id_is_refused(): void
    {
        $errors = $this->refusal($this->content(['elements' => ['hero.secret' => ['value' => 'x']]]));

        $this->assertContains("Unknown element 'hero.secret'.", $errors);
    }

    public function test_list_rows_beyond_the_maximum_are_refused(): void
    {
        $errors = $this->refusal($this->content(['elements' => ['problem.item.7.title' => ['value' => 'x']]]));

        $this->assertContains("Unknown element 'problem.item.7.title'.", $errors);
    }

    public function test_numbered_list_rows_within_the_maximum_are_accepted(): void
    {
        $clean = ContentValidator::clean(
            $this->content(['elements' => ['problem.item.3.title' => ['value' => 'Third']]]),
            TemplateRegistry::LUXURY
        );

        $this->assertSame('Third', $clean['elements']['problem.item.3.title']['value']);
    }

    public function test_checkout_cannot_be_switched_off(): void
    {
        $errors = $this->refusal($this->content(['sections' => ['checkout' => ['enabled' => false]]]));

        $this->assertContains('The checkout section cannot be switched off.', $errors);
    }

    public function test_at_least_one_section_besides_checkout_must_stay_on(): void
    {
        $sections = [];
        foreach (array_keys(TemplateRegistry::schema(TemplateRegistry::LUXURY)['sections']) as $key) {
            $sections[$key] = ['enabled' => false];
        }
        $sections['checkout'] = ['enabled' => true];

        $errors = $this->refusal($this->content(['sections' => $sections]));

        $this->assertContains('At least one section besides checkout must stay on.', $errors);
    }

    public function test_one_content_section_with_checkout_is_enough(): void
    {
        $sections = [];
        foreach (array_keys(TemplateRegistry::schema(TemplateRegistry::LUXURY)['sections']) as $key) {
            $sections[$key] = ['enabled' => false];
        }
        $sections['checkout'] = ['enabled' => true];
        $sections['faq'] = ['enabled' => true];

        $clean = ContentValidator::clean($this->content(['sections' => $sections]), TemplateRegistry::LUXURY);

        $this->assertTrue($clean['sections']['faq']['enabled']);
        $this->assertFalse($clean['sections']['hero']['enabled']);
    }

    public function test_a_section_switch_with_no_value_is_refused(): void
    {
        // Sent as-is, without the defaults merged in, so the empty switch is what is checked.
        $errors = $this->refusal(['sections' => ['hero' => []]]);

        $this->assertContains("Section 'hero' must be switched on or off.", $errors);
    }

    public function test_text_is_limited_to_its_maximum_length(): void
    {
        $errors = $this->refusal($this->content(['elements' => ['hero.eyebrow' => ['value' => str_repeat('a', 121)]]]));

        $this->assertContains("'hero.eyebrow' is longer than 120 characters.", $errors);
    }

    public function test_plain_text_loses_all_tags(): void
    {
        $clean = ContentValidator::clean(
            $this->content(['elements' => ['hero.title' => ['value' => '<script>x</script><b>Fast</b>']]]),
            TemplateRegistry::LUXURY
        );

        $this->assertSame('xFast', $clean['elements']['hero.title']['value']);
    }

    public function test_rich_text_keeps_basic_tags_and_drops_attributes(): void
    {
        $clean = ContentValidator::clean(
            $this->content(['elements' => ['hero.subtitle' => ['value' => '<b onclick="alert(1)">Bold</b><img src=x onerror=y><p>Para</p>']]]),
            TemplateRegistry::LUXURY
        );

        $this->assertSame('<b>Bold</b><p>Para</p>', $clean['elements']['hero.subtitle']['value']);
    }

    public function test_image_must_be_a_media_library_path(): void
    {
        $errors = $this->refusal($this->content(['elements' => ['hero.image' => ['value' => 'https://evil.example/x.png']]]));

        $this->assertContains("'hero.image' must be an image from the media library.", $errors);
    }

    public function test_image_path_cannot_climb_out_of_uploads(): void
    {
        $errors = $this->refusal($this->content(['elements' => ['hero.image' => ['value' => 'uploads/../.env']]]));

        $this->assertContains("'hero.image' must be an image from the media library.", $errors);
    }

    public function test_colours_must_be_six_digit_hex(): void
    {
        $errors = $this->refusal($this->content(['theme' => ['primary' => 'red']]));

        $this->assertContains("Colour 'primary' must be a hex value such as #4f46e5.", $errors);
    }

    public function test_an_unknown_colour_key_is_refused(): void
    {
        $errors = $this->refusal($this->content(['theme' => ['font' => '#000000']]));

        $this->assertContains("Unknown colour 'font'.", $errors);
    }

    public function test_unknown_template_fails_loudly(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ContentValidator::clean([], 'nope');
    }
}
