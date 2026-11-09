<?php

namespace Tests\Feature\Updates;

use App\Http\Controllers\Api\UpdateServerController;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * The license server finds an update by version and product. When two products share a version number, the lookup
 * must name this product, so the size lookup sends the product's name.
 */
class UpdateServerProductTest extends TestCase
{
    public function test_the_size_lookup_names_this_product_on_the_license_server(): void
    {
        Http::fake(['softmit.xyz/api/updates/size/*' => Http::response(['file_size' => 4096])]);

        $method = new \ReflectionMethod(UpdateServerController::class, 'getUpdateFileSize');
        $method->setAccessible(true);

        $this->assertSame(4096, $method->invoke(app(UpdateServerController::class), '9.9.9'));

        Http::assertSent(function ($request) {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

            return str_starts_with($request->url(), 'https://softmit.xyz/api/updates/size/9.9.9')
                && ($query['product'] ?? null) === config('updater.script_name');
        });
    }
}
