<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Theme;
use App\Models\GeneralSetting;
use Toastr;
use File;
use Image;

class ThemeController extends Controller
{
    function __construct()
    {
        $this->middleware('permission:theme-list|theme-create|theme-edit|theme-delete', ['only' => ['index', 'store']]);
        $this->middleware('permission:theme-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:theme-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:theme-delete', ['only' => ['destroy']]);
    }

    public function index()
    {
        $themes = Theme::orderBy('is_default', 'desc')->orderBy('name')->get();
        $activeTheme = Theme::where('is_default', true)->first();
        return view('backEnd.theme.index', compact('themes', 'activeTheme'));
    }

    public function create()
    {
        return view('backEnd.theme.edit', ['edit_data' => null]);
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|max:100',
            'slug' => 'required|max:120|unique:themes,slug',
            'primary_color' => 'required',
        ]);

        $input = $request->all();

        // Handle preview image
        if ($request->hasFile('preview_image')) {
            $file = $request->file('preview_image');
            $name = time() . '-' . $file->getClientOriginalName();
            $uploadPath = 'public/uploads/themes/';
            $file->move($uploadPath, $name);
            $input['preview_image'] = $uploadPath . $name;
        }

        // If this is the first theme, make it default
        if (Theme::count() === 0) {
            $input['is_default'] = true;
        }

        $input['is_active'] = $request->boolean('is_active');

        Theme::create($input);

        Toastr::success('Theme created successfully!', 'Success');
        return redirect()->route('themes.index');
    }

    public function edit($id)
    {
        $edit_data = Theme::findOrFail($id);
        return view('backEnd.theme.edit', compact('edit_data'));
    }

    public function update(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|max:100',
            'slug' => 'required|max:120|unique:themes,slug,' . $request->id,
            'primary_color' => 'required',
        ]);

        $update_data = Theme::findOrFail($request->id);
        $input = $request->all();

        // Handle preview image
        if ($request->hasFile('preview_image')) {
            // Delete old image
            if ($update_data->preview_image && File::exists($update_data->preview_image)) {
                File::delete($update_data->preview_image);
            }
            $file = $request->file('preview_image');
            $name = time() . '-' . $file->getClientOriginalName();
            $uploadPath = 'public/uploads/themes/';
            $file->move($uploadPath, $name);
            $input['preview_image'] = $uploadPath . $name;
        } else {
            $input['preview_image'] = $update_data->preview_image;
        }

        $input['is_active'] = $request->boolean('is_active');
        $input['is_default'] = $request->boolean('is_default');

        // If setting as default, remove default from all others
        if ($input['is_default']) {
            Theme::where('id', '!=', $update_data->id)->update(['is_default' => false]);
        }

        $update_data->update($input);

        Toastr::success('Theme updated successfully!', 'Success');
        return redirect()->route('themes.index');
    }

    /**
     * Apply a theme (set as active/default)
     */
    public function apply($id)
    {
        $theme = Theme::findOrFail($id);

        // Set this theme as default
        Theme::where('id', '!=', $id)->update(['is_default' => false]);
        $theme->is_default = true;
        $theme->save();

        // Also update general_settings if exists
        $setting = GeneralSetting::first();
        if ($setting) {
            $setting->theme_id = $theme->id;
            $setting->save();
        }

        Toastr::success("Theme '{$theme->name}' applied successfully!", 'Success');
        return redirect()->route('themes.index');
    }

    /**
     * Duplicate a theme
     */
    public function duplicate($id)
    {
        $original = Theme::findOrFail($id);
        $copy = $original->replicate();
        $copy->name = $original->name . ' (Copy)';
        $copy->slug = $original->slug . '-copy-' . time();
        $copy->is_default = false;
        $copy->save();

        Toastr::success("Theme '{$original->name}' duplicated successfully!", 'Success');
        return redirect()->route('themes.index');
    }

    public function destroy(Request $request)
    {
        $theme = Theme::findOrFail($request->hidden_id);

        // Prevent deleting the default theme
        if ($theme->is_default) {
            Toastr::error('Cannot delete the active/default theme!', 'Error');
            return redirect()->back();
        }

        // If this theme is referenced in general_settings, null it out
        GeneralSetting::where('theme_id', $theme->id)->update(['theme_id' => null]);

        // Delete preview image
        if ($theme->preview_image && File::exists($theme->preview_image)) {
            File::delete($theme->preview_image);
        }

        $theme->delete();

        Toastr::success('Theme deleted successfully!', 'Success');
        return redirect()->route('themes.index');
    }

    public function inactive(Request $request)
    {
        $theme = Theme::findOrFail($request->hidden_id);
        $theme->is_active = false;
        $theme->save();
        Toastr::success('Theme deactivated successfully!', 'Success');
        return redirect()->back();
    }

    public function active(Request $request)
    {
        $theme = Theme::findOrFail($request->hidden_id);
        $theme->is_active = true;
        $theme->save();
        Toastr::success('Theme activated successfully!', 'Success');
        return redirect()->back();
    }
}
