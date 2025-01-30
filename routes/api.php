<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\CouponController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\TagController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\WishlistController;
use App\Http\Controllers\Api\ProductVariantController;
use App\Http\Controllers\Api\ProductAttributeController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\LanguageController;
use App\Http\Controllers\Api\MediaController;
use App\Http\Controllers\Api\MediaCollectionController;
use App\Http\Controllers\Api\CartController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);
    Route::apiResource('products', ProductController::class);
    Route::apiResource('coupons', CouponController::class);
    Route::post('coupons/validate', [CouponController::class, 'validateCoupon']);
    Route::apiResource('orders', OrderController::class);
    Route::get('orders/{order}/items', [OrderController::class, 'items']);
    Route::post('orders/{order}/add-item', [OrderController::class, 'addItem']);
    Route::delete('orders/{order}/remove-item/{item}', [OrderController::class, 'removeItem']);
    Route::get('products/search', [ProductController::class, 'search']);
    Route::get('products/category/{category}', [ProductController::class, 'byCategory']);
    Route::get('products/tag/{tag}', [ProductController::class, 'byTag']);
    Route::apiResource('categories', CategoryController::class);
    Route::get('categories/{category}/products', [CategoryController::class, 'products']);
    Route::get('categories/tree', [CategoryController::class, 'tree']);
    Route::apiResource('tags', TagController::class);
    Route::get('tags/{tag}/products', [TagController::class, 'products']);
    Route::get('tags/popular', [TagController::class, 'popular']);
    Route::prefix('dashboard')->group(function () {
        Route::get('stats', [DashboardController::class, 'stats']);
        Route::get('recent-orders', [DashboardController::class, 'recentOrders']);
        Route::get('top-products', [DashboardController::class, 'topProducts']);
        Route::get('revenue-chart', [DashboardController::class, 'revenueChart']);
    });
    Route::prefix('settings')->group(function () {
        Route::get('/', [SettingController::class, 'index']);
        Route::put('/', [SettingController::class, 'update']);
        Route::post('tax-rates', [SettingController::class, 'updateTaxRates']);
        Route::post('shipping-methods', [SettingController::class, 'updateShippingMethods']);
    });
    Route::apiResource('reviews', ReviewController::class);
    Route::post('reviews/{review}/vote', [ReviewController::class, 'vote']);
    Route::get('products/{product}/reviews', [ReviewController::class, 'productReviews']);
    Route::get('users/{user}/reviews', [ReviewController::class, 'userReviews']);
    Route::apiResource('wishlists', WishlistController::class);
    Route::post('wishlists/{wishlist}/add-product', [WishlistController::class, 'addProduct']);
    Route::delete('wishlists/{wishlist}/remove-product/{product}', [WishlistController::class, 'removeProduct']);
    Route::post('wishlists/{wishlist}/share', [WishlistController::class, 'share']);
    Route::get('wishlists/shared/{token}', [WishlistController::class, 'viewShared']);
    Route::apiResource('products.variants', ProductVariantController::class);
    Route::get('products/{product}/attributes', [ProductVariantController::class, 'attributes']);
    Route::post('products/{product}/variants/bulk', [ProductVariantController::class, 'bulkCreate']);
    Route::put('products/{product}/variants/bulk', [ProductVariantController::class, 'bulkUpdate']);
    Route::get('variants/sku/{sku}', [ProductVariantController::class, 'findBySku']);
    Route::apiResource('attributes', ProductAttributeController::class);
    Route::get('attributes/{attribute}/values', [ProductAttributeController::class, 'values']);
    Route::post('attributes/{attribute}/values', [ProductAttributeController::class, 'addValues']);
    Route::get('search', [SearchController::class, 'search']);
    Route::get('search/suggest', [SearchController::class, 'suggest']);
    Route::get('search/analytics', [SearchController::class, 'analytics'])->middleware('can:view-analytics');
    Route::get('languages', [LanguageController::class, 'index']);
    Route::post('languages', [LanguageController::class, 'store'])->middleware('can:manage-languages');
    Route::get('languages/{language}', [LanguageController::class, 'show']);
    Route::put('languages/{language}', [LanguageController::class, 'update'])->middleware('can:manage-languages');
    Route::delete('languages/{language}', [LanguageController::class, 'destroy'])->middleware('can:manage-languages');
    Route::post('languages/{language}/default', [LanguageController::class, 'setDefault'])->middleware('can:manage-languages');
    Route::post('languages/order', [LanguageController::class, 'updateOrder'])->middleware('can:manage-languages');
    Route::get('languages/{language}/translations', [LanguageController::class, 'translations']);
    Route::post('languages/{language}/translations', [LanguageController::class, 'updateTranslations'])->middleware('can:manage-translations');
    Route::get('media', [MediaController::class, 'index']);
    Route::post('media', [MediaController::class, 'store']);
    Route::get('media/{media}', [MediaController::class, 'show']);
    Route::post('media/{media}', [MediaController::class, 'update']);
    Route::delete('media/{media}', [MediaController::class, 'destroy']);
    Route::post('media/{media}/duplicate', [MediaController::class, 'duplicate']);
    Route::post('media/order', [MediaController::class, 'updateOrder']);
    Route::get('media-collections', [MediaCollectionController::class, 'index']);
    Route::post('media-collections', [MediaCollectionController::class, 'store'])->middleware('can:manage-media');
    Route::get('media-collections/{mediaCollection}', [MediaCollectionController::class, 'show']);
    Route::put('media-collections/{mediaCollection}', [MediaCollectionController::class, 'update'])->middleware('can:manage-media');
    Route::delete('media-collections/{mediaCollection}', [MediaCollectionController::class, 'destroy'])->middleware('can:manage-media');
    Route::prefix('cart')->group(function () {
        Route::get('/', [CartController::class, 'show']);
        Route::post('/items', [CartController::class, 'addItem']);
        Route::put('/items/{itemId}', [CartController::class, 'updateItem']);
        Route::delete('/items/{itemId}', [CartController::class, 'removeItem']);
        Route::delete('/', [CartController::class, 'clear']);
        Route::post('/coupon', [CartController::class, 'applyCoupon']);
        Route::delete('/coupon', [CartController::class, 'removeCoupon']);
    });
});