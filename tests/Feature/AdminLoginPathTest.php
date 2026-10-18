<?php

namespace Tests\Feature;

use App\Models\GeneralSetting;
use App\Models\User;
use App\Support\AdminLoginPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Configurable admin login address. Routes load once per process, so the
 * route-level switch is verified manually against a running server; these tests
 * cover the rules, the save path and the recovery command.
 */
class AdminLoginPathTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(): User
    {
        // Never user id 1: the Gate override treats id 1 as super admin.
        $user = User::forceCreate([
            'id' => max(2, (int) User::max('id') + 1),
            'name' => 'Owner',
            'email' => uniqid('owner').'@test.local',
            'password' => Hash::make('secret-pass'),
            'status' => 1,
        ]);
        $user->assignRole(Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'admin']));

        return $user;
    }

    private function settings(): GeneralSetting
    {
        // Columns NOT NULL without a default in strict MySQL.
        return GeneralSetting::forceCreate([
            'name' => 'Test Shop',
            'white_logo' => 'public/logo.png',
            'dark_logo' => 'public/logo.png',
            'favicon' => 'public/favicon.png',
            'status' => 1,
        ]);
    }

    private function save(array $overrides = []): \Illuminate\Testing\TestResponse
    {
        $settings = $this->settings();

        return $this->actingAs($this->adminUser(), 'admin')->post(route('settings.update'), array_merge([
            'id' => $settings->id,
            'name' => 'Test Shop',
            'admin_login_path' => '',
            'admin_login_current_password' => '',
        ], $overrides));
    }

    public function test_default_address_is_the_stock_login_url(): void
    {
        // An installed site has a settings row; without one the app redirects to /install.
        $this->settings();
        $this->assertSame('login', AdminLoginPath::current());
        $this->assertStringEndsWith('/login', route('login'));
        // Route resolution only: the login view needs a full settings row to render.
        $this->assertSame('login', app('router')->getRoutes()->match(\Illuminate\Http\Request::create('/login', 'GET'))->getName());
    }

    public function test_format_rules(): void
    {
        $this->assertNull(AdminLoginPath::validationError('secure-panel-7'));
        $this->assertNotNull(AdminLoginPath::validationError('abc'), 'too short');
        $this->assertNotNull(AdminLoginPath::validationError('-secure-panel'), 'leading dash');
        $this->assertNotNull(AdminLoginPath::validationError('secure-panel-'), 'trailing dash');
        $this->assertNotNull(AdminLoginPath::validationError('under_score'), 'underscore');
        $this->assertNotNull(AdminLoginPath::validationError('a/b'), 'slash');
    }

    public function test_reserved_and_existing_addresses_are_refused(): void
    {
        $this->assertNotNull(AdminLoginPath::validationError('admin'), 'reserved');
        $this->assertNotNull(AdminLoginPath::validationError('customer'), 'reserved');
        $this->assertNotNull(AdminLoginPath::validationError('blog'), 'an existing storefront route');
    }

    public function test_saving_a_new_address_needs_the_current_password(): void
    {
        $response = $this->save(['admin_login_path' => 'secure-panel-7', 'admin_login_current_password' => 'wrong']);

        $response->assertRedirect();
        $this->assertNull($this->currentStoredPath(), 'a wrong password must not change the address');
        $this->assertSame('login', AdminLoginPath::current());
    }

    public function test_saving_a_new_address_with_the_right_password_stores_it(): void
    {
        $this->save(['admin_login_path' => 'Secure-Panel-7', 'admin_login_current_password' => 'secret-pass'])
            ->assertRedirect(route('settings.edit'));

        $this->assertSame('secure-panel-7', $this->currentStoredPath(), 'input is normalised to lowercase');
        $this->assertSame('secure-panel-7', AdminLoginPath::current());
    }

    public function test_reserved_address_is_refused_on_save(): void
    {
        $this->save(['admin_login_path' => 'admin', 'admin_login_current_password' => 'secret-pass'])
            ->assertRedirect();

        $this->assertNull($this->currentStoredPath());
    }

    public function test_blank_address_keeps_the_current_one_without_a_password(): void
    {
        $this->save(['admin_login_path' => '', 'admin_login_current_password' => ''])
            ->assertRedirect(route('settings.edit'));

        $this->assertNull($this->currentStoredPath());
    }

    public function test_recovery_command_restores_the_default(): void
    {
        $settings = $this->settings();
        $settings->forceFill(['admin_login_path' => 'secure-panel-7'])->save();
        AdminLoginPath::forget();

        $this->artisan('admin:login-path', ['--reset' => true])->assertSuccessful();

        $this->assertNull($this->currentStoredPath());
        $this->assertSame('login', AdminLoginPath::current());
    }

    private function currentStoredPath(): ?string
    {
        return GeneralSetting::query()->orderByDesc('id')->value('admin_login_path');
    }
}
