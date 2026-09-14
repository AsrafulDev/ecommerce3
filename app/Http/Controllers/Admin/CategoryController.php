<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;
use App\Models\Category;
use Toastr;
use Image;
use File;
use Str;

class CategoryController extends Controller
{
    function __construct()
    {
        $this->middleware('permission:category-list|category-create|category-edit|category-delete', ['only' => ['index','store']]);
        $this->middleware('permission:category-create', ['only' => ['create','store']]);
        $this->middleware('permission:category-edit', ['only' => ['edit','update']]);
        $this->middleware('permission:category-delete', ['only' => ['destroy']]);
    }

    public function index(Request $request)
    {
        $data = Category::orderBy('id','DESC')->with('category')->get();
        return view('backEnd.category.index',compact('data'));
    }

    public function create()
    {
        $categories = Category::orderBy('id','DESC')->select('id','name')->get();
        return view('backEnd.category.create',compact('categories'));
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'name'   => 'required',
            'status' => 'required',
            // icon optional
            // 'icon'   => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $imageUrl = $request->input('image_url');

        $iconUrl = null;
        if ($request->filled('icon_url')) {
            // Media Gallery থেকে বাছাই (path mode)
            $iconUrl = $request->input('icon_url');
        }

        /* ========= Input Prepare (whitelist — stray fields like 'files' are ignored) ========= */
        $input = $request->only(['name', 'meta_title', 'meta_description']);
        $input['status']     = $request->has('status') ? 1 : 0;
        $input['slug']       = strtolower(preg_replace('/\s+/', '-', $request->name));
        $input['slug']       = str_replace('/', '', $input['slug']);

        $input['parent_id']  = $request->parent_id ? $request->parent_id : 0;
        $input['front_view'] = $request->has('front_view') ? 1 : 0;
        $input['image']      = $imageUrl;
        $input['icon']       = $iconUrl; // নতুন icon কলাম

        Category::create($input);

        Toastr::success('Success','Data insert successfully');
        return redirect()->route('categories.index');
    }

    public function edit($id)
    {
        $edit_data  = Category::find($id);
        $categories = Category::select('id','name')->get();
        return view('backEnd.category.edit',compact('edit_data','categories'));
    }

    public function update(Request $request)
    {
        $this->validate($request, [
            'name' => 'required',
            // 'icon' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        // Fix: Use hidden_id instead of id (form may send hidden_id)
        $update_data = Category::find($request->hidden_id ?? $request->id);
        
        if (!$update_data) {
            Toastr::error('Error','Record not found');
            return redirect()->back();
        }
        
        $input       = $request->only(['name', 'meta_title', 'meta_description']);

        /* ========= Main Image Update (direct upload OR Media Gallery) ========= */
        if ($request->filled('image_url')) {
            // Media Gallery থেকে বাছাই (path mode)
            $input['image'] = $request->input('image_url');
        } else {
            $input['image'] = $update_data->image;
        }

        /* ========= Icon Update (direct upload OR Media Gallery) ========= */
        if ($request->filled('icon_url')) {
            // Media Gallery থেকে বাছাই (path mode)
            $input['icon'] = $request->input('icon_url');
        } else {
            $input['icon'] = $update_data->icon;
        }

        /* ========= Others ========= */
        $input['slug'] = strtolower(preg_replace('/\s+/', '-', $request->name));
        $input['slug'] = str_replace('/', '', $input['slug']);

        $input['parent_id']  = $request->parent_id ? $request->parent_id : 0;
        $input['front_view'] = $request->front_view ? 1 : 0;
        $input['status']     = $request->status ? 1 : 0;

        $update_data->update($input);

        Toastr::success('Success','Data update successfully');
        return redirect()->route('categories.index');
    }

    public function inactive(Request $request)
    {
        $inactive = Category::find($request->hidden_id);
        if (!$inactive) {
            Toastr::error('Error','Record not found');
            return redirect()->back();
        }
        $inactive->status = 0;
        $inactive->save();
        Cache::forget('frontend_homepage_v1');
        Cache::forget('side_categories');
        Cache::forget('menu_categories');

        Toastr::success('Success','Data inactive successfully');
        return redirect()->back();
    }

    public function active(Request $request)
    {
        $active = Category::find($request->hidden_id);
        if (!$active) {
            Toastr::error('Error','Record not found');
            return redirect()->back();
        }
        $active->status = 1;
        $active->save();
        Cache::forget('frontend_homepage_v1');
        Cache::forget('side_categories');
        Cache::forget('menu_categories');

        Toastr::success('Success','Data active successfully');
        return redirect()->back();
    }

    public function destroy(Request $request)
    {
        $request->validate(['hidden_id' => 'required']);
        $delete_data = Category::find($request->hidden_id);

        if (!$delete_data) {
            Toastr::error('Error', 'Category not found.');
            return redirect()->back();
        }

        // সাবক্যাটাগরি বা প্রোডাক্ট থাকলে ডিলিট না করে মেসেজ দিন
        $hasSubcategories = \App\Models\Subcategory::where('category_id', $delete_data->id)->exists();
        $hasProducts = \App\Models\Product::where('category_id', $delete_data->id)->exists();
        if ($hasSubcategories || $hasProducts) {
            Toastr::error('Cannot delete', 'Remove or reassign subcategories and products under this category first.');
            return redirect()->back();
        }

        if ($delete_data->image && File::exists($delete_data->image)) {
            File::delete($delete_data->image);
        }
        if ($delete_data->icon && File::exists($delete_data->icon)) {
            File::delete($delete_data->icon);
        }
        $delete_data->delete();

        Toastr::success('Success', 'Category deleted successfully.');
        return redirect()->back();
    }
}
