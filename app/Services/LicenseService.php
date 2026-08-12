<?php

namespace App\Services;

use App\Models\GeneralSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LicenseService
{
    /**
     * How long to cache a verification result (seconds).
     */
    public const CACHE_TTL = 600; // 10 minutes

    /**
     * Master domain that never requires validation.
     */
    public const MASTER_DOMAIN = 'softmit.xyz';

    /**
     * Local environments that never require validation.
     */
    public const LOCAL_ENVIRONMENTS = ['127.0.0.1', 'localhost'];

    /**
     * Resolve license config.
     *
     * The mother server URL, script name and version are HARDCODED in
     * config/updater.php (baked in, cannot be changed/removed at runtime).
     * The license key is admin-managed: stored ONLY in general_settings.license_key
     * (editable from the admin License page) — no hardcoded key in code.
     *
     * @return array{api_url:string, script_name:string, current_version:string, license_key:string}
     */
    public static function config(): array
    {
        return [
            'api_url'         => (string) config('updater.api_url', ''),
            'script_name'     => (string) config('updater.script_name', ''),
            'current_version' => (string) config('updater.current_version', ''),
            'license_key'     => self::licenseKey(),
        ];
    }

    /**
     * 🔒 Integrity check — throws if the hardcoded license server config was
     * removed or edited. DB-free on purpose (called during boot).
     *
     * @throws \RuntimeException
     */
    public static function assertConfigIntegrity(): void
    {
        $api = (string) config('updater.api_url', '');

        if ('' === $api || false === stripos($api, 'softmit.xyz')) {
            throw new \RuntimeException('License server configuration is invalid. Application integrity check failed. Reinstall required.');
        }
    }

    /**
     * Effective license key: admin-managed, stored in the database
     * (general_settings.license_key). Empty string = not configured yet.
     */
    public static function licenseKey(): string
    {
        self::assertConfigIntegrity();

        return self::licenseKeyFromDb();
    }

    /**
     * Read the admin-saved license key from general_settings (defensive:
     * returns '' if the column is missing or the query fails).
     */
    private static function licenseKeyFromDb(): string
    {
        try {
            $setting = GeneralSetting::where('status', 1)->first();
            return ($setting && isset($setting->license_key) && trim((string) $setting->license_key) !== '')
                ? trim((string) $setting->license_key)
                : '';
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Current request host without the www. prefix.
     */
    public static function domain(): string
    {
        return str_replace('www.', '', (string) request()->getHost());
    }

    /**
     * Whether we are running on the mother/master domain.
     */
    public static function isMaster(): bool
    {
        return self::domain() === self::MASTER_DOMAIN;
    }

    /**
     * Whether we are running on a local environment.
     */
    public static function isLocal(): bool
    {
        return in_array(self::domain(), self::LOCAL_ENVIRONMENTS, true);
    }

    /**
     * Verify the license against the mother server (cached).
     *
     * @param bool $fresh Force a fresh check (ignore cache).
     * @return array{valid:bool, data:?array, message:string}
     */
    public static function verify(bool $fresh = false): array
    {
        // 🔒 Hard integrity gate — broken config = broken app (exception propagates).
        self::assertConfigIntegrity();

        $domain = self::domain();
        $key    = self::licenseKey();

        // Mother domain never needs validation.
        if (self::isMaster()) {
            return ['valid' => true, 'data' => null, 'message' => 'Master domain — no verification needed.'];
        }

        // No license key configured yet — admin must set it from the License page.
        if ('' === $key) {
            return ['valid' => false, 'data' => null, 'message' => 'License key is not configured. Set it from the admin License page.'];
        }

        $cacheKey = 'license_verify_' . md5($domain . '|' . $key);
        if ($fresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($domain, $key) {
            try {
                $apiUrl   = rtrim(self::config()['api_url'], '/') . '/api/verify-license';
                $response = Http::withoutVerifying()
                    ->asJson()
                    ->acceptJson()
                    ->timeout(10)
                    ->post($apiUrl, [
                        'domain'      => $domain,
                        'license_key' => $key,
                    ]);

                $data = $response->successful() ? $response->json() : null;

                if ($response->successful() && isset($data['status']) && 'valid' === $data['status']) {
                    $respDomain = $data['domain_name'] ?? $data['domain'] ?? $data['registered_domain'] ?? null;
                    $respDomain = $respDomain ? str_replace('www.', '', strtolower($respDomain)) : null;

                    if (empty($respDomain)) {
                        return ['valid' => false, 'data' => $data, 'message' => 'Domain information not found in license response.'];
                    }

                    if ($respDomain !== strtolower($domain)) {
                        return [
                            'valid'   => false,
                            'data'    => $data,
                            'message' => "Domain mismatch. License is registered for \"{$respDomain}\" but current domain is \"{$domain}\".",
                        ];
                    }

                    return [
                        'valid'   => true,
                        'data'    => $data,
                        'message' => $data['message'] ?? 'License verified successfully.',
                    ];
                }

                return [
                    'valid'   => false,
                    'data'    => $data,
                    'message' => isset($data['message']) ? $data['message'] : 'License verification failed.',
                ];
            } catch (\Exception $e) {
                Log::error('LicenseService verify error: ' . $e->getMessage());
                return [
                    'valid'   => false,
                    'data'    => null,
                    'message' => 'Unable to connect to the license server. ' . $e->getMessage(),
                ];
            }
        });
    }

    /**
     * Mask a license key for display (XXXX••••••••••••••••WXYZ).
     */
    public static function maskKey(string $key): string
    {
        $len = strlen($key);
        if ($len <= 8) {
            return str_repeat('•', $len);
        }
        return substr($key, 0, 4) . str_repeat('•', $len - 8) . substr($key, -4);
    }
}
