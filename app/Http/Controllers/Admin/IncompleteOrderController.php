<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\IncompleteOrder;

// নতুন যেগুলো লাগবে
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderDetails;
use App\Models\Shipping;
use App\Models\Payment;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Support\Str;

class IncompleteOrderController extends Controller
{
    /**
     * ইনকমপ্লিট অর্ডার লিস্ট
     */
    public function index()
    {
        $orders = IncompleteOrder::latest()->paginate(25);
        return view('backEnd.incomplete_orders.index', compact('orders'));
    }

    /**
     * ইনকমপ্লিট অর্ডার স্টোর (AJAX থেকে)
     */
    public function store(Request $request)
    {
        if (!$request->has('items') || empty($request->items)) {
            return response()->json([
                'status'  => 'ignore',
                'message' => 'No items in cart, skip saving.',
            ]);
        }

        $request->validate([
            'name'          => 'nullable|string|max:255',
            'phone'         => 'nullable|string|max:55',
            'address'       => 'nullable|string',
            'items'         => 'nullable|array',
            'product_image' => 'nullable|string',
            'product_link'  => 'nullable|string',
            'total_amount'  => 'nullable|numeric',
        ]);

        $order = IncompleteOrder::create([
            'name'          => $request->name,
            'phone'         => $request->phone,
            'address'       => $request->address,
            'items'         => $request->items,
            'product_image' => $request->product_image,
            'product_link'  => $request->product_link,
            'total_amount'  => $request->total_amount,
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Incomplete order saved successfully',
            'data'    => $order,
        ]);
    }

    /**
     * ইনকমপ্লিট অর্ডারকে রেগুলার অর্ডারে কনভার্ট করবে
     */
    public function accept($id)
    {
        $incomplete = IncompleteOrder::findOrFail($id);

        DB::beginTransaction();

        try {
            // items অ্যারে / json হ্যান্ডেল
            $items = is_array($incomplete->items)
                ? $incomplete->items
                : (json_decode($incomplete->items, true) ?? []);

            if (empty($items)) {
                Toastr::error('এই ইনকমপ্লিট অর্ডারে কোন প্রোডাক্ট নেই!', 'Error');
                return redirect()->back();
            }

            // subtotal
            $subtotal = 0;
            foreach ($items as $item) {
                $qty   = isset($item['qty']) ? (int) $item['qty'] : 1;
                $price = isset($item['price']) ? (float) $item['price'] : 0;
                $subtotal += $qty * $price;
            }

            $grandTotal    = $incomplete->total_amount ?? $subtotal;
            $shippingAmount = 0;
            $discount       = 0;
            $shippingName   = 'N/A';

            /**
             * CUSTOMER HANDLE
             */
            $baseName  = $incomplete->name ?: 'Customer';
            $slugValue = Str::slug($baseName) . '-' . rand(1000, 9999);

            $customer = null;

            if (!empty($incomplete->phone)) {
                // ফোন থাকলে ফোন দিয়ে কাস্টমার খুঁজে / তৈরি
                $customer = Customer::firstOrCreate(
                    ['phone' => $incomplete->phone],
                    [
                        'name'     => $baseName,
                        'slug'     => $slugValue,
                        'password' => bcrypt(rand(111111, 999999)),
                        'verify'   => 1,
                        'status'   => 'active',
                    ]
                );
            }

            // ফোন না থাকলে নতুন কাস্টমার
            if (!$customer) {
                $customer = Customer::create([
                    'name'     => $baseName,
                    'slug'     => $slugValue,
                    'phone'    => $incomplete->phone ?: '00000000000',
                    'password' => bcrypt(rand(111111, 999999)),
                    'verify'   => 1,
                    'status'   => 'active',
                ]);
            }

            /**
             * ORDER CREATE
             */
            $order                  = new Order();
            $order->invoice_id      = rand(11111, 99999);
            $order->amount          = $grandTotal;
            $order->discount        = $discount;
            $order->shipping_charge = $shippingAmount;
            $order->customer_id     = $customer->id;
            $order->order_status    = 1; // pending
            $order->note            = null;
            $order->save();

            /**
             * SHIPPING CREATE
             */
            $shipping              = new Shipping();
            $shipping->order_id    = $order->id;
            $shipping->customer_id = $customer->id;
            $shipping->name        = $incomplete->name;
            $shipping->phone       = $incomplete->phone;
            $shipping->address     = $incomplete->address;
            $shipping->area        = $shippingName;
            $shipping->save();

            /**
             * PAYMENT CREATE
             */
            $payment                 = new Payment();
            $payment->order_id       = $order->id;
            $payment->customer_id    = $customer->id;
            $payment->payment_method = 'Cash On Delivery';
            $payment->amount         = $order->amount;
            $payment->payment_status = 'pending';
            $payment->save();

            /**
             * ORDER DETAILS + STOCK UPDATE
             */
            foreach ($items as $item) {
                $productId = $item['id'] ?? null;
                $product   = $productId ? Product::find($productId) : null;

                $qty = $item['qty'] ?? 1;

                $detail                   = new OrderDetails();
                $detail->order_id         = $order->id;
                $detail->product_id       = $productId;
                $detail->product_name     = $item['name'] ?? ($product->name ?? 'Product');
                $detail->purchase_price   = $product->purchase_price ?? 0;
                $detail->product_discount = 0;
                $detail->sale_price       = $item['price'] ?? ($product->new_price ?? 0);
                $detail->qty              = $qty;
                $detail->save();

                // 🔻 স্টক কমানো
                if ($product) {

                    if (Schema::hasColumn('products', 'stock')) {
                        $product->stock = max(0, (int)$product->stock - (int)$qty);
                        $product->save();
                    } elseif (Schema::hasColumn('products', 'qty')) {
                        $product->qty = max(0, (int)$product->qty - (int)$qty);
                        $product->save();
                    } elseif (Schema::hasColumn('products', 'quantity')) {
                        $product->quantity = max(0, (int)$product->quantity - (int)$qty);
                        $product->save();
                    }
                }
            }

            // ইনকমপ্লিট অর্ডার ডিলিট
            $incomplete->delete();

            DB::commit();

            Toastr::success('Incomplete order কে সফলভাবে রেগুলার অর্ডারে কনভার্ট করা হয়েছে।', 'Success');

            return redirect()->route('admin.order.edit', $order->invoice_id);

        } catch (\Exception $e) {
            DB::rollBack();
            Toastr::error('Something went wrong: ' . $e->getMessage(), 'Error');
            return redirect()->back();
        }
    }

    /**
     * ইনকমপ্লিট অর্ডার ডিলিট
     */
    public function destroy($id)
    {
        $order = IncompleteOrder::findOrFail($id);
        $order->delete();

        return redirect()->back()->with('success', 'Incomplete order deleted successfully.');
    }
}
