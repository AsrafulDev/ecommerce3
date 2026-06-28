<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\HomepageLayout;
use App\Models\HomepageSection;
use App\Models\HomepageLayoutSection;
use App\Helpers\PresetData;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ZipArchive;
use Toastr;

class BackupController extends Controller
{
    // ============================================================
    // 1. FULL SITE BACKUP (DB + uploads → ZIP download)
    // ============================================================
    public function createBackup()
    {
        ini_set('max_execution_time', 600);
        
        $backupDir = storage_path('app/backups');
        if (!is_dir($backupDir)) mkdir($backupDir, 0755, true);
        
        $timestamp = date('Y-m-d_H-i-s');
        $zipName = "site-backup-{$timestamp}.zip";
        $zipPath = $backupDir . '/' . $zipName;
        
        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            Toastr::error('Failed to create backup archive', 'Error');
            return redirect()->back();
        }
        
        // ── A. Export all database tables as SQL ──
        $tables = DB::select('SHOW TABLES');
        $dbName = DB::getDatabaseName();
        $sql = "-- Ecommerce3 Full Backup\n-- Date: {$timestamp}\n-- Database: {$dbName}\n\n";
        $sql .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";
        
        foreach ($tables as $table) {
            $tableName = reset($table);
            // Skip migrations table
            if ($tableName === 'migrations') continue;
            
            // Get CREATE TABLE statement
            $create = DB::select("SHOW CREATE TABLE `{$tableName}`");
            $sql .= "DROP TABLE IF EXISTS `{$tableName}`;\n";
            $sql .= $create[0]->{'Create Table'} . ";\n\n";
            
            // Get data as INSERT statements
            $rows = DB::table($tableName)->get();
            if ($rows->isNotEmpty()) {
                $sql .= "INSERT INTO `{$tableName}` VALUES\n";
                $values = [];
                foreach ($rows as $row) {
                    $vals = [];
                    foreach ((array) $row as $v) {
                        if (is_null($v)) {
                            $vals[] = 'NULL';
                        } else {
                            $vals[] = "'" . addslashes((string) $v) . "'";
                        }
                    }
                    $values[] = '(' . implode(', ', $vals) . ')';
                }
                $sql .= implode(",\n", $values) . ";\n\n";
            }
        }
        $sql .= "SET FOREIGN_KEY_CHECKS = 1;\n";
        $zip->addFromString('database.sql', $sql);
        
        // ── B. Add uploads directory ──
        $this->addDirToZip($zip, public_path('uploads'), 'uploads');
        
        // ── C. Add settings snapshot (JSON) ──
        $settings = [
            'general' => DB::table('general_settings')->first(),
            'seo' => DB::table('seo_settings')->first(),
            'themes' => DB::table('themes')->get(),
            'payment_gateways' => DB::table('payment_gateways')->get(),
            'sms_gateways' => DB::table('sms_gateways')->get(),
            'social_media' => DB::table('social_media')->get(),
        ];
        $zip->addFromString('settings.json', json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        // ── D. Add active layout snapshot ──
        $activeLayout = HomepageLayout::getActive();
        if ($activeLayout) {
            $layoutData = [
                'layout' => $activeLayout->toArray(),
                'sections' => $activeLayout->sections->map(function ($ls) {
                    return [
                        'section_slug' => $ls->section->slug,
                        'sort_order' => $ls->sort_order,
                        'is_visible' => $ls->is_visible,
                        'columns_config' => $ls->columns_config,
                        'extra_settings' => $ls->extra_settings,
                    ];
                }),
            ];
            $zip->addFromString('active-layout.json', json_encode($layoutData, JSON_PRETTY_PRINT));
        }
        
        $zip->close();
        
        // Store path for download
        session(['backup_file' => $zipName]);
        
        Toastr::success('Backup created! ' . round(filesize($zipPath) / 1048576, 1) . ' MB', 'Success');
        return redirect()->back();
    }
    
    public function downloadBackup()
    {
        $filename = session('backup_file');
        if (!$filename) {
            Toastr::error('No backup found. Create one first.', 'Error');
            return redirect()->back();
        }
        
        $path = storage_path('app/backups/' . $filename);
        if (!file_exists($path)) {
            Toastr::error('Backup file not found.', 'Error');
            return redirect()->back();
        }
        
        return response()->download($path);
    }
    
    // ============================================================
    // 2. FULL SITE RESTORE (upload ZIP → restore DB + files)
    // ============================================================
    public function restoreBackup(Request $request)
    {
        $request->validate([
            'backup_file' => 'required|file|mimes:zip|max:512000', // 500MB max
        ]);
        
        ini_set('max_execution_time', 600);
        
        $file = $request->file('backup_file');
        $tempDir = storage_path('app/backups/temp_' . time());
        mkdir($tempDir, 0755, true);
        
        try {
            $zip = new ZipArchive();
            if ($zip->open($file->getRealPath()) !== true) {
                throw new \Exception('Cannot open backup file');
            }
            $zip->extractTo($tempDir);
            $zip->close();
            
            // ── A. Restore database ──
            $sqlFile = $tempDir . '/database.sql';
            if (file_exists($sqlFile)) {
                // Split SQL by semicolons and execute
                $sql = file_get_contents($sqlFile);
                $statements = array_filter(
                    array_map('trim', explode(";\n", $sql)),
                    fn($s) => !empty($s)
                );
                
                DB::statement('SET FOREIGN_KEY_CHECKS = 0');
                foreach ($statements as $statement) {
                    if (!empty($statement) && !str_starts_with($statement, '--')) {
                        try {
                            DB::unprepared($statement);
                        } catch (\Exception $e) {
                            // Skip individual statement errors
                        }
                    }
                }
                DB::statement('SET FOREIGN_KEY_CHECKS = 1');
            }
            
            // ── B. Restore uploads ──
            $uploadsBackup = $tempDir . '/uploads';
            if (is_dir($uploadsBackup)) {
                $this->copyDir($uploadsBackup, public_path('uploads'));
            }
            
            // ── C. Restore settings from settings.json if DB restore missed them ──
            $settingsFile = $tempDir . '/settings.json';
            if (file_exists($settingsFile)) {
                $settings = json_decode(file_get_contents($settingsFile), true);
                if ($settings['general'] ?? null) {
                    DB::table('general_settings')->truncate();
                    DB::table('general_settings')->insert((array) $settings['general']);
                }
                // Restore other settings similarly if needed
            }
            
            Cache::flush();
            
            // Cleanup
            $this->deleteDir($tempDir);
            
            Toastr::success('Site restored successfully! Please refresh.', 'Success');
        } catch (\Exception $e) {
            $this->deleteDir($tempDir);
            Toastr::error('Restore failed: ' . $e->getMessage(), 'Error');
        }
        
        return redirect()->back();
    }
    
    // ============================================================
    // 3. THEME EXPORT / IMPORT (JSON)
    // ============================================================
    public function exportTheme()
    {
        $theme = DB::table('themes')->where('is_active', true)->first();
        $general = DB::table('general_settings')->select(
            'white_logo', 'dark_logo', 'favicon', 'copyright',
            'primary_color', 'secodery_color', 'footer_color'
        )->first();
        
        $data = [
            'type' => 'ecommerce3-theme-export',
            'version' => '1.0',
            'exported_at' => now()->toDateTimeString(),
            'theme' => $theme,
            'general' => $general,
        ];
        
        $filename = 'theme-export-' . date('Y-m-d') . '.json';
        
        return response()->streamDownload(function () use ($data) {
            echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }, $filename, ['Content-Type' => 'application/json']);
    }
    
    public function importTheme(Request $request)
    {
        $request->validate([
            'theme_file' => 'required|file|mimes:json|max:10240',
        ]);
        
        $file = $request->file('theme_file');
        $data = json_decode(file_get_contents($file->getRealPath()), true);
        
        if (!$data || ($data['type'] ?? '') !== 'ecommerce3-theme-export') {
            Toastr::error('Invalid theme export file!', 'Error');
            return redirect()->back();
        }
        
        // Update theme
        if ($data['theme'] ?? null) {
            DB::table('themes')->update(['is_active' => false]);
            $themeData = $data['theme'];
            unset($themeData['id']);
            DB::table('themes')->insert($themeData);
        }
        
        // Update general settings colors/logos
        if ($data['general'] ?? null) {
            $g = $data['general'];
            DB::table('general_settings')->update([
                'white_logo' => $g['white_logo'] ?? null,
                'dark_logo' => $g['dark_logo'] ?? null,
                'favicon' => $g['favicon'] ?? null,
                'primary_color' => $g['primary_color'] ?? null,
                'secodery_color' => $g['secodery_color'] ?? null,
                'footer_color' => $g['footer_color'] ?? null,
            ]);
        }
        
        Cache::flush();
        Toastr::success('Theme imported successfully!', 'Success');
        return redirect()->back();
    }
    
    // ============================================================
    // 4. LAYOUT BACKUP / RESTORE
    // ============================================================
    public function exportLayout(Request $request)
    {
        $request->validate(['layout_id' => 'required|exists:homepage_layouts,id']);
        
        $layout = HomepageLayout::with(['sections.section'])->findOrFail($request->layout_id);
        
        $data = [
            'type' => 'ecommerce3-layout-export',
            'version' => '1.0',
            'exported_at' => now()->toDateTimeString(),
            'layout' => [
                'name' => $layout->name,
                'description' => $layout->description,
                'is_default' => $layout->is_default,
            ],
            'sections' => $layout->sections->map(function ($ls) {
                return [
                    'slug' => $ls->section->slug,
                    'sort_order' => $ls->sort_order,
                    'is_visible' => $ls->is_visible,
                    'columns_config' => $ls->columns_config,
                    'extra_settings' => $ls->extra_settings,
                ];
            }),
        ];
        
        $filename = 'layout-' . Str::slug($layout->name) . '-' . date('Y-m-d') . '.json';
        
        return response()->streamDownload(function () use ($data) {
            echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }, $filename, ['Content-Type' => 'application/json']);
    }
    
    public function importLayout(Request $request)
    {
        $request->validate([
            'layout_file' => 'required|file|mimes:json|max:10240',
        ]);
        
        $file = $request->file('layout_file');
        $data = json_decode(file_get_contents($file->getRealPath()), true);
        
        if (!$data || ($data['type'] ?? '') !== 'ecommerce3-layout-export') {
            Toastr::error('Invalid layout export file!', 'Error');
            return redirect()->back();
        }
        
        // Create layout
        $layout = HomepageLayout::create([
            'name' => ($data['layout']['name'] ?? 'Imported Layout') . ' (Imported)',
            'description' => $data['layout']['description'] ?? '',
            'is_active' => false,
            'is_default' => false,
            'created_by' => auth()->guard('admin')->id(),
        ]);
        
        // Add sections
        foreach ($data['sections'] ?? [] as $sec) {
            $section = HomepageSection::where('slug', $sec['slug'])->first();
            if ($section) {
                HomepageLayoutSection::create([
                    'layout_id' => $layout->id,
                    'section_id' => $section->id,
                    'sort_order' => $sec['sort_order'],
                    'is_visible' => $sec['is_visible'] ?? true,
                    'columns_config' => $sec['columns_config'] ?? 'col-sm-12',
                    'extra_settings' => $sec['extra_settings'] ?? null,
                ]);
            }
        }
        
        Cache::forget('frontend_homepage_v1');
        Toastr::success("Layout '{$layout->name}' imported! Activate it from the Layouts page.", 'Success');
        return redirect()->route('layouts.index');
    }
    
    // ============================================================
    // 5. PRESET ZIP DOWNLOAD
    // ============================================================
    public function downloadPreset($slug)
    {
        $presets = PresetData::all();
        if (!isset($presets[$slug])) {
            Toastr::error('Invalid preset!', 'Error');
            return redirect()->back();
        }
        
        $zipPath = storage_path("app/demo-presets/{$slug}.zip");
        if (!file_exists($zipPath)) {
            // Create zip on-the-fly
            $presetDir = storage_path("app/demo-presets/{$slug}");
            if (!is_dir($presetDir)) {
                Toastr::error('Preset files not found!', 'Error');
                return redirect()->back();
            }
            
            $zip = new ZipArchive();
            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
                $this->addDirToZip($zip, $presetDir, '');
                $zip->close();
            }
        }
        
        return response()->download($zipPath, "{$slug}.zip");
    }
    
    // ============================================================
    // BACKUP LIST & MANAGEMENT
    // ============================================================
    public function index()
    {
        $backupDir = storage_path('app/backups');
        $backups = [];
        
        if (is_dir($backupDir)) {
            foreach (array_diff(scandir($backupDir), ['.', '..']) as $file) {
                if (str_ends_with($file, '.zip')) {
                    $path = $backupDir . '/' . $file;
                    $backups[] = [
                        'filename' => $file,
                        'size' => $this->formatBytes(filesize($path)),
                        'date' => date('Y-m-d H:i:s', filemtime($path)),
                    ];
                }
            }
        }
        
        rsort($backups); // newest first
        
        $presets = PresetData::all();
        $layouts = HomepageLayout::withCount('sections')->orderBy('name')->get();
        
        return view('backEnd.backup.index', compact('backups', 'presets', 'layouts'));
    }
    
    public function deleteBackup($filename)
    {
        $path = storage_path('app/backups/' . basename($filename));
        if (file_exists($path)) {
            unlink($path);
            Toastr::success('Backup deleted!', 'Success');
        }
        return redirect()->back();
    }
    
    // ============================================================
    // HELPERS
    // ============================================================
    private function addDirToZip(ZipArchive $zip, string $dir, string $prefix): void
    {
        if (!is_dir($dir)) return;
        
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );
        
        foreach ($files as $file) {
            $filePath = $file->getRealPath();
            $relativePath = $prefix . '/' . \Illuminate\Support\Str::after($filePath, $dir . '/');
            $zip->addFile($filePath, $relativePath);
        }
    }
    
    private function copyDir(string $src, string $dst): void
    {
        if (!is_dir($src)) return;
        if (!is_dir($dst)) mkdir($dst, 0755, true);
        
        $dir = opendir($src);
        while (($file = readdir($dir)) !== false) {
            if ($file === '.' || $file === '..') continue;
            $srcPath = $src . '/' . $file;
            $dstPath = $dst . '/' . $file;
            if (is_dir($srcPath)) {
                $this->copyDir($srcPath, $dstPath);
            } else {
                copy($srcPath, $dstPath);
            }
        }
        closedir($dir);
    }
    
    private function deleteDir(string $dir): void
    {
        if (!is_dir($dir)) return;
        $it = new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS);
        $files = new \RecursiveIteratorIterator($it, \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($files as $f) {
            if ($f->isDir()) rmdir($f->getRealPath());
            else unlink($f->getRealPath());
        }
        rmdir($dir);
    }
    
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 1) . ' ' . $units[$i];
    }
}
