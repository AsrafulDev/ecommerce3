<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\Complaint;

class ComplaintController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'name'        => 'required|string|max:255',
            'phone'       => 'required|string|max:20',
            'order_id'    => 'nullable|string|max:50',
            'description' => 'required|string',
            'image'       => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        // Store customer evidence inside the Media Manager library.
        $imagePath = null;

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $mediaDirectory = public_path('uploads/media/complaints');
            if (! is_dir($mediaDirectory)) {
                mkdir($mediaDirectory, 0775, true);
            }

            $extension = strtolower($image->getClientOriginalExtension());
            $baseName = Str::slug(pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME));
            $imageName = ($baseName ?: 'complaint').'-'.uniqid().'.'.$extension;
            $image->move($mediaDirectory, $imageName);

            $imagePath = 'uploads/media/complaints/'.$imageName;
        }

        // 🔹 Save complaint
        Complaint::create([
            'customer_id' => auth()->guard('customer')->check() ? auth()->guard('customer')->id() : null,
            'name'        => $request->name,
            'phone'       => $request->phone,
            'order_id'    => $request->order_id,
            'description' => $request->description,
            'image'       => $imagePath,
            'status'      => 'pending',
        ]);

        return back()->with('success', __('Your complaint has been submitted successfully.'));
    }
}
