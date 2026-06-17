<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Popup;
use Illuminate\Support\Facades\File; 
use Toastr;

class PopupController extends Controller
{
    public function index()
    {
        $popups = Popup::latest()->get();
        return view('backEnd.popup.index', compact('popups'));
    }

    public function store(Request $request)
    {
        // শুধু ইমেজ বাধ্যতামূলক - সুদু ইমেজ আপলোড করেই পপআপ তৈরী করা যাবে
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5000',
        ]);

        try {
            $popup = new Popup();

            // ইমেজ আপলোড
            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $new_name = time() . '.' . $image->getClientOriginalExtension();
                $image->move(public_path('uploads/popup'), $new_name);
                $popup->image = 'uploads/popup/' . $new_name;
            }

            // টাইটেল না দিলে ইমেজের নাম বা ডিফল্ট ব্যবহার
            $popup->title = $request->title ?: pathinfo($request->file('image')->getClientOriginalName(), PATHINFO_FILENAME) ?: 'Popup';
            $popup->description = $request->description;
            $popup->btn_text = $request->btn_text;
            $popup->offer_end_text = $request->offer_end_text;
            $popup->link = $request->link;
            $popup->status = (int) ($request->status ?? 1);
            $popup->save();

            if(function_exists('toastr')){
                \Toastr::success('Popup Created Successfully');
            }
            return redirect()->back()->with('success', 'Popup Created Successfully');

        } catch (\Exception $e) {
            // যদি কোনো ইন্টারনাল এরর হয়
            return redirect()->back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        $edit = Popup::find($id);
        return view('backEnd.popup.edit', compact('edit'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'hidden_id' => 'required|exists:popups,id',
        ]);

        $popup = Popup::find($request->hidden_id);

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $new_name = time() . '.' . $image->getClientOriginalExtension();
            
            // পুরাতন ছবি ডিলিট
            if (File::exists(public_path($popup->image))) {
                File::delete(public_path($popup->image));
            }

            $image->move(public_path('uploads/popup'), $new_name);
            $popup->image = 'uploads/popup/' . $new_name;
        }

        $popup->title = $request->title ?: ($popup->title ?: 'Popup');
        $popup->description = $request->description;
        $popup->btn_text = $request->btn_text;
        $popup->offer_end_text = $request->offer_end_text;
        $popup->link = $request->link;
        $popup->status = (int) ($request->status ?? $popup->status);
        $popup->save();

        if(function_exists('toastr')){
            \Toastr::success('Popup Updated Successfully');
        }
        return redirect()->route('admin.popup.index');
    }

    public function status($id)
    {
        $popup = Popup::find($id);
        $popup->status = $popup->status == 1 ? 0 : 1;
        $popup->save();
        
        if(function_exists('toastr')){
            \Toastr::success('Status Changed');
        }
        return redirect()->back();
    }

    public function destroy($id)
    {
        $popup = Popup::find($id);
        if (File::exists(public_path($popup->image))) {
            File::delete(public_path($popup->image));
        }
        $popup->delete();
        
        if(function_exists('toastr')){
            \Toastr::success('Popup Deleted');
        }
        return redirect()->back();
    }
}