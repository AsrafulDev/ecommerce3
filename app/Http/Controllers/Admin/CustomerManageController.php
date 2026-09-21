<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use App\Models\CustomerProfit;
use App\Models\Customer;
use App\Models\IpBlock;
use App\Models\PhoneBlock;
use Toastr;
use Image;
use File;
use Auth;
use Hash;
class CustomerManageController extends Controller
{
    public function index(Request $request){
        if($request->keyword){
            $show_data = Customer::orWhere('phone',$request->keyword)->orWhere('name',$request->keyword)->paginate(20);
        }else{
             $show_data = Customer::paginate(20);
        }
       
        return view('backEnd.customer.index',compact('show_data'));
    }

    public function edit($id){
        $edit_data = Customer::find($id);
        return view('backEnd.customer.edit',compact('edit_data'));
    }
    
    public function update(Request $request){
        $this->validate($request, [
            'name' => 'required',
            'phone' => 'required',
            'email' => 'required',
        ]);

        $input = $request->except('hidden_id');
        $update_data = Customer::find($request->hidden_id);
        // new password
        
        
        if(!empty($input['password'])){ 
            $input['password'] = Hash::make($input['password']);
        }else{
            $input = Arr::except($input,array('password'));    
        }

        // new image
        $image = $request->file('image');
        if($image){
            // image with intervention 
            $name =  time().'-'.$image->getClientOriginalName();
            $name = preg_replace('"\.(jpg|jpeg|png|webp)$"', '.webp',$name);
            $name = strtolower(preg_replace('/\s+/', '-', $name));
            $uploadpath = 'public/uploads/customer/';
            $imageUrl = $uploadpath.$name; 
            $img=Image::make($image->getRealPath());
            $img->encode('webp', 90);
            $width = 100;
            $height = 100;
            $img->height() > $img->width() ? $width=null : $height=null;
            $img->resize($width, $height, function ($constraint) {
                $constraint->aspectRatio();
            });
            $img->save($imageUrl);
            $input['image'] = $imageUrl;
            File::delete($update_data->image);
        }else{
            $input['image'] = $update_data->image;
        }
        $input['status'] = $request->status?1:0;
        $update_data->update($input);

        Toastr::success('Success','Data update successfully');
        return redirect()->route('customers.index');
    }
 
    public function inactive(Request $request){
        $inactive = Customer::find($request->hidden_id);
        $inactive->status = 'inactive';
        $inactive->save();
        Toastr::success('Success','Data inactive successfully');
        return redirect()->back();
    }
    public function active(Request $request){
        $active = Customer::find($request->hidden_id);
        $active->status = 'active';
        $active->save();
        Toastr::success('Success','Data active successfully');
        return redirect()->back();
    }
    public function profile(Request $request){
        $profile = Customer::with('orders')->find($request->id);
        return view('backEnd.customer.profile',compact('profile'));
    }
    public function adminlog(Request $request){
        $customer = Customer::find($request->hidden_id);
        Auth::guard('customer')->loginUsingId($customer->id);
        return redirect()->route('customer.account');
    }
    public function ip_block(Request $request){
        $data = IpBlock::get();
        $phoneData = PhoneBlock::orderByDesc('id')->get();
        // Query parameter থেকে IP এবং reason নেওয়া
        $prefillIp = $request->query('ip');
        $prefillReason = $request->query('reason', 'ফেইক অর্ডার');
        $prefillPhone = $request->query('phone');
        return view('backEnd.reports.ipblock',compact('data', 'phoneData', 'prefillIp', 'prefillReason', 'prefillPhone'));
    }
    public function ipblock_store(Request $request){

        $store_data = new IpBlock();
        $store_data->ip_no = trim($request->ip_no);
        $store_data->reason = $request->reason;
        $store_data->save();

        // Block takes effect immediately — clear cached check for this IP
        Cache::forget('ip_block_' . trim($request->ip_no));
        Cache::forget('blocked_ips');
        Toastr::success('Success','IP address add successfully');
        return redirect()->back();
    }
    public function ipblock_update(Request $request){
        $update_data = IpBlock::find($request->id);
        if (!$update_data) {
            Toastr::error('Error','Record not found');
            return redirect()->back();
        }
        // Clear cached check for the OLD IP before overwriting it
        Cache::forget('ip_block_' . $update_data->ip_no);
        $update_data->ip_no = trim($request->ip_no);
        $update_data->reason = $request->reason;
        $update_data->save();
        // And for the NEW IP
        Cache::forget('ip_block_' . trim($request->ip_no));
        Cache::forget('blocked_ips');
        Toastr::success('Success','IP address update successfully');
        return redirect()->back();
    }
    public function ipblock_destroy(Request $request){
        $delete_data = IpBlock::find($request->id);
        if (!$delete_data) {
            Toastr::error('Error','Record not found');
            return redirect()->back();
        }
        // Unblock takes effect immediately — clear cached "blocked" result for this IP
        Cache::forget('ip_block_' . $delete_data->ip_no);
        $delete_data->delete();
        Cache::forget('blocked_ips');
        Toastr::success('Success','IP address delete successfully');
        return redirect()->back();
    }
    
    // AJAX method for quick IP block from order page
    public function ipblock_quick_store(Request $request){
        try {
            $ip = trim($request->ip);
            $reason = $request->reason ?? 'ফেইক অর্ডার';
            
            if(!$ip){
                return response()->json([
                    'status' => 'error',
                    'message' => 'IP address is required'
                ], 400);
            }
            
            // Check if IP already blocked
            $existing = IpBlock::where('ip_no', $ip)->first();
            if($existing){
                return response()->json([
                    'status' => 'error',
                    'message' => 'This IP is already blocked'
                ], 400);
            }
            
            $store_data = new IpBlock();
            $store_data->ip_no = $ip;
            $store_data->reason = $reason;
            $store_data->save();
            
            // Block takes effect immediately — clear cached check for this IP
            Cache::forget('ip_block_' . $ip);
            Cache::forget('blocked_ips');
            
            return response()->json([
                'status' => 'success',
                'message' => 'IP address blocked successfully'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to block IP: ' . $e->getMessage()
            ], 500);
        }
    }

    // ==========================================================
    // 📵 Phone number block list
    // ==========================================================

    public function phoneblock_store(Request $request){
        $request->validate([
            'phone'  => 'required',
            'reason' => 'nullable',
        ]);

        $normalized = normalize_phone($request->phone);
        if ($normalized === '') {
            Toastr::error('A valid phone number is required', 'Error');
            return redirect()->back();
        }

        if (PhoneBlock::where('phone_normalized', $normalized)->exists()) {
            Toastr::error('This phone number is already blocked', 'Error');
            return redirect()->back();
        }

        $store_data = new PhoneBlock();
        $store_data->phone  = trim($request->phone);
        $store_data->reason = $request->reason ?: 'ফেইক অর্ডার';
        $store_data->save();

        forget_phone_block_cache($store_data->phone);
        Toastr::success('Success','Phone number blocked successfully');
        return redirect()->back();
    }

    public function phoneblock_update(Request $request){
        $update_data = PhoneBlock::find($request->id);
        if (!$update_data) {
            Toastr::error('Error','Record not found');
            return redirect()->back();
        }

        $request->validate([
            'phone'  => 'required',
            'reason' => 'nullable',
        ]);

        $normalized = normalize_phone($request->phone);
        if ($normalized === '') {
            Toastr::error('A valid phone number is required', 'Error');
            return redirect()->back();
        }

        // Reject a change that would collide with a different existing row.
        $clash = PhoneBlock::where('phone_normalized', $normalized)
            ->where('id', '!=', $update_data->id)
            ->exists();
        if ($clash) {
            Toastr::error('Another block already uses this phone number', 'Error');
            return redirect()->back();
        }

        // Clear the OLD number's cached verdict before it is overwritten.
        forget_phone_block_cache($update_data->phone);

        $update_data->phone  = trim($request->phone);
        $update_data->reason = $request->reason ?: 'ফেইক অর্ডার';
        $update_data->save();

        forget_phone_block_cache($update_data->phone);
        Toastr::success('Success','Phone number updated successfully');
        return redirect()->back();
    }

    public function phoneblock_destroy(Request $request){
        $delete_data = PhoneBlock::find($request->id);
        if (!$delete_data) {
            Toastr::error('Error','Record not found');
            return redirect()->back();
        }

        // Unblock must be effective immediately.
        forget_phone_block_cache($delete_data->phone);
        $delete_data->delete();
        Toastr::success('Success','Phone number unblocked successfully');
        return redirect()->back();
    }

    // AJAX — quick block from the order page
    public function phoneblock_quick_store(Request $request){
        try {
            $normalized = normalize_phone($request->phone);
            if ($normalized === '') {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Phone number is required',
                ], 400);
            }

            if (PhoneBlock::where('phone_normalized', $normalized)->exists()) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'This phone number is already blocked',
                ], 400);
            }

            $store_data = new PhoneBlock();
            $store_data->phone  = trim($request->phone);
            $store_data->reason = $request->reason ?: 'ফেইক অর্ডার';
            $store_data->save();

            forget_phone_block_cache($store_data->phone);

            return response()->json([
                'status'  => 'success',
                'message' => 'Phone number blocked successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to block phone: ' . $e->getMessage(),
            ], 500);
        }
    }

    // AJAX — used by the POS form to warn (not block) when a phone is banned
    public function phoneblock_check(Request $request){
        $blocked = is_phone_blocked($request->phone);

        return response()->json([
            'blocked' => (bool) $blocked,
            'phone'   => $request->phone,
            'reason'  => $blocked->reason ?? null,
        ]);
    }
}
