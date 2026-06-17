<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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

        /* ========= Main Image Upload ========= */
        $image = $request->file('image');
        if ($image) {
            $name = time().'-'.$image->getClientOriginalName();
            $name = preg_replace('"\.(jpg|jpeg|png|webp)$"', '.webp', $name);
            $name = strtolower(preg_replace('/\s+/', '-', $name));

            $uploadpath = 'public/uploads/category/';
            if (!File::isDirectory($uploadpath)) {
                File::makeDirectory($uploadpath, 0775, true, true);
            }

            $imageUrl = $uploadpath.$name;

            $img = Image::make($image->getRealPath());
            $img->encode('webp', 90);
            $width  = "";
            $height = "";
            $img->height() > $img->width() ? $width = null : $height = null;
            $img->resize($width, $height, function ($constraint) {
                $constraint->aspectRatio();
            });
            $img->save($imageUrl);
        } else {
            $imageUrl = null;
        }

        /* ========= Icon Image Upload (নতুন) ========= */
        $icon      = $request->file('icon');
        $iconUrl   = null;

        if ($icon) {
            $iconName = time().'-icon-'.$icon->getClientOriginalName();
            $iconName = preg_replace('"\.(jpg|jpeg|png|webp)$"', '.webp', $iconName);
            $iconName = strtolower(preg_replace('/\s+/', '-', $iconName));

            $uploadpathIcon = 'public/uploads/category/';
            if (!File::isDirectory($uploadpathIcon)) {
                File::makeDirectory($uploadpathIcon, 0775, true, true);
            }

            $iconUrl = $uploadpathIcon.$iconName;

            $iconImg = Image::make($icon->getRealPath());
            $iconImg->encode('webp', 90);

            // ছোট ও সমান সাইজের আইকন
            $iconImg->fit(64, 64, function ($constraint) {
                $constraint->upsize();
            });

            $iconImg->save($iconUrl);
        }

        /* ========= Input Prepare ========= */
        $input = $request->all();

        $input['slug'] = strtolower(preg_replace('/\s+/', '-', $request->name));
        $input['slug'] = str_replace('/', '', $input['slug']);

        $input['parent_id']  = $request->parent_id ? $request->parent_id : 0;
        $input['front_view'] = $request->front_view ? 1 : 0;
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
        
        $input       = $request->except('hidden_id');

        /* ========= Main Image Update ========= */
        $image = $request->file('image');
        if ($image) {
            $name = time().'-'.$image->getClientOriginalName();
            $name = preg_replace('"\.(jpg|jpeg|png|webp)$"', '.webp', $name);
            $name = strtolower(preg_replace('/\s+/', '-', $name));

            $uploadpath = 'public/uploads/category/';
            if (!File::isDirectory($uploadpath)) {
                File::makeDirectory($uploadpath, 0775, true, true);
            }

            $imageUrl = $uploadpath.$name;

            $img = Image::make($image->getRealPath());
            $img->encode('webp', 90);
            $width  = "";
            $height = "";
            $img->height() > $img->width() ? $width = null : $height = null;
            $img->resize($width, $height, function ($constraint) {
                $constraint->aspectRatio();
            });
            $img->save($imageUrl);

            // পুরনো main image ডিলিট
            if ($update_data->image) {
                File::delete($update_data->image);
            }

            $input['image'] = $imageUrl;
        } else {
            $input['image'] = $update_data->image;
        }

        /* ========= Icon Update (নতুন) ========= */
        $icon = $request->file('icon');
        if ($icon) {
            $iconName = time().'-icon-'.$icon->getClientOriginalName();
            $iconName = preg_replace('"\.(jpg|jpeg|png|webp)$"', '.webp', $iconName);
            $iconName = strtolower(preg_replace('/\s+/', '-', $iconName));

            $uploadpathIcon = 'public/uploads/category/';
            if (!File::isDirectory($uploadpathIcon)) {
                File::makeDirectory($uploadpathIcon, 0775, true, true);
            }

            $iconUrl = $uploadpathIcon.$iconName;

            $iconImg = Image::make($icon->getRealPath());
            $iconImg->encode('webp', 90);
            $iconImg->fit(64, 64, function ($constraint) {
                $constraint->upsize();
            });
            $iconImg->save($iconUrl);

            // পুরনো icon থাকলে ডিলিট
            if ($update_data->icon) {
                File::delete($update_data->icon);
            }

            $input['icon'] = $iconUrl;
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
