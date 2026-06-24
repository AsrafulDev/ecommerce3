<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Theme;
use App\Models\HomepageLayout;
use App\Models\HomepageSection;
use App\Models\HomepageLayoutSection;
use App\Models\GeneralSetting;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
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
        
        return view('backEnd.demo.index', compact('themes', 'layouts', 'activeLayout', 'activeTheme', 'presets'));
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

        // 4. Export general settings (only theme_id and active_layout_id)
        $setting = GeneralSetting::first();
        $settings = $setting ? [
            'theme_id' => $setting->theme_id,
            'active_layout_id' => $setting->active_layout_id,
        ] : [];
        file_put_contents($tempDir . '/general_settings.json', json_encode($settings, JSON_PRETTY_PRINT));

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
}
