<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

use App\Http\Controllers\Frontend\FrontendController;
use App\Http\Controllers\Frontend\ShoppingController;
use App\Http\Controllers\Frontend\CustomerController;
use App\Http\Controllers\Frontend\BkashController;
use App\Http\Controllers\Frontend\ShurjopayControllers;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\EmailSettingController;
use App\Http\Controllers\Admin\StockController;
use App\Http\Controllers\Admin\DashboardController;
use Illuminate\Support\Facades\Response;

use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\SubcategoryController;
use App\Http\Controllers\Admin\ChildcategoryController;
use App\Http\Controllers\Admin\PixelsController;
use App\Http\Controllers\Admin\TiktokPixelsController;
use App\Http\Controllers\Admin\BrandController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ApiIntegrationController;
use App\Http\Controllers\Admin\CronJobController;
use App\Http\Controllers\Admin\GeneralSettingController;
use App\Http\Controllers\Admin\SocialMediaController;
use App\Http\Controllers\Admin\ContactController;
use App\Http\Controllers\Admin\BannerCategoryController;
use App\Http\Controllers\Admin\BannerController;
use App\Http\Controllers\Admin\MediaController;
use App\Http\Controllers\Admin\CreatePageController;
use App\Http\Controllers\Admin\CampaignController;
use App\Http\Controllers\Admin\ThemeController;
use App\Http\Controllers\Admin\LayoutController;
use App\Http\Controllers\Admin\ProductDesignController;
use App\Http\Controllers\Admin\DemoController;
use App\Http\Controllers\Admin\BackupController;
use App\Http\Controllers\Admin\HeaderFooterController;
use App\Http\Controllers\Admin\ErrorLogController;
use App\Http\Controllers\Admin\ReviewController;
use App\Http\Controllers\Admin\CustomerManageController;
use App\Http\Controllers\Admin\ShippingChargeController;
use App\Http\Controllers\Admin\ColorController;
use App\Http\Controllers\Admin\SizeController;
use App\Http\Controllers\Admin\DistrictController;
use App\Http\Controllers\Admin\TagManagerController;
use App\Http\Controllers\Admin\IncompleteOrderController;
use App\Http\Controllers\Frontend\UddoktaPayController;
use App\Http\Controllers\Frontend\AamarPayController;
use Illuminate\Support\Facades\Artisan;
use Brian2694\Toastr\Facades\Toastr; // যদি না থাকে তাহলে যোগ করো
use App\Http\Controllers\Admin\SitemapController;
use App\Http\Controllers\Admin\CouponController;
use App\Http\Controllers\Admin\FraudSettingController;
use App\Http\Controllers\Admin\FundController;
use App\Http\Controllers\Admin\ExpenseController;
use App\Http\Controllers\Admin\PurchaseController;
use App\Http\Controllers\Admin\SupplierController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\DigitalDownloadController;
use App\Http\Controllers\Frontend\ComplaintController;
use App\Http\Controllers\Admin\AdminComplaintController;
use App\Http\Controllers\Admin\ContactMessageController;
use App\Http\Controllers\Admin\FacebookCapiSettingController;
use App\Http\Controllers\Admin\GoogleAnalyticSettingController;
use App\Http\Controllers\Admin\AdsAnalyticsController;
use App\Http\Controllers\Admin\FacebookPageController;
use App\Http\Controllers\Frontend\ContactMessageController as FrontendContactMessageController;
use App\Http\Controllers\Frontend\BlogController;
use App\Http\Controllers\Admin\BlogController as AdminBlogController;
use App\Http\Controllers\Admin\PopupController;


Route::get('admin/clear-cache', function () {
    Artisan::call('optimize:clear');
    return redirect()->back()->with('success', '✅ Cache cleared successfully!');
})->middleware(['auth:admin', 'admin', 'demo_mode'])->name('admin.clear.cache');

// Admin root route - redirect to login if not authenticated, otherwise to dashboard
Route::get('admin', function () {
    if (Auth::guard('admin')->check()) {
        return redirect()->route('admin.dashboard');
    }
    return redirect()->route('login');
})->name('admin');

Auth::routes();
// Admin Forgot Password Routes
Route::get('admin/forgot-password', [App\Http\Controllers\Admin\Auth\ForgotPasswordController::class, 'showLinkRequestForm'])->name('admin.password.request');
Route::post('admin/forgot-password', [App\Http\Controllers\Admin\Auth\ForgotPasswordController::class, 'sendResetLinkEmail'])->name('admin.password.email');

// Admin Reset Password Routes
Route::get('admin/reset-password/{token}', [App\Http\Controllers\Admin\Auth\ResetPasswordController::class, 'showResetForm'])
    ->name('admin.password.reset');
Route::post('admin/reset-password', [App\Http\Controllers\Admin\Auth\ResetPasswordController::class, 'reset'])
    ->name('admin.password.update');

Route::post('/admin/fraud-check', [App\Http\Controllers\Admin\OrderController::class, 'fraudCheck'])
    ->middleware(['auth:admin', 'admin', 'demo_mode'])
    ->name('admin.fraud.check');
Route::get('/cc', function() {
    Artisan::call('config:clear');
    Artisan::call('cache:clear');
    Artisan::call('route:clear');
    Artisan::call('view:clear');
    return "Cleared!";
});


Route::group(['prefix' => 'admin', 'as' => 'admin.', 'middleware' => ['demo_mode']], function () {
    
    // Popup Routes
    Route::get('/popup', [PopupController::class, 'index'])->name('popup.index');
    Route::post('/popup/store', [PopupController::class, 'store'])->name('popup.store');
    
    // Edit & Update Routes (নতুন যোগ করা হয়েছে)
    Route::get('/popup/edit/{id}', [PopupController::class, 'edit'])->name('popup.edit');
    Route::post('/popup/update', [PopupController::class, 'update'])->name('popup.update');

    // Status & Delete
    Route::post('/popup/status/{id}', [PopupController::class, 'status'])->name('popup.status');
    Route::post('/popup/delete/{id}', [PopupController::class, 'destroy'])->name('popup.destroy');

});


Route::prefix('admin')
    ->middleware(['auth:admin', 'admin', 'lock', 'check_refer', 'demo_mode'])
    ->name('admin.')
    ->group(function () {
        Route::get('/fraud-settings', [FraudSettingController::class, 'index'])->name('fraud.index');
        Route::post('/fraud-settings/update', [FraudSettingController::class, 'update'])->name('fraud.update');
        
        // Order Restriction Settings
        Route::get('/order-restriction-settings', [App\Http\Controllers\Admin\OrderRestrictionSettingController::class, 'index'])->name('order.restriction.setting.index');
        Route::post('/order-restriction-settings/update', [App\Http\Controllers\Admin\OrderRestrictionSettingController::class, 'update'])->name('order.restriction.setting.update');
    });


Route::prefix('admin')->middleware(['auth:admin', 'admin', 'demo_mode'])->group(function () {
    Route::get('/sitemap', [SitemapController::class, 'index'])->name('admin.sitemap.index');
    Route::post('/sitemap/generate', [SitemapController::class, 'generate'])->name('admin.sitemap.generate');
});

// চেকআউট থেকে কাস্টমার ইনকমপ্লিট অর্ডার সেভ করে (অ্যাডমিন লগইন লাগে না)
Route::post('/incomplete-order/store', 
    [\App\Http\Controllers\Frontend\FrontendController::class, 'storeIncompleteOrder']
)->name('incomplete.order.store');

// RedX Webhook (CSRF excluded)
Route::post('/api/redx/webhook', [\App\Http\Controllers\Admin\RedXWebhookController::class, 'handleWebhook'])
    ->name('redx.webhook');

	
Route::get('/style.css', function () {
    $css = view('frontEnd.assets.style')->render();   // Blade থেকে CSS রেন্ডার
    return Response::make($css, 200, [
        'Content-Type'  => 'text/css',
        'Cache-Control' => 'public, max-age=3600', // ১ ঘন্টা ব্রাউজারে ক্যাশ হবে
    ]);
});

Route::get('/responsive.css', function () {
    $css = view('frontEnd.assets.responsive')->render();
    return Response::make($css, 200, [
        'Content-Type'  => 'text/css',
        'Cache-Control' => 'public, max-age=3600',
    ]);
});
Route::get('/dynamic-theme.css', function () {
    return response()
        ->view('frontEnd.assets.theme')
        ->header('Content-Type', 'text/css');
});

// Serve sitemap.xml from public folder (for php artisan serve compatibility)
Route::get('/sitemap.xml', function () {
    $path = public_path('sitemap.xml');
    if (!file_exists($path)) {
        abort(404);
    }
    return response()->file($path, [
        'Content-Type' => 'application/xml',
    ]);
})->name('sitemap.xml');

Route::get('/digital-download/{token}', [DigitalDownloadController::class, 'download'])
    ->name('digital.download');
/* Blog Frontend */
Route::get('/blogs', [BlogController::class, 'index'])->name('blogs');
Route::get('/blog/{slug}', [BlogController::class, 'details'])->name('blog.details');




Route::prefix('admin')
    ->middleware(['auth', 'demo_mode'])
    ->name('admin.')
    ->group(function () {

        // Blog Management
        Route::get('/blogs', [AdminBlogController::class, 'index'])
            ->name('blog.index');

        Route::get('/blog/create', [AdminBlogController::class, 'create'])
            ->name('blog.create');

        Route::post('/blog/store', [AdminBlogController::class, 'store'])
            ->name('blog.store');

        Route::get('/blog/edit/{id}', [AdminBlogController::class, 'edit'])
            ->name('blog.edit');

        Route::post('/blog/update/{id}', [AdminBlogController::class, 'update'])
            ->name('blog.update');

        Route::get('/blog/delete/{id}', [AdminBlogController::class, 'delete'])
            ->name('blog.delete');
    });

	
Route::get('/complaint', function () {
    $contact = \App\Models\Contact::where('status',1)->first();
    $cmnmenu = \App\Models\CreatePage::where('status',1)->get();
    return view('frontEnd.layouts.pages.complaint', compact('contact','cmnmenu'));
})->name('complaint');

Route::post('/complaint-store', [ComplaintController::class, 'store'])
    ->name('complaint.store');
// Admin complaints
Route::get('/admin/complaints', [AdminComplaintController::class, 'index'])
    ->middleware(['auth:admin', 'admin'])->name('backEnd.complaints.index');

Route::post('/admin/complaints/{id}/status', [AdminComplaintController::class, 'updateStatus'])
    ->middleware(['auth:admin', 'admin', 'demo_mode'])->name('backEnd.complaints.status');

Route::delete('/admin/complaints/{id}', [AdminComplaintController::class, 'destroy'])
    ->middleware(['auth:admin', 'admin', 'demo_mode'])->name('backEnd.complaints.destroy');


Route::post('cart/apply-coupon', [ShoppingController::class, 'applyCoupon'])->name('coupon.apply');
Route::get('cart/remove-coupon', [ShoppingController::class, 'removeCoupon'])->name('coupon.remove');
Route::prefix('admin')->middleware(['auth:admin', 'admin', 'demo_mode'])->group(function () {
    // Fund Routes
    Route::get('/fund', [FundController::class, 'index'])->name('admin.fund.index');
    Route::post('/fund/add', [FundController::class, 'add'])->name('admin.fund.add');
    Route::post('/fund/withdraw', [FundController::class, 'withdraw'])->name('admin.fund.withdraw');
    Route::get('/fund/export', [FundController::class, 'export'])->name('admin.fund.export');
    Route::get('/fund/logs', [FundController::class, 'logs'])->name('admin.fund.logs');
    Route::get('/fund/{id}/edit', [FundController::class, 'edit'])->name('admin.fund.edit');
    Route::post('/fund/{id}/update', [FundController::class, 'update'])->name('admin.fund.update');
    Route::delete('/fund/{id}', [FundController::class, 'destroy'])->name('admin.fund.destroy');

    // Expense Routes
    Route::get('/expenses', [ExpenseController::class,'index'])->name('admin.expenses.index');
    Route::post('/expenses/store', [ExpenseController::class,'store'])->name('admin.expenses.store');
    Route::get('/expenses/logs', [ExpenseController::class,'logs'])->name('admin.expenses.logs');
    Route::get('/expenses/{id}/edit', [ExpenseController::class,'edit'])->name('admin.expenses.edit');
    Route::post('/expenses/{id}/update', [ExpenseController::class,'update'])->name('admin.expenses.update');
    Route::delete('/expenses/{id}', [ExpenseController::class,'destroy'])->name('admin.expenses.destroy');
    Route::get('/expenses/export', [ExpenseController::class,'export'])->name('admin.expenses.export');
});

	

// ✅ উদ্যোক্তা পে (UddoktaPay) রাউট
Route::get('/uddoktapay/checkout', [UddoktaPayController::class, 'checkout'])->name('uddoktapay.checkout');
Route::get('/uddoktapay/deposit/checkout/{deposit_id}', [UddoktaPayController::class, 'depositCheckout'])->name('uddoktapay.deposit.checkout');
Route::get('/uddoktapay/verify', [UddoktaPayController::class, 'verify'])->name('uddoktapay.verify');
Route::get('/uddoktapay/cancel', [UddoktaPayController::class, 'cancel'])->name('uddoktapay.cancel');
Route::post('/uddoktapay/ipn', [UddoktaPayController::class, 'ipn'])->name('uddoktapay.ipn');

// ✅ aamarPay রাউট (GET এবং POST দুটোই support করে)
Route::match(['get', 'post'], '/aamarpay/checkout', [AamarPayController::class, 'checkout'])->name('aamarpay.checkout');
Route::match(['get', 'post'], '/aamarpay/success', [AamarPayController::class, 'success'])->name('aamarpay.success');
Route::match(['get', 'post'], '/aamarpay/fail', [AamarPayController::class, 'fail'])->name('aamarpay.fail');
Route::get('/aamarpay/cancel', [AamarPayController::class, 'cancel'])->name('aamarpay.cancel');

// ✅ Manual Payment Status Change
Route::post('admin/order/update-payment-status', [App\Http\Controllers\Admin\OrderController::class, 'updatePaymentStatus'])
     ->middleware(['auth:admin', 'admin', 'demo_mode'])->name('admin.order.updatePaymentStatus');

// ✅ Manual Order Status Change (from invoice page)
Route::post('admin/order/update-single-status', [App\Http\Controllers\Admin\OrderController::class, 'updateSingleStatus'])
     ->middleware(['auth:admin', 'admin', 'demo_mode'])->name('admin.order.updateSingleStatus');

Route::post('admin/order/update-note', [\App\Http\Controllers\Admin\OrderController::class, 'updateNote'])
    ->middleware(['auth:admin', 'admin', 'demo_mode'])->name('admin.order.update_note');


// Admin Routes
Route::prefix('admin')->middleware(['auth:admin', 'admin', 'demo_mode'])->group(function(){
    // ইনকমপ্লিট অর্ডার লিস্ট
    Route::get('/incomplete-orders', [IncompleteOrderController::class, 'index'])
        ->name('admin.incomplete-orders.index');

    // ✅ ইনকমপ্লিট অর্ডার থেকে Accept করে অর্ডারে নিয়ে যাও
    Route::post('/incomplete-orders/{id}/accept', [IncompleteOrderController::class, 'accept'])
        ->name('admin.incomplete-orders.accept');

    // ইনকমপ্লিট অর্ডার ডিলিট
    Route::delete('/incomplete-orders/{id}', [IncompleteOrderController::class, 'destroy'])
        ->name('admin.incomplete-orders.destroy');

});

// Manual Fraud Check Routes
Route::get('admin/manual-fraud-check', [App\Http\Controllers\Admin\OrderController::class, 'manualFraudCheckPage'])->middleware(['auth:admin', 'admin'])->name('manualFraud.page');
Route::post('admin/manual-fraud-check', [App\Http\Controllers\Admin\OrderController::class, 'manualFraudCheck'])->middleware(['auth:admin', 'admin', 'demo_mode'])->name('manualFraud.check');

// Duplicate Order Check Routes
Route::post('/admin/duplicate-order-check', [App\Http\Controllers\Admin\OrderController::class, 'duplicateOrderCheck'])
    ->middleware(['auth:admin', 'admin', 'demo_mode'])->name('admin.duplicate.order.check');
Route::get('admin/manual-duplicate-order-check', [App\Http\Controllers\Admin\OrderController::class, 'manualDuplicateOrderCheckPage'])->middleware(['auth:admin', 'admin'])->name('manualDuplicateOrder.page');
Route::post('admin/manual-duplicate-order-check', [App\Http\Controllers\Admin\OrderController::class, 'manualDuplicateOrderCheck'])->middleware(['auth:admin', 'admin', 'demo_mode'])->name('manualDuplicateOrder.check');
Route::get('admin/all-duplicate-orders', [App\Http\Controllers\Admin\OrderController::class, 'allDuplicateOrders'])->middleware(['auth:admin', 'admin'])->name('admin.all_duplicate_orders');


Route::get('/admin/dashboard', [App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('dashboard');

Route::get('/controller', function() {
    Artisan::call('make:controller Admin/TagManagerController');
    return "Controller Done!";
});

// Admin custom SMS
Route::get('/admin/sms/custom-send', [App\Http\Controllers\Admin\ApiIntegrationController::class, 'sms_custom_send_page'])->middleware(['auth:admin', 'admin'])->name('admin.sms.custom.page');
Route::post('/admin/sms/custom-send', [App\Http\Controllers\Admin\ApiIntegrationController::class, 'sms_custom_send'])->middleware(['auth:admin', 'admin', 'demo_mode'])->name('admin.sms.custom.send');


// 🌐 Language Switcher
Route::get('lang/{locale}', function ($locale) {
    if (in_array($locale, ['en', 'bn'])) {
        session(['locale' => $locale]);
    }
    return redirect()->back();
})->name('lang.switch');

// 📄 Public Invoice Download (no auth needed — from order tracking)
Route::get('order/invoice/{id}', [App\Http\Controllers\Frontend\FrontendController::class, 'orderInvoice'])
    ->name('customer.order_invoice_download');

Route::group(['namespace'=>'Frontend', 'middleware' => ['ipcheck','check_refer']], function() {
    Route::get('/', [FrontendController::class, 'index'])->name('home');
	
    Route::get('brands', [FrontendController::class, 'brands'])->name('brands');
    Route::get('brand/{slug}', [FrontendController::class, 'brand'])
        ->name('brand.products');

    Route::get('category/{category}', [FrontendController::class, 'category'])->name('category');

    Route::get('subcategory/{subcategory}', [FrontendController::class, 'subcategory'])->name('subcategory');

    Route::get('products/{slug}', [FrontendController::class, 'products'])->name('products');
    Route::get('wholesale-products', [FrontendController::class, 'wholesaleProducts'])->name('wholesale.products');

    Route::get('hot-deals', [FrontendController::class, 'hotdeals'])->name('hotdeals');
    Route::get('flash-sales', [FrontendController::class, 'flashsales'])->name('flashsales');
    Route::get('shop', [FrontendController::class, 'shop'])->name('shop');
    Route::get('livesearch', [FrontendController::class, 'livesearch'])->name('livesearch');
    Route::get('search', [FrontendController::class, 'search'])->name('search');
    Route::get('product/{id}', [FrontendController::class, 'details'])->name('product');    
    Route::get('quick-view', [FrontendController::class, 'quickview'])->name('quickview');
    Route::get('/shipping-charge', [FrontendController::class, 'shipping_charge'])->name('shipping.charge');
    Route::get('/page/{slug}', [FrontendController::class, 'page'])->name('page');
    Route::get('districts', [FrontendController::class, 'districts'])->name('districts');
    Route::get('/campaign/{slug}', [FrontendController::class, 'campaign'])->name('campaign');
    Route::get('/offer', [FrontendController::class, 'offers'])->name('offers');
     Route::get('/payment-success', [FrontEndController::class, 'payment_success'])->name('payment_success');
    Route::get('/payment-cancel', [FrontEndController::class, 'payment_cancel'])->name('payment_cancel');




Route::post('/cart/store', [FrontendController::class, 'cartStore'])->name('cart.store');


    Route::get('/add-to-cart/{id}/{qty}', [ShoppingController::class, 'addTocartGet']);

    Route::get('shop/cart', [ShoppingController::class, 'cart_show'])->name('cart.show');
    Route::get('cart/remove', [ShoppingController::class, 'cart_remove'])->name('cart.remove');
    Route::get('cart/count', [ShoppingController::class, 'cart_count'])->name('cart.count');
    Route::get('mobilecart/count', [ShoppingController::class, 'mobilecart_qty'])->name('mobile.cart.count');
    Route::get('cart/sidebar', [ShoppingController::class, 'cartSidebar'])->name('cart.sidebar');
    Route::get('cart/decrement', [ShoppingController::class, 'cart_decrement'])->name('cart.decrement');

    Route::get('cart/increment', [ShoppingController::class, 'cart_increment'])->name('cart.increment');
    Route::get('/cart/change-product', [ShoppingController::class, 'changeProduct'])->name('cart.changeProduct');
    Route::get('cart/update', [ShoppingController::class, 'cart_update'])->name('cart.update');


});

Route::group(['prefix'=>'customer','namespace'=>'Frontend', 'middleware' => ['ipcheck','check_refer']], function() {
    
	
	Route::get('/login', [CustomerController::class, 'login'])->name('customer.login');
    Route::post('/signin', [CustomerController::class, 'signin'])->name('customer.signin');
    Route::get('/register', [CustomerController::class, 'register'])->name('customer.register');
    Route::post('/store', [CustomerController::class, 'store'])->name('customer.store');
    Route::get('/verify', [CustomerController::class, 'verify'])->name('customer.verify');
    Route::post('/verify-account', [CustomerController::class, 'account_verify'])->name('customer.account.verify');
    Route::post('/resend-otp', [CustomerController::class, 'resendotp'])->name('customer.resendotp');
    Route::post('/logout', [CustomerController::class, 'logout'])->name('customer.logout');
    Route::post('/post/review', [CustomerController::class, 'review'])->name('customer.review');
    Route::get('/forgot-password', [CustomerController::class, 'forgot_password'])->name('customer.forgot.password');
    Route::post('/forgot-verify', [CustomerController::class, 'forgot_verify'])->name('customer.forgot.verify');
    Route::get('/forgot-password/reset', [CustomerController::class, 'forgot_reset'])->name('customer.forgot.reset');
    Route::post('/forgot-password/store', [CustomerController::class, 'forgot_store'])->name('customer.forgot.store');
    Route::post('/forgot-password/resendotp', [CustomerController::class, 'forgot_resend'])->name('customer.forgot.resendotp');
    Route::get('/checkout', [CustomerController::class, 'checkout'])->name('customer.checkout');
    Route::post('/order-save', [CustomerController::class, 'order_save'])->name('customer.ordersave');
    Route::get('/order-success/{id}', [CustomerController::class, 'order_success'])->name('customer.order_success');
    Route::get('/order-success/{id}/download-invoice', [CustomerController::class, 'downloadInvoicePdf'])->name('customer.order_invoice_pdf');

   Route::get('/order-track', [CustomerController::class, 'order_track'])->name('customer.order_track');
    Route::get('/order-track/result', [CustomerController::class, 'order_track_result'])->name('customer.order_track_result');
    

});
// customer auth
Route::group(['prefix'=>'customer','namespace'=>'Frontend','middleware' => ['customer','ipcheck','check_refer']], function() {
    
    Route::get('/account', [CustomerController::class, 'account'])->name('customer.account');
    
    Route::get('/orders', [CustomerController::class, 'orders'])->name('customer.orders');
    Route::get('/invoice', [CustomerController::class, 'invoice'])->name('customer.invoice');
    Route::get('/invoice/order-note', [CustomerController::class, 'order_note'])->name('customer.order_note');
    Route::get('/profile-edit', [CustomerController::class, 'profile_edit'])->name('customer.profile_edit');
    Route::post('/profile-update', [CustomerController::class, 'profile_update'])->name('customer.profile_update');
    Route::get('/change-password', [CustomerController::class, 'change_pass'])->name('customer.change_pass');
    Route::post('/password-update', [CustomerController::class, 'password_update'])->name('customer.password_update');
    
    // Refund Routes
    Route::get('/refunds', [\App\Http\Controllers\Frontend\RefundController::class, 'index'])->name('customer.refunds');
    Route::get('/refunds/request/{order_id}', [\App\Http\Controllers\Frontend\RefundController::class, 'create'])->name('customer.refunds.create');
    Route::post('/refunds/request', [\App\Http\Controllers\Frontend\RefundController::class, 'store'])->name('customer.refunds.store');
    Route::get('/refunds/{id}', [\App\Http\Controllers\Frontend\RefundController::class, 'show'])->name('customer.refunds.show');
    Route::delete('/refunds/{id}/cancel', [\App\Http\Controllers\Frontend\RefundController::class, 'cancel'])->name('customer.refunds.cancel');
    
    // Customer Complaints (Support Tickets)
    Route::get('/complaints', [CustomerController::class, 'complaints'])->name('customer.complaints');
    
    // ── Warranty ──────────────────────────────
    Route::get('/warranties', fn() => view('frontEnd.layouts.customer.warranties'))->name('customer.warranties');
    Route::get('/warranty-claim/{warranty_sale_id}', function ($warranty_sale_id) {
        $warrantySale = \App\Models\WarrantySale::with(['product', 'order', 'claims', 'activeClaim'])->findOrFail($warranty_sale_id);
        return view('frontEnd.layouts.customer.file-warranty-claim', compact('warrantySale'));
    })->name('customer.warranty.claim');
    Route::post('/warranty-claim', [App\Http\Controllers\Api\WarrantyApiController::class, 'fileClaimWeb'])
        ->name('customer.warranty.submit-claim');
    Route::get('/warranty-track/{claim_id}', function ($claim_id) {
        $claim = \App\Models\WarrantyClaim::with(['product', 'warrantySale', 'stages', 'notes.user', 'challans'])->findOrFail($claim_id);
        // Eager-load per-step attachments if the migration has been run (safe otherwise)
        if (\Illuminate\Support\Facades\Schema::hasTable('warranty_claim_stage_attachments')) {
            $claim->load('stages.attachments');
        }
        return view('frontEnd.layouts.customer.track-warranty-claim', compact('claim'));
    })->name('customer.warranty.track');
    Route::post('/warranty-cancel', [App\Http\Controllers\Api\WarrantyApiController::class, 'cancelClaimWeb'])
        ->name('customer.warranty.cancel-claim');
    Route::get('/warranty-challan/{challan}', function (\App\Models\WarrantyChallan $challan) {
        $customer = auth('customer')->user();
        if (!$customer || $challan->warrantyClaim->customer_id !== $customer->id) {
            abort(403, 'Unauthorized access to this challan.');
        }
        // Customers may only view/download customer-facing challans
        // (Product Receive + Customer Delivery), not internal supplier ones.
        if (!in_array($challan->challan_type, ['receive', 'delivery'])) {
            abort(403, 'This challan is not available to customers.');
        }
        $challan->load('warrantyClaim.product', 'warrantyClaim.warrantySale');
        return view('frontEnd.layouts.customer.challan_print', compact('challan'));
    })->name('customer.warranty.challan');

        // Contact page
    Route::get('site/contact-us', [FrontendController::class, 'contact'])
        ->name('contact');

    // Contact form submit (✅ correct place)
Route::post('contact/store',
    [FrontendContactMessageController::class, 'store']
)->name('frontend.contact.store');

    // Newsletter subscribe (footer)
Route::post('newsletter/subscribe',
    [\App\Http\Controllers\Frontend\NewsletterController::class, 'store']
)->name('frontend.newsletter.subscribe');
    Route::get('bkash/checkout-url/pay',[BkashController::class,'pay'])->name('url-pay');
Route::any('bkash/checkout-url/create',[BkashController::class,'create'])->name('url-create');
Route::get('bkash/checkout-url/callback',[BkashController::class,'callback'])->name('url-callback');
    Route::get('/payment-success', [ShurjopayControllers::class, 'payment_success'])->name('payment_success');
    Route::get('/payment-cancel', [ShurjopayControllers::class, 'payment_cancel'])->name('payment_cancel');

});

// unathenticate admin route
Route::group(['namespace'=>'Admin','prefix'=>'admin','middleware' => ['customer','ipcheck','check_refer']], function() {
    Route::get('locked', [DashboardController::class, 'locked'])->name('locked');
    Route::post('unlocked', [DashboardController::class, 'unlocked'])->name('unlocked');
});

// ajax route
Route::get('/ajax-product-subcategory', [ProductController::class, 'getSubcategory']);
Route::get('/ajax-product-childcategory', [ProductController::class, 'getChildcategory']);
Route::get('/admin/products/{id}/variants', function($id) {
    $variants = \App\Models\ProductVariantPrice::where('product_id', $id)
        ->with(['color', 'size'])
        ->get()
        ->map(fn($v) => [
            'id' => $v->id,
            'color_name' => $v->color->colorName ?? $v->color->name ?? null,
            'size_name' => $v->size->sizeName ?? $v->size->name ?? null,
            'stock' => $v->stock ?? 0,
            'price' => $v->price ?? 0,
        ]);
    return response()->json($variants);
})->name('admin.product.variants');

// auth route
// admin route group
Route::group(['middleware' => ['auth:admin','admin','lock','check_refer','demo_mode'], 'prefix' => 'admin'], function () {
	// 🟢 Coupon Management
Route::get('coupon/manage', [CouponController::class, 'index'])->name('admin.coupons.index');
Route::get('coupon/create', [CouponController::class, 'create'])->name('admin.coupons.create');
Route::post('coupon/save', [CouponController::class, 'store'])->name('admin.coupons.store');
Route::get('coupon/{id}/edit', [CouponController::class, 'edit'])->name('admin.coupons.edit');
Route::match(['put', 'post'], 'coupon/update/{id}', [CouponController::class, 'update'])->name('admin.coupons.update');

Route::delete('coupon/destroy/{id}', [CouponController::class, 'destroy'])
     ->name('admin.coupons.destroy');

// লাইসেন্স ইনফরমেশন দেখার রাউট
Route::get('license-info', [App\Http\Controllers\Admin\LicenseController::class, 'licenseInfo'])->name('admin.license.info');

// লাইসেন্স কী আপডেট করার রাউট (অ্যাডমিন থেকে, DB-তে সংরক্ষণ)
Route::post('license-info/save-key', [App\Http\Controllers\Admin\LicenseController::class, 'saveLicenseKey'])->name('admin.license.save');

// Update Management Routes (License Protected)
Route::get('updates', [App\Http\Controllers\Admin\UpdateController::class, 'index'])->name('admin.updates.index');
Route::get('updates/check', [App\Http\Controllers\Admin\UpdateController::class, 'checkUpdates'])->name('admin.updates.check');
Route::get('updates/info', [App\Http\Controllers\Admin\UpdateController::class, 'getUpdateInfo'])->name('admin.updates.info');
Route::post('updates/download', [App\Http\Controllers\Admin\UpdateController::class, 'downloadUpdate'])->name('admin.updates.download');
Route::post('updates/install', [App\Http\Controllers\Admin\UpdateController::class, 'installUpdate'])->name('admin.updates.install');
Route::get('updates/backups', [App\Http\Controllers\Admin\UpdateController::class, 'listBackups'])->name('admin.updates.backups');
Route::post('updates/create-backup', [App\Http\Controllers\Admin\UpdateController::class, 'createBackup'])->name('admin.updates.create-backup');
Route::get('updates/backup/download/{filename}', [App\Http\Controllers\Admin\UpdateController::class, 'downloadBackup'])->name('admin.updates.backup.download');
// Update Release Routes (For Main Website)
Route::get('update-release', [App\Http\Controllers\Admin\UpdateReleaseController::class, 'index'])->name('admin.update.release');
Route::post('update-release', [App\Http\Controllers\Admin\UpdateReleaseController::class, 'store'])->name('admin.update.release.store');
Route::post('update-release/{id}/toggle', [App\Http\Controllers\Admin\UpdateReleaseController::class, 'toggleActive'])->name('admin.update.release.toggle');
Route::delete('update-release/{id}', [App\Http\Controllers\Admin\UpdateReleaseController::class, 'destroy'])->name('admin.update.release.destroy');

Route::get('contact-messages',
        [ContactMessageController::class, 'index']
    )->name('admin.contact.messages');

Route::post('contact-messages/status/{id}',
        [ContactMessageController::class, 'status']
    )->name('admin.contact.messages.status');

Route::delete('contact-messages/delete/{id}',
        [ContactMessageController::class, 'destroy']
    )->name('admin.contact.messages.delete');

// Newsletter Subscribers
Route::get('newsletter-subscribers',
    [\App\Http\Controllers\Admin\NewsletterSubscriberController::class, 'index']
)->name('admin.newsletter.subscribers');

Route::delete('newsletter-subscribers/delete/{id}',
    [\App\Http\Controllers\Admin\NewsletterSubscriberController::class, 'destroy']
)->name('admin.newsletter.subscribers.delete');
	 
	 
// Purchase Routes
Route::get('purchases/manage', [PurchaseController::class, 'index'])->name('purchases.index');
Route::post('purchases/store', [PurchaseController::class, 'store'])->name('purchases.store');
Route::get('purchases/logs', [PurchaseController::class, 'logs'])->name('purchases.logs');
Route::get('purchases/{id}/edit', [PurchaseController::class, 'edit'])->name('purchases.edit');
Route::post('purchases/{id}/update', [PurchaseController::class, 'update'])->name('purchases.update');
Route::delete('purchases/{id}', [PurchaseController::class, 'destroy'])->name('purchases.destroy');
Route::post('purchases/{id}/pay-due', [PurchaseController::class, 'payDue'])->name('purchases.pay_due');
Route::post('purchase-item/{id}/return', [PurchaseController::class, 'returnItem'])->name('purchases.item_return');
Route::get('purchases/{id}/invoice', [PurchaseController::class, 'invoice'])->name('purchases.invoice');
Route::get('purchases/{id}/invoice/download', [PurchaseController::class, 'downloadInvoice'])->name('purchases.invoice.download');
Route::get('purchases/export', [PurchaseController::class, 'export'])->name('purchases.export');
// ✅ Purchases AJAX Pagination
Route::get('purchases/ajax', [PurchaseController::class, 'ajaxIndex'])
    ->name('purchases.ajax');

// ⭐ Batch-wise pricing engine — purchases/manage right panel
Route::get('purchases/price-panel', [PurchaseController::class, 'pricePanel'])
    ->middleware(['auth:admin', 'admin'])->name('purchases.price.panel');
Route::post('purchases/price/batch-save', [PurchaseController::class, 'saveBatchPricing'])
    ->middleware(['auth:admin', 'admin', 'demo_mode'])->name('purchases.price.batch.save');
Route::post('purchases/price/activate', [PurchaseController::class, 'activateWebsiteBatch'])
    ->middleware(['auth:admin', 'admin', 'demo_mode'])->name('purchases.price.activate');
Route::post('purchases/price/variant-save', [PurchaseController::class, 'saveVariantPricing'])
    ->middleware(['auth:admin', 'admin', 'demo_mode'])->name('purchases.price.variant.save');
Route::post('purchases/price/wholesale-save', [PurchaseController::class, 'saveWholesalePricing'])
    ->middleware(['auth:admin', 'admin', 'demo_mode'])->name('purchases.price.wholesale.save');
Route::post('purchases/price/warranty-save', [PurchaseController::class, 'saveWarrantyPricing'])
    ->middleware(['auth:admin', 'admin', 'demo_mode'])->name('purchases.price.warranty.save');


// ==== REPORT ROUTES ==== //
Route::get('reports/orders',        [ReportController::class, 'orders'])->name('admin.reports.orders');
Route::get('reports/purchases',     [ReportController::class, 'purchases'])->name('admin.reports.purchases');
Route::get('reports/expenses',      [ReportController::class, 'expenses'])->name('admin.reports.expenses');
Route::get('reports/stock',         [ReportController::class, 'stock'])->name('admin.reports.stock');
Route::get('reports/profit-loss',   [ReportController::class, 'profitLoss'])->name('admin.reports.profit_loss');

// ============================================================
// 🆕 STOCK MANAGEMENT ROUTES
// ============================================================
Route::get('stock/dashboard',         [StockController::class, 'index'])->name('admin.stock.dashboard');
Route::get('stock/batches',           [StockController::class, 'batches'])->name('admin.stock.batches');
Route::get('stock/adjustments',       [StockController::class, 'adjustments'])->name('admin.stock.adjustments');
Route::get('stock/adjustments/create',[StockController::class, 'createAdjustment'])->name('admin.stock.adjustments.create');
Route::post('stock/adjustments/store',[StockController::class, 'storeAdjustment'])->name('admin.stock.adjustments.store');
Route::get('stock/valuation',         [StockController::class, 'valuation'])->name('admin.stock.valuation');
Route::get('stock/cogs',              [StockController::class, 'cogs'])->name('admin.stock.cogs');
Route::get('stock/barcode/print',     [StockController::class, 'printBarcode'])->name('admin.stock.barcode.print');

// Supplier Returns
Route::get('stock/supplier-returns',          [StockController::class, 'supplierReturns'])->name('admin.stock.supplier-returns');
Route::get('stock/supplier-returns/create',   [StockController::class, 'createSupplierReturn'])->name('admin.stock.supplier-returns.create');
Route::post('stock/supplier-returns/store',   [StockController::class, 'storeSupplierReturn'])->name('admin.stock.supplier-returns.store');
Route::get('stock/products/{id}/batches',     [StockController::class, 'getProductBatches'])->name('admin.stock.product-batches');

    // Supplier Routes
    Route::get('suppliers/manage', [SupplierController::class, 'index'])->name('admin.suppliers.index');
    Route::post('suppliers/store', [SupplierController::class, 'store'])->name('admin.suppliers.store');
    Route::get('suppliers/{id}/edit', [SupplierController::class, 'edit'])->name('admin.suppliers.edit');
    Route::post('suppliers/{id}/update', [SupplierController::class, 'update'])->name('admin.suppliers.update');
    Route::delete('suppliers/{id}', [SupplierController::class, 'destroy'])->name('admin.suppliers.destroy');

    // CRM - Employee Management Routes
    Route::get('employees', [\App\Http\Controllers\Admin\EmployeeController::class, 'index'])->name('admin.employees.index');
    Route::get('employees/create', [\App\Http\Controllers\Admin\EmployeeController::class, 'create'])->name('admin.employees.create');
    Route::post('employees/store', [\App\Http\Controllers\Admin\EmployeeController::class, 'store'])->name('admin.employees.store');
    Route::post('employees/import-user', [\App\Http\Controllers\Admin\EmployeeController::class, 'importFromUser'])->name('admin.employees.import');
    Route::get('employees/{id}', [\App\Http\Controllers\Admin\EmployeeController::class, 'show'])->name('admin.employees.show');
    Route::get('employees/{id}/edit', [\App\Http\Controllers\Admin\EmployeeController::class, 'edit'])->name('admin.employees.edit');
    Route::put('employees/{id}/update', [\App\Http\Controllers\Admin\EmployeeController::class, 'update'])->name('admin.employees.update');
    Route::delete('employees/{id}', [\App\Http\Controllers\Admin\EmployeeController::class, 'destroy'])->name('admin.employees.destroy');

    // CRM - Attendance Routes
    Route::get('attendances', [\App\Http\Controllers\Admin\AttendanceController::class, 'index'])->name('admin.attendances.index');
    Route::get('attendances/create', [\App\Http\Controllers\Admin\AttendanceController::class, 'create'])->name('admin.attendances.create');
    Route::post('attendances/store', [\App\Http\Controllers\Admin\AttendanceController::class, 'store'])->name('admin.attendances.store');
    Route::post('attendances/bulk-mark', [\App\Http\Controllers\Admin\AttendanceController::class, 'bulkMark'])->name('admin.attendances.bulk');
    Route::get('attendances/{id}/edit', [\App\Http\Controllers\Admin\AttendanceController::class, 'edit'])->name('admin.attendances.edit');
    Route::put('attendances/{id}/update', [\App\Http\Controllers\Admin\AttendanceController::class, 'update'])->name('admin.attendances.update');
    Route::delete('attendances/{id}', [\App\Http\Controllers\Admin\AttendanceController::class, 'destroy'])->name('admin.attendances.destroy');

    // CRM - Leave Routes
    Route::get('leaves', [\App\Http\Controllers\Admin\LeaveController::class, 'index'])->name('admin.leaves.index');
    Route::get('leaves/create', [\App\Http\Controllers\Admin\LeaveController::class, 'create'])->name('admin.leaves.create');
    Route::post('leaves/store', [\App\Http\Controllers\Admin\LeaveController::class, 'store'])->name('admin.leaves.store');
    Route::post('leaves/{id}/approve', [\App\Http\Controllers\Admin\LeaveController::class, 'approve'])->name('admin.leaves.approve');
    Route::post('leaves/{id}/reject', [\App\Http\Controllers\Admin\LeaveController::class, 'reject'])->name('admin.leaves.reject');
    Route::get('leaves/{id}/edit', [\App\Http\Controllers\Admin\LeaveController::class, 'edit'])->name('admin.leaves.edit');
    Route::put('leaves/{id}/update', [\App\Http\Controllers\Admin\LeaveController::class, 'update'])->name('admin.leaves.update');
    Route::delete('leaves/{id}', [\App\Http\Controllers\Admin\LeaveController::class, 'destroy'])->name('admin.leaves.destroy');

    // CRM - Salary Routes
    Route::get('salaries', [\App\Http\Controllers\Admin\SalaryController::class, 'index'])->name('admin.salaries.index');
    Route::post('salaries/calculate', [\App\Http\Controllers\Admin\SalaryController::class, 'calculate'])->name('admin.salaries.calculate');
    Route::post('salaries/bulk-calculate', [\App\Http\Controllers\Admin\SalaryController::class, 'bulkCalculate'])->name('admin.salaries.bulk_calculate');
    Route::get('salaries/{id}', [\App\Http\Controllers\Admin\SalaryController::class, 'show'])->name('admin.salaries.show');

    // CRM - Bonus Routes
    Route::get('bonuses', [\App\Http\Controllers\Admin\BonusController::class, 'index'])->name('admin.bonuses.index');
    Route::get('bonuses/create', [\App\Http\Controllers\Admin\BonusController::class, 'create'])->name('admin.bonuses.create');
    Route::post('bonuses/store', [\App\Http\Controllers\Admin\BonusController::class, 'store'])->name('admin.bonuses.store');
    Route::post('bonuses/{id}/approve', [\App\Http\Controllers\Admin\BonusController::class, 'approve'])->name('admin.bonuses.approve');
    Route::post('bonuses/{id}/pay', [\App\Http\Controllers\Admin\BonusController::class, 'pay'])->name('admin.bonuses.pay');
    Route::post('bonuses/{id}/reject', [\App\Http\Controllers\Admin\BonusController::class, 'reject'])->name('admin.bonuses.reject');
    Route::get('bonuses/{id}/edit', [\App\Http\Controllers\Admin\BonusController::class, 'edit'])->name('admin.bonuses.edit');
    Route::put('bonuses/{id}/update', [\App\Http\Controllers\Admin\BonusController::class, 'update'])->name('admin.bonuses.update');
    Route::delete('bonuses/{id}', [\App\Http\Controllers\Admin\BonusController::class, 'destroy'])->name('admin.bonuses.destroy');

    // CRM - Salary Payment Routes
    Route::get('salary-payments', [\App\Http\Controllers\Admin\SalaryPaymentController::class, 'index'])->name('admin.salary_payments.index');
    Route::get('salary-payments/create', [\App\Http\Controllers\Admin\SalaryPaymentController::class, 'create'])->name('admin.salary_payments.create');
    Route::post('salary-payments/store', [\App\Http\Controllers\Admin\SalaryPaymentController::class, 'store'])->name('admin.salary_payments.store');
    Route::post('salary-payments/pay-from-salary/{salaryId}', [\App\Http\Controllers\Admin\SalaryPaymentController::class, 'payFromSalary'])->name('admin.salary_payments.pay_from_salary');
    Route::get('salary-payments/{id}', [\App\Http\Controllers\Admin\SalaryPaymentController::class, 'show'])->name('admin.salary_payments.show');


    Route::get('email-setting', [EmailSettingController::class, 'index'])->name('email_setting');
    Route::post('email-setting', [EmailSettingController::class, 'update'])->name('email_setting.update');
	Route::get('seo-settings', [App\Http\Controllers\Admin\SeoSettingController::class, 'index'])
        ->name('admin.seo_settings.index');

    Route::post('seo-settings', [App\Http\Controllers\Admin\SeoSettingController::class, 'update'])
        ->name('admin.seo_settings.update');

    Route::get('dashboard', [DashboardController::class, 'dashboard'])->name('admin.dashboard');
    Route::get('activity-logs', [App\Http\Controllers\Admin\ActivityLogController::class, 'index'])->name('admin.activity_logs.index');

    Route::get('change-password', [DashboardController::class, 'changepassword'])->name('change_password');
    Route::post('new-password', [DashboardController::class, 'newpassword'])->name('new_password');

    // users route 
    Route::get('users/manage', [UserController::class,'index'])->name('users.index');
    Route::get('users/create', [UserController::class,'create'])->name('users.create');
    Route::post('users/save', [UserController::class,'store'])->name('users.store');
    Route::get('users/{id}/edit', [UserController::class,'edit'])->name('users.edit');
    Route::post('users/update', [UserController::class,'update'])->name('users.update');
    Route::post('users/inactive', [UserController::class,'inactive'])->name('users.inactive');
    Route::post('users/active', [UserController::class,'active'])->name('users.active');
    Route::post('users/destroy', [UserController::class,'destroy'])->name('users.destroy');
    
    // roles
    Route::get('roles/manage', [RoleController::class,'index'])->name('roles.index');
    Route::get('roles/{id}/show', [RoleController::class,'show'])->name('roles.show');
    Route::get('roles/create', [RoleController::class,'create'])->name('roles.create');
    Route::post('roles/save', [RoleController::class,'store'])->name('roles.store');
    Route::get('roles/{id}/edit', [RoleController::class,'edit'])->name('roles.edit');
    Route::post('roles/update', [RoleController::class,'update'])->name('roles.update');
    Route::post('roles/destroy', [RoleController::class,'destroy'])->name('roles.destroy');

    // permissions
    Route::get('permissions/manage', [PermissionController::class,'index'])->name('permissions.index');
    Route::get('permissions/create', [PermissionController::class,'create'])->name('permissions.create');
    Route::post('permissions/save', [PermissionController::class,'store'])->name('permissions.store');
    Route::get('permissions/{id}/edit', [PermissionController::class,'edit'])->name('permissions.edit');
    Route::post('permissions/update', [PermissionController::class,'update'])->name('permissions.update');
    Route::post('permissions/destroy', [PermissionController::class,'destroy'])->name('permissions.destroy');
    Route::post('permissions/sync', [PermissionController::class,'syncPermissions'])->name('permissions.sync');

    // categories
    Route::get('categories/manage', [CategoryController::class,'index'])->name('categories.index');
    Route::get('categories/{id}/show', [CategoryController::class,'show'])->name('categories.show');
    Route::get('categories/create', [CategoryController::class,'create'])->name('categories.create');
    Route::post('categories/save', [CategoryController::class,'store'])->name('categories.store');
    Route::get('categories/{id}/edit', [CategoryController::class,'edit'])->name('categories.edit');
    Route::post('categories/update', [CategoryController::class,'update'])->name('categories.update');
    Route::post('categories/inactive', [CategoryController::class,'inactive'])->name('categories.inactive');
    Route::post('categories/active', [CategoryController::class,'active'])->name('categories.active');
    Route::post('categories/destroy', [CategoryController::class,'destroy'])->name('categories.destroy');

    // Subcategories
    Route::get('subcategories/manage', [SubcategoryController::class,'index'])->name('subcategories.index');
    Route::get('subcategories/{id}/show', [SubcategoryController::class,'show'])->name('subcategories.show');
    Route::get('subcategories/create', [SubcategoryController::class,'create'])->name('subcategories.create');
    Route::post('subcategories/save', [SubcategoryController::class,'store'])->name('subcategories.store');
    Route::get('subcategories/{id}/edit', [SubcategoryController::class,'edit'])->name('subcategories.edit');
    Route::post('subcategories/update', [SubcategoryController::class,'update'])->name('subcategories.update');
    Route::post('subcategories/inactive', [SubcategoryController::class,'inactive'])->name('subcategories.inactive');
    Route::post('subcategories/active', [SubcategoryController::class,'active'])->name('subcategories.active');
    Route::post('subcategories/destroy', [SubcategoryController::class,'destroy'])->name('subcategories.destroy');

    // Childcategories
    Route::get('childcategories/manage', [ChildcategoryController::class,'index'])->name('childcategories.index');
    Route::get('childcategories/{id}/show', [ChildcategoryController::class,'show'])->name('childcategories.show');
    Route::get('childcategories/create', [ChildcategoryController::class,'create'])->name('childcategories.create');
    Route::post('childcategories/save', [ChildcategoryController::class,'store'])->name('childcategories.store');
    Route::get('childcategories/{id}/edit', [ChildcategoryController::class,'edit'])->name('childcategories.edit');
    Route::post('childcategories/update', [ChildcategoryController::class,'update'])->name('childcategories.update');
    Route::post('childcategories/inactive', [ChildcategoryController::class,'inactive'])->name('childcategories.inactive');
    Route::post('childcategories/active', [ChildcategoryController::class,'active'])->name('childcategories.active');
    Route::post('childcategories/destroy', [ChildcategoryController::class,'destroy'])->name('childcategories.destroy');
    
     // paymentgeteway
    Route::get('paymentgeteway/manage', [ApiIntegrationController::class,'pay_manage'])->name('paymentgeteway.manage');
    Route::post('paymentgeteway/save', [ApiIntegrationController::class,'pay_update'])->name('paymentgeteway.update');
    
     // smsgeteway
    Route::get('smsgeteway/manage', [ApiIntegrationController::class,'sms_manage'])->name('smsgeteway.manage');
    Route::post('smsgeteway/save', [ApiIntegrationController::class,'sms_update'])->name('smsgeteway.update');
    
    // courierapi
    Route::get('courierapi/manage', [ApiIntegrationController::class,'courier_manage'])->name('courierapi.manage');

    // Cron Job Management
    Route::get('cron-jobs',                    [CronJobController::class, 'index']          )->name('admin.cron.index');
    Route::post('cron-jobs/{id}/toggle',       [CronJobController::class, 'toggle']         )->name('admin.cron.toggle');
    Route::post('cron-jobs/{id}/settings',     [CronJobController::class, 'update_settings'])->name('admin.cron.settings');
    Route::post('cron-jobs/{id}/run-now',      [CronJobController::class, 'run_now']        )->name('admin.cron.run_now');
    Route::get('cron-jobs/{id}/status',        [CronJobController::class, 'status']         )->name('admin.cron.status');
    Route::post('courierapi/save', [ApiIntegrationController::class,'courier_update'])->name('courierapi.update');
    Route::post('courierapi/pathao-generate-token', [ApiIntegrationController::class,'pathao_generate_token'])->name('admin.courierapi.pathao.generate_token');
    
    // RedX Areas AJAX
    Route::get('redx/areas', [OrderController::class, 'redxAreas'])->name('admin.redx.areas');
    Route::get('redx/pickup-stores', [OrderController::class, 'redxPickupStores'])->name('admin.redx.pickup-stores');

    // attribute
    // ⚠️ Order Status management removed — now enum-driven via app/Enums/OrderStatus.php
    
    // pixels
    Route::get('pixels/manage', [PixelsController::class,'index'])->name('pixels.index');
    Route::get('pixels/{id}/show', [PixelsController::class,'show'])->name('pixels.show');
    Route::get('pixels/create', [PixelsController::class,'create'])->name('pixels.create');
    Route::post('pixels/save', [PixelsController::class,'store'])->name('pixels.store');
    Route::get('pixels/{id}/edit', [PixelsController::class,'edit'])->name('pixels.edit');
    Route::post('pixels/update', [PixelsController::class,'update'])->name('pixels.update');
    Route::post('pixels/inactive', [PixelsController::class,'inactive'])->name('pixels.inactive');
    Route::post('pixels/active', [PixelsController::class,'active'])->name('pixels.active');
    Route::post('pixels/destroy', [PixelsController::class,'destroy'])->name('pixels.destroy');
    
    // TikTok Pixel
    Route::get('tiktok-pixels/manage', [TiktokPixelsController::class, 'index'])->name('tiktok.pixels.index');
    Route::get('tiktok-pixels/create', [TiktokPixelsController::class, 'create'])->name('tiktok.pixels.create');
    Route::post('tiktok-pixels/save', [TiktokPixelsController::class, 'store'])->name('tiktok.pixels.store');
    Route::get('tiktok-pixels/{id}/edit', [TiktokPixelsController::class, 'edit'])->name('tiktok.pixels.edit');
    Route::post('tiktok-pixels/update', [TiktokPixelsController::class, 'update'])->name('tiktok.pixels.update');
    Route::post('tiktok-pixels/inactive', [TiktokPixelsController::class, 'inactive'])->name('tiktok.pixels.inactive');
    Route::post('tiktok-pixels/active', [TiktokPixelsController::class, 'active'])->name('tiktok.pixels.active');
    Route::post('tiktok-pixels/destroy', [TiktokPixelsController::class, 'destroy'])->name('tiktok.pixels.destroy');

    // Facebook Conversion API settings
    Route::get('facebook-capi/settings', [FacebookCapiSettingController::class, 'edit'])->name('admin.facebook_capi.edit');
    Route::post('facebook-capi/settings', [FacebookCapiSettingController::class, 'update'])->name('admin.facebook_capi.update');

    // Google Analytics 4 settings
    Route::get('google-analytics/settings', [GoogleAnalyticSettingController::class, 'edit'])->name('admin.google_analytics.edit');
    Route::post('google-analytics/settings', [GoogleAnalyticSettingController::class, 'update'])->name('admin.google_analytics.update');

    // Ads Analytics - separate pages for each platform
    Route::get('ads-analytics', [AdsAnalyticsController::class, 'dashboard'])->name('admin.ads_analytics.dashboard');
    Route::get('ads-analytics/facebook', [AdsAnalyticsController::class, 'facebook'])->name('admin.ads_analytics.facebook');
    Route::get('ads-analytics/google', [AdsAnalyticsController::class, 'google'])->name('admin.ads_analytics.google');
    Route::get('ads-analytics/tiktok', [AdsAnalyticsController::class, 'tiktok'])->name('admin.ads_analytics.tiktok');
    Route::get('ads-analytics/live', [AdsAnalyticsController::class, 'liveData'])->name('admin.ads_analytics.live_data');
    Route::get('ads-analytics/settings', [AdsAnalyticsController::class, 'settings'])->name('admin.ads_analytics.settings');
    Route::post('ads-analytics/settings', [AdsAnalyticsController::class, 'saveSettings'])->name('admin.ads_analytics.save_settings');

    // Facebook Page - Auto post products
    Route::get('facebook-page/settings', [FacebookPageController::class, 'settings'])->name('admin.facebook_page.settings');
    Route::post('facebook-page/settings', [FacebookPageController::class, 'saveSettings'])->name('admin.facebook_page.save_settings');
    Route::post('facebook-page/post-product/{product}', [FacebookPageController::class, 'postProduct'])->name('admin.facebook_page.post_product');
    
     // tag manager
    Route::get('tag-manager/manage', [TagManagerController::class,'index'])->name('tagmanagers.index');
    Route::get('tag-manager/{id}/show', [TagManagerController::class,'show'])->name('tagmanagers.show');
    Route::get('tag-manager/create', [TagManagerController::class,'create'])->name('tagmanagers.create');
    Route::post('tag-manager/save', [TagManagerController::class,'store'])->name('tagmanagers.store');
    Route::get('tag-manager/{id}/edit', [TagManagerController::class,'edit'])->name('tagmanagers.edit');
    Route::post('tag-manager/update', [TagManagerController::class,'update'])->name('tagmanagers.update');
    Route::post('tag-manager/inactive', [TagManagerController::class,'inactive'])->name('tagmanagers.inactive');
    Route::post('tag-manager/active', [TagManagerController::class,'active'])->name('tagmanagers.active');
    Route::post('tag-manager/destroy', [TagManagerController::class,'destroy'])->name('tagmanagers.destroy');
    
    // attribute
    Route::get('brands/manage', [BrandController::class,'index'])->name('brands.index');
    Route::get('brands/{id}/show', [BrandController::class,'show'])->name('brands.show');
    Route::get('brands/create', [BrandController::class,'create'])->name('brands.create');
    Route::post('brands/save', [BrandController::class,'store'])->name('brands.store');
    Route::get('brands/{id}/edit', [BrandController::class,'edit'])->name('brands.edit');
    Route::post('brands/update', [BrandController::class,'update'])->name('brands.update');
    Route::post('brands/inactive', [BrandController::class,'inactive'])->name('brands.inactive');
    Route::post('brands/active', [BrandController::class,'active'])->name('brands.active');
    Route::post('brands/destroy', [BrandController::class,'destroy'])->name('brands.destroy');

     // color
    Route::get('color/manage', [ColorController::class,'index'])->name('colors.index');
    Route::get('color/{id}/show', [ColorController::class,'show'])->name('colors.show');
    Route::get('color/create', [ColorController::class,'create'])->name('colors.create');
    Route::post('color/save', [ColorController::class,'store'])->name('colors.store');
    Route::get('color/{id}/edit', [ColorController::class,'edit'])->name('colors.edit');
    Route::post('color/update', [ColorController::class,'update'])->name('colors.update');
    Route::post('color/inactive', [ColorController::class,'inactive'])->name('colors.inactive');
    Route::post('color/active', [ColorController::class,'active'])->name('colors.active');
    Route::post('color/destroy', [ColorController::class,'destroy'])->name('colors.destroy');
    Route::post('color/import-default', [ColorController::class,'importDefault'])->name('colors.import_default');

    // size
    Route::get('size/manage', [SizeController::class,'index'])->name('sizes.index');
    Route::get('size/{id}/show', [SizeController::class,'show'])->name('sizes.show');
    Route::get('size/create', [SizeController::class,'create'])->name('sizes.create');
    Route::post('size/save', [SizeController::class,'store'])->name('sizes.store');
    Route::get('size/{id}/edit', [SizeController::class,'edit'])->name('sizes.edit');
    Route::post('size/update', [SizeController::class,'update'])->name('sizes.update');
    Route::post('size/inactive', [SizeController::class,'inactive'])->name('sizes.inactive');
    Route::post('size/active', [SizeController::class,'active'])->name('sizes.active');
    Route::post('size/destroy', [SizeController::class,'destroy'])->name('sizes.destroy');
    Route::post('size/import-default', [SizeController::class,'importDefault'])->name('sizes.import_default');

    // District (shipping areas) CRUD
    Route::get('district/manage', [DistrictController::class,'index'])->name('admin.district.index');
    Route::post('district/save', [DistrictController::class,'store'])->name('admin.district.store');
    Route::get('district/{id}/edit', [DistrictController::class,'edit'])->name('admin.district.edit');
    Route::post('district/update', [DistrictController::class,'update'])->name('admin.district.update');
    Route::post('district/destroy', [DistrictController::class,'destroy'])->name('admin.district.destroy');
    Route::post('district/sync', [DistrictController::class,'syncDefault'])->name('admin.district.sync');
    Route::post('district/toggle-charge', [DistrictController::class,'toggleChargeUpdate'])->name('admin.district.toggle-charge');
   
   
    // Inhouse Products
    Route::get('inhouse-products/manage', [App\Http\Controllers\Admin\InhouseProductController::class,'index'])->name('inhouse.products.index');
    Route::get('inhouse-products/{id}/show', [App\Http\Controllers\Admin\InhouseProductController::class,'show'])->name('inhouse.products.show');
    
    // product
    Route::get('products/wholesale', [ProductController::class,'wholesale'])->name('admin.products.wholesale');
    Route::get('products/{id}/show', [ProductController::class,'show'])->name('products.show');
    Route::get('products/create', [ProductController::class,'create'])->name('products.create');
    
    // Wholesale Products
    Route::get('wholesale-products', [\App\Http\Controllers\Admin\WholesaleProductController::class, 'index'])->name('admin.wholesale_products.index');
    Route::get('wholesale-products/create', [\App\Http\Controllers\Admin\WholesaleProductController::class, 'create'])->name('admin.wholesale_products.create');
    Route::post('wholesale-products', [\App\Http\Controllers\Admin\WholesaleProductController::class, 'store'])->name('admin.wholesale_products.store');
    Route::get('wholesale-products/{id}', [\App\Http\Controllers\Admin\WholesaleProductController::class, 'show'])->name('admin.wholesale_products.show');
    Route::get('wholesale-products/{id}/edit', [\App\Http\Controllers\Admin\WholesaleProductController::class, 'edit'])->name('admin.wholesale_products.edit');
    Route::post('wholesale-products/{id}', [\App\Http\Controllers\Admin\WholesaleProductController::class, 'update'])->name('admin.wholesale_products.update');
    Route::delete('wholesale-products/{id}', [\App\Http\Controllers\Admin\WholesaleProductController::class, 'destroy'])->name('admin.wholesale_products.destroy');
    Route::post('wholesale-products/{id}/approve', [\App\Http\Controllers\Admin\WholesaleProductController::class, 'approve'])->name('admin.wholesale_products.approve');
    Route::post('wholesale-products/{id}/reject', [\App\Http\Controllers\Admin\WholesaleProductController::class, 'reject'])->name('admin.wholesale_products.reject');
    Route::get('ajax-wholesale-subcategory', [\App\Http\Controllers\Admin\WholesaleProductController::class, 'getSubcategory'])->name('admin.ajax.wholesale.subcategory');
    Route::get('ajax-wholesale-childcategory', [\App\Http\Controllers\Admin\WholesaleProductController::class, 'getChildcategory'])->name('admin.ajax.wholesale.childcategory');
    Route::post('products/save', [ProductController::class,'store'])->name('products.store');
    Route::get('products/{id}/edit', [ProductController::class,'edit'])->name('products.edit');
    Route::post('products/update', [ProductController::class,'update'])->name('products.update');
    Route::post('products/inactive', [ProductController::class,'inactive'])->name('products.inactive');
    Route::post('products/active', [ProductController::class,'active'])->name('products.active');
    Route::post('products/destroy', [ProductController::class,'destroy'])->name('products.destroy');
    Route::get('products/image/destroy', [ProductController::class,'imgdestroy'])->name('products.image.destroy');
    Route::get('products/price/destroy', [ProductController::class,'pricedestroy'])->name('products.price.destroy');
    Route::post('products/update-deals', [ProductController::class,'update_deals'])->name('products.update_deals');
    Route::get('products/update-feature', [ProductController::class,'update_feature'])->name('products.update_feature');
    Route::post('products/update-status', [ProductController::class,'update_status'])->name('products.update_status');
    Route::get('products/price-edit', [ProductController::class,'price_edit'])->name('products.price_edit');
    Route::post('products/price-update', [ProductController::class,'price_update'])->name('products.price_update');
    
    // Product Approval Routes
    Route::get('products/pending', [ProductController::class,'pending'])->name('products.pending');
    Route::post('products/approve', [ProductController::class,'approve'])->name('products.approve');
    Route::post('products/reject', [ProductController::class,'reject'])->name('products.reject');
    
    // campaign
    Route::get('campaign/manage', [CampaignController::class,'index'])->name('campaign.index');
    Route::get('campaign/{id}/show', [CampaignController::class,'show'])->name('campaign.show');
    Route::get('campaign/create', [CampaignController::class,'create'])->name('campaign.create');
    Route::post('campaign/save', [CampaignController::class,'store'])->name('campaign.store');
    Route::get('campaign/{id}/edit', [CampaignController::class,'edit'])->name('campaign.edit');
    Route::post('campaign/update', [CampaignController::class,'update'])->name('campaign.update');
    Route::post('campaign/inactive', [CampaignController::class,'inactive'])->name('campaign.inactive');
    Route::post('campaign/active', [CampaignController::class,'active'])->name('campaign.active');
    Route::post('campaign/destroy', [CampaignController::class,'destroy'])->name('campaign.destroy');
    Route::get('campaign/image/destroy', [CampaignController::class,'imgdestroy'])->name('campaign.image.destroy');
    Route::post('campaign/builder/upload-image', [CampaignController::class,'uploadBuilderImage'])->name('campaign.builder.upload');

    // Theme Management Routes
    Route::get('themes', [ThemeController::class, 'index'])->name('themes.index');
    Route::get('theme/create', [ThemeController::class, 'create'])->name('themes.create');
    Route::post('theme/save', [ThemeController::class, 'store'])->name('themes.store');
    Route::get('theme/{id}/edit', [ThemeController::class, 'edit'])->name('themes.edit');
    Route::post('theme/update', [ThemeController::class, 'update'])->name('themes.update');
    Route::get('theme/{id}/apply', [ThemeController::class, 'apply'])->name('themes.apply');
    Route::get('theme/{id}/duplicate', [ThemeController::class, 'duplicate'])->name('themes.duplicate');
    Route::post('theme/inactive', [ThemeController::class, 'inactive'])->name('themes.inactive');
    Route::post('theme/active', [ThemeController::class, 'active'])->name('themes.active');
    Route::post('theme/destroy', [ThemeController::class, 'destroy'])->name('themes.destroy');

    // Product Card Design (Theme System → Product Design)
    Route::get('product-design', [ProductDesignController::class, 'index'])->name('product.design');
    Route::post('product-design/save', [ProductDesignController::class, 'store'])->name('product.design.save');

    // Layout Management Routes
    Route::get('layouts', [LayoutController::class, 'index'])->name('layouts.index');
    Route::get('layout/create', [LayoutController::class, 'create'])->name('layouts.create');
    Route::post('layout/save', [LayoutController::class, 'store'])->name('layouts.store');
    Route::get('layout/{id}/edit', [LayoutController::class, 'edit'])->name('layouts.edit');
    Route::post('layout/update', [LayoutController::class, 'update'])->name('layouts.update');
    Route::get('layout/{id}/builder', [LayoutController::class, 'builder'])->name('layouts.builder');
    Route::get('layout/{id}/apply', [LayoutController::class, 'apply'])->name('layouts.apply');
    Route::post('layout/add-section', [LayoutController::class, 'addSection'])->name('layouts.sections.add');
    Route::post('layout/reorder-sections', [LayoutController::class, 'reorderSections'])->name('layouts.sections.reorder');
    Route::post('layout/toggle-section', [LayoutController::class, 'toggleSection'])->name('layouts.sections.toggle');
    Route::post('layout/update-section-settings', [LayoutController::class, 'updateSectionSettings'])->name('layouts.sections.update-settings');
    Route::post('layout/remove-section', [LayoutController::class, 'removeSection'])->name('layouts.sections.remove');
    Route::get('layout/section/{slug}/preview', [LayoutController::class, 'previewSection'])->name('layouts.sections.preview');
    Route::post('layout/capture-screenshot', [LayoutController::class, 'captureScreenshot'])->name('layouts.sections.capture-screenshot');
    Route::post('layout/inactive', [LayoutController::class, 'inactive'])->name('layouts.inactive');
    Route::post('layout/active', [LayoutController::class, 'active'])->name('layouts.active');
    Route::post('layout/destroy', [LayoutController::class, 'destroy'])->name('layouts.destroy');

    // Demo Import/Export
    Route::get('demo', [DemoController::class, 'index'])->name('demo.index');
    Route::get('demo/export', [DemoController::class, 'exportDemo'])->name('demo.export');
    Route::post('demo/import', [DemoController::class, 'importDemo'])->name('demo.import');
    Route::post('demo/import-preset/{slug}', [DemoController::class, 'importPreset'])->name('demo.import-preset');
    Route::post('demo/import-zip', [DemoController::class, 'importPresetZip'])->name('demo.import-zip');
    Route::post('demo/reset', [DemoController::class, 'resetSite'])->name('demo.reset');
    Route::post('demo/clean', [DemoController::class, 'cleanSite'])->name('demo.clean');
    Route::get('demo/delete-preset/{name}', [DemoController::class, 'deletePreset'])->name('demo.delete-preset');

    // Backup & Restore
    Route::get('backup', [BackupController::class, 'index'])->name('backup.index');
    Route::post('backup/create', [BackupController::class, 'createBackup'])->name('backup.create');
    Route::get('backup/download', [BackupController::class, 'downloadBackup'])->name('backup.download');
    Route::post('backup/restore', [BackupController::class, 'restoreBackup'])->name('backup.restore');
    Route::get('backup/delete/{filename}', [BackupController::class, 'deleteBackup'])->name('backup.delete');

    // Theme Export/Import
    Route::get('theme/export', [BackupController::class, 'exportTheme'])->name('theme.export');
    Route::post('theme/import', [BackupController::class, 'importTheme'])->name('theme.import');

    // Layout Export/Import
    Route::post('layout/export', [BackupController::class, 'exportLayout'])->name('layout.export');
    Route::post('layout/import', [BackupController::class, 'importLayout'])->name('layout.import');

    // Preset Download + Theme/Layout Restore
    Route::get('preset/download/{slug}', [BackupController::class, 'downloadPreset'])->name('preset.download');
    Route::get('preset/theme/{slug}', [BackupController::class, 'restorePresetTheme'])->name('preset.restore-theme');
    Route::get('preset/layout/{slug}', [BackupController::class, 'restorePresetLayout'])->name('preset.restore-layout');

    // Header & Footer Builder
    Route::get('headerfooter', [HeaderFooterController::class, 'index'])->name('headerfooter.index');
    Route::post('headerfooter/update', [HeaderFooterController::class, 'update'])->name('headerfooter.update');
    Route::post('headerfooter/preview', [HeaderFooterController::class, 'preview'])->name('headerfooter.preview');
    Route::post('headerfooter/add-component', [HeaderFooterController::class, 'addComponent'])->name('headerfooter.add-component');
    Route::post('headerfooter/remove-component', [HeaderFooterController::class, 'removeComponent'])->name('headerfooter.remove-component');
    Route::post('headerfooter/reorder-components', [HeaderFooterController::class, 'reorderComponents'])->name('headerfooter.reorder-components');

    // Error Log (Laravel log viewer)
    Route::get('error-log', [ErrorLogController::class,'index'])->name('error-log.index');
    Route::post('error-log/create', [ErrorLogController::class,'create'])->name('error-log.create');
    Route::post('error-log/test', [ErrorLogController::class,'testLog'])->name('error-log.test');
    Route::post('error-log/delete', [ErrorLogController::class,'delete'])->name('error-log.delete');

    // settings route
    Route::get('settings/manage', [GeneralSettingController::class,'index'])->name('settings.index');
    Route::get('settings/create', [GeneralSettingController::class,'create'])->name('settings.create');
    Route::post('settings/save', [GeneralSettingController::class,'store'])->name('settings.store');
    // Single-vendor site → only one settings row, so no {id} in the URL
    Route::get('settings/edit', [GeneralSettingController::class,'edit'])->name('settings.edit');
    Route::post('settings/update', [GeneralSettingController::class,'update'])->name('settings.update');
    Route::post('settings/inactive', [GeneralSettingController::class,'inactive'])->name('settings.inactive');
    Route::post('settings/active', [GeneralSettingController::class,'active'])->name('settings.active');
    Route::post('settings/destroy', [GeneralSettingController::class,'destroy'])->name('settings.destroy');

     // settings route 
    Route::get('social-media/manage', [SocialMediaController::class,'index'])->name('socialmedias.index');
    Route::get('social-media/create', [SocialMediaController::class,'create'])->name('socialmedias.create');
    Route::post('social-media/save', [SocialMediaController::class,'store'])->name('socialmedias.store');
    Route::get('social-media/{id}/edit', [SocialMediaController::class,'edit'])->name('socialmedias.edit');
    Route::post('social-media/update', [SocialMediaController::class,'update'])->name('socialmedias.update');
    Route::post('social-media/inactive', [SocialMediaController::class,'inactive'])->name('socialmedias.inactive');
    Route::post('social-media/active', [SocialMediaController::class,'active'])->name('socialmedias.active');
    Route::post('social-media/destroy', [SocialMediaController::class,'destroy'])->name('socialmedias.destroy');

     // contact route 
    Route::get('contact/manage', [ContactController::class,'index'])->name('contact.index');
    Route::get('contact/create', [ContactController::class,'create'])->name('contact.create');

    Route::get('contact/{id}/edit', [ContactController::class,'edit'])->name('contact.edit');
    Route::post('contact/update', [ContactController::class,'update'])->name('contact.update');
    Route::post('contact/inactive', [ContactController::class,'inactive'])->name('contact.inactive');
    Route::post('contact/active', [ContactController::class,'active'])->name('contact.active');
    Route::post('contact/destroy', [ContactController::class,'destroy'])->name('contact.destroy');

     // banner category route 
    Route::get('banner-category/manage', [BannerCategoryController::class,'index'])->name('banner_category.index');
    Route::get('banner-category/create', [BannerCategoryController::class,'create'])->name('banner_category.create');
    Route::post('banner-category/save', [BannerCategoryController::class,'store'])->name('banner_category.store');
    Route::get('banner-category/{id}/edit', [BannerCategoryController::class,'edit'])->name('banner_category.edit');
    Route::post('banner-category/update', [BannerCategoryController::class,'update'])->name('banner_category.update');
    Route::post('banner-category/inactive', [BannerCategoryController::class,'inactive'])->name('banner_category.inactive');
    Route::post('banner-category/active', [BannerCategoryController::class,'active'])->name('banner_category.active');
    Route::post('banner-category/destroy', [BannerCategoryController::class,'destroy'])->name('banner_category.destroy');

    // banner  route 
    Route::get('banner/manage', [BannerController::class,'index'])->name('banners.index');
    Route::get('banner/create', [BannerController::class,'create'])->name('banners.create');
    Route::post('banner/save', [BannerController::class,'store'])->name('banners.store');
    Route::get('banner/{id}/edit', [BannerController::class,'edit'])->name('banners.edit');
    Route::post('banner/update', [BannerController::class,'update'])->name('banners.update');
    Route::post('banner/inactive', [BannerController::class,'inactive'])->name('banners.inactive');
    Route::post('banner/active', [BannerController::class,'active'])->name('banners.active');
    Route::post('banner/destroy', [BannerController::class,'destroy'])->name('banners.destroy');

    // ─────────────────────────────────────────────────────────────
    // Media Gallery (folder-wise image & PDF manager, reusable picker)
    // ─────────────────────────────────────────────────────────────
    Route::get('media', [MediaController::class, 'index'])->name('admin.media.index');
    Route::post('media/folder/create', [MediaController::class, 'createFolder'])->name('admin.media.folder.create');
    Route::post('media/folder/rename', [MediaController::class, 'renameFolder'])->name('admin.media.folder.rename');
    Route::post('media/folder/delete', [MediaController::class, 'deleteFolder'])->name('admin.media.folder.delete');
    Route::post('media/upload', [MediaController::class, 'upload'])->name('admin.media.upload');
    Route::post('media/file/rename', [MediaController::class, 'renameFile'])->name('admin.media.file.rename');
    Route::post('media/file/delete', [MediaController::class, 'deleteFile'])->name('admin.media.file.delete');
    Route::post('media/move', [MediaController::class, 'move'])->name('admin.media.move');
    Route::post('media/copy', [MediaController::class, 'copy'])->name('admin.media.copy');
    Route::get('media/picker', [MediaController::class, 'pickerContent'])->name('admin.media.picker');
    Route::post('media/picker/upload', [MediaController::class, 'pickerUpload'])->name('admin.media.picker.upload');
    
    // contact route 
    Route::get('page/manage', [CreatePageController::class,'index'])->name('pages.index');
    Route::get('page/create', [CreatePageController::class,'create'])->name('pages.create');
    Route::post('page/save', [CreatePageController::class,'store'])->name('pages.store');
    Route::get('page/{id}/edit', [CreatePageController::class,'edit'])->name('pages.edit');
    Route::post('page/update', [CreatePageController::class,'update'])->name('pages.update');
    Route::post('page/inactive', [CreatePageController::class,'inactive'])->name('pages.inactive');
    Route::post('page/active', [CreatePageController::class,'active'])->name('pages.active');
    Route::post('page/destroy', [CreatePageController::class,'destroy'])->name('pages.destroy');

    // Pos route
    Route::get('order/create', [OrderController::class,'order_create'])->name('admin.order.create');
    Route::post('order/store', [OrderController::class,'order_store'])->name('admin.order.store');
    Route::get('order/cart-add', [OrderController::class,'cart_add'])->name('admin.order.cart_add');
    Route::get('order/cart-content', [OrderController::class,'cart_content'])->name('admin.order.cart_content');
    Route::get('order/cart-refresh', [OrderController::class,'cart_refresh'])->name('admin.order.cart_refresh');
    Route::get('order/cart-increment', [OrderController::class,'cart_increment'])->name('admin.order.cart_increment');
    Route::get('order/cart-decrement', [OrderController::class,'cart_decrement'])->name('admin.order.cart_decrement');
    Route::get('order/cart-remove', [OrderController::class,'cart_remove'])->name('admin.order.cart_remove');
    Route::get('order/cart-product-discount', [OrderController::class,'product_discount'])->name('admin.order.product_discount');
    Route::get('order/cart-details', [OrderController::class,'cart_details'])->name('admin.order.cart_details');
    Route::get('order/cart-shipping', [OrderController::class,'cart_shipping'])->name('admin.order.cart_shipping');
    Route::get('order/cart-clear', [OrderController::class,'cart_clear'])->name('admin.order.cart_clear');
    Route::get('order/cart/update', [OrderController::class, 'cart_update'])->name('admin.order.cart.update');
    Route::post('order/pos/apply-coupon', [OrderController::class, 'posApplyCoupon'])->name('admin.order.pos.apply_coupon');
    Route::get('order/pos/remove-coupon', [OrderController::class, 'posRemoveCoupon'])->name('admin.order.pos.remove_coupon');
    // Barcode scanning
    Route::get('order/scan-barcode/{barcode}', [OrderController::class, 'scanBarcode'])->name('admin.order.scan_barcode');
    // Hold Cart routes
    Route::post('order/hold-cart', [OrderController::class, 'holdCart'])->name('admin.order.hold_cart');
    Route::get('order/held-carts', [OrderController::class, 'heldCarts'])->name('admin.order.held_carts');
    Route::post('order/restore-hold/{id}', [OrderController::class, 'restoreHold'])->name('admin.order.restore_hold');
    Route::delete('order/delete-hold/{id}', [OrderController::class, 'deleteHold'])->name('admin.order.delete_hold');

    // 🆕 One-page POS: print / recent / load / receive payment (BEFORE the {slug} wildcard)
    Route::get('order/recent', [OrderController::class, 'recentOrders'])->name('admin.order.recent');
    Route::get('order/load/{invoice_id}', [OrderController::class, 'loadOrderIntoCart'])->name('admin.order.load');
    Route::get('order/print/{invoice_id}', [OrderController::class, 'printInvoice'])->name('admin.order.print');
    Route::post('order/receive-payment', [OrderController::class, 'receivePayment'])->name('admin.order.receive_payment');

    // Order route 
	Route::get('order/{slug}/ajax', [OrderController::class, 'ajaxIndex'])->name('admin.orders.ajax');

    Route::get('order/{slug}', [OrderController::class,'index'])->name('admin.orders');
    Route::get('order/edit/{invoice_id}', [OrderController::class,'order_edit'])->name('admin.order.edit');
    Route::post('order/update', [OrderController::class,'order_update'])->name('admin.order.update');
    Route::get('order/invoice/{invoice_id}', [OrderController::class,'invoice'])->name('admin.order.invoice');
    Route::get('order/process/{invoice_id}', [OrderController::class,'process'])->name('admin.order.process');
    Route::post('order/change', [OrderController::class,'order_process'])->name('admin.order_change');
    Route::post('order/destroy', [OrderController::class,'destroy'])->name('admin.order.destroy');

    // ═══════════════════════════════════════════════════════
    // 🌟 NEW: Action-Based Order Management (System-Driven)
    // Each action auto-transitions status + records note
    // ═══════════════════════════════════════════════════════
    Route::post('order/add-note', [OrderController::class, 'addOrderNote'])->name('admin.order.addNote');
    Route::post('order/action/confirm', [OrderController::class, 'confirmOrder'])->name('admin.order.confirm');
    Route::post('order/action/start-picking', [OrderController::class, 'startPicking'])->name('admin.order.startPicking');
    Route::post('order/action/start-packing', [OrderController::class, 'startPacking'])->name('admin.order.startPacking');
    Route::post('order/action/mark-packed', [OrderController::class, 'markPacked'])->name('admin.order.markPacked');
    Route::post('order/action/ship', [OrderController::class, 'shipOrder'])->name('admin.order.ship');
    Route::post('order/action/out-for-delivery', [OrderController::class, 'markOutForDelivery'])->name('admin.order.outForDelivery');
    Route::post('order/action/deliver', [OrderController::class, 'markDelivered'])->name('admin.order.deliver');
    Route::post('order/action/complete', [OrderController::class, 'completeOrder'])->name('admin.order.complete');
    Route::post('order/action/request-return', [OrderController::class, 'requestReturn'])->name('admin.order.requestReturn');
    Route::post('order/action/approve-return', [OrderController::class, 'approveReturn'])->name('admin.order.approveReturn');
    Route::post('order/action/mark-returned', [OrderController::class, 'markReturned'])->name('admin.order.markReturned');
    Route::post('order/action/close', [OrderController::class, 'closeOrder'])->name('admin.order.close');
    Route::post('order/action/cancel', [OrderController::class, 'cancelOrder'])->name('admin.order.cancel');

    Route::get('order-assign', [OrderController::class,'order_assign'])->name('admin.order.assign');
    Route::get('order-status', [OrderController::class,'order_status'])->name('admin.order.status');
    Route::get('order-bulk-destroy', [OrderController::class,'bulk_destroy'])->name('admin.order.bulk_destroy');
    Route::get('order-print', [OrderController::class,'order_print'])->name('admin.order.order_print');
    Route::get('bulk-courier/{slug}', [OrderController::class,'bulk_courier'])->name('admin.bulk_courier');
    Route::get('stock-report', [OrderController::class,'stock_report'])->name('admin.stock_report');
    Route::get('order-report', [OrderController::class,'order_report'])->name('admin.order_report');
    Route::post('order-pathao', [OrderController::class,'order_pathao'])->name('admin.order.pathao');
    Route::get('/pathao-city', [OrderController::class, 'pathaocity'])->name('pathaocity');
    Route::get('/pathao-zone', [OrderController::class, 'pathaozone'])->name('pathaozone');

    // Order route 
    Route::get('reviews', [ReviewController::class,'index'])->name('reviews.index');
    Route::get('review/pending', [ReviewController::class,'pending'])->name('reviews.pending');
     Route::post('review/inactive', [ReviewController::class,'inactive'])->name('reviews.inactive');
    Route::post('review/active', [ReviewController::class,'active'])->name('reviews.active');
     Route::get('review/create', [ReviewController::class,'create'])->name('reviews.create');
    Route::post('review/save', [ReviewController::class,'store'])->name('reviews.store');
    Route::get('review/{id}/edit', [ReviewController::class,'edit'])->name('reviews.edit');
    Route::post('review/update', [ReviewController::class,'update'])->name('reviews.update');
    Route::post('review/destroy', [ReviewController::class,'destroy'])->name('reviews.destroy');

    // flavor  route 
    Route::get('shipping-charge/manage', [ShippingChargeController::class,'index'])->name('shippingcharges.index');
    Route::get('shipping-charge/create', [ShippingChargeController::class,'create'])->name('shippingcharges.create');
    Route::post('shipping-charge/save', [ShippingChargeController::class,'store'])->name('shippingcharges.store');
    Route::get('shipping-charge/{id}/edit', [ShippingChargeController::class,'edit'])->name('shippingcharges.edit');
    Route::post('shipping-charge/update', [ShippingChargeController::class,'update'])->name('shippingcharges.update');
    Route::post('shipping-charge/inactive', [ShippingChargeController::class,'inactive'])->name('shippingcharges.inactive');
    Route::post('shipping-charge/active', [ShippingChargeController::class,'active'])->name('shippingcharges.active');
    Route::post('shipping-charge/destroy', [ShippingChargeController::class,'destroy'])->name('shippingcharges.destroy');
    
    // backend customer route 
    Route::get('customer', [CustomerManageController::class,'index'])->name('customers.index');
    Route::get('customer/manage', [CustomerManageController::class,'index'])->name('customers.manage');
    Route::get('customer/{id}/edit', [CustomerManageController::class,'edit'])->name('customers.edit');
    Route::post('customer/update', [CustomerManageController::class,'update'])->name('customers.update');
    Route::post('customer/inactive', [CustomerManageController::class,'inactive'])->name('customers.inactive');
    Route::post('customer/active', [CustomerManageController::class,'active'])->name('customers.active');
    Route::get('customer/profile', [CustomerManageController::class,'profile'])->name('customers.profile');
    Route::post('customer/adminlog', [CustomerManageController::class,'adminlog'])->name('customers.adminlog');
    Route::get('customer/ip-block', [CustomerManageController::class,'ip_block'])->name('customers.ip_block');
    Route::post('customer/ip-store', [CustomerManageController::class,'ipblock_store'])->name('customers.ipblock.store');
    Route::post('customer/ip-update', [CustomerManageController::class,'ipblock_update'])->name('customers.ipblock.update');
    Route::post('customer/ip-destroy', [CustomerManageController::class,'ipblock_destroy'])->name('customers.ipblock.destroy');
    Route::post('customer/ip-quick-block', [CustomerManageController::class,'ipblock_quick_store'])->name('customers.ipblock.quick');

    // Refund Management Routes
    Route::get('refunds', [\App\Http\Controllers\Admin\RefundController::class, 'index'])->name('admin.refunds.index');
    Route::get('refunds/{id}', [\App\Http\Controllers\Admin\RefundController::class, 'show'])->name('admin.refunds.show');
    Route::post('refunds/{id}/approve', [\App\Http\Controllers\Admin\RefundController::class, 'approve'])->name('admin.refunds.approve');
    Route::post('refunds/{id}/reject', [\App\Http\Controllers\Admin\RefundController::class, 'reject'])->name('admin.refunds.reject');
    Route::post('refunds/{id}/process', [\App\Http\Controllers\Admin\RefundController::class, 'process'])->name('admin.refunds.process');
    Route::delete('refunds/{id}', [\App\Http\Controllers\Admin\RefundController::class, 'destroy'])->name('admin.refunds.destroy');

    // ═══════════════════════════════════════════
    // 🛡️ WARRANTY MANAGEMENT (Admin Web)
    // ═══════════════════════════════════════════
    Route::prefix('warranty')->name('admin.warranty.')->group(function () {
        Route::get('dashboard', [App\Http\Controllers\Admin\WarrantyController::class, 'dashboard'])->name('dashboard');

        // Supplier warranties
        Route::get('supplier', [App\Http\Controllers\Admin\WarrantyController::class, 'supplierIndex'])->name('supplier.index');
        Route::post('supplier', [App\Http\Controllers\Admin\WarrantyController::class, 'supplierStore'])->name('supplier.store');
        Route::post('supplier/update', [App\Http\Controllers\Admin\WarrantyController::class, 'supplierUpdate'])->name('supplier.update');
        Route::post('supplier/{supplierWarranty}/destroy', [App\Http\Controllers\Admin\WarrantyController::class, 'supplierDestroy'])->name('supplier.destroy');

        // Product tiers (listing only — editing now in product edit page)
        Route::get('tiers', [App\Http\Controllers\Admin\WarrantyController::class, 'tierIndex'])->name('tiers.index');

        // Sales
        Route::get('sales', [App\Http\Controllers\Admin\WarrantyController::class, 'salesIndex'])->name('sales.index');
        Route::get('sales/{warrantySale}', [App\Http\Controllers\Admin\WarrantyController::class, 'salesShow'])->name('sales.show');
        Route::post('sales/{warrantySale}/void', [App\Http\Controllers\Admin\WarrantyController::class, 'salesVoid'])->name('sales.void');

        // Claims
        Route::get('claims', [App\Http\Controllers\Admin\WarrantyController::class, 'claimsIndex'])->name('claims.index');
        Route::get('claims/{warrantyClaim}', [App\Http\Controllers\Admin\WarrantyController::class, 'claimsShow'])->name('claims.show');

        // Specific claim actions — MUST be before the wildcard {action} route
        Route::post('claims/{warrantyClaim}/reject', [App\Http\Controllers\Admin\WarrantyController::class, 'claimsReject'])->name('claims.reject');
        Route::post('claims/{warrantyClaim}/note', [App\Http\Controllers\Admin\WarrantyController::class, 'claimsAddNote'])->name('claims.note');

        // 🆕 Claim Pipeline Actions
        Route::post('claims/{warrantyClaim}/receive-product', [App\Http\Controllers\Admin\WarrantyController::class, 'receiveProduct'])->name('claims.receive-product');
        Route::post('claims/{warrantyClaim}/send-to-supplier', [App\Http\Controllers\Admin\WarrantyController::class, 'sendToSupplier'])->name('claims.send-to-supplier');
        Route::post('claims/{warrantyClaim}/supplier-return', [App\Http\Controllers\Admin\WarrantyController::class, 'supplierReturn'])->name('claims.supplier-return');
        Route::post('claims/{warrantyClaim}/ready-for-delivery', [App\Http\Controllers\Admin\WarrantyController::class, 'readyForDelivery'])->name('claims.ready-for-delivery');
        Route::post('claims/{warrantyClaim}/deliver', [App\Http\Controllers\Admin\WarrantyController::class, 'deliverToCustomer'])->name('claims.deliver');

        // 🆕 Reminders & Instant Replacement (BEFORE the wildcard {action} route)
        Route::post('claims/{warrantyClaim}/reminder', [App\Http\Controllers\Admin\WarrantyController::class, 'storeReminder'])->name('claims.reminder');
        Route::post('reminders/{reminder}/complete', [App\Http\Controllers\Admin\WarrantyController::class, 'completeReminder'])->name('reminders.complete');
        Route::post('claims/{warrantyClaim}/replacement', [App\Http\Controllers\Admin\WarrantyController::class, 'giveReplacement'])->name('claims.replacement');

        // 🆕 Per-step attachments (images/PDF on each claim step)
        Route::post('claims/stage/{stage}/attachment', [App\Http\Controllers\Admin\WarrantyController::class, 'storeStageAttachment'])->name('claims.stage.attachment');
        Route::post('claim-attachments/{attachment}/delete', [App\Http\Controllers\Admin\WarrantyController::class, 'deleteStageAttachment'])->name('claims.stage.attachment.delete');

        // 🆕 Damage products
        Route::get('damage', [App\Http\Controllers\Admin\WarrantyController::class, 'damageIndex'])->name('damage.index');
        Route::post('damage/{damageProduct}/status', [App\Http\Controllers\Admin\WarrantyController::class, 'updateDamageStatus'])->name('damage.status');

        // Wildcard action route — must be LAST to avoid capturing specific routes above
        Route::post('claims/{warrantyClaim}/{action}', [App\Http\Controllers\Admin\WarrantyController::class, 'claimsAction'])->name('claims.action');

        // 🆕 File claim on behalf of customer (admin)
        Route::post('claims/file-for-customer', [App\Http\Controllers\Admin\WarrantyController::class, 'fileClaimForCustomer'])->name('claims.file-for-customer');

        // 🆕 Manually update serial number (e.g., store replacement)
        Route::post('claims/{warrantyClaim}/update-serial', [App\Http\Controllers\Admin\WarrantyController::class, 'updateSerialNumber'])->name('claims.update-serial');

        // 🆕 Challans
        Route::get('claims/{warrantyClaim}/challans', [App\Http\Controllers\Admin\WarrantyController::class, 'challans'])->name('claims.challans');
        Route::get('challans/{challan}/print', [App\Http\Controllers\Admin\WarrantyController::class, 'printChallan'])->name('challans.print');
        Route::get('challans/{challan}/pdf', [App\Http\Controllers\Admin\WarrantyController::class, 'downloadChallanPdf'])->name('challans.pdf');
        Route::delete('challans/{challan}', [App\Http\Controllers\Admin\WarrantyController::class, 'destroyChallan'])->name('challans.destroy');
    });

});