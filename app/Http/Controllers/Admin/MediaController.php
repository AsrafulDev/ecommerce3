<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Toastr;

class MediaController extends Controller
{
    protected array $imageExt = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg', 'avif'];
    protected array $allowedExt = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg', 'avif', 'pdf'];
    protected array $allowedMime = [
        'jpg' => ['image/jpeg'], 'jpeg' => ['image/jpeg'], 'png' => ['image/png'],
        'gif' => ['image/gif'], 'webp' => ['image/webp'], 'bmp' => ['image/bmp'],
        'svg' => ['image/svg+xml'], 'avif' => ['image/avif'], 'pdf' => ['application/pdf'],
    ];

    protected function root(): string
    {
        $root = public_path('uploads/media');
        if (!is_dir($root)) mkdir($root, 0775, true);
        return $root;
    }

    protected function relative(?string $path): string
    {
        $path = str_replace('\\', '/', trim((string) $path));
        if ($path === '' || $path === '.') return '';
        if (str_starts_with($path, '/') || preg_match('/^[A-Za-z]:\//', $path)) abort(403, 'Invalid media path.');
        $parts = array_values(array_filter(explode('/', $path), fn ($part) => $part !== '' && $part !== '.'));
        if (in_array('..', $parts, true)) abort(403, 'Invalid media path.');
        return implode('/', $parts);
    }

    protected function absolute(?string $path): string
    {
        $relative = $this->relative($path);
        $root = realpath($this->root()) ?: $this->root();
        $full = $relative ? $root . '/' . $relative : $root;
        $resolved = realpath($full);
        if ($resolved !== false && !str_starts_with($resolved, rtrim($root, '/') . '/') && $resolved !== $root) {
            abort(403, 'Invalid media path.');
        }
        return $full;
    }

    protected function name(string $name): string
    {
        $name = str_replace(['\\', '/', "\0", '..'], '', trim($name));
        $name = preg_replace('/[^\p{L}\p{N} _\.\-()@]/u', '', $name) ?? '';
        $name = preg_replace('/\.(php\d*|phtml|html?|js|sh|exe|bat|cmd|pl|py)$/i', '', $name) ?? '';
        return trim($name, " .\t\n\r");
    }

    protected function allowed(string $file): bool
    {
        return in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), $this->allowedExt, true);
    }

    protected function image(string $file): bool
    {
        return in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), $this->imageExt, true);
    }

    protected function mimeAllowed(string $ext, ?string $mime): bool
    {
        if (!$mime || !isset($this->allowedMime[$ext])) return false;
        foreach ($this->allowedMime[$ext] as $allowed) if (stripos($mime, $allowed) !== false) return true;
        return false;
    }

    protected function unique(string $dir, string $name): string
    {
        $candidate = $name;
        $base = pathinfo($name, PATHINFO_FILENAME);
        $ext = pathinfo($name, PATHINFO_EXTENSION);
        $i = 1;
        while (file_exists($dir . '/' . $candidate)) {
            $candidate = $base . '-' . $i++ . ($ext ? '.' . $ext : '');
        }
        return $candidate;
    }

    protected function deleteTree(string $path): bool
    {
        if (!file_exists($path)) return true;
        if (!is_dir($path)) return @unlink($path);
        foreach (scandir($path) as $item) {
            if ($item === '.' || $item === '..') continue;
            if (!$this->deleteTree($path . '/' . $item)) return false;
        }
        return @rmdir($path);
    }

    protected function copyTree(string $source, string $destination): bool
    {
        if (is_dir($source)) {
            if (!is_dir($destination) && !mkdir($destination, 0775, true)) return false;
            foreach (scandir($source) as $item) {
                if ($item === '.' || $item === '..') continue;
                if (!$this->copyTree($source . '/' . $item, $destination . '/' . $item)) return false;
            }
            return true;
        }
        return copy($source, $destination);
    }

    protected function humanSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB']; $size = (float) $bytes; $i = 0;
        while ($size >= 1024 && $i < 3) { $size /= 1024; $i++; }
        return round($size, 1) . ' ' . $units[$i];
    }

    public function index(Request $request)
    {
        $path = $this->relative($request->query('path'));
        $directory = $this->absolute($path);
        if (!is_dir($directory)) return redirect()->route('admin.media.index')->with('error', 'Folder not found.');
        $folders = []; $files = [];
        foreach (scandir($directory) as $item) {
            if ($item === '.' || $item === '..' || str_starts_with($item, '.')) continue;
            $full = $directory . '/' . $item;
            $relative = $path ? $path . '/' . $item : $item;
            if (is_dir($full)) {
                $count = count(array_filter(scandir($full), fn ($entry) => $entry !== '.' && $entry !== '..' && !str_starts_with($entry, '.')));
                $folders[] = ['name' => $item, 'path' => $relative, 'count' => $count];
            } elseif (is_file($full) && $this->allowed($item)) {
                $files[] = ['name' => $item, 'path' => $relative, 'ext' => strtolower(pathinfo($item, PATHINFO_EXTENSION)), 'is_image' => $this->image($item), 'size' => $this->humanSize(filesize($full)), 'modified' => date('Y-m-d H:i', filemtime($full)), 'url' => asset('public/uploads/media/' . $relative)];
            }
        }
        usort($folders, fn ($a, $b) => strcmp($a['name'], $b['name']));
        usort($files, fn ($a, $b) => strcmp($a['name'], $b['name']));
        $breadcrumbs = []; $segment = '';
        foreach ($path ? explode('/', $path) : [] as $part) {
            $segment = $segment ? $segment . '/' . $part : $part;
            $breadcrumbs[] = ['name' => $part, 'path' => $segment];
        }
        return view('backEnd.media.index', compact('path', 'folders', 'files', 'breadcrumbs'));
    }

    public function createFolder(Request $request)
    {
        $request->validate(['folder_name' => 'required|string|max:100']);
        $parent = $this->relative($request->input('path')); $name = $this->name($request->input('folder_name'));
        if ($name === '') return back()->with('error', 'Invalid folder name.');
        $path = $this->absolute($parent ? $parent . '/' . $name : $name);
        if (is_dir($path) || !mkdir($path, 0775, true)) return back()->with('error', 'Could not create folder.');
        return back()->with('success', 'Folder created successfully.');
    }

    public function renameFolder(Request $request)
    {
        $request->validate(['path' => 'required|string', 'new_name' => 'required|string|max:100']);
        $path = $this->relative($request->input('path')); $new = $this->name($request->input('new_name'));
        if ($path === '' || $new === '') return back()->with('error', 'Invalid folder name.');
        $old = $this->absolute($path); $parent = dirname($path) === '.' ? '' : dirname($path); $target = $this->absolute($parent ? $parent . '/' . $new : $new);
        if (!is_dir($old) || file_exists($target) || !rename($old, $target)) return back()->with('error', 'Could not rename folder.');
        return back()->with('success', 'Folder renamed successfully.');
    }

    public function deleteFolder(Request $request)
    {
        $request->validate(['path' => 'required|string']); $path = $this->relative($request->input('path'));
        if ($path === '' || !$this->deleteTree($this->absolute($path))) return back()->with('error', 'Could not delete folder.');
        return back()->with('success', 'Folder deleted successfully.');
    }

    public function upload(Request $request)
    {
        $request->validate(['path' => 'nullable|string', 'files' => 'required|array', 'files.*' => 'file|max:51200']);
        $directory = $this->absolute($request->input('path')); if (!is_dir($directory)) return back()->with('error', 'Folder not found.');
        $uploaded = 0;
        foreach ($request->file('files', []) as $file) {
            $ext = strtolower($file->getClientOriginalExtension());
            if (!$this->allowed($file->getClientOriginalName()) || !$this->mimeAllowed($ext, $file->getMimeType())) continue;
            $name = $this->unique($directory, $this->name($file->getClientOriginalName()));
            $file->move($directory, $name); $uploaded++;
        }
        return back()->with($uploaded ? 'success' : 'error', $uploaded ? $uploaded . ' file(s) uploaded.' : 'No allowed files were uploaded.');
    }

    public function renameFile(Request $request)
    {
        $request->validate(['path' => 'required|string', 'new_name' => 'required|string|max:150', 'kind' => 'nullable|string']);
        $path = $this->relative($request->input('path')); $old = $this->absolute($path); $new = $this->name($request->input('new_name'));
        if (!is_file($old) && !is_dir($old)) return back()->with('error', 'Item not found.');
        if (is_file($old)) { $ext = pathinfo($path, PATHINFO_EXTENSION); $new = pathinfo($new, PATHINFO_EXTENSION) ? pathinfo($new, PATHINFO_FILENAME) . '.' . $ext : $new . ($ext ? '.' . $ext : ''); }
        $parent = dirname($path) === '.' ? '' : dirname($path); $target = $this->absolute($parent ? $parent . '/' . $new : $new);
        if ($new === '' || file_exists($target) || !rename($old, $target)) return back()->with('error', 'Could not rename item.');
        return back()->with('success', 'Item renamed successfully.');
    }

    public function deleteFile(Request $request)
    {
        $request->validate(['path' => 'required|string']); $path = $this->relative($request->input('path')); $file = $this->absolute($path);
        if (!is_file($file) || !$this->allowed($file) || !unlink($file)) return back()->with('error', 'Could not delete file.');
        return back()->with('success', 'File deleted successfully.');
    }

    protected function transfer(Request $request, bool $copy)
    {
        $request->validate(['items' => 'required|array', 'target' => 'nullable|string']);
        $target = $this->absolute($request->input('target')); if (!is_dir($target)) return back()->with('error', 'Destination folder not found.');
        foreach ($request->input('items') as $item) {
            $relative = $this->relative($item); $source = $this->absolute($relative); if (!file_exists($source)) continue;
            $name = basename($relative); $destination = $target . '/' . $this->unique($target, $name);
            if ($copy) $this->copyTree($source, $destination); else rename($source, $destination);
        }
        return back()->with('success', $copy ? 'Items copied successfully.' : 'Items moved successfully.');
    }

    public function move(Request $request) { return $this->transfer($request, $request->input('action') === 'copy'); }
    public function copy(Request $request) { return $this->transfer($request, true); }

    public function pickerContent(Request $request)
    {
        $path = $this->relative($request->query('path')); $directory = $this->absolute($path); $files = [];
        if (is_dir($directory)) foreach (scandir($directory) as $item) {
            if ($item === '.' || $item === '..' || str_starts_with($item, '.') || !is_file($directory . '/' . $item) || !$this->allowed($item)) continue;
            $relative = $path ? $path . '/' . $item : $item;
            $files[] = ['name' => $item, 'path' => $relative, 'is_image' => $this->image($item), 'url' => asset('public/uploads/media/' . $relative)];
        }
        return view('backEnd.media._picker_content', compact('files', 'path'));
    }

    public function pickerUpload(Request $request)
    {
        $request->validate(['path' => 'nullable|string', 'files' => 'required|array', 'files.*' => 'file|max:51200']);
        $directory = $this->absolute($request->input('path')); $files = [];
        foreach ($request->file('files', []) as $file) {
            $ext = strtolower($file->getClientOriginalExtension());
            if (!$this->allowed($file->getClientOriginalName()) || !$this->mimeAllowed($ext, $file->getMimeType())) continue;
            $name = $this->unique($directory, $this->name($file->getClientOriginalName())); $file->move($directory, $name);
            $relative = $this->relative($request->input('path'));
            $files[] = ['name' => $name, 'path' => $relative ? $relative . '/' . $name : $name, 'url' => asset('public/uploads/media/' . ($relative ? $relative . '/' . $name : $name)), 'is_image' => $this->image($name)];
        }
        return response()->json(['ok' => true, 'files' => $files]);
    }
}
