<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;
use App\Models\Brand;
use Image;
use File;
use Toastr;
class BrandController extends Controller
{
    
    public function index(Request $request)
    {
        $data = Brand::orderBy('id','DESC')->get();
        return view('backEnd.brand.index',compact('data'));
    }
    public function create()
    {
        return view('backEnd.brand.create');
    }
    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'required',
            'status' => 'required',
        ]);
        $imageUrl = $request->input('image_url');

        // whitelist — stray fields like 'files' / 'image_url' are ignored
        $input = $request->only(['name', 'name_bn']);
        $input['slug'] = strtolower(preg_replace('/\s+/u', '-', trim($request->name)));
        $input['name_bn'] = $request->name_bn ?? $request->name;
        $input['status'] = $request->has('status') ? 1 : 0;
        $input['image'] = $imageUrl;
        Brand::create($input);
        Cache::forget('frontend_homepage_v1');
        Cache::forget('brands_list');
        Toastr::success('Success','Data insert successfully');
        return redirect()->route('brands.index');
    }
    
    public function edit($id)
    {
        $edit_data = Brand::find($id);
        return view('backEnd.brand.edit',compact('edit_data'));
    }
    
    public function update(Request $request)
    {
        $this->validate($request, [
            'name' => 'required',
        ]);
        $update_data = Brand::find($request->id);
        $input = $request->only(['name', 'name_bn']);
        if ($request->filled('image_url')) {
            $input['image'] = $request->input('image_url');
        } else {
            $input['image'] = $update_data->image;
        }
        $input['status'] = $request->status?1:0;
        $input['name_bn'] = $request->name_bn ?? $request->name;
        $update_data->update($input);

        Cache::forget('frontend_homepage_v1');
        Cache::forget('brands_list');
        Toastr::success('Success','Data update successfully');
        return redirect()->route('brands.index');
    }
 
    public function inactive(Request $request)
    {
        $inactive = Brand::find($request->hidden_id);
        $inactive->status = 0;
        $inactive->save();
        Cache::forget('frontend_homepage_v1');
        Cache::forget('brands_list');
        Toastr::success('Success','Data inactive successfully');
        return redirect()->back();
    }
    public function active(Request $request)
    {
        $active = Brand::find($request->hidden_id);
        $active->status = 1;
        $active->save();
        Cache::forget('frontend_homepage_v1');
        Cache::forget('brands_list');
        Toastr::success('Success','Data active successfully');
        return redirect()->back();
    }
    public function destroy(Request $request)
    {
        $delete_data = Brand::find($request->hidden_id);
        $delete_data->delete();
        Toastr::success('Success','Data delete successfully');
        return redirect()->back();
    }
}
