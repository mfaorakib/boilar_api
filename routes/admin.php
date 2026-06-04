<?php

use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Auth\VerificationController;
use App\Http\Controllers\Common\ActivityController;
use App\Http\Controllers\Common\AssetController;
use App\Http\Controllers\Common\AssetCategoryController;
use App\Http\Controllers\Common\CacheManagementController;
use App\Http\Controllers\Common\FileController;
use App\Http\Controllers\Common\FilterController;
use App\Http\Controllers\Common\MenuController;
use App\Http\Controllers\Common\SettingController;
use App\Http\Controllers\Common\TagController;
use App\Http\Controllers\User\CustomerController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Home\MainSliderController;
use App\Http\Controllers\Order\OrderController;
use App\Http\Controllers\Order\OrderStatusController;
use App\Http\Controllers\Marketing\CouponController;
use App\Http\Controllers\Payment\PaymentMethodController;
use App\Http\Controllers\Payment\PaymentStatusController;
use App\Http\Controllers\Product\CategoryController as ProductCategoryController;
use App\Http\Controllers\Product\ProductController;
use App\Http\Controllers\Product\ProductSliderController;
use App\Http\Controllers\Marketing\BranchController;
use App\Http\Controllers\Shipping\AreaController;
use App\Http\Controllers\Shipping\DistrictController;
use App\Http\Controllers\Shipping\DivisionController;
use App\Http\Controllers\Reports\SalesReportController;
use App\Http\Controllers\Shipping\ShippingCostController;
use App\Http\Controllers\Shipping\ShippingPathaoController;
use App\Http\Controllers\Shipping\ShippingProviderController;
use App\Http\Controllers\User\AuthController;
use App\Http\Controllers\User\CustomerAuthController;
use App\Http\Controllers\User\RoleController;
use App\Http\Controllers\User\UserController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\User\CustomerAddressController;

/*
|--------------------------------------------------------------------------
| API admin Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "admin" middleware group. Enjoy building your API!
|
*/

// Controllers Within The "App\Http\Controllers\User" Namespace

Route::prefix('auth')->group(function () {
    // confirm email
    Route::get('confirm-email/{token}', [AuthController::class, 'confirmEmail']);

    // confirm email resent
    Route::get('send-token/{email}', [AuthController::class, 'sendConfirmationToken']);

    // login a user — throttle: max 10 attempts per minute per IP
    Route::post('login', [AuthController::class, 'login'])->name('login')->middleware('throttle:10,1');

    // check if email or mobile exist
    Route::get('check', [AuthController::class, 'checkIsEmailMobileExist']);

    // get token
    Route::post('token', [AuthController::class, 'token']);

    // auth protected routes
    Route::middleware('auth:sanctum')->group(function () {
        // logout auth user
        Route::post('logout', [AuthController::class, 'logout']);
        // get auth user
        Route::get('me', [AuthController::class, 'me']);
    });
});


// Controllers Within The "App\Http\Controllers\User" Namespace
Route::middleware('auth:sanctum')->group(function () {
    // user routes
    Route::post('update-user-avatar/{user}', [UserController::class, 'updateUserAvatar']);
    Route::patch('update-user-info/{user}', [UserController::class, 'updateUserInfo']);
    Route::patch('update-user-password/{user}', [UserController::class, 'updateUserPassword']);
    Route::get('user-filter', [UserController::class, 'getBySearch']);
    Route::get('user-order', [UserController::class, 'getByOrder']);
    Route::resource('user', UserController::class);

    Route::get('customer/check', [CustomerAuthController::class, 'checkIsEmailMobileExist']);

    // Update customer profile
    Route::patch('customer/update-profile/{customer}', [CustomerController::class, 'updateProfile']);

    // Customer address
    Route::post('customer/address', [CustomerAddressController::class, 'store']);

    // customer routes
    Route::get('customer/statistic', [CustomerController::class, 'getStatistic']);
    Route::get('get-customers', [CustomerController::class, 'getCustomer']);
    Route::get('customer/address', [CustomerController::class, 'getAddress']);
    Route::apiResource('customer', CustomerController::class);

    // role routes
    Route::patch('add-permission/{role}', [RoleController::class, 'addPermissions']);
    Route::get('get-permission/{role}', [RoleController::class, 'getPermissionsByRole']);
    Route::get('permission', [RoleController::class, 'getPermissions']);
    Route::get('role-filter', [RoleController::class, 'getBySearch']);
    Route::get('role-order', [RoleController::class, 'getByOrder']);
    Route::get('role-all', [RoleController::class, 'getAllRoles']);
    Route::get('roles-all', [RoleController::class, 'getRoles']);
    Route::resource('role', RoleController::class);
});

// Controllers Within The "App\Http\Controllers\Common" Namespace
Route::middleware('auth:sanctum')->group(function () {
    // setting routes
    Route::patch('settings-image/{setting}', [SettingController::class, 'updateImage']);
    Route::patch('settings-file/{setting}', [SettingController::class, 'updateFile']);
    Route::patch('settings/{setting}', [SettingController::class, 'update']);
    Route::get('settings', [SettingController::class, 'getSettings']);
    // activity logs
    Route::get('activity-log', [ActivityController::class, 'getActivityLogs']);
    Route::delete('activity-log/{activity}', [ActivityController::class, 'destroy']);
    Route::delete('activity-log', [ActivityController::class, 'destroyAll']);
    // menu routes
    Route::patch('menu-rebuild', [MenuController::class, 'rebuildTree']);
    Route::patch('menu-rebuild-children', [MenuController::class, 'rebuildTree']);
    Route::get('menus', [MenuController::class, 'getMenus']);
    Route::resource('menu', MenuController::class);
    // media routes
    Route::get('file-download/{file}', [FileController::class, 'downloadFile']);
    Route::apiResource('file', FileController::class);
   
    // Controllers Within The "App\Http\Controllers\Common" Namespace
    Route::prefix('asset')->name('asset.')->group(function () {
        // categories routes
        Route::post('category-rebuild', [AssetCategoryController::class, 'rebuildTree']);
        // delete trashed all category
        Route::delete('category-force', [AssetCategoryController::class, 'forceDelete']);
        // delete trashed single category
        Route::delete('category-force/{id}', [AssetCategoryController::class, 'forceSingleDelete']);
        // get all
        Route::get('category-all', [AssetCategoryController::class, 'getAll']);
        // get all as tree
        Route::get('category-tree', [AssetCategoryController::class, 'getAllAsTree']);
        // get all child
        Route::get('category-child', [AssetCategoryController::class, 'getAllChild']);
        Route::apiResource('category', AssetCategoryController::class);
    });

    // media assets
    Route::apiResource('asset', AssetController::class);


    // cache management routes
    Route::get('cache-supported-commands', [CacheManagementController::class, 'getArtisanCommands']);
    Route::get('cache-run-command/{command}', [CacheManagementController::class, 'runArtisanCommand']);
});

Route::prefix('{locale}')->middleware('auth:sanctum')->group(function () {
    // Controllers Within The "App\Http\Controllers\Common" Namespace
    Route::prefix('common')->group(function () {
        // filter routes
        Route::get('filter-all', [FilterController::class, 'getAll']);
        Route::post('filter-rebuild', [FilterController::class, 'rebuildTree']);
        // get trashed filters
        Route::get('filter-trashed', [FilterController::class, 'getTrashed']);
        // restore trashed all filters
        Route::get('filter-trashed-restore', [FilterController::class, 'restoreTrashed']);
        // restore trashed single filter
        Route::get('filter-trashed/{id}', [FilterController::class, 'restoreSingleTrashed']);
        // delete trashed all filter
        Route::delete('filter-force', [FilterController::class, 'forceDelete']);
        // delete trashed single filter
        Route::delete('filter-force/{id}', [FilterController::class, 'forceSingleDelete']);
        Route::delete('filter-child/{filter}', [FilterController::class, 'deleteFilter']);
        Route::resource('filter', FilterController::class);

        // tag routes
        Route::get('tag-filter', [TagController::class, 'getBySearch']);
        Route::get('tag-order', [TagController::class, 'getByOrder']);
        Route::get('tag-all', [TagController::class, 'getAll']);
        // get trashed tags
        Route::get('tag-trashed', [TagController::class, 'getTrashed']);
        // restore trashed all tags
        Route::get('tag-trashed-restore', [TagController::class, 'restoreTrashed']);
        // restore trashed single tag
        Route::get('tag-trashed/{id}', [TagController::class, 'restoreSingleTrashed']);
        // delete trashed all tag
        Route::delete('tag-force', [TagController::class, 'forceDelete']);
        // delete trashed single tag
        Route::delete('tag-force/{id}', [TagController::class, 'forceSingleDelete']);
        Route::apiResource('tag', TagController::class);
    });

    // Controllers Within The "App\Http\Controllers\Product" Namespace
    Route::prefix('product')->name('product.')->group(function () {
        // categories routes
        Route::post('category-rebuild', [ProductCategoryController::class, 'rebuildTree']);
        // get trashed categories
        Route::get('category-trashed', [ProductCategoryController::class, 'getTrashed']);
        // restore trashed all categories
        Route::get('category-trashed-restore', [ProductCategoryController::class, 'restoreTrashed']);
        // restore trashed single category
        Route::get('category-trashed/{id}', [ProductCategoryController::class, 'restoreSingleTrashed']);
        // delete trashed all category
        Route::delete('category-force', [ProductCategoryController::class, 'forceDelete']);
        // delete trashed single category
        Route::delete('category-force/{id}', [ProductCategoryController::class, 'forceSingleDelete']);
        // get all
        Route::get('category-all', [ProductCategoryController::class, 'getAll']);
        // get all as tree
        Route::get('category-tree', [ProductCategoryController::class, 'getAllAsTree']);
        // get all child
        Route::get('category-child', [ProductCategoryController::class, 'getAllChild']);
        // check slug
        Route::get('category/slug/{name}', [ProductCategoryController::class, 'checkSlug']);
        Route::apiResource('category', ProductCategoryController::class);

        // products routes
        // get trashed products
        Route::get('product-trashed', [ProductController::class, 'getTrashed']);
        // restore trashed all products
        Route::get('product-trashed-restore', [ProductController::class, 'restoreTrashed']);
        // restore trashed single product
        Route::get('product-trashed/{id}', [ProductController::class, 'restoreSingleTrashed']);
        // delete trashed all product
        Route::delete('product-force', [ProductController::class, 'forceDelete']);
        // delete trashed single product
        Route::delete('product-force/{id}', [ProductController::class, 'forceSingleDelete']);
        Route::get('get-products', [ProductController::class, 'getProducts']);
        Route::get('product-all', [ProductController::class, 'getAll']);
        // check slug
        Route::get('product/slug/{name}', [ProductController::class, 'checkSlug']);
        Route::apiResource('product', ProductController::class);

        // sliders routes
        Route::apiResource('slider', ProductSliderController::class);
    });

    // Controllers Within The "App\Http\Controllers\Order" Namespace
    Route::prefix('order')->name('order.')->group(function () {
        // status routes
        Route::get('status-all', [OrderStatusController::class, 'getAll']);
        Route::post('status-rebuild', [OrderStatusController::class, 'rebuildTree']);
        // get trashed statuses
        Route::get('status-trashed', [OrderStatusController::class, 'getTrashed']);
        // restore trashed all statuses
        Route::get('status-trashed-restore', [OrderStatusController::class, 'restoreTrashed']);
        // restore trashed single status
        Route::get('status-trashed/{id}', [OrderStatusController::class, 'restoreSingleTrashed']);
        // delete trashed all status
        Route::delete('status-force', [OrderStatusController::class, 'forceDelete']);
        // delete trashed single status
        Route::delete('status-force/{id}', [OrderStatusController::class, 'forceSingleDelete']);
        Route::apiResource('status', OrderStatusController::class);
    });

    // Controllers Within The "App\Http\Controllers\Payment" Namespace
    Route::prefix('payment')->name('payment.')->group(function () {
        // payment-method routes
        Route::get('payment-method-all', [PaymentMethodController::class, 'getAll']);
        Route::post('payment-method-rebuild', [PaymentMethodController::class, 'rebuildTree']);
        // get trashed payment-methods
        Route::get('payment-method-trashed', [PaymentMethodController::class, 'getTrashed']);
        // restore trashed all payment-methods
        Route::get('payment-method-trashed-restore', [PaymentMethodController::class, 'restoreTrashed']);
        // restore trashed single payment-method
        Route::get('payment-method-trashed/{id}', [PaymentMethodController::class, 'restoreSingleTrashed']);
        // delete trashed all payment-method
        Route::delete('payment-method-force', [PaymentMethodController::class, 'forceDelete']);
        // delete trashed single payment-method
        Route::delete('payment-method-force/{id}', [PaymentMethodController::class, 'forceSingleDelete']);
        Route::apiResource('payment-method', PaymentMethodController::class);

        // status routes
        Route::get('status-all', [PaymentStatusController::class, 'getAll']);
        Route::post('status-rebuild', [PaymentStatusController::class, 'rebuildTree']);
        // get trashed statuses
        Route::get('status-trashed', [PaymentStatusController::class, 'getTrashed']);
        // restore trashed all statuses
        Route::get('status-trashed-restore', [PaymentStatusController::class, 'restoreTrashed']);
        // restore trashed single status
        Route::get('status-trashed/{id}', [PaymentStatusController::class, 'restoreSingleTrashed']);
        // delete trashed all status
        Route::delete('status-force', [PaymentStatusController::class, 'forceDelete']);
        // delete trashed single status
        Route::delete('status-force/{id}', [PaymentStatusController::class, 'forceSingleDelete']);
        Route::apiResource('status', PaymentStatusController::class);
    });

    // Controllers Within The "App\Http\Controllers\Shipping" Namespace
    Route::prefix('shipping')->group(function () {
        // provider routes
        Route::get('provider-all', [ShippingProviderController::class, 'getAll']);
        Route::post('provider-rebuild', [ShippingProviderController::class, 'rebuildTree']);
        // get trashed providers
        Route::get('provider-trashed', [ShippingProviderController::class, 'getTrashed']);
        // restore trashed all providers
        Route::get('provider-trashed-restore', [ShippingProviderController::class, 'restoreTrashed']);
        // restore trashed single provider
        Route::get('provider-trashed/{id}', [ShippingProviderController::class, 'restoreSingleTrashed']);
        // delete trashed all provider
        Route::delete('provider-force', [ShippingProviderController::class, 'forceDelete']);
        // delete trashed single provider
        Route::delete('provider-force/{id}', [ShippingProviderController::class, 'forceSingleDelete']);
        Route::apiResource('provider', ShippingProviderController::class);
    });

    // Controllers Within The "App\Http\Controllers\Home" Namespace
    Route::prefix('home')->name('home.')->group(function () {
        // sliders routes
        Route::apiResource('slider', MainSliderController::class);
    });

});

// Controllers Within The "App\Http\Controllers\Order" Namespace
Route::prefix('order')->middleware('auth:sanctum')->name('order.')->group(function () {
    // order routes
    Route::get('order-all', [OrderController::class, 'getAll']);
    Route::post('order-rebuild', [OrderController::class, 'rebuildTree']);
    // get trashed orders
    Route::get('order-trashed', [OrderController::class, 'getTrashed']);
    // restore trashed all orders
    Route::get('order-trashed-restore', [OrderController::class, 'restoreTrashed']);
    // restore trashed single order
    Route::get('order-trashed/{id}', [OrderController::class, 'restoreSingleTrashed']);
    // delete trashed all order
    Route::delete('order-force', [OrderController::class, 'forceDelete']);
    // delete trashed single order
    Route::delete('order-force/{id}', [OrderController::class, 'forceSingleDelete']);
    // update status
    Route::patch('order-status/{order}', [OrderController::class, 'updateStatus']);
    // update process
    Route::patch('order-process/{order}', [OrderController::class, 'updateProcess']);

    // place order
    Route::post('store', [OrderController::class, 'store']);

    Route::apiResource('order', OrderController::class);
});

// Controllers Within The "App\Http\Controllers\Shipping" Namespace
Route::prefix('shipping')->group(function () {
    // division routes
    Route::get('division-all', [DivisionController::class, 'getDivisions']);
    Route::get('division-tree', [DivisionController::class, 'getTreeView']);
    Route::get('district-by-division/{division}', [DistrictController::class, 'getDistricts']);
    Route::get('division-id-by-division-name/{division}', [DivisionController::class, 'getDivisionByName']);
    Route::get('district-all', [DistrictController::class, 'getAllDistricts']);
    Route::get('area-by-district/{district}', [AreaController::class, 'getAreas']);
    Route::get('district-id-by-district-name/{district}', [DistrictController::class, 'getDistrictByName']);
    Route::get('area-all', [AreaController::class, 'getAllAreas']);

    // division routes
    Route::apiResource('division', DivisionController::class);

    // district routes
    Route::apiResource('district', DistrictController::class);

    // area routes
    Route::apiResource('area', AreaController::class);

    // shipping cost routes
    Route::get('cost/{area_id}', [ShippingCostController::class, 'getShippingCost']);
    Route::post('cost-bulk', [ShippingCostController::class, 'insertOrUpdateBulk']);
    Route::apiResource('cost', ShippingCostController::class);

    // Pathao service
    Route::prefix('pathao')->group(function () {
        // generate access token
        Route::get('token', [ShippingPathaoController::class, 'getAccessToken']);
        // generate refresh token
        Route::get('refresh-token', [ShippingPathaoController::class, 'refreshAccessToken']);
        // get cities
        Route::get('cities', [ShippingPathaoController::class, 'getAvailableCities']);
        // get zones
        Route::get('zones/{city_id}', [ShippingPathaoController::class, 'getAvailableZones']);
        // get areas
        Route::get('areas/{zone_id}', [ShippingPathaoController::class, 'getAvailableAreas']);
        // create store
        Route::post('store', [ShippingPathaoController::class, 'createStore']);
        // get stores
        Route::get('store', [ShippingPathaoController::class, 'getStores']);
        // place order
        Route::post('order', [ShippingPathaoController::class, 'createOrder']);
    });
});

// Controllers Within The "App\Http\Controllers\Marketing" Namespace
Route::prefix('marketing')->group(function () {
    // coupon routes
    Route::get('coupon', [CouponController::class, 'checkCoupon']);
    Route::apiResource('coupons', CouponController::class);

     // branch route
     Route::apiResource('branch', BranchController::class);
});

// Controllers Within The "App\Http\Controllers\Sales" Namespace
Route::prefix('reports')->group(function () {
    // Sales report routes
    Route::get('best-selling', [SalesReportController::class, 'bestSelling']);
    Route::apiResource('sales', SalesReportController::class);
});

Route::get('common-settings', [HomeController::class, 'getCommonSettings']);

Route::prefix('dynamic')->group(function () {
    Route::get('product', [ProductController::class, 'generateDynamicLinks']);
});
