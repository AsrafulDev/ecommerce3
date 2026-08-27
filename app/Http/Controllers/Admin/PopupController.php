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
        // শুধু ইমেজ বাধ্যতামূলক - মিডিয়া লাইব্রেরি থেকে বাছাই অথবা সরাসরি আপলোড
        $request->validate([
            'image'     => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5000',
            'image_url' => 'required_without:image|nullable|string',
        ]);

        try {
            $popup = new Popup();

            // ইমেজ: সরাসরি আপলোড অথবা মিডিয়া লাইব্রেরি থেকে বাছাই
            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $new_name = time() . '.' . $image->getClientOriginalExtension();
                $image->move(public_path('uploads/popup'), $new_name);
                $popup->image = 'uploads/popup/' . $new_name;
            } elseif ($request->filled('image_url')) {
                // Selected from Media Gallery — popup stores WITHOUT 'public/' prefix
                $mediaPath = $request->input('image_url');
                if (str_starts_with($mediaPath, 'public/')) {
                    $mediaPath = substr($mediaPath, 7);
                }
                $popup->image = $mediaPath;
            }

            // টাইটেল না দিলে ইমেজের নাম বা ডিফল্ট ব্যবহার
            $defaultTitle = $request->hasFile('image')
                ? pathinfo($request->file('image')->getClientOriginalName(), PATHINFO_FILENAME)
                : 'Popup';
            $popup->title = $request->title ?: $defaultTitle;
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
            
            // পুরাতন ছবি ডিলিট (কিন্তু শেয়ার্ড Media Gallery এর ফাইল না)
            if (strpos($popup->image ?? '', 'uploads/media/') === false && File::exists(public_path($popup->image))) {
                File::delete(public_path($popup->image));
            }

            $image->move(public_path('uploads/popup'), $new_name);
            $popup->image = 'uploads/popup/' . $new_name;
        } elseif ($request->filled('image_url')) {
            // Selected from Media Gallery — popup stores WITHOUT 'public/' prefix
            $mediaPath = $request->input('image_url');
            if (str_starts_with($mediaPath, 'public/')) {
                $mediaPath = substr($mediaPath, 7);
            }
            if (strpos($popup->image ?? '', 'uploads/media/') === false && File::exists(public_path($popup->image ?? ''))) {
                File::delete(public_path($popup->image));
            }
            $popup->image = $mediaPath;
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