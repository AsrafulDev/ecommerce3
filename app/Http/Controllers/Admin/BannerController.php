<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\BannerCategory;
use App\Models\Banner;
use Toastr;
use Image;
use File;
class BannerController extends Controller
{
    function __construct()
    {
         $this->middleware('permission:banner-list|banner-create|banner-edit|banner-delete', ['only' => ['index','store']]);
         $this->middleware('permission:banner-create', ['only' => ['create','store']]);
         $this->middleware('permission:banner-edit', ['only' => ['edit','update']]);
         $this->middleware('permission:banner-delete', ['only' => ['destroy']]);
    }

    public function index(Request $request)
    {
        $data = Banner::orderBy('id','DESC')->with('category')->get();
        return view('backEnd.banner.index',compact('data'));
    }
    public function create()
    {
        $categories = BannerCategory::orderBy('id','DESC')->select('id','name')->get();
        return view('backEnd.banner.create',compact('categories'));
    }
    public function store(Request $request)
    {
        if (!$request->filled('image_url')) {
            Toastr::warning('Please select a banner image from the Media Manager before saving.', 'Image required');
            return redirect()->back()->withInput()->withErrors(['image_url' => 'Please select a banner image from the Media Manager.']);
        }

        if (!$request->filled('category_id')) {
            Toastr::warning('Please select a banner category before saving.', 'Category required');
            return redirect()->back()->withInput()->withErrors(['category_id' => 'Please select a banner category.']);
        }

        $this->validate($request, [
            'link' => 'required',
            'category_id' => 'required|integer|exists:banner_categories,id',
            'status' => 'required',
        ]);
        
        $input = $request->all();
        unset($input['image_url']);
        $input['status'] = $request->status?1:0;

        $input['image'] = $request->input('image_url');
        Banner::create($input);
        Toastr::success('Success','Data insert successfully');
        return redirect()->route('banners.index');
    }
    
    public function edit($id)
    {
        $edit_data = Banner::find($id);
        $categories = BannerCategory::select('id','name')->get();
        return view('backEnd.banner.edit',compact('edit_data','categories'));
    }
    
    public function update(Request $request)
    {
        if (!$request->filled('image_url')) {
            Toastr::warning('Please select a banner image from the Media Manager before updating.', 'Image required');
            return redirect()->back()->withInput()->withErrors(['image_url' => 'Please select a banner image from the Media Manager.']);
        }

        if (!$request->filled('category_id')) {
            Toastr::warning('Please select a banner category before updating.', 'Category required');
            return redirect()->back()->withInput()->withErrors(['category_id' => 'Please select a banner category.']);
        }

        $this->validate($request, [
            'link' => 'required',
            'category_id' => 'required|integer|exists:banner_categories,id',
        ]);
        $update_data = Banner::find($request->id);
        $input = $request->all();
        unset($input['image_url']);
        $fileUrl = $update_data->image; // keep existing by default
        if ($request->filled('image_url')) {
            // Picked from the Media Gallery
            $fileUrl = $request->input('image_url');
        }
        $input['image'] = $fileUrl;

        $input['status'] = $request->status?1:0;
        $update_data->update($input);

        Toastr::success('Success','Data update successfully');
        return redirect()->route('banners.index');
    }
 
    public function inactive(Request $request)
    {
        $inactive = Banner::find($request->hidden_id);
        $inactive->status = 0;
        $inactive->save();
        Toastr::success('Success','Data inactive successfully');
        return redirect()->back();
    }
    public function active(Request $request)
    {
        $active = Banner::find($request->hidden_id);
        $active->status = 1;
        $active->save();
        Toastr::success('Success','Data active successfully');
        return redirect()->back();
    }
    public function destroy(Request $request)
    {
        $delete_data = Banner::find($request->hidden_id);
        $delete_data->delete();
        Toastr::success('Success','Data delete successfully');
        return redirect()->back();
    }
}
