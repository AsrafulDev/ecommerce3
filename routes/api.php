<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\FrontendController;
use App\Http\Controllers\Api\UpdateServerController;
use App\Http\Controllers\Api\Mobile\AuthController;
use App\Http\Controllers\Api\Mobile\ProductController;
use App\Http\Controllers\Api\Mobile\CartController;
use App\Http\Controllers\Api\Mobile\OrderController;


Route::group(['namespace' => 'Api','prefix'=>'v1','middleware' => 'api'], function(){
    
     Route::get('app-config', [FrontendController::class, 'appconfig']);
     Route::get('slider', [FrontendController::class, 'slider']);
     Route::get('category-menu', [FrontendController::class, 'categorymenu']);
     Route::get('hotdeal-product', [FrontendController::class, 'hotdealproduct']);
     Route::get('homepage-product', [FrontendController::class, 'homepageproduct']);
     Route::get('footer-menu-left', [FrontendController::class, 'footermenuleft']);
     Route::get('footer-menu-right', [FrontendController::class, 'footermenuright']);
     Route::get('social-media', [FrontendController::class, 'socialmedia']);
     Route::get('contactinfo', [FrontendController::class, 'contactinfo']);
     
    //  Home Page Api End =================================
    
    Route::get('category/{id}', [FrontendController::class, 'catproduct']);
    

});

// ============================================
// Mobile API Routes (Flutter App)
// ============================================

// Public Routes
Route::prefix('v1/mobile')->group(function () {
    
    // Authentication (Public)
    Route::prefix('auth')->group(function () {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login', [AuthController::class, 'login']);
    });

    // Products (Public)
    Route::prefix('products')->group(function () {
        Route::get('/', [ProductController::class, 'index']);
        Route::get('featured', [ProductController::class, 'featured']);
        Route::get('hot-deals', [ProductController::class, 'hotDeals']);
        Route::get('category/{categoryId}', [ProductController::class, 'byCategory']);
        Route::get('{id}', [ProductController::class, 'show']);
    });

    // Order Tracking (Public)
    Route::get('orders/track/{invoiceId}', [OrderController::class, 'track']);
});

// Protected Routes (Require Authentication)
Route::prefix('v1/mobile')->middleware('auth:sanctum')->group(function () {
    
    // Authentication (Protected)
    Route::prefix('auth')->group(function () {
        Route::get('me', [AuthController::class, 'me']);
        Route::put('profile', [AuthController::class, 'updateProfile']);
        Route::post('change-password', [AuthController::class, 'changePassword']);
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('logout-all', [AuthController::class, 'logoutAll']);
    });

    // Cart (Protected)
    Route::prefix('cart')->group(function () {
        Route::get('/', [CartController::class, 'index']);
        Route::get('count', [CartController::class, 'count']);
        Route::post('add', [CartController::class, 'add']);
        Route::put('{id}', [CartController::class, 'update']);
        Route::delete('{id}', [CartController::class, 'remove']);
        Route::delete('clear', [CartController::class, 'clear']);
    });

    // Orders (Protected)
    Route::prefix('orders')->group(function () {
        Route::get('/', [OrderController::class, 'index']);
        Route::get('{id}', [OrderController::class, 'show']);
        Route::post('/', [OrderController::class, 'store']);
    });
});

// Update Server API Routes (License Protected)
Route::prefix('updates')->group(function () {
    Route::post('check', [UpdateServerController::class, 'check']);
    Route::post('info', [UpdateServerController::class, 'info']);
    Route::post('download', [UpdateServerController::class, 'download']);
    Route::get('file/{version}', [UpdateServerController::class, 'downloadFile'])->name('api.updates.file');
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// ═══════════════════════════════════════════════════════
// 🛡️ WARRANTY MANAGEMENT API ROUTES
// ═══════════════════════════════════════════════════════

// ── Public: Warranty tiers for a product ──────────
Route::get('v1/products/{product}/warranty-tiers', [App\Http\Controllers\Api\WarrantyApiController::class, 'productTiers']);
Route::get('v1/products/{product}/warranty-price', [App\Http\Controllers\Api\WarrantyApiController::class, 'calculatePrice']);

// ── Customer: My Warranties & Claims ──────────────
Route::middleware('auth:sanctum')->prefix('v1/customer')->group(function () {
    Route::get('my-warranties', [App\Http\Controllers\Api\WarrantyApiController::class, 'myWarranties']);
    Route::get('my-warranties/{warrantySale}', [App\Http\Controllers\Api\WarrantyApiController::class, 'showWarranty']);
    Route::post('my-warranties/{warrantySale}/claim', [App\Http\Controllers\Api\WarrantyApiController::class, 'fileClaim']);
    Route::get('my-claims', [App\Http\Controllers\Api\WarrantyApiController::class, 'myClaims']);
    Route::get('my-claims/{warrantyClaim}', [App\Http\Controllers\Api\WarrantyApiController::class, 'showClaim']);
    Route::post('my-claims/{warrantyClaim}/cancel', [App\Http\Controllers\Api\WarrantyApiController::class, 'cancelClaim']);
});

// ── Admin: Warranty Management ────────────────────
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('v1/admin/warranty')->group(function () {
    Route::get('stats', [App\Http\Controllers\Api\Admin\WarrantyAdminController::class, 'stats']);
    Route::apiResource('supplier', App\Http\Controllers\Api\Admin\WarrantyAdminController::class)->only(['index', 'show', 'store', 'update']);
    Route::get('sales', [App\Http\Controllers\Api\Admin\WarrantyAdminController::class, 'sales']);
    Route::get('sales/{warrantySale}', [App\Http\Controllers\Api\Admin\WarrantyAdminController::class, 'showSale']);
    Route::post('sales/{warrantySale}/void', [App\Http\Controllers\Api\Admin\WarrantyAdminController::class, 'voidSale']);
    Route::get('claims', [App\Http\Controllers\Api\Admin\WarrantyAdminController::class, 'claims']);
    Route::get('claims/{warrantyClaim}', [App\Http\Controllers\Api\Admin\WarrantyAdminController::class, 'showClaim']);
    Route::post('claims/{warrantyClaim}/review', [App\Http\Controllers\Api\Admin\WarrantyAdminController::class, 'reviewClaim']);
    Route::post('claims/{warrantyClaim}/approve', [App\Http\Controllers\Api\Admin\WarrantyAdminController::class, 'approveClaim']);
    Route::post('claims/{warrantyClaim}/reject', [App\Http\Controllers\Api\Admin\WarrantyAdminController::class, 'rejectClaim']);
    Route::post('claims/{warrantyClaim}/advance-stage', [App\Http\Controllers\Api\Admin\WarrantyAdminController::class, 'advanceStage']);
    Route::post('claims/{warrantyClaim}/resolve', [App\Http\Controllers\Api\Admin\WarrantyAdminController::class, 'resolveClaim']);
    Route::post('claims/{warrantyClaim}/notes', [App\Http\Controllers\Api\Admin\WarrantyAdminController::class, 'addNote']);
});
