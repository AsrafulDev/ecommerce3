<?php

namespace Tests\Feature;

use App\Models\Campaign;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Campaign reviews are saved as a list and read back as the same list.
 */
class CampaignReviewsTest extends TestCase
{
    use RefreshDatabase;

    public function test_campaign_reviews_round_trip(): void
    {
        $reviews = [
            ['name' => 'Rahim', 'text' => 'Works well', 'rating' => 5],
            ['name' => 'Karim', 'text' => 'Good value', 'rating' => 4],
        ];

        $campaign = Campaign::create([
            'name' => 'Review Campaign',
            'slug' => 'review-campaign',
            'status' => 1,
            'reviews' => $reviews,
            'short_description' => 'Short text',
            'review' => 'Review text',
            'description' => 'Description',
            'image_one' => 'public/uploads/campaign/one.webp',
        ]);

        $this->assertSame($reviews, $campaign->fresh()->reviews);
    }
}
