<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use App\Models\User;
use Illuminate\Http\Request;
use Brian2694\Toastr\Facades\Toastr;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Arr;

class VendorController extends Controller
{
    /**
     * Display a listing of vendors.
     */
    public function index(Request $request)
    {
        $query = Vendor::with('wallet', 'products');
        
        if ($request->keyword) {
            $query->where(function($q) use ($request) {
                $q->where('shop_name', 'like', '%' . $request->keyword . '%')
                  ->orWhere('owner_name', 'like', '%' . $request->keyword . '%')
                  ->orWhere('email', 'like', '%' . $request->keyword . '%')
                  ->orWhere('phone', 'like', '%' . $request->keyword . '%');
            });
        }
        
        $vendors = $query->latest()->paginate(20);
        
        return view('backEnd.vendor.index', compact('vendors'));
    }

    /**
     * Show the form for editing the specified vendor.
     */
    public function edit($id)
    {
        $vendor = Vendor::findOrFail($id);
        $user = User::where('vendor_id', $id)->first();
        
        return view('backEnd.vendor.edit', compact('vendor', 'user'));
    }

    /**
     * Update the specified vendor.
     */
    public function update(Request $request)
    {
        $this->validate($request, [
            'shop_name' => 'required|string|max:255',
            'owner_name' => 'required|string|max:255',
            'email' => 'required|email|unique:vendors,email,' . $request->hidden_id,
            'phone' => 'required|string|max:55|unique:vendors,phone,' . $request->hidden_id,
            'slug' => 'required|string|max:255|unique:vendors,slug,' . $request->hidden_id,
            'commission_rate' => 'required|numeric|min:0|max:100',
        ]);

        $vendor = Vendor::findOrFail($request->hidden_id);
        $input = $request->except('hidden_id', 'password', 'logo', 'banner');
        
        // Handle logo upload
        if ($request->hasFile('logo')) {
            $logo = $request->file('logo');
            $name = time() . '-' . $logo->getClientOriginalName();
            $name = preg_replace('"\.(jpg|jpeg|png|webp)$"', '.webp', $name);
            $name = strtolower(preg_replace('/\s+/', '-', $name));
            $uploadpath = 'public/uploads/vendor/logo/';
            $imageUrl = $uploadpath . $name;
            
            if (!File::exists($uploadpath)) {
                File::makeDirectory($uploadpath, 0755, true);
            }
            
            $img = Image::make($logo->getRealPath());
            $img->encode('webp', 90);
            $img->resize(200, 200, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });
            $img->save($imageUrl);
            
            $input['logo'] = $imageUrl;
            if ($vendor->logo && File::exists($vendor->logo)) {
                File::delete($vendor->logo);
            }
        } else {
            $input['logo'] = $vendor->logo;
        }

        // Handle banner upload
        if ($request->hasFile('banner')) {
            $banner = $request->file('banner');
            $name = time() . '-' . $banner->getClientOriginalName();
            $name = preg_replace('"\.(jpg|jpeg|png|webp)$"', '.webp', $name);
            $name = strtolower(preg_replace('/\s+/', '-', $name));
            $uploadpath = 'public/uploads/vendor/banner/';
            $imageUrl = $uploadpath . $name;
            
            if (!File::exists($uploadpath)) {
                File::makeDirectory($uploadpath, 0755, true);
            }
            
            $img = Image::make($banner->getRealPath());
            $img->encode('webp', 90);
            $img->resize(1200, 400, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });
            $img->save($imageUrl);
            
            $input['banner'] = $imageUrl;
            if ($vendor->banner && File::exists($vendor->banner)) {
                File::delete($vendor->banner);
            }
        } else {
            $input['banner'] = $vendor->banner;
        }

        $input['status'] = $request->status ? 1 : 0;
        $vendor->update($input);

        // Update user account if exists
        $user = User::where('vendor_id', $vendor->id)->first();
        if ($user) {
            $userInput = [
                'name' => $request->owner_name,
                'email' => $request->email,
            ];
            
            if (!empty($request->password)) {
                $userInput['password'] = Hash::make($request->password);
            }
            
            $user->update($userInput);
        }

        Toastr::success('Vendor updated successfully', 'Success');
        return redirect()->route('admin.vendors.index');
    }

    /**
     * Remove the specified vendor.
     */
    public function destroy($id)
    {
        $vendor = Vendor::findOrFail($id);
        
        // Delete logo and banner
        if ($vendor->logo && File::exists($vendor->logo)) {
            File::delete($vendor->logo);
        }
        if ($vendor->banner && File::exists($vendor->banner)) {
            File::delete($vendor->banner);
        }
        
        // Delete associated user
        $user = User::where('vendor_id', $id)->first();
        if ($user) {
            $user->delete();
        }
        
        // Delete vendor
        $vendor->delete();

        Toastr::success('Vendor deleted successfully', 'Success');
        return redirect()->route('admin.vendors.index');
    }

    /**
     * Toggle vendor status.
     */
    public function toggleStatus($id)
    {
        $vendor = Vendor::findOrFail($id);
        $vendor->status = $vendor->status == 1 ? 0 : 1;
        $vendor->save();

        Toastr::success('Vendor status updated successfully', 'Success');
        return redirect()->back();
    }

    /**
     * Approve vendor verification
     */
    public function approveVerification(Request $request, $id)
    {
        $vendor = Vendor::findOrFail($id);
        
        $vendor->verification_status = 'approved';
        $vendor->verified_at = now();
        $vendor->verification_note = $request->admin_note ?? null;
        $vendor->save();

        Toastr::success('Vendor verification approved successfully', 'Success');
        return redirect()->back();
    }

    /**
     * Reject vendor verification
     */
    public function rejectVerification(Request $request, $id)
    {
        $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        $vendor = Vendor::findOrFail($id);
        
        $vendor->verification_status = 'rejected';
        $vendor->verification_note = $request->rejection_reason;
        $vendor->save();

        Toastr::success('Vendor verification rejected', 'Success');
        return redirect()->back();
    }
}
