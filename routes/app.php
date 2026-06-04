<?php

use App\Http\Controllers\Common\FilterApiController;
use App\Http\Controllers\Common\TagApiController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Marketing\CouponApiController;
use App\Http\Controllers\Marketing\CouponAppController;
use App\Http\Controllers\Order\CartApiController;
use App\Http\Controllers\Order\CartAppController;
use App\Http\Controllers\Order\OrderApiController;
use App\Http\Controllers\Order\OrderAppController;
use App\Http\Controllers\Order\WishListApiController;
use App\Http\Controllers\Order\WishListAppController;
use App\Http\Controllers\Payment\BKashPaymentController;
use App\Http\Controllers\Payment\PaymentApiController;
use App\Http\Controllers\Product\CategoryApiController as ProductCategoryApiController;
use App\Http\Controllers\Product\ProductApiController;
use App\Http\Controllers\Product\ProductAppController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\Shipping\ShippingApiController;
use App\Http\Controllers\User\CustomerAddressController;
use App\Http\Controllers\User\CustomerAddressAppController;
use App\Http\Controllers\User\CompanyAppController;
use App\Http\Controllers\User\CustomerApiController;
use App\Http\Controllers\User\CustomerAppController;
use App\Http\Controllers\User\CustomerAuthController;
use App\Http\Controllers\Home\MainSliderController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| APP Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// ===== Start App apis =====
Route::prefix('v1')->group(function () {
    // Auth
    Route::prefix('auth')->group(function () {
        Route::post('login', [CustomerAppController::class, 'login']);
        Route::post('register', [CustomerAppController::class, 'register']);
        Route::post('social', [CustomerAppController::class, 'socialLogin']);

        // forgot password
        Route::post('forgot-password', [CustomerAppController::class, 'forgotPassword']);
        Route::post('reset-password-by-email', [CustomerAppController::class, 'resetPasswordByEmail']);

        Route::middleware('auth:sanctum')->group(function () {
            Route::get('me', [CustomerAppController::class, 'me']);
            Route::post('change-password', [CustomerAppController::class, 'changePassword']);
            Route::post('logout', [CustomerAppController::class, 'logout']);
        });
    });

    // Customers
    Route::prefix('customer')->middleware('auth:sanctum')->group(function () {
        // update profile
        Route::patch('update-profile', [CustomerAppController::class, 'updateProfile']);
        Route::post('update-customer-avatar', [CustomerAppController::class, 'updateAvatar']);
        Route::patch('update-password', [CustomerAppController::class, 'updateProfile']);

        // customer address
        Route::get('get-default-address', [CustomerAddressAppController::class, 'getDefaultAddress']);
        Route::get('address', [CustomerAddressAppController::class, 'index']);
        Route::post('address', [CustomerAddressAppController::class, 'store']);
        Route::get('address/{address}', [CustomerAddressAppController::class, 'edit']);
        Route::patch('address/{address}', [CustomerAddressAppController::class, 'update']);
        Route::delete('address/{address}', [CustomerAddressAppController::class, 'delete']);
        Route::get('devices', [CustomerAppController::class, 'getDevices']);
        Route::post('device', [CustomerAppController::class, 'createDevice']);

        // customer order
        Route::prefix('order')->group(function () {
            // cart
            Route::get('cart', [CartAppController::class, 'getCart']);
            Route::post('cart', [CartAppController::class, 'updateOrInsert']);
            Route::delete('cart', [CartAppController::class, 'removeFromCart']);
            // place order
            Route::post('store', [OrderAppController::class, 'store']);
            Route::get('/', [CustomerAppController::class, 'getOrderList']);
            Route::get('/{order}', [CustomerAppController::class, 'getOrder']);
        });

        // wish list
        Route::get('wishlist', [WishListAppController::class, 'index']);
        Route::post('wishlist', [WishListAppController::class, 'store']);
        Route::post('wishlist-delete', [WishListAppController::class, 'destroy']);

    });

    // Controllers Within The "App\Http\Controllers\Marketing" Namespace
    Route::prefix('marketing')->group(function () {
        // coupon
        Route::get('coupon', [CouponAppController::class, 'checkCoupon']);
    });

    Route::prefix('shipping')->group(function () {
        // get shipping cost
        Route::get('cost', [ShippingApiController::class, 'getShippingCost']);
    });

    Route::prefix('{locale}')->group(function () {

        // Controllers Within The "App\Http\Controllers\Product" Namespace
        Route::prefix('product')->group(function () {
            Route::get('recommend', [ProductApiController::class, 'recommendProductsForCustomer']);
            Route::get('categories', [ProductCategoryApiController::class, 'getAll']);
            Route::get('categories/{category}', [ProductCategoryApiController::class, 'getSingleCategory']);
            Route::get('category-ancestor-descendant/{category}', [ProductCategoryApiController::class, 'getAncestorsDescendants']);
            Route::get('products', [ProductAppController::class, 'getProducts']);
            Route::get('products/{product}', [ProductAppController::class, 'getProductBySlug']);
            Route::get('tracker', [ProductAppController::class, 'getTrackerProduct']);
        });

        // search
        Route::get('search', [SearchController::class, 'search']);

        // Controllers Within The "App\Http\Controllers\Payment" Namespace
        Route::prefix('payment')->group(function () {
            // payment method
            Route::get('payment-method', [PaymentApiController::class, 'getPaymentMethods']);
        });

        // Controllers Within The "App\Http\Controllers\Shipping" Namespace
        Route::prefix('shipping')->group(function () {
            // division routes
            Route::get('division-all', [ShippingApiController::class, 'getDivisions']);
            Route::get('district-by-division/{division}', [ShippingApiController::class, 'getDistricts']);
            Route::get('area-by-district/{district}', [ShippingApiController::class, 'getAreas']);
        });

        Route::prefix('home')->group(function () {
            // sliders routes
            Route::get('sliders', [MainSliderController::class, 'getAllSliders']);
        });
    });

});
