<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\Order;
use App\Models\User;
use App\Models\Product;
use App\Models\Customer;
use App\Models\OrderDetails;
use App\Models\FundTransaction;
use App\Models\Expense;
use App\Models\Category;
use Carbon\Carbon;
use Session;
use Toastr;
use Auth;
use DB;

class DashboardController extends Controller
{
    public function __construct()
    {
        // চাইলে এখানে auth middleware চালু করতে পারো
        // $this->middleware('auth')->except(['locked','unlocked']);
    }

    public function dashboard()
    {
        // =========================
        // BASIC COUNTS
        // =========================
        $total_order    = Order::count();
        $today_order    = Order::whereDate('created_at', Carbon::today())->count();
        $total_product  = Product::count();
        $total_customer = Customer::count();

        $latest_order = Order::latest()
            ->with('customer', 'product', 'product.image')
            ->limit(5)
            ->get();

        $latest_customer = Customer::latest()->limit(5)->get();

        // =========================
        // DELIVERY / LAST WEEK / LAST MONTH
        // =========================
        // ✅ Delivered status = 6
        // আজকে যেগুলো ডেলিভার্ড হলো (updated_at আজ)
        $today_delivery = Order::where('order_status', '6')
            ->whereDate('updated_at', Carbon::today())
            ->count();

        // মোট ডেলিভার্ড অর্ডার
        $total_delivery = Order::where('order_status', '6')->count();

        // এই সপ্তাহে যেগুলো ডেলিভার্ড হয়েছে
        $last_week = Order::where('order_status', '6')
            ->whereBetween('updated_at', [
                Carbon::now()->startOfWeek(),
                Carbon::now()->endOfWeek()
            ])->count();

        // গত মাসে ডেলিভার্ড
        $last_month = Order::where('order_status', '6')
            ->whereYear('updated_at', Carbon::now()->subMonth()->year)
            ->whereMonth('updated_at', Carbon::now()->subMonth()->month)
            ->count();

        // =========================
        // MONTHLY SALE (চার্টের জন্য)
        // =========================
        // এখানে চাইলে created_at বা updated_at যেকোনো ব্যবহার করতে পারো,
        // আমি delivered হিসাব ধরে updated_at নিলাম
        $monthly_sale = Order::select(
                DB::raw('DATE(updated_at) as date')
            )
            ->selectRaw('SUM(amount) as amount')
            ->where('order_status', '6') // শুধুই delivered অর্ডার
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->limit(30)
            ->get();

        // =========================
        // ⭐ TODAY PROFIT হিসাব
        // =========================
        // আজকে যেসব অর্ডার ডেলিভার্ড হয়েছে (status = 6 & updated_at = আজ)
        $todayDeliveredOrders = Order::where('order_status', '6')
            ->whereDate('updated_at', Carbon::today())
            ->get();

        // আজকের সেল (amount এর sum)
        $today_sales = $todayDeliveredOrders->sum('amount');

        // আজকের অর্ডারগুলোর id
        $todayOrderIds = $todayDeliveredOrders->pluck('id');

        // সেই অর্ডারগুলোর ডিটেইলস (with product to avoid N+1)
        $todayDetails = OrderDetails::whereIn('order_id', $todayOrderIds)
            ->with('product:id,purchase_price')
            ->get();

        // আজকের COGS (Cost of Goods Sold)
        $today_cogs = 0;

        foreach ($todayDetails as $row) {
            // OrderDetails এ purchase_price থাকলে সেটাই use করা ভাল
            $purchase_price = $row->purchase_price ?? ($row->product->purchase_price ?? 0);
            $today_cogs += ($purchase_price * $row->qty);
        }

        // আজকের প্রফিট = আজকের সেল - আজকের COGS
        $today_profit = $today_sales - $today_cogs;

        // =========================
        // ⭐ FUND BALANCE (তহবিল)
        // =========================
        $total_fund_in  = FundTransaction::where('direction', 'in')->sum('amount');
        $total_fund_out = FundTransaction::where('direction', 'out')->sum('amount');
        $fund_balance   = $total_fund_in - $total_fund_out;

        // =========================
        // ⭐ EXPENSES (খরচ)
        // =========================
        // সব খরচ
        $total_expenses = Expense::sum('amount');

        // আজকের খরচ
        $today_expenses = Expense::whereDate('created_at', Carbon::today())
            ->sum('amount');

        // এই মাসের খরচ
        $monthly_expenses = Expense::whereYear('created_at', Carbon::now()->year)
            ->whereMonth('created_at', Carbon::now()->month)
            ->sum('amount');

        // =========================
        // ⭐ SALES BY CATEGORY (Chart Data)
        // =========================
        // Delivered orders এর order_details থেকে category-wise sales calculate
        $deliveredOrderIds = Order::where('order_status', '6')->pluck('id');
        
        $categorySales = OrderDetails::whereIn('order_id', $deliveredOrderIds)
            ->join('products', 'order_details.product_id', '=', 'products.id')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->select(
                'categories.id as category_id',
                'categories.name as category_name',
                DB::raw('SUM(order_details.sale_price * order_details.qty) as total_sales')
            )
            ->groupBy('categories.id', 'categories.name')
            ->orderBy('total_sales', 'DESC')
            ->get();

        // Chart এর জন্য data format
        $categoryLabels = $categorySales->pluck('category_name')->toArray();
        $categorySeries = $categorySales->pluck('total_sales')->map(function($amount) {
            return (float) number_format($amount, 2, '.', '');
        })->toArray();

        // যদি কোনো category sales না থাকে, empty array return করবে
        if (empty($categoryLabels)) {
            $categoryLabels = ['No Sales'];
            $categorySeries = [0];
        }

        return view('backEnd.admin.dashboard', compact(
            'total_order',
            'today_order',
            'total_product',
            'total_customer',
            'latest_order',
            'latest_customer',
            'today_delivery',
            'total_delivery',
            'last_week',
            'last_month',
            'monthly_sale',
            'today_profit',
            'fund_balance',
            'total_expenses',
            'today_expenses',
            'monthly_expenses',
            'categoryLabels',
            'categorySeries'
        ));
    }

    public function changepassword()
    {
        return view('backEnd.admin.changepassword');
    }

    public function newpassword(Request $request)
    {
        $this->validate($request, [
            'old_password'     => 'required',
            'new_password'     => 'required',
            'confirm_password' => 'required_with:new_password|same:new_password|'
        ]);

        $user = User::find(Auth::id());
        $hashPass = $user->password;

        if (Hash::check($request->old_password, $hashPass)) {

            $user->fill([
                'password' => Hash::make($request->new_password)
            ])->save();

            Toastr::success('Success', 'Password changed successfully!');
            return redirect()->route('dashboard'); // অথবা route('admin.dashboard') তোমার রাউট নাম অনুযায়ী
        } else {
            Toastr::error('Failed', 'Old password not match!');
            return back();
        }
    }

    public function locked()
    {
        Session::put('locked', true);
        return view('backEnd.auth.locked');
    }

    public function unlocked(Request $request)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $password = $request->password;

        if (Hash::check($password, Auth::user()->password)) {
            Session::forget('locked');
            Toastr::success('Success', 'You are logged in successfully!');
            return redirect()->route('dashboard'); // অথবা route('admin.dashboard')
        }

        Toastr::error('Failed', 'Your password not match!');
        return back();
    }
}
