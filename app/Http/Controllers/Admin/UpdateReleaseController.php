<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Version;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Brian2694\Toastr\Facades\Toastr;

class UpdateReleaseController extends Controller
{
    public function index()
    {
        $versions = Version::orderBy('release_date', 'desc')->get();
        return view('backEnd.update.release', compact('versions'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'version' => 'required|string|unique:versions,version|regex:/^\d+\.\d+\.\d+$/',
            'release_date' => 'required|date',
            'changelog' => 'nullable|string',
            'update_file' => 'required|file|mimes:zip|max:102400', // Max 100MB
            'requires_migration' => 'boolean',
        ], [
            'version.regex' => 'Version must be in format: X.X.X (e.g., 1.1.0)',
            'update_file.max' => 'Update file size must be less than 100MB',
        ]);

        try {
            // Upload file
            $file = $request->file('update_file');
            $fileName = 'update-' . $request->version . '.zip';
            $path = $file->storeAs('updates', $fileName);

            // Create version record
            Version::create([
                'version' => $request->version,
                'release_date' => $request->release_date,
                'changelog' => $request->changelog,
                'file_size' => $file->getSize(),
                'file_path' => $path,
                'is_active' => true,
                'requires_migration' => $request->has('requires_migration'),
            ]);

            Toastr::success('Update released successfully!', 'Success');
            return redirect()->back();

        } catch (\Exception $e) {
            Toastr::error('Failed to release update: ' . $e->getMessage(), 'Error');
            return redirect()->back()->withInput();
        }
    }

    public function toggleActive($id)
    {
        try {
            $version = Version::findOrFail($id);
            $version->is_active = !$version->is_active;
            $version->save();

            $status = $version->is_active ? 'activated' : 'deactivated';
            Toastr::success("Version {$version->version} {$status} successfully!", 'Success');
            
            return redirect()->back();
        } catch (\Exception $e) {
            Toastr::error('Failed to update version status: ' . $e->getMessage(), 'Error');
            return redirect()->back();
        }
    }

    public function destroy($id)
    {
        try {
            $version = Version::findOrFail($id);
            
            // Delete file if exists
            if ($version->file_path && Storage::exists($version->file_path)) {
                Storage::delete($version->file_path);
            }
            
            $version->delete();
            
            Toastr::success("Version {$version->version} deleted successfully!", 'Success');
            return redirect()->back();
        } catch (\Exception $e) {
            Toastr::error('Failed to delete version: ' . $e->getMessage(), 'Error');
            return redirect()->back();
        }
    }
}
