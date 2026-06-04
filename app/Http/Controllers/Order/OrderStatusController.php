<?php

namespace App\Http\Controllers\Order;

use App\Models\Order\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Order\OrderStatusEditResource;
use App\Http\Resources\Order\OrderStatusResource;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Throwable;

class OrderStatusController extends Controller
{
    /**
     * Display a listing of the resource.
     * @param Request $request
     * @param $locale
     * @return AnonymousResourceCollection
     * @throws AuthorizationException
     */
    public function index(Request $request, $locale)
    {
        App::setLocale($locale);

        $this->authorize('viewAny order_status');

        $query = $request->query('query');
        $sortBy = $request->query('sortBy');
        $direction = $request->query('direction');
        $per_page = $request->query('per_page', 10);

        $statuses = OrderStatus::latest();
        if ($query) {
            $statuses = OrderStatus::whereTranslationLike('name', '%' . $query . '%');
        }
        if ($sortBy) {
            $statuses = OrderStatus::orderBy($sortBy, $direction);
        }
        if ($per_page === '-1') {
            $results = $statuses->get();
            $statuses = new LengthAwarePaginator($results, $results->count(), -1);
        } else {
            $statuses = $statuses->paginate($per_page);
        }

        return OrderStatusResource::collection($statuses);
    }

    /**
     * Get all order_statuses
     *
     * @param $locale
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function getAll($locale)
    {
        $this->authorize('viewAny order_status');

        $statuses = DB::table('order_statuses as sp')
            ->join('order_status_translations as spt', 'sp.id', '=', 'spt.order_status_id')
            ->select('sp.id', 'sp.code', 'spt.name')
            ->where('spt.locale', '=', $locale)
            ->where('sp.status', '=', 'active')
            ->get();

        return response()->json([
            'data' => $statuses
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param $locale
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function store($locale)
    {
        App::setLocale($locale);

        $this->authorize('create order_status');

        // begin database transaction
        DB::beginTransaction();
        try {
            $status = OrderStatus::create();

            // commit changes
            DB::commit();
            return response()->json([
                'message' => Lang::get('crud.create'),
                'orderStatusId' => $status->id
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
     * Show order_status.
     *
     * @param $locale
     * @param OrderStatus $status
     * @return OrderStatusEditResource|JsonResponse
     */
    public function show($locale, OrderStatus $status)
    {
        App::setLocale($locale);

        try {
            return new OrderStatusEditResource($status);
        } catch (Throwable $exception) {
            report($exception);
            return response()->json([
                'message' => Lang::get('crud.error')
            ], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param $locale
     * @param OrderStatus $status
     * @return JsonResponse
     * @throws AuthorizationException
     * @throws \Illuminate\Validation\ValidationException
     */
    public function update(Request $request, $locale, OrderStatus $status)
    {
        App::setLocale($locale);

        $this->authorize('update order_status');

        $this->validate($request, [
            'name' => 'required',
            'code' => 'required|alpha_dash'
        ]);

        // begin database transaction
        DB::beginTransaction();
        try {
            $status->update($request->all());

            // commit changes
            DB::commit();
            return response()->json([
                'message' => Lang::get('crud.update')
            ], 200);
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
     * @param $locale
     * @param OrderStatus $status
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function destroy($locale, OrderStatus $status)
    {
        App::setLocale($locale);

        $this->authorize('delete order_status');

        // begin database transaction
        DB::beginTransaction();
        try {
            $status->delete();

            // commit changes
            DB::commit();
            return response()->json([
                'message' => Lang::get('crud.delete')
            ], 200);
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
     * Get trashed order_statuses
     *
     * @param Request $request
     * @param $locale
     * @return AnonymousResourceCollection
     * @throws AuthorizationException
     */
    public function getTrashed(Request $request, $locale)
    {
        App::setLocale($locale);

        $this->authorize('viewAny order_status');

        $per_page = empty($request->query('per_page')) ? 10 : (int)$request->query('per_page');

        $statuses = OrderStatus::onlyTrashed()->latest()->paginate($per_page);

        return OrderStatusResource::collection($statuses);
    }

    /**
     * Restore all trashed order_statuses
     *
     * @param Request $request
     * @param $locale
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function restoreTrashed(Request $request, $locale)
    {
        App::setLocale($locale);

        $this->authorize('restore order_status');

        $ids = explode(',', $request->get('ids'));

        // begin database transaction
        DB::beginTransaction();
        try {
            if (isset($ids)) {
                OrderStatus::onlyTrashed()
                    ->whereIn('id', $ids)
                    ->restore();
            } else {
                OrderStatus::onlyTrashed()->restore();
            }

            // commit database
            DB::commit();
            // return success message
            return response()->json([
                'message' => Lang::get('crud.restore')
            ], 200);
        } catch (Throwable $exception) {
            // log exception
            report($exception);
            // rollback database
            DB::rollBack();
            // return failed message
            return response()->json([
                'message' => Lang::get('crud.error')
            ], 400);
        }
    }

    /**
     * Restore single trashed order_status
     *
     * @param $locale
     * @param $id
     * @return mixed
     * @throws AuthorizationException
     */
    public function restoreSingleTrashed($locale, $id)
    {
        App::setLocale($locale);

        $this->authorize('restore order_status');

        // begin database transaction
        DB::beginTransaction();
        try {
            OrderStatus::onlyTrashed()
                ->where('id', '=', $id)
                ->restore();

            // commit database
            DB::commit();
            // return success message
            return response()->json([
                'message' => Lang::get('crud.restore')
            ], 200);
        } catch (Throwable $exception) {
            // log exception
            report($exception);
            // rollback database
            DB::rollBack();
            // return failed message
            return response()->json([
                'message' => Lang::get('crud.error')
            ], 400);
        }
    }

    /**
     * Permanently delete all trashed order_statuses
     *
     * @param Request $request
     * @param $locale
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function forceDelete(Request $request, $locale)
    {
        App::setLocale($locale);

        $this->authorize('forceDelete order_status');

        $ids = explode(',', $request->get('ids'));

        // begin database transaction
        DB::beginTransaction();
        try {
            if (isset($ids)) {
                $statuses = OrderStatus::onlyTrashed()
                    ->whereIn('id', $ids)
                    ->get();
            } else {
                $statuses = OrderStatus::onlyTrashed()->get();
            }
            foreach ($statuses as $status) {
                // delete order_status
                $status->forceDelete();
            }

            // commit database
            DB::commit();
            // return success message
            return response()->json([
                'message' => Lang::get('crud.delete')
            ], 200);
        } catch (Throwable $exception) {
            // log exception
            report($exception);
            // rollback database
            DB::rollBack();
            // return failed message
            return response()->json([
                'message' => Lang::get('crud.error')
            ], 400);
        }
    }

    /**
     * Permanently delete single trashed order_status
     *
     * @param $locale
     * @param $id
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function forceSingleDelete($locale, $id)
    {
        App::setLocale($locale);

        $this->authorize('forceDelete order_status');

        // begin database transaction
        DB::beginTransaction();
        try {
            $status = OrderStatus::onlyTrashed()
                ->where('id', '=', $id);

            // delete order_status
            $status->forceDelete();

            // commit database
            DB::commit();
            // return success message
            return response()->json([
                'message' => Lang::get('crud.delete')
            ], 200);
        } catch (Throwable $exception) {
            // log exception
            report($exception);
            // rollback database
            DB::rollBack();
            // return failed message
            return response()->json([
                'message' => Lang::get('crud.error')
            ], 400);
        }
    }

    /**
     * Rebuild order_status parent children.
     *
     * @param Request $request
     * @param $locale
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function rebuildTree(Request $request, $locale)
    {
        App::setLocale($locale);

        $this->authorize('update order_status');

        // begin database transaction
        DB::beginTransaction();
        try {
            // rearrange order_status
            OrderStatus::rebuildTree($request->all());

            // commit database
            DB::commit();
            // return success message
            return response()->json([
                'message' => Lang::get('crud.update')
            ], 200);
        } catch (Throwable $exception) {
            // log exception
            report($exception);
            // rollback database
            DB::rollBack();
            // return failed message
            return response()->json([
                'message' => Lang::get('crud.error')
            ], 400);
        }
    }
}
