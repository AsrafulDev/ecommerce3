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
     * Export current themes, layouts, and settings as a zip file
     */
    public function exportDemo()
    {
        $tempDir = storage_path('app/demo-export-' . time());
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        // 1. Export themes
        $themes = Theme::all()->toArray();
        file_put_contents($tempDir . '/themes.json', json_encode($themes, JSON_PRETTY_PRINT));

        // 2. Export homepage sections
        $sections = HomepageSection::all()->toArray();
        file_put_contents($tempDir . '/homepage_sections.json', json_encode($sections, JSON_PRETTY_PRINT));

        // 3. Export layouts with their sections
        $layouts = HomepageLayout::with('sections')->get()->toArray();
        file_put_contents($tempDir . '/homepage_layouts.json', json_encode($layouts, JSON_PRETTY_PRINT));

        // 4. Export general settings (full row) + base setup data
        //    (colors, sizes, districts, shipping charges, roles, permissions)
        $setting = GeneralSetting::first();
        file_put_contents(
            $tempDir . '/general_settings.json',
            json_encode($setting ? $setting->toArray() : [], JSON_PRETTY_PRINT)
        );
        foreach (['colors', 'sizes', 'districts', 'shipping_charges', 'roles', 'permissions'] as $table) {
            file_put_contents(
                $tempDir . '/' . $table . '.json',
                json_encode(DB::table($table)->get()->toArray(), JSON_PRETTY_PRINT)
            );
        }

        // 5. Copy theme preview images
        $imgDir = $tempDir . '/images';
        mkdir($imgDir, 0755, true);
        foreach ($themes as $theme) {
            if (!empty($theme['preview_image']) && file_exists(public_path($theme['preview_image']))) {
                $name = basename($theme['preview_image']);
                copy(public_path($theme['preview_image']), $imgDir . '/' . $name);
            }
        }
        // Copy section preview images
        foreach ($sections as $section) {
            if (!empty($section['preview_image']) && file_exists(public_path($section['preview_image']))) {
                $name = basename($section['preview_image']);
                copy(public_path($section['preview_image']), $imgDir . '/' . $name);
            }
        }

        // 6. Create zip
        $zipPath = storage_path('app/demo-presets/' . 'demo-export-' . date('Y-m-d-His') . '.zip');
        $zipDir = dirname($zipPath);
        if (!is_dir($zipDir)) {
            mkdir($zipDir, 0755, true);
        }

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($tempDir),
                \RecursiveIteratorIterator::LEAVES_ONLY
            );
            foreach ($files as $file) {
                if (!$file->isDir()) {
                    $relativePath = substr($file->getRealPath(), strlen($tempDir) + 1);
                    $zip->addFile($file->getRealPath(), $relativePath);
                }
            }
            $zip->close();
        }

        // Cleanup temp
        array_map('unlink', glob($tempDir . '/images/*'));
        rmdir($tempDir . '/images');
        array_map('unlink', glob($tempDir . '/*.json'));
        rmdir($tempDir);

        Toastr::success('Demo exported successfully!', 'Success');
        return response()->download($zipPath)->deleteFileAfterSend(false);
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
            // ── Copy all images flat → public/uploads/images/ ──
            $publicImagesDir = public_path('uploads/images');
            if (!is_dir($publicImagesDir)) mkdir($publicImagesDir, 0755, true);

            // Save data.json alongside images (reference)
            copy($jsonPath, $publicImagesDir . '/data.json');

            $copyCount = 0;
            $zipImageDir = $tempDir . '/images';
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
     * All image paths are normalised to public/uploads/images/{basename} here.
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
        // Every image path is stored as: public/uploads/images/{basename}
        // No matter what format the JSON uses — we just pull the filename.
        $imgBase = 'public/uploads/images/';
        $normalizePath = static function (?string &$path) use ($imgBase): void {
            if (empty($path)) return;
            // Already in our flat format — skip
            if (str_starts_with($path, $imgBase)) return;
            // Just use the basename
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
            $galleryImages = $p['gallery_images'] ?? [];
            if (empty($galleryImages)) {
                $galleryImages = [$productImage];
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
