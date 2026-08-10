<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Toastr;

/**
 * Media Gallery — folder-wise file manager.
 *
 * - Works directly on the filesystem under public/uploads/media/
 * - Folder create / rename / delete, file upload / rename / delete
 * - Move & copy files/folders between folders
 * - Copy live URL of any file
 * - Reusable "picker" (backEnd.media._picker) usable in ANY admin form
 *   wherever an image is needed.
 * - SECURITY: only images + PDF are allowed (extension AND mime checked);
 *   path traversal is blocked; hidden/system files are skipped.
 */
class MediaController extends Controller
{
    /** Image extensions (previewable). */
    protected array $imageExt = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg', 'avif'];

    /** All allowed extensions — images + PDF only (security). */
    protected array $allowedExt = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg', 'avif', 'pdf'];

    /** Mime prefixes allowed per extension (defense in depth). */
    protected array $allowedMime = [
        'jpg'  => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png'  => ['image/png'],
        'gif'  => ['image/gif'],
        'webp' => ['image/webp'],
        'bmp'  => ['image/bmp'],
        'svg'  => ['image/svg+xml'],
        'avif' => ['image/avif'],
        'pdf'  => ['application/pdf'],
    ];

    /** Absolute path to the media root directory. */
    protected function mediaRoot(): string
    {
        return public_path('uploads/media');
    }

    /**
     * Normalize + sanitize a relative media path.
     * Returns '' for the root. Blocks traversal & absolute paths (403).
     */
    protected function resolvePath(?string $path = null): string
    {
        $path = trim((string) $path);
        $path = str_replace('\\', '/', $path);

        // Absolute paths are never allowed
        if (str_starts_with($path, '/') || preg_match('/^[A-Za-z]:\//', $path)) {
            abort(403, 'Invalid media path.');
        }

        $parts = array_values(array_filter(explode('/', $path), fn ($p) => $p !== '' && $p !== '.'));

        // Path traversal guard
        if (in_array('..', $parts, true)) {
            abort(403, 'Invalid media path.');
        }

        return implode('/', $parts);
    }

    /** Return the absolute filesystem path for a relative media path (traversal-safe). */
    protected function absPath(?string $rel): string
    {
        $rel   = $this->resolvePath($rel);
        $root  = rtrim($this->mediaRoot(), '/');
        $full  = $rel === '' ? $root : $root.'/'.$rel;

        $realRoot = realpath($root) ?: $root;
        $real     = realpath($full);

        if ($real !== false && strpos($real, $realRoot) !== 0) {
            abort(403, 'Invalid media path.');
        }

        return $full;
    }

    /** Security: is this extension allowed (image or PDF)? */
    protected function isAllowedExt(string $name): bool
    {
        return in_array(strtolower(pathinfo($name, PATHINFO_EXTENSION)), $this->allowedExt, true);
    }

    /** Is this a previewable image? */
    protected function isImageExt(string $name): bool
    {
        return in_array(strtolower(pathinfo($name, PATHINFO_EXTENSION)), $this->imageExt, true);
    }

    /** Security: sanitize a user-provided name (blocks traversal & weird chars). */
    protected function sanitizeName(string $name): string
    {
        $name = trim($name);
        // Block path separators & null bytes
        $name = str_replace(['\\', '/', "\0"], '', $name);
        // Keep only letters, numbers, spaces and safe punctuation
        $name = preg_replace('/[^\p{L}\p{N} _.\-()@]/u', '', $name) ?? $name;
        // Remove the two-dot traversal marker anywhere in the string
        $name = str_replace('..', '', $name);
        // Never allow a name that could resolve to a script extension
        $name = preg_replace('/\.(php\d*|phtml|html?|js|sh|exe|bat|cmd|pl|py)$/i', '', $name) ?? $name;
        return trim($name, " .\t\n\r");
    }

    /** Build a unique file name in a directory (appends -1, -2, ...). */
    protected function uniqueName(string $dir, string $name): string
    {
        if (!file_exists($dir.'/'.$name)) {
            return $name;
        }
        $ext  = pathinfo($name, PATHINFO_EXTENSION);
        $base = pathinfo($name, PATHINFO_FILENAME);
        $i    = 1;
        while (file_exists($dir.'/'.$base.'-'.$i.($ext ? '.'.$ext : ''))) {
            $i++;
        }
        return $base.'-'.$i.($ext ? '.'.$ext : '');
    }

    /** Verify uploaded file MIME matches the claimed extension (security). */
    protected function safeMime(string $ext, ?string $mime): bool
    {
        $ext = strtolower($ext);
        if (!isset($this->allowedMime[$ext])) {
            return false;
        }
        if (!$mime) {
            return false;
        }
        // allow a couple of common aliases
        foreach ($this->allowedMime[$ext] as $ok) {
            if (stripos($mime, $ok) !== false) {
                return true;
            }
        }
        return false;
    }

    /** Recursively delete a directory. */
    protected function deleteTree(string $dir): bool
    {
        if (!file_exists($dir)) {
            return true;
        }
        if (!is_dir($dir)) {
            return @unlink($dir);
        }
        foreach (scandir($dir) as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            if (!$this->deleteTree($dir.'/'.$item)) {
                return false;
            }
        }
        return @rmdir($dir);
    }

    /* ------------------------------------------------------------------
     |  Main gallery page
     | ------------------------------------------------------------------ */

    public function index(Request $request)
    {
        $path = $this->resolvePath($request->query('path'));
        $abs  = $this->absPath($path);

        if (!is_dir($abs)) {
            Toastr::error('Folder not found.', 'Error');
            return redirect()->route('admin.media.index');
        }

        $folders = [];
        $files   = [];

        foreach (scandir($abs) as $item) {
            if ($item === '.' || $item === '..' || str_starts_with($item, '.')) {
                continue;
            }
            $full = $abs.'/'.$item;
            $rel  = $path ? $path.'/'.$item : $item;

            if (is_dir($full)) {
                $count  = 0;
                foreach (scandir($full) as $sub) {
                    if ($sub !== '.' && $sub !== '..' && !str_starts_with($sub, '.')) {
                        $count++;
                    }
                }
                $folders[] = [
                    'name'  => $item,
                    'path'  => $rel,
                    'count' => $count,
                ];
            } elseif (is_file($full) && $this->isAllowedExt($item)) {
                $files[] = [
                    'name'     => $item,
                    'path'     => $rel,
                    'ext'      => strtolower(pathinfo($item, PATHINFO_EXTENSION)),
                    'is_image' => $this->isImageExt($item),
                    'size'     => $this->humanSize(filesize($full)),
                    'url'      => asset('public/uploads/media/'.$rel),
                    'modified' => date('Y-m-d H:i', filemtime($full)),
                ];
            }
        }

        usort($folders, fn ($a, $b) => strcmp($a['name'], $b['name']));
        usort($files, fn ($a, $b) => strcmp($a['name'], $b['name']));

        // Breadcrumbs
        $breadcrumbs = [];
        if ($path) {
            $seg = '';
            foreach (explode('/', $path) as $part) {
                $seg          = $seg ? $seg.'/'.$part : $part;
                $breadcrumbs[] = ['name' => $part, 'path' => $seg];
            }
        }

        // Available folders (for move/copy dropdown) — all folders under root
        $allFolders = $this->listAllFolders();

        return view('backEnd.media.index', compact('path', 'folders', 'files', 'breadcrumbs', 'allFolders'));
    }

    /** Recursively list every folder (relative path) under the media root. */
    protected function listAllFolders(string $rel = ''): array
    {
        $abs     = $this->absPath($rel);
        $folders = [];
        foreach (scandir($abs) as $item) {
            if ($item === '.' || $item === '..' || str_starts_with($item, '.')) {
                continue;
            }
            $full = $abs.'/'.$item;
            if (is_dir($full)) {
                $childRel = $rel ? $rel.'/'.$item : $item;
                $folders[] = $childRel;
                $folders = array_merge($folders, $this->listAllFolders($childRel));
            }
        }
        return $folders;
    }

    protected function humanSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i     = 0;
        $size  = (float) $bytes;
        while ($size >= 1024 && $i < count($units) - 1) {
            $size /= 1024;
            $i++;
        }
        return round($size, 1).' '.$units[$i];
    }

    /* ------------------------------------------------------------------
     |  Folder operations
     | ------------------------------------------------------------------ */

    public function createFolder(Request $request)
    {
        $request->validate(['folder_name' => 'required|string|max:100']);
        $parent = $this->resolvePath($request->input('path'));
        $name   = $this->sanitizeName($request->input('folder_name'));

        if ($name === '') {
            Toastr::error('Folder name is invalid.', 'Error');
            return back();
        }

        $dir = $this->absPath($parent ? $parent.'/'.$name : $name);

        if (is_dir($dir)) {
            Toastr::error('A folder with this name already exists.', 'Error');
            return back();
        }

        if (!mkdir($dir, 0775, true)) {
            Toastr::error('Could not create folder.', 'Error');
            return back();
        }

        Toastr::success('Folder created successfully.', 'Success');
        return back();
    }

    public function renameFolder(Request $request)
    {
        $request->validate([
            'path'     => 'required|string',
            'new_name' => 'required|string|max:100',
        ]);

        $path    = $this->resolvePath($request->input('path'));
        $newName = $this->sanitizeName($request->input('new_name'));

        if ($path === '' || $newName === '') {
            Toastr::error('Invalid folder or name.', 'Error');
            return back();
        }

        $parent = dirname($path) === '.' ? '' : dirname($path);
        $oldAbs = $this->absPath($path);
        $newAbs = $this->absPath($parent ? $parent.'/'.$newName : $newName);

        if (!is_dir($oldAbs)) {
            Toastr::error('Folder not found.', 'Error');
            return back();
        }
        if (is_dir($newAbs)) {
            Toastr::error('A folder with this name already exists.', 'Error');
            return back();
        }

        rename($oldAbs, $newAbs);
        Toastr::success('Folder renamed successfully.', 'Success');
        return back();
    }

    public function deleteFolder(Request $request)
    {
        $request->validate(['path' => 'required|string']);
        $path = $this->resolvePath($request->input('path'));

        if ($path === '') {
            Toastr::error('Invalid folder.', 'Error');
            return back();
        }

        $abs = $this->absPath($path);

        if (!is_dir($abs)) {
            Toastr::error('Folder not found.', 'Error');
            return back();
        }

        $this->deleteTree($abs);
        Toastr::success('Folder deleted successfully.', 'Success');
        return back();
    }

    /* ------------------------------------------------------------------
     |  File operations
     | ------------------------------------------------------------------ */

    public function upload(Request $request)
    {
        $request->validate(['files' => 'required', 'files.*' => 'file']);

        $dir = $this->resolvePath($request->input('path'));
        $abs = $this->absPath($dir);

        if (!is_dir($abs)) {
            Toastr::error('Folder not found.', 'Error');
            return back();
        }

        $uploaded = 0;
        $skipped  = 0;

        foreach ((array) $request->file('files', []) as $file) {
            if (!$file || !$file->isValid()) {
                $skipped++;
                continue;
            }
            $ext = strtolower($file->getClientOriginalExtension());

            // Security: extension must be image/PDF
            if (!in_array($ext, $this->allowedExt, true)) {
                $skipped++;
                continue;
            }
            // Security: real MIME must match
            if (!$this->safeMime($ext, $file->getMimeType())) {
                $skipped++;
                continue;
            }

            $name = $this->uniqueName($abs, $this->sanitizeName($file->getClientOriginalName()));
            try {
                $file->move($abs, $name);
                $uploaded++;
            } catch (\Throwable $e) {
                $skipped++;
            }
        }

        if ($uploaded > 0) {
            Toastr::success($uploaded.' file(s) uploaded successfully.', 'Success');
        }
        if ($skipped > 0) {
            Toastr::warning($skipped.' file(s) skipped (only images & PDF are allowed).', 'Notice');
        }
        if ($uploaded === 0 && $skipped === 0) {
            Toastr::error('No file selected.', 'Error');
        }

        return back();
    }

    public function renameFile(Request $request)
    {
        $request->validate([
            'path'     => 'required|string',
            'new_name' => 'required|string|max:150',
        ]);

        $path    = $this->resolvePath($request->input('path'));
        $newName = $this->sanitizeName($request->input('new_name'));

        if ($path === '' || $newName === '') {
            Toastr::error('Invalid file or name.', 'Error');
            return back();
        }

        $oldExt = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $newExt = strtolower(pathinfo($newName, PATHINFO_EXTENSION));

        // Keep the original extension unless user typed a new allowed one
        if (!in_array($newExt, $this->allowedExt, true)) {
            $newName .= '.'.$oldExt;
        } else {
            // keep original ext when user only changed the base name
            $base  = pathinfo($newName, PATHINFO_FILENAME);
            $newName = $base.'.'.$oldExt;
        }

        $oldAbs = $this->absPath($path);

        if (!is_file($oldAbs)) {
            Toastr::error('File not found.', 'Error');
            return back();
        }

        $parent = dirname($path) === '.' ? '' : dirname($path);
        $newAbs = $this->absPath($parent ? $parent.'/'.$newName : $newName);

        if (file_exists($newAbs)) {
            Toastr::error('A file with this name already exists.', 'Error');
            return back();
        }

        rename($oldAbs, $newAbs);
        Toastr::success('File renamed successfully.', 'Success');
        return back();
    }

    public function deleteFile(Request $request)
    {
        $request->validate(['path' => 'required|string']);
        $path = $this->resolvePath($request->input('path'));

        if ($path === '') {
            Toastr::error('Invalid file.', 'Error');
            return back();
        }

        $abs = $this->absPath($path);

        if (!is_file($abs)) {
            Toastr::error('File not found.', 'Error');
            return back();
        }

        @unlink($abs);
        Toastr::success('File deleted successfully.', 'Success');
        return back();
    }

    /* ------------------------------------------------------------------
     |  Move / Copy
     | ------------------------------------------------------------------ */

    /** Move selected files and/or folders to a destination folder. */
    public function move(Request $request)
    {
        $request->validate(['target' => 'required|string']);

        $items   = (array) $request->input('items', []);
        $target  = $this->resolvePath($request->input('target'));
        $targetAbs = $this->absPath($target);

        if (empty($items)) {
            Toastr::error('No item selected.', 'Error');
            return back();
        }
        if (!is_dir($targetAbs)) {
            Toastr::error('Destination folder not found.', 'Error');
            return back();
        }

        $moved = 0;
        foreach ($items as $rel) {
            $rel = $this->resolvePath($rel);
            if ($rel === '') {
                continue;
            }
            $srcAbs = $this->absPath($rel);
            $name   = basename($rel);

            // Prevent moving a folder into itself or its own descendant
            if ($target !== '' && str_starts_with($target.'/', $rel.'/')) {
                continue;
            }

            if (!file_exists($srcAbs)) {
                continue;
            }

            $destAbs = $targetAbs.'/'.$name;
            if (file_exists($destAbs)) {
                continue;
            }

            if (rename($srcAbs, $destAbs)) {
                $moved++;
            }
        }

        Toastr::success($moved.' item(s) moved successfully.', 'Success');
        return back();
    }

    /** Copy selected files/folders into a destination folder. */
    public function copy(Request $request)
    {
        $request->validate(['target' => 'required|string']);

        $items   = (array) $request->input('items', []);
        $target  = $this->resolvePath($request->input('target'));
        $targetAbs = $this->absPath($target);

        if (empty($items)) {
            Toastr::error('No item selected.', 'Error');
            return back();
        }
        if (!is_dir($targetAbs)) {
            Toastr::error('Destination folder not found.', 'Error');
            return back();
        }

        $copied = 0;
        foreach ($items as $rel) {
            $rel = $this->resolvePath($rel);
            if ($rel === '') {
                continue;
            }
            $srcAbs = $this->absPath($rel);
            $name   = basename($rel);

            if ($target !== '' && str_starts_with($target.'/', $rel.'/')) {
                continue;
            }

            if (!file_exists($srcAbs)) {
                continue;
            }

            $destAbs = $targetAbs.'/'.$this->uniqueName($targetAbs, $name);
            if (is_dir($srcAbs)) {
                if ($this->copyTree($srcAbs, $destAbs)) {
                    $copied++;
                }
            } elseif (copy($srcAbs, $destAbs)) {
                $copied++;
            }
        }

        Toastr::success($copied.' item(s) copied successfully.', 'Success');
        return back();
    }

    /** Recursively copy a directory. */
    protected function copyTree(string $src, string $dst): bool
    {
        if (!is_dir($dst)) {
            if (!mkdir($dst, 0775, true)) {
                return false;
            }
        }
        foreach (scandir($src) as $item) {
            if ($item === '.' || $item === '..' || str_starts_with($item, '.')) {
                continue;
            }
            $s = $src.'/'.$item;
            $d = $dst.'/'.$item;
            if (is_dir($s)) {
                if (!$this->copyTree($s, $d)) {
                    return false;
                }
            } elseif (!copy($s, $d)) {
                return false;
            }
        }
        return true;
    }

    /* ------------------------------------------------------------------
     |  Reusable picker (for any admin form)
     | ------------------------------------------------------------------ */

    /** Return the picker modal content (folders + files) for a path — AJAX. */
    public function pickerContent(Request $request)
    {
        $path = $this->resolvePath($request->query('path'));
        $abs  = $this->absPath($path);

        $folders = [];
        $files   = [];

        if (is_dir($abs)) {
            foreach (scandir($abs) as $item) {
                if ($item === '.' || $item === '..' || str_starts_with($item, '.')) {
                    continue;
                }
                $full = $abs.'/'.$item;
                $rel  = $path ? $path.'/'.$item : $item;
                if (is_dir($full)) {
                    $folders[] = ['name' => $item, 'path' => $rel];
                } elseif (is_file($full) && $this->isAllowedExt($item)) {
                    $files[] = [
                        'name'     => $item,
                        'path'     => $rel,
                        'ext'      => strtolower(pathinfo($item, PATHINFO_EXTENSION)),
                        'is_image' => $this->isImageExt($item),
                        'url'      => asset('public/uploads/media/'.$rel),
                        'rel'      => 'public/uploads/media/'.$rel,
                    ];
                }
            }
        }

        usort($folders, fn ($a, $b) => strcmp($a['name'], $b['name']));
        usort($files, fn ($a, $b) => strcmp($a['name'], $b['name']));

        return view('backEnd.media._picker_content', compact('path', 'folders', 'files'));
    }

    /** Upload files from inside the picker modal (AJAX). */
    public function pickerUpload(Request $request)
    {
        $request->validate(['files' => 'required', 'files.*' => 'file']);

        $dir = $this->resolvePath($request->input('path'));
        $abs = $this->absPath($dir);

        $uploaded = 0;
        if (is_dir($abs)) {
            foreach ((array) $request->file('files', []) as $file) {
                if (!$file || !$file->isValid()) {
                    continue;
                }
                $ext = strtolower($file->getClientOriginalExtension());
                if (!in_array($ext, $this->allowedExt, true) || !$this->safeMime($ext, $file->getMimeType())) {
                    continue;
                }
                $name = $this->uniqueName($abs, $this->sanitizeName($file->getClientOriginalName()));
                try {
                    $file->move($abs, $name);
                    $uploaded++;
                } catch (\Throwable $e) {
                    // ignore
                }
            }
        }

        return response()->json(['ok' => true, 'uploaded' => $uploaded, 'path' => $dir]);
    }
}
