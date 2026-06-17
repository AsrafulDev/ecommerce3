<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class UpdateServerController extends Controller
{
    /**
     * Check for available updates
     * 
     * POST /api/updates/check
     */
    public function check(Request $request)
    {
        try {
            $domain = $request->input('domain');
            $licenseKey = $request->input('license_key');
            $currentVersion = $request->input('current_version', '1.0.0');

            // Verify license
            $licenseValid = $this->verifyLicense($domain, $licenseKey);
            
            if (!$licenseValid) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid license key or domain'
                ], 403);
            }

            // Get latest version (you can store this in database or config)
            $latestVersion = $this->getLatestVersion();
            
            // Compare versions
            $updatesAvailable = version_compare($latestVersion, $currentVersion, '>');

            if ($updatesAvailable) {
                // Get update info from database if available
                $versionInfo = null;
                try {
                    $versionModel = \App\Models\Version::where('version', $latestVersion)
                        ->where('is_active', true)
                        ->first();
                    
                    if ($versionModel) {
                        $versionInfo = [
                            'version' => $versionModel->version,
                            'release_date' => $versionModel->release_date->format('Y-m-d'),
                            'changelog' => $versionModel->changelog ?? 'Bug fixes and improvements',
                            'file_size' => $versionModel->file_size ?? $this->getUpdateFileSize($latestVersion),
                            'requires_migration' => $versionModel->requires_migration ?? false
                        ];
                    }
                } catch (\Exception $e) {
                    // Fallback if database query fails
                }
                
                // Fallback if no database record
                if (!$versionInfo) {
                    $versionInfo = [
                        'version' => $latestVersion,
                        'release_date' => date('Y-m-d'),
                        'changelog' => 'Bug fixes and improvements',
                        'file_size' => $this->getUpdateFileSize($latestVersion),
                        'requires_migration' => false
                    ];
                }
                
                return response()->json([
                    'status' => 'success',
                    'message' => 'Update available',
                    'updates_available' => true,
                    'latest_version' => $latestVersion,
                    'current_version' => $currentVersion,
                    'update_info' => $versionInfo
                ]);
            } else {
                return response()->json([
                    'status' => 'success',
                    'message' => 'System is up to date',
                    'updates_available' => false,
                    'latest_version' => $latestVersion,
                    'current_version' => $currentVersion
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Update check API error: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get update information
     * 
     * POST /api/updates/info
     */
    public function info(Request $request)
    {
        try {
            $domain = $request->input('domain');
            $licenseKey = $request->input('license_key');
            $version = $request->input('version');

            if (empty($version)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Version parameter is required'
                ], 400);
            }

            // Verify license
            $licenseValid = $this->verifyLicense($domain, $licenseKey);
            
            if (!$licenseValid) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid license key or domain'
                ], 403);
            }

            // Get update info for specific version
            return response()->json([
                'status' => 'success',
                'version' => $version,
                'release_date' => date('Y-m-d'),
                'changelog' => 'Bug fixes and improvements',
                'file_size' => $this->getUpdateFileSize($version),
                'requires_migration' => false,
                'requirements' => [
                    'php_version' => '>=8.1',
                    'laravel_version' => '>=12.0'
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Update info API error: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download update file directly
     * 
     * GET /api/updates/file/{version}
     */
    public function downloadFile($version)
    {
        try {
            $filePath = storage_path('app/updates/update-' . $version . '.zip');
            
            if (!file_exists($filePath)) {
                abort(404, 'Update file not found');
            }

            return response()->download($filePath, 'update-' . $version . '.zip', [
                'Content-Type' => 'application/zip',
            ]);
        } catch (\Exception $e) {
            Log::error('Update file download error: ' . $e->getMessage());
            abort(500, 'Failed to download update file');
        }
    }

    /**
     * Get download URL for update
     * 
     * POST /api/updates/download
     */
    public function download(Request $request)
    {
        try {
            $domain = $request->input('domain');
            $licenseKey = $request->input('license_key');
            $version = $request->input('version');

            if (empty($version)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Version parameter is required'
                ], 400);
            }

            // Verify license
            $licenseValid = $this->verifyLicense($domain, $licenseKey);
            
            if (!$licenseValid) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid license key or domain'
                ], 403);
            }

            // Check if update file exists
            $updateFilePath = storage_path('app/updates/update-' . $version . '.zip');
            
            // If file doesn't exist locally, provide a URL to download from
            // You can store update files on a CDN or file server
            $downloadUrl = $this->getDownloadUrl($version);
            
            if (empty($downloadUrl)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Update file not found for version ' . $version
                ], 404);
            }

            // Calculate checksum from remote server or local file
            $checksum = null;
            
            // Try to get checksum from remote server first
            try {
                $remoteChecksumUrl = 'https://www.creativedesign.com.bd/api/updates/checksum/' . $version;
                $checksumResponse = Http::withoutVerifying()
                    ->timeout(10)
                    ->get($remoteChecksumUrl);
                
                if ($checksumResponse->successful()) {
                    $checksumData = $checksumResponse->json();
                    $checksum = $checksumData['checksum'] ?? null;
                }
            } catch (\Exception $e) {
                // Fallback to local file checksum if remote fails
                Log::info('Remote checksum fetch failed, using local: ' . $e->getMessage());
            }
            
            // Fallback: Calculate checksum from local file if exists
            if (empty($checksum) && file_exists($updateFilePath)) {
                $checksum = md5_file($updateFilePath);
            }

            return response()->json([
                'status' => 'success',
                'download_url' => $downloadUrl,
                'checksum' => $checksum,
                'file_size' => $this->getUpdateFileSize($version),
                'expires_at' => now()->addHours(24)->toIso8601String()
            ]);

        } catch (\Exception $e) {
            Log::error('Update download API error: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verify license with main license server
     */
    private function verifyLicense(string $domain, string $licenseKey): bool
    {
        try {
            // Use the same license verification as UpdateController
            $response = Http::withoutVerifying()
                ->asJson()
                ->acceptJson()
                ->timeout(10)
                ->post('https://www.creativedesign.com.bd/api/verify-license', [
                    'domain' => $domain,
                    'license_key' => $licenseKey,
                ]);

            return $response->successful() && $response->json('status') === 'valid';
        } catch (\Exception $e) {
            Log::error('License verification error in UpdateServerController: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get latest available version
     * Priority: Database > Directory Listing > Config
     */
    private function getLatestVersion(): string
    {
        // Option 1: From database (Recommended)
        try {
            $latestVersion = \App\Models\Version::where('is_active', true)
                ->orderBy('release_date', 'desc')
                ->orderBy('version', 'desc')
                ->first();
            
            if ($latestVersion) {
                return $latestVersion->version;
            }
        } catch (\Exception $e) {
            // Database table might not exist yet, fallback to other methods
        }
        
        // Option 2: From directory listing (Fallback)
        $updatesDir = storage_path('app/updates');
        if (is_dir($updatesDir)) {
            $files = glob($updatesDir . '/update-*.zip');
            if (!empty($files)) {
                // Extract version from filename: update-1.1.0.zip
                $versions = [];
                foreach ($files as $file) {
                    preg_match('/update-([\d.]+)\.zip/', $file, $matches);
                    if (!empty($matches[1])) {
                        $versions[] = $matches[1];
                    }
                }
                
                if (!empty($versions)) {
                    // Sort versions and return latest
                    usort($versions, 'version_compare');
                    return end($versions);
                }
            }
        }
        
        // Option 3: Default fallback
        return config('app.version', '1.0.0');
    }

    /**
     * Get update file size from remote server or local file
     */
    private function getUpdateFileSize(string $version): int
    {
        // Try to get file size from database first
        try {
            $versionModel = \App\Models\Version::where('version', $version)
                ->where('is_active', true)
                ->first();
            
            if ($versionModel && $versionModel->file_size) {
                return $versionModel->file_size;
            }
        } catch (\Exception $e) {
            // Database query failed, continue to other methods
        }
        
        // Try to get file size from remote server
        try {
            $remoteSizeUrl = 'https://www.creativedesign.com.bd/api/updates/size/' . $version;
            $sizeResponse = Http::withoutVerifying()
                ->timeout(10)
                ->get($remoteSizeUrl);
            
            if ($sizeResponse->successful()) {
                $sizeData = $sizeResponse->json();
                $fileSize = $sizeData['file_size'] ?? 0;
                if ($fileSize > 0) {
                    return $fileSize;
                }
            }
        } catch (\Exception $e) {
            // Remote fetch failed, continue to local file check
            Log::info('Remote file size fetch failed: ' . $e->getMessage());
        }
        
        // Fallback: Get file size from local file if exists
        $filePath = storage_path('app/updates/update-' . $version . '.zip');
        if (file_exists($filePath)) {
            return filesize($filePath);
        }
        
        return 0;
    }

    /**
     * Get download URL for update file from creativedesign.com.bd
     */
    private function getDownloadUrl(string $version): ?string
    {
        // Primary: Download from creativedesign.com.bd update server
        $remoteUrl = 'https://www.creativedesign.com.bd/api/updates/file/' . $version;
        
        // Optionally check if file exists on remote server
        // For now, we'll return the remote URL directly
        // The UpdateController will handle the actual download
        
        return $remoteUrl;
        
        // Fallback: Local file exists (for development/testing)
        $localPath = storage_path('app/updates/update-' . $version . '.zip');
        if (file_exists($localPath)) {
            return url('/api/updates/file/' . $version);
        }
        
        return null;
    }
}
