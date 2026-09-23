<?php

namespace Tests\Feature;

use App\Support\AppKey;
use Tests\TestCase;

/**
 * APP_KEY self-healing (App\Support\AppKey).
 *
 * Every test writes to a throwaway .env in storage/tmp — the real .env is never
 * touched. The shell also exports APP_KEY, so tests that need a key-less process
 * clear it via withoutEnvKey().
 */
class AppKeyTest extends TestCase
{
    private string $envPath;

    protected function setUp(): void
    {
        parent::setUp();

        $dir = storage_path('tmp');
        if (! is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $this->envPath = $dir.'/test-app-key.env';
        @unlink($this->envPath);
    }

    protected function tearDown(): void
    {
        @unlink($this->envPath);
        parent::tearDown();
    }

    private function writeEnv(string $contents): void
    {
        file_put_contents($this->envPath, $contents);
    }

    private function envContents(): string
    {
        return (string) file_get_contents($this->envPath);
    }

    /**
     * Run the callback with APP_KEY removed from the process environment
     * (the developer shell exports one), restoring it afterwards.
     */
    private function withoutEnvKey(callable $callback)
    {
        $saved = getenv('APP_KEY') ?: null;

        unset($_SERVER['APP_KEY'], $_ENV['APP_KEY']);
        putenv('APP_KEY');

        try {
            return $callback();
        } finally {
            putenv('APP_KEY');
            unset($_SERVER['APP_KEY'], $_ENV['APP_KEY']);

            if ($saved !== null) {
                putenv('APP_KEY='.$saved);
                $_SERVER['APP_KEY'] = $saved;
                $_ENV['APP_KEY']    = $saved;
            }
        }
    }

    public function test_generates_a_key_when_the_env_value_is_blank(): void
    {
        $this->writeEnv("APP_NAME=Organic\nAPP_KEY=\nAPP_DEBUG=true\n");

        $generated = $this->withoutEnvKey(fn () => AppKey::ensure($this->envPath));

        $this->assertTrue($generated, 'a blank APP_KEY must be filled in');
        $this->assertMatchesRegularExpression(
            '/^APP_KEY=base64:[A-Za-z0-9+\/=]{44}$/m',
            $this->envContents(),
            'a valid Laravel key must be written'
        );
    }

    public function test_appends_a_key_when_the_line_is_missing_entirely(): void
    {
        $this->writeEnv("APP_NAME=Organic\nAPP_DEBUG=true\n");

        $generated = $this->withoutEnvKey(fn () => AppKey::ensure($this->envPath));

        $this->assertTrue($generated);
        $this->assertMatchesRegularExpression('/^APP_KEY=base64:/m', $this->envContents());
    }

    public function test_generated_key_is_a_valid_laravel_key(): void
    {
        $key = AppKey::generate();

        $this->assertStringStartsWith('base64:', $key);
        $this->assertSame(32, strlen(base64_decode(substr($key, 7))), 'must be 32 random bytes');
    }

    public function test_never_rotates_an_existing_key(): void
    {
        $existing = 'base64:'.base64_encode(random_bytes(32));
        $this->writeEnv("APP_KEY={$existing}\n");

        $generated = $this->withoutEnvKey(fn () => AppKey::ensure($this->envPath));

        $this->assertFalse($generated, 'an existing key must never be replaced');
        $this->assertStringContainsString("APP_KEY={$existing}", $this->envContents());
    }

    public function test_a_real_env_var_wins_over_a_blank_file_entry(): void
    {
        $this->writeEnv("APP_KEY=\n");

        $saved = $_SERVER['APP_KEY'] ?? null;
        $_SERVER['APP_KEY'] = 'base64:from-process-env';

        try {
            $generated = AppKey::ensure($this->envPath);
        } finally {
            if ($saved === null) {
                unset($_SERVER['APP_KEY']);
            } else {
                $_SERVER['APP_KEY'] = $saved;
            }
        }

        $this->assertFalse($generated, 'a key from the environment must be respected');
        $this->assertStringNotContainsString('base64:', $this->envContents(), 'the file must be left alone');
    }

    public function test_quoted_key_counts_as_present(): void
    {
        $this->writeEnv("APP_KEY=\"base64:quoted-value\"\n");

        $generated = $this->withoutEnvKey(fn () => AppKey::ensure($this->envPath));

        $this->assertFalse($generated);
    }

    public function test_leaves_the_rest_of_the_env_file_intact(): void
    {
        $this->writeEnv("APP_NAME=Organic\nDB_DATABASE=ecommerce3\nAPP_KEY=\nMAIL_MAILER=smtp\n");

        $this->withoutEnvKey(fn () => AppKey::ensure($this->envPath));

        $contents = $this->envContents();
        $this->assertStringContainsString('APP_NAME=Organic', $contents);
        $this->assertStringContainsString('DB_DATABASE=ecommerce3', $contents);
        $this->assertStringContainsString('MAIL_MAILER=smtp', $contents);
        $this->assertSame(1, substr_count($contents, 'APP_KEY='), 'exactly one APP_KEY line');
    }

    public function test_a_missing_env_file_is_a_safe_no_op(): void
    {
        $this->withoutEnvKey(function () {
            $generated = AppKey::ensure($this->envPath); // never created
            $this->assertFalse($generated);
            $this->assertFileDoesNotExist($this->envPath);
        });
    }
}
