<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\LandingTemplate;
use App\Models\Product;
use App\Models\ProductVariantPrice;
use App\Models\ShippingCharge;
use App\Services\IncompleteOrderRecorder;
use App\Services\Landing\LandingPage;
use App\Services\Landing\LandingPricing;
use Illuminate\Http\Request;

/**
 * The public landing page. Only active landing pages are shown. Outside the validity window
 * the page is still shown, but its checkout is replaced by a closed notice (plan D6).
 */
class LandingController extends Controller
{
    public function show(string $slug)
    {
        $landing = LandingTemplate::where('slug', $slug)->where('status', true)->firstOrFail();

        return view('frontEnd.layouts.landing.' . $landing->template_key . '.page', LandingPage::viewData($landing));
    }

    /**
     * An order the visitor filled in but did not place. Saved like the shop's incomplete orders, so the
     * admin can follow it up. The price, delivery charge and discount are worked out here from the
     * database, never taken from the page.
     */
    public function incompleteOrder(Request $request, IncompleteOrderRecorder $recorder)
    {
        $data = $request->validate([
            'landing' => 'required|integer',
            'product' => 'required|integer',
            'variant' => 'nullable|integer',
            'quantity' => 'nullable|integer|min:1',
            'area' => 'nullable|integer',
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:55',
            'address' => 'required|string|max:500',
        ]);

        $landing = LandingTemplate::where('id', $data['landing'])->where('product_id', $data['product'])->first();
        if (! $landing || ! LandingPricing::isOpen($landing)) {
            return response()->json(['status' => 'closed'], 422);
        }

        $product = Product::with('image')->find($data['product']);

        $variant = null;
        if (! empty($data['variant'])) {
            $variant = ProductVariantPrice::with(['color', 'size'])
                ->where('product_id', $product->id)
                ->find($data['variant']);
            if (! $variant) {
                return response()->json(['status' => 'invalid', 'message' => 'Unknown option.'], 422);
            }
        }

        $quantity = max(1, min(LandingPricing::MAX_QUANTITY, (int) ($data['quantity'] ?? 1)));
        $unit = $variant
            ? (float) ($variant->price ?: $product->new_price)
            : (float) ($product->new_price ?? $product->old_price ?? 1);
        $subtotal = $unit * $quantity;

        $area = ! empty($data['area'])
            ? ShippingCharge::where('id', $data['area'])->where('status', '1')->first()
            : null;
        $delivery = $area ? (float) $area->amount : 0.0;
        $discount = LandingPricing::best($landing, $subtotal)['discount'];

        $option = $variant
            ? collect([optional($variant->color)->colorName, optional($variant->size)->sizeName])->filter()->implode(' / ')
            : '';

        $recorder->record([
            'name' => $data['name'],
            'phone' => $data['phone'],
            'address' => $data['address'],
            'items' => [[
                'id' => $product->id,
                'name' => $option !== '' ? $product->name . ' (' . $option . ')' : $product->name,
                'qty' => $quantity,
                'price' => $unit,
                'variant_price_id' => $variant?->id,
                'product_color' => optional(optional($variant)->color)->colorName,
                'product_size' => optional(optional($variant)->size)->sizeName,
            ]],
            'product_image' => $product->image->image ?? null,
            'product_link' => url('/landing/' . $landing->slug),
            'total_amount' => round($subtotal + $delivery - $discount, 2),
        ], $request->hasSession() ? $request->session()->getId() : null);

        return response()->json(['status' => 'success'], 200);
    }
}
