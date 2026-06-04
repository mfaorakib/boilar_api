<?php

namespace App\Http\Controllers\Order;

use App\Http\Controllers\Controller;
use App\Http\Resources\Order\CartResource;
use App\Models\Order\Cart;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Throwable;

class CartApiController extends Controller
{
    /**
     * @return CartResource|JsonResponse
     */
    public function getCart(): CartResource|JsonResponse
    {
        $cart = Cart::with('products', 'products.product')
            ->where('customer_id', '=', Auth::guard('customer')->id())
            ->first();

        if ($cart) {
            return new CartResource($cart);
        } else {
            return response()->json([
                'data' => [
                    'id' => 0,
                    'subtotal' => 0,
                    'total' => 0,
                    'totalItems' => 0,
                    'discount' => 0,
                    'products' => []
                ]
            ]);
        }
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function updateOrInsert(Request $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            $customer_id = Auth::guard('customer')->id();
            $cart = Cart::query()->updateOrCreate(
                ['customer_id' => $customer_id],
                [
                    'customer_id' => $customer_id
                ]
            );
            $cart->products()->delete();

            $cart->products()->createMany($request->all());

            DB::commit();

            return response()->json([
                'message' => Lang::get('crud.update'),
                'data' => $cart?->id,
            ]);
        } catch (Throwable $exception) {
            report($exception);
            // rollback changes
            DB::rollBack();
            return response()->json([
                'message' => Lang::get('crud.error'),
                'error' => $exception->getMessage()
            ], 400);
        }
    }

    /**
     * @return JsonResponse
     */
    public function removeFromCart(): JsonResponse
    {
        DB::beginTransaction();
        try {
            $customer_id = Auth::guard('customer')->id();
            Cart::query()->where('customer_id', '=', $customer_id)->delete();

            DB::commit();

            return response()->json([
                'message' => Lang::get('crud.delete'),
            ]);
        } catch (Throwable $exception) {
            report($exception);
            // rollback changes
            DB::rollBack();
            return response()->json([
                'message' => Lang::get('crud.error'),
                'error' => $exception->getMessage()
            ], 400);
        }
    }
}
