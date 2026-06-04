<?php

namespace App\Http\Controllers\Shipping;

use App\Models\Shipping\ShippingCost;
use App\Http\Controllers\Controller;
use App\Http\Resources\Shipping\ShippingCostResource;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Illuminate\Validation\ValidationException;
use Throwable;

class ShippingCostController extends Controller
{
    /**
     * Display a listing of the resource.
     * @param Request $request
     * @return AnonymousResourceCollection
     * @throws AuthorizationException
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny shipping_cost');

        $query = $request->query('query');
        $sortBy = $request->query('sortBy');
        $direction = $request->query('direction');
        $per_page = $request->query('per_page', 10);

        $costs = ShippingCost::query();

        if (isset($query)) {
            $costs = ShippingCost::whereHas('area', function (Builder $q) use ($query) {
                $q->where('name', 'like', '%' . $query . '%');
            });
        }

        if (isset($direction)) {
            $costs = ShippingCost::orderBy($sortBy, $direction);
        }

        if ($per_page === '-1') {
            $results = $costs->get();
            $costs = new LengthAwarePaginator($results, $results->count(), -1);
        } else {
            $costs = $costs->paginate($per_page);
        }

        return ShippingCostResource::collection($costs);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws Throwable
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create shipping_cost');

        // begin database transaction
        DB::beginTransaction();
        try {
            $cost = ShippingCost::create($request->all());

            // commit changes
            DB::commit();
            return response()->json([
                'message' => Lang::get('crud.create'),
                'costId' => $cost->id
            ], 201);
        } catch (Throwable $exception) {
            report($exception);
            // rollback changes
            DB::rollBack();
            return response()->json([
                'message' => Lang::get('crud.error')
            ], 400);
        }
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function insertOrUpdateBulk(Request $request): JsonResponse
    {
        $this->authorize('create shipping_cost');

        $this->validate($request, [
            'area_ids' => 'required',
            'cost' => 'required',
        ]);
        // begin database transaction
        DB::beginTransaction();
        try {
            // first get ids from table
            $exist_ids = DB::table('shipping_costs')->pluck('area_id')->toArray();
            // get requested ids
            $requested_ids = $request->get('area_ids');
            // get updatable ids
            $updatable_ids = array_values(array_intersect($exist_ids, $requested_ids));
            // get insertable ids
            $insertable_ids = array_values(array_diff($requested_ids, $exist_ids));
            // prepare data for insert
            $data = collect();
            foreach ($insertable_ids as $id) {
                $data->push([
                    'area_id' => $id,
                    'cost' => $request->get('cost'),
                    'cart_amount' => $request->get('cart_amount')?:0,
                    'special_delivery_cost' => $request->get('special_delivery_cost')?:0,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
            DB::table('shipping_costs')->insert($data->toArray());

            // prepare for update
            DB::table('shipping_costs')
                ->whereIn('area_id', $updatable_ids)
                ->update([
                    'cost' => $request->get('cost'),
                    'cart_amount' => $request->get('cart_amount')?:0,
                    'special_delivery_cost' => $request->get('special_delivery_cost')?:0,
                    'updated_at' => now()
                ]);

            // commit changes
            DB::commit();
            return response()->json([
                'message' => Lang::get('crud.update'),
            ]);
        } catch (Throwable $exception) {
            report($exception);
            // rollback changes
            DB::rollBack();
            return response()->json([
//                'message' => Lang::get('crud.error')
                'message' => $exception->getMessage()
            ], 400);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param ShippingCost $cost
     * @return JsonResponse
     * @throws Throwable
     */
    public function update(Request $request, ShippingCost $cost): JsonResponse
    {
        $this->authorize('update shipping_cost');

        $this->validate($request, [
            'area_id' => 'required',
            'cost' => 'required',
        ]);

        // begin database transaction
        DB::beginTransaction();
        try {
            $cost->update($request->all());

            // commit changes
            DB::commit();
            return response()->json([
                'message' => Lang::get('crud.update')
            ]);
        } catch (Throwable $exception) {
            report($exception);
            // rollback changes
            DB::rollBack();
            return response()->json([
                'message' => Lang::get('crud.error')
            ], 400);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param ShippingCost $cost
     * @return JsonResponse
     * @throws Throwable
     */
    public function destroy(ShippingCost $cost): JsonResponse
    {
        $this->authorize('delete shipping_cost');

        // begin database transaction
        DB::beginTransaction();
        try {
            $cost->delete();

            // commit changes
            DB::commit();
            return response()->json([
                'message' => Lang::get('crud.delete')
            ]);
        } catch (Throwable $exception) {
            report($exception);
            // rollback changes
            DB::rollBack();
            return response()->json([
                'message' => Lang::get('crud.error')
            ], 400);
        }
    }

     /**
     * @return JsonResponse
     */
    public function getShippingCost(Request $request, $areaId): JsonResponse
    {
        $cost_info = DB::table('shipping_costs')
        ->where('area_id', '=', $areaId)
        ->first();
    
        $total_order_amount = $request->query('total_amount');
        $cost = $cost_info->cost?:0;
        $shipping_cart_amount = $cost_info->cart_amount?:0;

        if( (float)$total_order_amount >= (float)$shipping_cart_amount ) {
            $cost = $cost_info->special_delivery_cost?:0;
        }

        return response()->json([
            'data' => $cost?:0
        ]);
    }
}
