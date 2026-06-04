<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Models\Marketing\Coupon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Throwable;

class CouponAppController extends Controller
{
    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function checkCoupon(Request $request): JsonResponse
    {
        try {
            $area_id = $request->query('areaId');
            $code = $request->query('coupon');
            $cart_amount = $request->query('cartAmount');
            $platform = $request->query('platform');
            $customer_id = Auth::id();
            // find coupon
            $coupon = Coupon::query()
                ->where('code', '=', $code)
                ->where('status', '=', 'active')
                ->first();
            // if exist
            if ($coupon) {
                // get coupon history
                $total_uses = DB::table('coupon_histories')
                    ->where('code', '=', $code)
                    ->count();
                $customer_uses = DB::table('coupon_histories')
                    ->where('code', '=', $code)
                    ->where('customer_id', '=', $customer_id)
                    ->count();

                // get area ids
                $area_ids = $coupon->area;
                // get platforms
                $platforms = $coupon->platforms;

                // if cart amount less then
                if ($cart_amount < $coupon->total_amount) {
                    return response()->json([
                        'message' => 'Cart amount must be greater than BDT ' . $coupon->total_amount,
                    ], 400);
                } elseif (!now()->betweenIncluded($coupon->start_date, $coupon->end_date)) {
                    return response()->json([
                        'message' => 'Coupon expired',
                    ], 400);
                } elseif ($total_uses > $coupon->uses_per_coupon) {
                    return response()->json([
                        'message' => 'Coupon maximum use limit exited',
                    ], 400);
                } elseif ($customer_uses > $coupon->uses_per_customer) {
                    return response()->json([
                        'message' => 'You have reached maximum use limit',
                    ], 400);
                } elseif (!empty($area_ids) && !in_array($area_id, $area_ids)) {
                    return response()->json([
                        'message' => 'Coupon isn\'t applicable for your location',
                    ], 400);
                } elseif (!in_array($platform, $platforms)) {
                    return response()->json([
                        'message' => 'Coupon isn\'t applicable for your platform',
                    ], 400);
                } else {
                    // calculate discount if coupon type is percentage
                    if ($coupon->type === 'percentage') {
                        $discount = ceil(($coupon->discount * $cart_amount) / 100);
                    } else {
                        // fixed coupon type
                        $discount = $coupon->discount;
                    }

                    return response()->json([
                        'discount' => (float)$discount,
                        'message' => 'Coupon applied successfully'
                    ]);
                }
            } else {
                return response()->json([
                    'message' => 'Coupon does\'t exist',
                ], 404);
            }
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'Coupon does\'t exist',
                'error' => $exception->getMessage()
            ], 404);
        }
    }
}
