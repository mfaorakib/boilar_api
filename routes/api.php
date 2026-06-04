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
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::prefix('auth')->group(function () {
    Route::post('login', [CustomerAuthController::class, 'login']);
    Route::post('register', [CustomerAuthController::class, 'register']);
    Route::post('social', [CustomerAuthController::class, 'socialLogin']);
    Route::get('email-mobile-exist', [CustomerAuthController::class, 'checkIsEmailMobileExist']);

    // forgot password
    Route::post('forgot-password', [CustomerAuthController::class, 'forgotPassword']);
    Route::post('reset-password-by-email', [CustomerAuthController::class, 'resetPasswordByEmail']);

    // verify email
    Route::middleware('auth:customer')->group(function () {
        Route::get('me', [CustomerAuthController::class, 'me']);
        Route::post('logout', [CustomerAuthController::class, 'logout']);
    });
});

Route::prefix('customer')->middleware('auth:customer')->group(function () {
    // update profile
    Route::patch('update-profile/{customer}', [CustomerApiController::class, 'updateProfile']);
    Route::patch('update-password/{customer}', [CustomerApiController::class, 'updateProfile']);
    Route::post('update-customer-avatar/{customer}', [CustomerApiController::class, 'updateAvatar']);
    
    // customer address
    Route::get('get-default-address', [CustomerAddressController::class, 'getDefaultAddress']);
    Route::get('address', [CustomerAddressController::class, 'index']);
    Route::post('address', [CustomerAddressController::class, 'store']);
    Route::get('address/{address}', [CustomerAddressController::class, 'edit']);
    Route::patch('address/{address}', [CustomerAddressController::class, 'update']);
    Route::delete('address/{address}', [CustomerAddressController::class, 'delete']);
    // customer order
    Route::prefix('order')->group(function () {
        Route::get('/', [CustomerApiController::class, 'getOrderList']);
        Route::get('/{order}', [CustomerApiController::class, 'getOrder']);
    });
    // wish list
    Route::get('wishlist', [WishListApiController::class, 'index']);
    Route::post('wishlist', [WishListApiController::class, 'store']);
    Route::post('wishlist-delete', [WishListApiController::class, 'destroy']);

    // get parent questions
    Route::patch('parent-question/{customer}', [CustomerApiController::class, 'storeParentQuestion']);

});

Route::prefix('{locale}')->group(function () {
    // search
    Route::get('search', [SearchController::class, 'search']);
    // Controllers Within The "App\Http\Controllers\Common" Namespace
    Route::prefix('common')->group(function () {
        Route::get('tag-articles', [TagApiController::class, 'getAllWithArticles']);
        Route::get('tag-articles/{article_id}', [TagApiController::class, 'getTagsByArticle']);
        // filters
        Route::get('filters', [FilterApiController::class, 'getAllFilters']);
    });
   
    // Controllers Within The "App\Http\Controllers\Product" Namespace
    Route::prefix('product')->group(function () {
        Route::get('categories', [ProductCategoryApiController::class, 'getAll']);
        Route::get('categories/{category}', [ProductCategoryApiController::class, 'getSingleCategory']);
        Route::get('category-ancestor-descendant/{category}', [ProductCategoryApiController::class, 'getAncestorsDescendants']);
        Route::get('products', [ProductApiController::class, 'getProducts']);
        Route::get('products/{product}', [ProductApiController::class, 'getProductBySlug']);
        Route::get('recommend', [ProductApiController::class, 'recommendProductsForCustomer']);
        Route::get('related/products/{product_id}', [ProductApiController::class, 'getRelatedProducts']);
    });

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

// Controllers Within The "App\Http\Controllers\Order" Namespace
Route::prefix('order')->name('order.')->group(function () {
    Route::middleware('auth:customer')->group(function () {
        // cart
        Route::get('cart', [CartApiController::class, 'getCart']);
        Route::post('cart', [CartApiController::class, 'updateOrInsert']);
        Route::delete('cart', [CartApiController::class, 'removeFromCart']);
        // place order
        Route::post('store', [OrderApiController::class, 'store']);
    });
});

// Controllers Within The "App\Http\Controllers\Payment" Namespace
Route::prefix('payment')->group(function () {
    // bkash
    Route::prefix('bkash')->group(function () {
        Route::get('token', [BKashPaymentController::class, 'getToken']);
        Route::get('refresh-token', [BKashPaymentController::class, 'getRefreshToken']);
        Route::post('create', [BKashPaymentController::class, 'createPayment']);
        Route::post('execute', [BKashPaymentController::class, 'executePayment']);
    });
});

// Controllers Within The "App\Http\Controllers\Marketing" Namespace
Route::prefix('marketing')->group(function () {
    // coupon
    Route::get('coupon', [CouponApiController::class, 'checkCoupon']);
});

Route::prefix('shipping')->group(function () {
    // get shipping cost
    Route::get('cost', [ShippingApiController::class, 'getShippingCost']);
});

// contact
Route::post('contact', [HomeController::class, 'contact']);