<?php

namespace App\Http\Controllers\Order;

use App\Http\Controllers\Controller;
use App\Http\Resources\Product\ProductApiResource;
use App\Models\Product\Product;
use App\Models\User\CustomerWishlist;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Throwable;

class WishListApiController extends Controller
{
    /**
     * @return AnonymousResourceCollection
     */
    public function index(): AnonymousResourceCollection
    {
        $customer_id = Auth::guard('customer')->id();
        $wishlist = CustomerWishlist::query()
            ->where('customer_id', '=', $customer_id)
            ->first();
        if ($wishlist) {
            $products = Product::with('translations')->whereIn('id', $wishlist->products)->get();
        } else {
            $products = collect();
        }

        return ProductApiResource::collection($products);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required'
        ]);
        DB::beginTransaction();
        try {
            $customer_id = Auth::guard('customer')->id();
            $product_id = $request->get('product_id');
            $wishlist = CustomerWishlist::query()
                ->where('customer_id', '=', $customer_id)
                ->first();
            $product_ids = collect([]);
            if ($wishlist) {
                if (!in_array($product_id, $wishlist->products)) {
                    $product_ids = collect($wishlist->products)->push($product_id);
                    $wishlist->update(['products' => $product_ids]);
                }
            } else {
                $product_ids = collect($product_ids)->push($product_id);
                CustomerWishlist::query()->create([
                    'customer_id' => $customer_id,
                    'products' => $product_ids
                ]);
            }

            DB::commit();
            return response()->json([
                'message' => Lang::get('crud.update'),
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
     * @param Request $request
     * @return JsonResponse
     */
    public function destroy(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required'
        ]);
        DB::beginTransaction();
        try {
            $customer_id = Auth::guard('customer')->id();
            $product_id = $request->get('product_id');
            $wishlist = CustomerWishlist::query()
                ->where('customer_id', '=', $customer_id)
                ->first();
            if ($wishlist) {
                if (in_array($product_id, $wishlist->products)) {
                    $product_ids = collect($wishlist->products)->filter(function ($item) use ($product_id) {
                        return $item !== $product_id;
                    });
                    $wishlist->update(['products' => $product_ids]);
                }
            }

            DB::commit();
            return response()->json([
                'message' => Lang::get('crud.delete')
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
