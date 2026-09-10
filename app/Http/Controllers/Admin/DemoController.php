<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Theme;
use App\Models\HomepageLayout;
use App\Models\HomepageSection;
use App\Models\HomepageLayoutSection;
use App\Models\GeneralSetting;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Subcategory;
use App\Models\Banner;
use App\Models\BannerCategory;
use App\Models\Blog;
use App\Models\Product;
use App\Models\Productimage;
use App\Models\ShippingCharge;
use App\Helpers\PresetData;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use ZipArchive;
use Toastr;

class DemoController extends Controller
{
    function __construct()
    {
        $this->middleware('permission:theme-list|theme-create|theme-edit|theme-delete');
    }

    /**
     * Show demo management page
     */
    public function index()
    {
        $themes = Theme::orderBy('name')->get();
        $layouts = HomepageLayout::withCount('sections')->orderBy('name')->get();
        $activeLayout = HomepageLayout::where('is_active', true)->first();
        $activeTheme = Theme::where('is_default', true)->first();
        
        // Get available demo preset files
        $presets = [];
        $presetDir = storage_path('app/demo-presets');
        if (is_dir($presetDir)) {
            foreach (glob($presetDir . '/*.zip') as $file) {
                $presets[] = [
                    'name' => basename($file, '.zip'),
                    'size' => filesize($file),
                    'modified' => filemtime($file),
                    'path' => $file,
                ];
            }
        }

        // Get predefined shop presets from PresetData helper
        $shopPresets = PresetData::all();
        
        return view('backEnd.demo.index', compact('themes', 'layouts', 'activeLayout', 'activeTheme', 'presets', 'shopPresets'));
    }

    /**
     * Export ALL database tables as JSON + ALL media files as a downloadable ZIP.
     * ZIP structure:
     *   data/           — one JSON file per database table
     *   uploads/        — full copy of public/uploads/ (images, media)
     */
    public function exportDemo()
    {
        ini_set('max_execution_time', 600);
        ini_set('memory_limit', '512M');

        $tempDir = storage_path('app/demo-export-' . microtime(true));
        @mkdir($tempDir, 0755, true);

        // ── 1. Export every database table as a separate JSON file ──
        $dataDir = $tempDir . '/data';
        @mkdir($dataDir, 0755, true);

        $tables = DB::select('SHOW TABLES');
        $dbName = DB::getDatabaseName();
        $tableRows = [];
        foreach ($tables as $table) {
            $tableName = reset($table);
            if ($tableName === 'migrations') continue;

            $rows = DB::table($tableName)->get()->toArray();
            // Convert stdClass → array for clean JSON
            $rows = array_map(fn($r) => (array) $r, $rows);
            file_put_contents(
                $dataDir . '/' . $tableName . '.json',
                json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            );
            $tableRows[$tableName] = count($rows);
        }

        // Write a manifest so restore knows table order & counts
        file_put_contents(
            $dataDir . '/_manifest.json',
            json_encode([
                'database'   => $dbName,
                'exported_at' => now()->toDateTimeString(),
                'tables'     => $tableRows,
            ], JSON_PRETTY_PRINT)
        );

        // ── 2. Copy ALL uploads / media files ──
        $uploadsSource = public_path('uploads');
        if (is_dir($uploadsSource)) {
            $this->copyDirRecursive($uploadsSource, $tempDir . '/uploads');
        }

        // ── 3. Create ZIP and stream download ──
        $zipName = 'full-backup-' . date('Y-m-d_His') . '.zip';
        $zipPath = storage_path('app/demo-presets/' . $zipName);
        @mkdir(dirname($zipPath), 0755, true);

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($tempDir, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::LEAVES_ONLY
            );
            foreach ($iterator as $file) {
                if (!$file->isDir()) {
                    $relativePath = substr($file->getRealPath(), strlen($tempDir) + 1);
                    $zip->addFile($file->getRealPath(), $relativePath);
                }
            }
            $zip->close();
        }

        // ── 4. Cleanup temp directory ──
        $this->deleteDir($tempDir);

        return response()->download($zipPath, $zipName)->deleteFileAfterSend(true);
    }

    /**
     * Recursively copy a directory.
     */
    private function copyDirRecursive(string $src, string $dst): void
    {
        @mkdir($dst, 0755, true);
        $items = scandir($src);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            $srcPath = $src . '/' . $item;
            $dstPath = $dst . '/' . $item;
            if (is_dir($srcPath)) {
                $this->copyDirRecursive($srcPath, $dstPath);
            } else {
                copy($srcPath, $dstPath);
            }
        }
    }

    /**
     * Recursively delete a directory.
     */
    private function deleteDir(string $dir): void
    {
        if (!is_dir($dir)) return;
        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            $path = $dir . '/' . $item;
            if (is_dir($path)) {
                $this->deleteDir($path);
            } else {
                @unlink($path);
            }
        }
        @rmdir($dir);
    }

    /**
     * Import demo from uploaded zip file
     */
    public function importDemo(Request $request)
    {
        $request->validate([
            'demo_file' => 'required|file|mimes:zip|max:102400',
        ]);

        $file = $request->file('demo_file');
        $tempDir = storage_path('app/demo-import-' . time());
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        // Extract zip
        $zip = new ZipArchive();
        if ($zip->open($file->getRealPath()) === true) {
            $zip->extractTo($tempDir);
            $zip->close();
        } else {
            Toastr::error('Invalid zip file!', 'Error');
            return redirect()->back();
        }

        DB::beginTransaction();
        try {
            // 1. Import themes
            $themesPath = $tempDir . '/themes.json';
            if (file_exists($themesPath)) {
                $themes = json_decode(file_get_contents($themesPath), true);
                foreach ($themes as $themeData) {
                    // Copy preview image if exists
                    if (!empty($themeData['preview_image'])) {
                        $imgName = basename($themeData['preview_image']);
                        $srcImg = $tempDir . '/images/' . $imgName;
                        $dstImg = public_path('uploads/section-previews/' . $imgName);
                        if (file_exists($srcImg) && !file_exists($dstImg)) {
                            copy($srcImg, $dstImg);
                            $themeData['preview_image'] = 'uploads/section-previews/' . $imgName;
                        }
                    }
                    unset($themeData['id'], $themeData['created_at'], $themeData['updated_at']);
                    Theme::create($themeData);
                }
            }

            // 2. Import homepage sections
            $sectionsPath = $tempDir . '/homepage_sections.json';
            if (file_exists($sectionsPath)) {
                $sections = json_decode(file_get_contents($sectionsPath), true);
                foreach ($sections as $sectionData) {
                    if (!empty($sectionData['preview_image'])) {
                        $imgName = basename($sectionData['preview_image']);
                        $srcImg = $tempDir . '/images/' . $imgName;
                        $dstImg = public_path('uploads/section-previews/' . $imgName);
                        if (file_exists($srcImg) && !file_exists($dstImg)) {
                            copy($srcImg, $dstImg);
                            $sectionData['preview_image'] = 'uploads/section-previews/' . $imgName;
                        }
                    }
                    unset($sectionData['id'], $sectionData['created_at'], $sectionData['updated_at']);
                    HomepageSection::create($sectionData);
                }
            }

            // 3. Import layouts with sections
            $layoutsPath = $tempDir . '/homepage_layouts.json';
            if (file_exists($layoutsPath)) {
                $layouts = json_decode(file_get_contents($layoutsPath), true);
                foreach ($layouts as $layoutData) {
                    $sectionsData = $layoutData['sections'] ?? [];
                    unset($layoutData['id'], $layoutData['sections'], $layoutData['created_at'], $layoutData['updated_at']);
                    $layout = HomepageLayout::create($layoutData);
                    
                    foreach ($sectionsData as $lsData) {
                        // Find matching section by slug
                        $section = HomepageSection::where('slug', $lsData['section']['slug'] ?? '')->first();
                        if ($section) {
                            HomepageLayoutSection::create([
                                'layout_id' => $layout->id,
                                'section_id' => $section->id,
                                'sort_order' => $lsData['sort_order'] ?? 0,
                                'is_visible' => $lsData['is_visible'] ?? true,
                                'columns_config' => $lsData['columns_config'] ?? 'col-sm-12',
                            ]);
                        }
                    }
                }
            }

            // 4. Import general settings reference
            $settingsPath = $tempDir . '/general_settings.json';
            if (file_exists($settingsPath)) {
                $settings = json_decode(file_get_contents($settingsPath), true);
                $setting = GeneralSetting::first();
                if ($setting && !empty($settings)) {
                    if (!empty($settings['theme_id'])) {
                        $theme = Theme::skip($settings['theme_id'] - 1)->first();
                        if ($theme) $setting->theme_id = $theme->id;
                    }
                    if (!empty($settings['active_layout_id'])) {
                        $layout = HomepageLayout::skip($settings['active_layout_id'] - 1)->first();
                        if ($layout) $setting->active_layout_id = $layout->id;
                    }
                    $setting->save();
                }
            }

            // 5. Import base setup data (settings, colors, sizes, districts,
            //    shipping charges, roles, permissions)
            self::importBaseData($tempDir);

            DB::commit();

            // Cleanup
            array_map('unlink', glob($tempDir . '/images/*'));
            rmdir($tempDir . '/images');
            array_map('unlink', glob($tempDir . '/*.json'));
            rmdir($tempDir);

            // Clear cache
            \Illuminate\Support\Facades\Cache::flush();

            Toastr::success('Demo imported successfully! ' . count($themes ?? []) . ' themes, ' . count($layouts ?? []) . ' layouts imported.', 'Success');
        } catch (\Exception $e) {
            DB::rollBack();
            // Cleanup on error
            if (is_dir($tempDir)) {
                array_map('unlink', glob($tempDir . '/images/*'));
                @rmdir($tempDir . '/images');
                array_map('unlink', glob($tempDir . '/*.json'));
                @rmdir($tempDir);
            }
            Toastr::error('Import failed: ' . $e->getMessage(), 'Error');
        }

        return redirect()->back();
    }

    /**
     * Upload and import a preset zip file.
     * Zip must contain: data.json + images/ folder
     */
    public function importPresetZip(Request $request)
    {
        $request->validate([
            'preset_zip' => 'required|file|mimes:zip|max:512000',
        ]);

        $file = $request->file('preset_zip');
        $tempDir = storage_path('app/demo-import-' . time());
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $zip = new ZipArchive();
        if ($zip->open($file->getRealPath()) !== true) {
            Toastr::error('Invalid zip file!', 'Error');
            return redirect()->back();
        }
        $zip->extractTo($tempDir);
        $zip->close();

        $jsonPath = $tempDir . '/data.json';
        if (!file_exists($jsonPath)) {
            self::cleanTempDir($tempDir);
            Toastr::error('Zip must contain a data.json file!', 'Error');
            return redirect()->back();
        }

        $data = json_decode(file_get_contents($jsonPath), true);
        if (!$data || !isset($data['meta'])) {
            self::cleanTempDir($tempDir);
            Toastr::error('Invalid data.json format!', 'Error');
            return redirect()->back();
        }

        $slug = $data['meta']['slug'] ?? pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $slug = preg_replace('/[^a-z0-9-]/', '', strtolower(str_replace(' ', '-', $slug)));

        try {
            // Make the uploaded preset independent of the live image host.
            $zipImageDir = $tempDir . '/images';
            if (!is_dir($zipImageDir)) mkdir($zipImageDir, 0755, true);
            self::downloadPresetImages($data, $zipImageDir);
            file_put_contents($jsonPath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            // ── Copy all images flat → public/uploads/images/ ──
            $publicImagesDir = public_path('uploads/images');
            if (!is_dir($publicImagesDir)) mkdir($publicImagesDir, 0755, true);

            // Save data.json alongside images (reference)
            copy($jsonPath, $publicImagesDir . '/data.json');

            $copyCount = 0;
            if (is_dir($zipImageDir)) {
                $iterator = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($zipImageDir, \RecursiveDirectoryIterator::SKIP_DOTS),
                    \RecursiveIteratorIterator::LEAVES_ONLY
                );
                foreach ($iterator as $item) {
                    $dest = $publicImagesDir . '/' . $item->getFilename();
                    // avoid overwriting — append timestamp if conflict
                    if (file_exists($dest)) {
                        $dest = $publicImagesDir . '/' . time() . '-' . $item->getFilename();
                    }
                    copy($item->getPathname(), $dest);
                    $copyCount++;
                }
            }

            // ── Seed data ──
            self::seedPresetData($data, $slug);
            \Illuminate\Support\Facades\Cache::flush();

            $name = $data['meta']['name'] ?? $slug;
            Toastr::success("「{$name}」 imported successfully! {$copyCount} images copied.", 'Success');
        } catch (\Exception $e) {
            Toastr::error('Import failed: ' . $e->getMessage(), 'Error');
        }

        self::cleanTempDir($tempDir);
        return redirect()->route('demo.index');
    }

    /**
     * Upload and import a standalone data.json preset.
     */
    public function importPresetJson(Request $request)
    {
        $request->validate([
            'preset_json' => 'required|file|mimes:json,txt|max:51200',
        ]);

        $file = $request->file('preset_json');
        $data = json_decode(file_get_contents($file->getRealPath()), true);
        if (!is_array($data) || !isset($data['meta'])) {
            Toastr::error('Invalid data.json format!', 'Error');
            return redirect()->back();
        }

        try {
            $downloadCount = self::downloadPresetImagesToMedia($data);
            self::seedPresetData($data, $data['meta']['slug'] ?? 'json-import');
            Cache::flush();

            $name = $data['meta']['name'] ?? 'JSON preset';
            Toastr::success("「{$name}」 imported successfully! {$downloadCount} images downloaded.", 'Success');
        } catch (\Throwable $e) {
            Toastr::error('Import failed: ' . $e->getMessage(), 'Error');
        }

        return redirect()->route('demo.index');
    }

    /**
     * Delete a demo preset zip
     */
    public function deletePreset($name)
    {
        $path = storage_path('app/demo-presets/' . basename($name) . '.zip');
        if (file_exists($path)) {
            unlink($path);
            Toastr::success('Demo preset deleted!', 'Success');
        } else {
            Toastr::error('Preset not found!', 'Error');
        }
        return redirect()->route('demo.index');
    }

    /**
     * Import a predefined shop preset (one-click)
     * Note: TRUNCATE is DDL in MySQL and commits implicitly, so we cannot use DB transactions here.
     */
    public function importPreset($slug)
    {
        $data = PresetData::get($slug);
        if (!$data) {
            Toastr::error('Invalid preset: ' . $slug, 'Error');
            return redirect()->route('demo.index');
        }

        try {
            // ── Copy all images flat → public/uploads/images/ ──
            $presetDir = storage_path("app/demo-presets/{$slug}");
            $publicImagesDir = public_path('uploads/images');
            if (!is_dir($publicImagesDir)) mkdir($publicImagesDir, 0755, true);

            // Save data.json alongside images (reference)
            $presetJson = $presetDir . '/data.json';
            if (file_exists($presetJson)) {
                copy($presetJson, $publicImagesDir . '/data.json');
            }

            // Flatten-copy all images (no subdirectory nesting)
            $copyCount = 0;
            $presetImageDir = $presetDir . '/images';
            if (is_dir($presetImageDir)) {
                $iterator = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($presetImageDir, \RecursiveDirectoryIterator::SKIP_DOTS),
                    \RecursiveIteratorIterator::LEAVES_ONLY
                );
                foreach ($iterator as $item) {
                    $dest = $publicImagesDir . '/' . $item->getFilename();
                    if (file_exists($dest)) {
                        $dest = $publicImagesDir . '/' . time() . '-' . $item->getFilename();
                    }
                    copy($item->getPathname(), $dest);
                    $copyCount++;
                }
            }

            // ── Seed data ──
            self::seedPresetData($data, $slug);
            \Illuminate\Support\Facades\Cache::flush();

            $name = $data['meta']['name'] ?? $slug;
            Toastr::success("「{$name}」 imported successfully! {$copyCount} images copied.", 'Success');
        } catch (\Exception $e) {
            Toastr::error('Import failed: ' . $e->getMessage(), 'Error');
        }

        return redirect()->route('demo.index');
    }

    /**
     * Reset site — FULL hard reset.
     * 1. Asks the logged-in admin for their password (safety confirmation).
     * 2. Truncates EVERY data table (orders, warranty sales/claims, damage
     *    products, stock batches, products, …) so nothing is left behind.
     * 3. Re-seeds only the base/default data (NO demo products):
     *    roles + permissions, general settings, themes/layouts, admin user,
     *    contacts, homepage sections, colors, sizes, districts, coupons and
     *    shipping charges (Inside Dhaka 70TK / Outside Dhaka 120TK).
     *
     * Note: TRUNCATE is DDL in MySQL and commits implicitly, so we cannot use
     * DB transactions here.
     */
    public function resetSite(Request $request)
    {
        // ── 1. Require the admin password before anything destructive ──
        $request->validate(['password' => 'required']);
        if (!Hash::check($request->password, auth('admin')->user()->password)) {
            Toastr::error('Incorrect admin password. Reset was cancelled.', 'Error');
            return redirect()->route('demo.index');
        }

        // Remember who we are so we can re-login after the users table is reset
        $adminEmail = auth('admin')->user()->email;

        try {
            self::truncateAllTables();   // full hard reset — every table
            self::deleteUploadedFiles();

            // ── 2. Re-seed base data only (no products) ──
            Artisan::call('db:seed', [
                '--class' => 'Database\\Seeders\\DefaultDatabaseSeeder',
                '--force' => true,
            ]);

            // ── 3. Re-authenticate the current admin (user row was re-created) ──
            $fresh = \App\Models\User::where('email', $adminEmail)->first();
            if ($fresh) {
                auth('admin')->login($fresh);
            }

            Cache::flush();

            Toastr::success('Site fully reset. Admin login → asraful@curlware.com / password: 123456', 'Success');
        } catch (\Exception $e) {
            Toastr::error('Reset failed: ' . $e->getMessage(), 'Error');
        }

        return redirect()->route('demo.index');
    }

    /**
     * Clean site — truncate ALL data tables without re-seeding.
     * Also deletes uploaded files (uploads folder).
     * Leaves the site completely empty; only the admin users, roles and
     * permissions are preserved so you can still log in.
     */
    public function cleanSite(Request $request)
    {
        // ── Require the admin password before anything destructive ──
        $request->validate(['password' => 'required']);
        if (!Hash::check($request->password, auth('admin')->user()->password)) {
            Toastr::error('Incorrect admin password. Clean was cancelled.', 'Error');
            return redirect()->route('demo.index');
        }

        try {
            // Keep the auth/ACL tables so the admin can still log in afterwards
            self::truncateAllTables([
                'users', 'roles', 'permissions',
                'model_has_roles', 'model_has_permissions',
            ]);
            self::deleteUploadedFiles();
            Cache::flush();

            Toastr::success('All data has been wiped clean! The site is now empty.', 'Success');
        } catch (\Exception $e) {
            Toastr::error('Clean failed: ' . $e->getMessage(), 'Error');
        }

        return redirect()->route('demo.index');
    }

    /**
     * Delete uploaded files from public/uploads/ (except essential system files)
     */
    /**
     * Recursively delete a temp directory
     */
    private static function cleanTempDir(string $dir): void
    {
        if (!is_dir($dir)) return;
        $it = new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS);
        $files = new \RecursiveIteratorIterator($it, \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($files as $f) {
            if ($f->isDir()) @rmdir($f->getRealPath());
            else @unlink($f->getRealPath());
        }
        @rmdir($dir);
    }

    /**
     * Download remote image fields from a preset and replace them with local paths.
     */
    private static function downloadPresetImages(array &$data, string $imageDir): void
    {
        $imageKeys = [
            'image', 'gallery_images', 'white_logo', 'dark_logo', 'favicon',
        ];
        $downloaded = [];

        $walk = function (&$value, ?string $key = null) use (&$walk, $imageKeys, &$downloaded, $imageDir): void {
            if (is_array($value)) {
                foreach ($value as $childKey => &$childValue) {
                    $childField = $key === 'gallery_images' ? 'image' : (string) $childKey;
                    $walk($childValue, $childField);
                }
                unset($childValue);
                return;
            }

            if (!is_string($value) || !in_array($key, $imageKeys, true)) return;
            if (!filter_var($value, FILTER_VALIDATE_URL) || !preg_match('/^https?:\/\//i', $value)) return;
            if (isset($downloaded[$value])) {
                $value = $downloaded[$value];
                return;
            }

            $response = Http::timeout(30)->retry(2, 200)->get($value);
            if (!$response->successful() || $response->body() === '') {
                throw new \RuntimeException("Unable to download preset image: {$value}");
            }

            $urlPath = parse_url($value, PHP_URL_PATH) ?: '';
            $filename = basename($urlPath) ?: 'preset-image';
            $filename = preg_replace('/[^a-zA-Z0-9._-]/', '-', $filename);
            if (!pathinfo($filename, PATHINFO_EXTENSION)) {
                $extension = match (strtolower((string) $response->header('Content-Type'))) {
                    'image/png' => 'png',
                    'image/webp' => 'webp',
                    'image/gif' => 'gif',
                    default => 'jpg',
                };
                $filename .= '.' . $extension;
            }

            $target = $imageDir . '/' . $filename;
            $suffix = 1;
            while (file_exists($target)) {
                $target = $imageDir . '/' . pathinfo($filename, PATHINFO_FILENAME)
                    . '-' . $suffix++ . '.' . pathinfo($filename, PATHINFO_EXTENSION);
            }
            file_put_contents($target, $response->body());

            $localPath = 'public/uploads/images/' . basename($target);
            $downloaded[$value] = $localPath;
            $value = $localPath;
        };

        $walk($data);
    }

    /**
     * Download JSON image URLs directly into the public media structure.
     */
    private static function downloadPresetImagesToMedia(array &$data): int
    {
        $mediaBase = public_path('uploads/media');
        $downloaded = [];
        $count = 0;

        $download = static function (string $url, string $folder) use (&$downloaded, &$count, $mediaBase): string {
            // Accept JSON URLs containing unencoded spaces in the filename.
            $url = preg_replace('/\s+/', '%20', trim($url));
            if (!filter_var($url, FILTER_VALIDATE_URL) || !preg_match('/^https?:\/\//i', $url)) return $url;
            $cacheKey = $folder . '|' . $url;
            if (isset($downloaded[$cacheKey])) return $downloaded[$cacheKey];

            $response = Http::timeout(30)->retry(2, 200)->get($url);
            if (!$response->successful() || $response->body() === '') {
                throw new \RuntimeException("Unable to download image: {$url}");
            }

            $directory = $mediaBase . '/' . $folder;
            if (!is_dir($directory)) mkdir($directory, 0755, true);
            $urlPath = parse_url($url, PHP_URL_PATH) ?: '';
            $filename = preg_replace('/[^a-zA-Z0-9._-]/', '-', basename($urlPath) ?: 'image');
            if (!pathinfo($filename, PATHINFO_EXTENSION)) {
                $extension = match (strtolower((string) $response->header('Content-Type'))) {
                    'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif', default => 'jpg',
                };
                $filename .= '.' . $extension;
            }

            $target = $directory . '/' . $filename;
            $suffix = 1;
            while (file_exists($target)) {
                $target = $directory . '/' . pathinfo($filename, PATHINFO_FILENAME)
                    . '-' . $suffix++ . '.' . pathinfo($filename, PATHINFO_EXTENSION);
            }
            file_put_contents($target, $response->body());
            $localPath = 'public/uploads/media/' . $folder . '/' . basename($target);
            $downloaded[$cacheKey] = $localPath;
            $count++;
            return $localPath;
        };

        // Import image-like fields that are not part of the standard preset schema.
        // Keep live_url, links, and other navigation URLs untouched.
        $imageKeys = [
            'image', 'image_url', 'image_one', 'image_two', 'image_three',
            'thumbnail', 'thumbnail_url', 'meta_image', 'logo', 'white_logo',
            'dark_logo', 'favicon', 'banner_image', 'cover_image', 'icon',
        ];
        $folderForKey = static function (?string $parentKey, string $key): string {
            $context = strtolower(($parentKey ?? '') . ' ' . $key);
            if (str_contains($context, 'categor')) return 'category';
            if (str_contains($context, 'brand')) return 'brand';
            if (str_contains($context, 'product')) return 'product';
            if (str_contains($context, 'banner') || str_contains($context, 'slider')) return 'banner';
            return 'adds';
        };
        $walk = function (&$value, ?string $parentKey = null, ?string $folder = null) use (&$walk, $imageKeys, $folderForKey, $download): void {
            if (!is_array($value)) return;
            foreach ($value as $key => &$child) {
                $key = (string) $key;
                $childFolder = $folder ?: $folderForKey($parentKey, $key);
                if (is_string($child)
                    && in_array(strtolower($key), $imageKeys, true)
                    && filter_var($child, FILTER_VALIDATE_URL)
                    && preg_match('/^https?:\/\//i', $child)) {
                    $child = $download($child, $childFolder);
                } elseif (is_array($child)) {
                    $walk($child, $key, $childFolder);
                }
            }
            unset($child);
        };

        $walk($data);

        foreach ($data['categories'] ?? [] as &$item) {
            if (!empty($item['image']) && filter_var($item['image'], FILTER_VALIDATE_URL)) $item['image'] = $download($item['image'], 'category');
        }
        foreach ($data['brands'] ?? [] as &$item) {
            if (is_array($item) && !empty($item['image']) && filter_var($item['image'], FILTER_VALIDATE_URL)) $item['image'] = $download($item['image'], 'brand');
        }
        foreach ($data['products'] ?? [] as &$item) {
            if (!empty($item['image']) && filter_var($item['image'], FILTER_VALIDATE_URL)) $item['image'] = $download($item['image'], 'product');
            foreach ($item['gallery_images'] ?? [] as &$galleryImage) {
                if (filter_var($galleryImage, FILTER_VALIDATE_URL)) $galleryImage = $download($galleryImage, 'product');
            }
            unset($galleryImage);
        }
        foreach ($data['banners'] ?? [] as &$item) {
            if (!empty($item['image']) && filter_var($item['image'], FILTER_VALIDATE_URL)) $item['image'] = $download($item['image'], 'banner');
        }
        foreach ($data['blogs'] ?? [] as &$item) {
            if (!empty($item['image']) && filter_var($item['image'], FILTER_VALIDATE_URL)) $item['image'] = $download($item['image'], 'adds');
        }
        foreach (['white_logo', 'dark_logo', 'favicon'] as $key) {
            if (!empty($data['general_settings'][$key]) && filter_var($data['general_settings'][$key], FILTER_VALIDATE_URL)) {
                $data['general_settings'][$key] = $download($data['general_settings'][$key], 'adds');
            }
        }
        unset($item);

        return $count;
    }

    private static function deleteUploadedFiles(): void
    {
        $uploadDir = public_path('uploads');
        if (!is_dir($uploadDir)) return;

        // Subdirectories to clean (files inside these will be deleted)
        $cleanDirs = [
            'category', 'brand', 'product', 'banner', 'campaign',
            'blogs', 'subcategory', 'settings', 'popup', 'customer',
            'user', 'users', 'demo', 'videos',
            'images',
        ];

        // Also clean preset folders (gadget-fashion-grocery, electronics, etc.)
        $presetSlugs = array_keys(\App\Helpers\PresetData::all());

        foreach ($cleanDirs as $dir) {
            $path = $uploadDir . '/' . $dir;
            if (is_dir($path)) {
                $files = array_diff(scandir($path), ['.', '..']);
                foreach ($files as $file) {
                    $filePath = $path . '/' . $file;
                    if (is_file($filePath)) {
                        @unlink($filePath);
                    }
                }
            }
        }

        // Delete entire preset folders under public/uploads/ (recursively)
        foreach ($presetSlugs as $slug) {
            $presetPath = $uploadDir . '/' . $slug;
            if (is_dir($presetPath)) {
                // Recursively delete all files and subdirectories
                $it = new \RecursiveDirectoryIterator($presetPath, \RecursiveDirectoryIterator::SKIP_DOTS);
                $files = new \RecursiveIteratorIterator($it, \RecursiveIteratorIterator::CHILD_FIRST);
                foreach ($files as $file) {
                    if ($file->isDir()) @rmdir($file->getRealPath());
                    else @unlink($file->getRealPath());
                }
                @rmdir($presetPath);
            }
        }
    }

    /**
     * Truncate EVERY data table in the database (full hard reset).
     *
     * The list is discovered from the live schema so no table is ever missed
     * (orders, order_details, warranty_sales, warranty_claims, damage_products,
     * stock_batches, products, …). Only Laravel system tables are skipped.
     *
     * @param array $keep tables to preserve (e.g. users/roles for "clean")
     */
    private static function truncateAllTables(array $keep = []): void
    {
        $keep = array_flip(array_merge(['migrations'], $keep));

        DB::statement('SET FOREIGN_KEY_CHECKS = 0');
        $tables = DB::select('SHOW TABLES');
        foreach ($tables as $table) {
            $name = current((array) $table);
            if (isset($keep[$name])) {
                continue;
            }
            DB::table($name)->truncate();
        }
        DB::statement('SET FOREIGN_KEY_CHECKS = 1');
    }

    /**
     * Import base/setup data from an exported zip:
     * full general settings, colors, sizes, districts, shipping charges,
     * roles and permissions. Uses DELETE (not TRUNCATE) so it stays inside the
     * surrounding DB transaction.
     */
    private static function importBaseData(string $tempDir): void
    {
        // ── General settings (full row) — update first row or create ──
        // NOTE: theme_id / active_layout_id are handled by the existing
        // step-4 mapping (position-based), so they are skipped here.
        $settingsPath = $tempDir . '/general_settings.json';
        if (file_exists($settingsPath)) {
            $settings = json_decode(file_get_contents($settingsPath), true);
            if (is_array($settings) && !empty($settings)) {
                $data = array_diff_key($settings, array_flip([
                    'id', 'created_at', 'updated_at', 'theme_id', 'active_layout_id',
                ]));
                $setting = GeneralSetting::first();
                if ($setting) {
                    foreach ($data as $key => $val) {
                        if (\Illuminate\Support\Facades\Schema::hasColumn('general_settings', $key)) {
                            $setting->$key = $val;
                        }
                    }
                    $setting->save();
                } else {
                    GeneralSetting::create($data);
                }
            }
        }

        // ── Colors / sizes / districts / shipping charges / roles / permissions ──
        foreach (['colors', 'sizes', 'districts', 'shipping_charges', 'roles', 'permissions'] as $table) {
            self::importTable($tempDir, $table . '.json', $table);
        }

        // ── Re-sync the admin role assignment after importing roles/permissions ──
        if (file_exists($tempDir . '/roles.json') || file_exists($tempDir . '/permissions.json')) {
            if (\Illuminate\Support\Facades\Schema::hasTable('model_has_roles')) {
                DB::table('model_has_roles')->delete();
            }
            if (\Illuminate\Support\Facades\Schema::hasTable('model_has_permissions')) {
                DB::table('model_has_permissions')->delete();
            }

            app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

            try {
                Artisan::call('db:seed', [
                    '--class' => 'Database\\Seeders\\CreateAdminUserSeeder',
                    '--force' => true,
                ]);
            } catch (\Exception $e) {
                // roles/permissions may not exist yet — non-fatal
            }
        }
    }

    /**
     * Import a plain table dump (rows without id/timestamps) and re-insert it.
     */
    private static function importTable(string $tempDir, string $file, string $table): void
    {
        $path = $tempDir . '/' . $file;
        if (!file_exists($path)) {
            return;
        }
        $rows = json_decode(file_get_contents($path), true);
        if (!is_array($rows) || empty($rows) || !\Illuminate\Support\Facades\Schema::hasTable($table)) {
            return;
        }

        DB::table($table)->delete();
        foreach ($rows as $row) {
            $row = array_diff_key((array) $row, array_flip(['id', 'created_at', 'updated_at']));
            DB::table($table)->insert(array_merge($row, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }

    /**
     * Seed preset data into the database.
     * Local media paths are preserved; older image paths remain supported.
     */
    private static function seedPresetData(array $data, string $slug = 'default'): void
    {
        // Disable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS = 0');

        // Truncate existing data
        $tables = ['categories','subcategories','brands','products','productimages',
                    'banners','banner_categories','blogs','shipping_charges',
                    'reviews','campaigns','campaign_reviews','coupons',
                    'orders','order_details','payments','shippings','carts',
                    'carts','incomplete_orders',
                    'suppliers','stock_batches'];
        foreach ($tables as $table) {
            if (\Illuminate\Support\Facades\Schema::hasTable($table)) {
                DB::table($table)->truncate();
            }
        }

        // ── Image path normaliser ──────────────────────────────────
        // Preserve local media paths and normalize external/legacy paths.
        $imgBase = 'public/uploads/images/';
        $normalizePath = static function (?string &$path) use ($imgBase): void {
            if (empty($path)) return;
            if (str_starts_with($path, 'public/uploads/media/') || str_starts_with($path, $imgBase)) return;
            $path = $imgBase . basename($path);
        };

        // 1. General Settings
        $gs = $data['general_settings'] ?? [];
        $setting = GeneralSetting::first();
        if ($setting && !empty($gs)) {
            foreach ($gs as $key => $val) {
                if ($key !== 'id' && \Illuminate\Support\Facades\Schema::hasColumn('general_settings', $key)) {
                    $setting->$key = $val;
                }
            }
            $setting->save();
        }

        // 2. Categories
        $catMap = [];
        foreach ($data['categories'] ?? [] as $c) {
            $catImage = $c['image'] ?? 'public/uploads/images/_placeholder.jpg';
            $normalizePath($catImage);
            $id = DB::table('categories')->insertGetId([
                'name'       => $c['name'],
                'slug'       => $c['slug'],
                'parent_id'  => $c['parent_id'] ?? 0,
                'image'      => $catImage,
                'status'     => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $catMap[$c['slug']] = $id;
        }

        // 3. Subcategories
        foreach ($data['subcategories'] ?? [] as $s) {
            $catId = $catMap[$s['cat']] ?? null;
            if ($catId) {
                DB::table('subcategories')->insert([
                    'category_id'      => $catId,
                    'subcategoryName'  => $s['name'],
                    'slug'             => $s['slug'],
                    'status'           => 1,
                    'created_at'       => now(),
                    'updated_at'       => now(),
                ]);
            }
        }

        // 4. Brands
        $brandMap = [];
        $hasNameBn = \Illuminate\Support\Facades\Schema::hasColumn('brands', 'name_bn');
        foreach ($data['brands'] ?? [] as $b) {
            // Support both string names and objects with 'name' key
            $brandName = is_string($b) ? $b : ($b['name'] ?? 'Brand');
            $brandImage = is_array($b) ? ($b['image'] ?? null) : null;
            if (empty($brandImage)) {
                $brandImage = 'public/uploads/images/_placeholder.jpg';
            }
            $normalizePath($brandImage);
            $brandSlug = Str::slug($brandName);
            $brandRow = [
                'name'       => $brandName,
                'slug'       => $brandSlug,
                'image'      => $brandImage,
                'status'     => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ];
            if ($hasNameBn) {
                $brandRow['name_bn'] = $brandName;
            }
            $id = DB::table('brands')->insertGetId($brandRow);
            $brandMap[$brandName] = $id;
        }

        // 4.5. Suppliers (for batch-based stock)
        $supplierMap = [];
        foreach ($data['suppliers'] ?? [] as $s) {
            $supplierName = is_string($s) ? $s : ($s['name'] ?? 'Supplier');
            if ($supplierName === '' || isset($supplierMap[$supplierName])) continue;
            $id = DB::table('suppliers')->insertGetId([
                'name'       => $supplierName,
                'phone'      => is_array($s) ? ($s['phone'] ?? null) : null,
                'email'      => is_array($s) ? ($s['email'] ?? null) : null,
                'address'    => is_array($s) ? ($s['address'] ?? null) : null,
                'status'     => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $supplierMap[$supplierName] = $id;
        }

        // 5. Products
        foreach ($data['products'] ?? [] as $i => $p) {
            $catId = $catMap[$p['cat']] ?? 1;
            $brandId = $brandMap[$p['brand']] ?? 1;
            $productImage = $p['image'] ?? ('public/uploads/images/product-' . ($i + 1) . '.jpg');
            $normalizePath($productImage);
            $pid = DB::table('products')->insertGetId([
                'name'           => $p['name'],
                'slug'           => Str::slug($p['name']) . '-' . ($i + 1),
                'category_id'    => $catId,
                'brand_id'       => $brandId,
                'product_code'   => 'PRD-' . str_pad($i + 1, 4, '0', STR_PAD_LEFT),
                'purchase_price' => round($p['price'] * 0.7),
                'old_price'      => $p['old'] ?? null,
                'new_price'      => $p['price'],
                'stock'          => $p['stock'] ?? 100,
                'status'         => 1,
                'approval_status' => 'approved',
                'topsale'        => $i < 3 ? 1 : 0,
                'flashsale'      => $i > 0 && $i % 3 == 0 ? 1 : 0,
                'description'    => 'High-quality ' . $p['name'] . ' at the best price in Bangladesh.',
                'meta_description' => 'Buy ' . $p['name'] . ' online at best price in Bangladesh.',
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);

            // Product gallery images (stored in productimages table)
            // The storefront reads the first productimages row as the primary image.
            // Always seed the downloaded primary image first, then append the gallery.
            $galleryImages = [$productImage];
            foreach ($p['gallery_images'] ?? [] as $galleryImage) {
                if (is_array($galleryImage)) {
                    $galleryImage = $galleryImage['image'] ?? $galleryImage['url'] ?? null;
                }
                if (is_string($galleryImage) && $galleryImage !== '') {
                    $galleryImages[] = $galleryImage;
                }
            }
            foreach ($galleryImages as $gi) {
                $img = $gi;
                $normalizePath($img);
                DB::table('productimages')->insert([
                    'product_id' => $pid,
                    'image'      => $img,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Supplier-based stock batches (quantity by batch)
            $batchRows = $p['stock_batches'] ?? [];
            if (empty($batchRows)) {
                $batchRows = [[
                    'supplier'  => null,
                    'batch_no'  => 'B-' . $pid,
                    'quantity'  => (int) ($p['stock'] ?? 0),
                    'unit_cost' => 0,
                ]];
            }
            foreach ($batchRows as $b) {
                $qty = (int) ($b['quantity'] ?? 0);
                if ($qty <= 0) continue;
                $supplierName = is_array($b) ? ($b['supplier'] ?? null) : null;
                DB::table('stock_batches')->insert([
                    'product_id'     => $pid,
                    'supplier_id'    => $supplierMap[$supplierName] ?? null,
                    'batch_no'       => $b['batch_no'] ?? ('B-' . $pid),
                    'quantity'       => $qty,
                    'remaining_qty'  => $qty,
                    'unit_cost'      => $b['unit_cost'] ?? 0,
                    'selling_price'  => $p['price'] ?? null,
                    'type'           => 'in',
                    'reference_type' => 'purchase',
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ]);
            }
        }

        // 6. Banners
        if (DB::table('banner_categories')->count() == 0) {
            $bcNames = [
                1 => 'Sliders', 5 => 'Slider Bottom Ads', 6 => 'Footer Top Ads',
                7 => 'Campaign Ads', 8 => 'Customer Reviews',
                9 => 'Hot Deal Banners', 10 => 'Homepage Ads', 11 => 'Homepage Ads 2',
            ];
            foreach ($bcNames as $id => $name) {
                DB::table('banner_categories')->insert([
                    'id' => $id, 'name' => $name, 'status' => 1,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        }

        if (DB::table('banners')->count() == 0) {
            // Try to use banners from preset data, otherwise fallback to defaults
            $bannerData = $data['banners'] ?? [
                ['category_id' => 1, 'image' => 'public/uploads/images/_placeholder.jpg', 'link' => '#'],
                ['category_id' => 1, 'image' => 'public/uploads/images/_placeholder.jpg', 'link' => '#'],
                ['category_id' => 5, 'image' => 'public/uploads/images/_placeholder.jpg', 'link' => '#'],
                ['category_id' => 6, 'image' => 'public/uploads/images/_placeholder.jpg', 'link' => '#'],
                ['category_id' => 8, 'image' => 'public/uploads/images/_placeholder.jpg', 'link' => '#'],
                ['category_id' => 10, 'image' => 'public/uploads/images/_placeholder.jpg', 'link' => '#'],
            ];
            foreach ($bannerData as $b) {
                $bannerImage = $b['image'] ?? ('public/uploads/images/_placeholder.jpg');
                $normalizePath($bannerImage);
                DB::table('banners')->insert([
                    'category_id' => $b['category_id'],
                    'image'       => $bannerImage,
                    'link'        => $b['link'] ?? '#',
                    'status'      => 1,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);
            }
        }

        // 7. Blogs
        foreach ($data['blogs'] ?? [] as $b) {
            $blogImage = $b['image'] ?? ('public/uploads/images/_placeholder.jpg');
            $normalizePath($blogImage);
            DB::table('blogs')->insert([
                'title'            => $b['title'],
                'slug'             => Str::slug($b['title']),
                'short_description' => $b['short_desc'] ?? '',
                'description'      => '<p>' . ($b['short_desc'] ?? '') . '</p>',
                'image'            => $blogImage,
                'views'            => $b['views'] ?? 0,
                'status'           => 1,
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);
        }

        // 8. Shipping Charges
        foreach ($data['shipping_charges'] ?? [] as $sc) {
            DB::table('shipping_charges')->insert([
                'name'       => $sc['name'],
                'amount'     => $sc['amount'],
                'status'     => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS = 1');
    }
}
