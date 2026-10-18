<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Guests who open an admin page are sent to the home page, never to the admin
 * login, so the login address is not revealed by a redirect.
 */
class AdminRouteGuestRedirectTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Installed site: a settings row exists, so RedirectIfInstalled does not intervene.
        \App\Models\GeneralSetting::forceCreate([
            'name' => 'Test Shop',
            'white_logo' => 'public/logo.png',
            'dark_logo' => 'public/logo.png',
            'favicon' => 'public/favicon.png',
            'status' => 1,
        ]);
    }

    public function test_guest_on_an_admin_page_is_sent_to_home(): void
    {
        $response = $this->get(route('admin.fund.index'));

        $response->assertRedirect(route('home'));
        $this->assertStringNotContainsString('login', (string) $response->headers->get('Location'));
    }

    public function test_guest_on_the_admin_root_is_sent_to_home(): void
    {
        $response = $this->get('/admin');

        $response->assertRedirect(route('home'));
    }

    public function test_guest_on_a_protected_admin_action_is_sent_to_home(): void
    {
        $response = $this->post(route('settings.update'), ['id' => 1, 'name' => 'x']);

        $response->assertRedirect(route('home'));
        $this->assertStringNotContainsString('login', (string) $response->headers->get('Location'));
    }

    public function test_guest_on_the_admin_dashboard_is_sent_to_home(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('home'));
    }
}
