# 📁 Media Gallery (Media Library)

A **folder-wise** image & PDF manager for the admin panel. Files are stored on the
filesystem under `public/uploads/media/` and served with the same `asset()` path
convention used by every other module (e.g. banners store `public/uploads/banner/...`).

## Features

- **Folder-wise view** — navigate folders directly (breadcrumb path).
- **Create / Rename / Delete folders** (delete is recursive).
- **Upload** images + PDF (drag & drop or click).
- **Rename files** (extension is preserved / locked to the original).
- **Move & Copy** files and folders to any other folder (multi-select).
- **Copy live URL** of any file (full `asset()` URL to clipboard).
- **Preview** images / PDF inside a modal.
- **Reusable picker** — usable in **any admin form** where an image/PDF is needed.

## Security

- **Only images + PDF are allowed** (`jpg jpeg png gif webp svg bmp avif pdf`).
  Uploads are rejected unless BOTH the extension **and** the real MIME type match
  (`MediaController::$allowedExt` + `safeMime()`), so a `.php`/`.html` disguised as
  `.jpg` is blocked.
- **Path traversal is blocked** — `resolvePath()` rejects absolute paths and `..`,
  and `absPath()` re-verifies the resolved path stays inside the media root.
- Hidden/system files (dot-prefixed) are never listed or touched.
- Dangerous names are sanitized (`sanitizeName()` strips script extensions).

## Routes

| Method | URI | Name | Purpose |
|---|---|---|---|
| GET  | `admin/media` | `admin.media.index` | Main gallery page (`?path=` opens a folder) |
| POST | `admin/media/folder/create` | `admin.media.folder.create` | Create folder (`path`, `folder_name`) |
| POST | `admin/media/folder/rename` | `admin.media.folder.rename` | Rename folder (`path`, `new_name`) |
| POST | `admin/media/folder/delete` | `admin.media.folder.delete` | Delete folder (`path`) |
| POST | `admin/media/upload` | `admin.media.upload` | Upload `files[]` (multi) into `path` |
| POST | `admin/media/file/rename` | `admin.media.file.rename` | Rename file (`path`, `new_name`) |
| POST | `admin/media/file/delete` | `admin.media.file.delete` | Delete file (`path`) |
| POST | `admin/media/move` | `admin.media.move` | Move `items[]` → `target` |
| POST | `admin/media/copy` | `admin.media.copy` | Copy `items[]` → `target` |
| GET  | `admin/media/picker` | `admin.media.picker` | Picker content (`?path=`) for AJAX |
| POST | `admin/media/picker/upload` | `admin.media.picker.upload` | Upload from inside the picker |

## Using the picker in ANY admin form

1. Include the partial **once**, outside the `<form>`:

   ```blade
   @include('backEnd.media._picker')
   ```

2. Add a button that opens the picker, targeting the input that will receive the
   value (and optionally a preview `<img>`):

   ```blade
   <input type="text" name="image_url" id="image_url" value="{{ old('image_url') }}">
   <img id="imagePreview" src="" class="d-none" width="100">
   <button type="button" class="btn btn-outline-primary btn-sm"
           onclick="openMediaPicker('#image_url', '#imagePreview', 'path')">
     <i class="fe-image"></i> Choose from Media Library
   </button>
   ```

3. `openMediaPicker(targetSelector, previewSelector?, valueMode?)`
   - `targetSelector` — the input that gets the chosen value.
   - `previewSelector` — optional `img` element to update.
   - `valueMode` — `'url'` (default) inserts the **full live URL**;
     `'path'` inserts the **relative storage path** (`public/uploads/media/...`)
     which is what you should save in your DB so `asset($row->image)` works
     in the frontend — this is what the Banner form uses.

4. In your controller, accept either the uploaded file **or** the media path:

   ```php
   if ($request->hasFile('image') && $request->file('image')->isValid()) {
       // existing file upload…
   } elseif ($request->filled('image_url')) {
       $input['image'] = $request->input('image_url'); // public/uploads/media/...
   }
   ```

> The Banner create/edit forms already include this as a working example —
> copy that pattern for any other module (products, blogs, categories, …).

## Tests

`tests/Feature/MediaControllerTest.php` covers folder create/rename/delete,
upload (incl. blocked fake extensions), file rename/delete, move, copy, and
path-traversal rejection. Run with:

```bash
php vendor/bin/phpunit tests/Feature/MediaControllerTest.php
```
