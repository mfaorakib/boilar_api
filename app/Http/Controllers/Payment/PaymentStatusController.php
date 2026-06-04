<?php

namespace App\Http\Controllers\Payment;

use App\Models\Payment\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Payment\PaymentStatusEditResource;
use App\Http\Resources\Payment\PaymentStatusResource;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Throwable;

class PaymentStatusController extends Controller
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

        $this->authorize('viewAny payment_status');

        // $per_page = empty($request->query('per_page')) ? 10 : (int)$request->query('per_page');
        // $statuses = PaymentStatus::whereIsRoot()->defaultOrder()->paginate($per_page);

        $query = $request->query('query');
        $sortBy = $request->query('sortBy');
        $direction = $request->query('direction');
        $per_page = $request->query('per_page', 10);

        $statuses = PaymentStatus::latest();
        if ($query) {
            $statuses = PaymentStatus::whereTranslationLike('name', '%' . $query . '%');
        }
        if ($sortBy) {
            $statuses = PaymentStatus::orderBy($sortBy, $direction);
        }
        if ($per_page === '-1') {
            $results = $statuses->get();
            $statuses = new LengthAwarePaginator($results, $results->count(), -1);
        } else {
            $statuses = $statuses->paginate($per_page);
        }

        return PaymentStatusResource::collection($statuses);
    }

    /**
     * Get all payment_statuses
     *
     * @param $locale
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function getAll($locale)
    {
        $this->authorize('viewAny payment_status');

        $statuses = DB::table('payment_statuses as sp')
            ->join('payment_status_translations as spt', 'sp.id', '=', 'spt.payment_status_id')
            ->select('sp.id', 'sp.code', 'spt.name')
            ->where('spt.locale', '=', $locale)
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

        $this->authorize('create payment_status');

        // begin database transaction
        DB::beginTransaction();
        try {
            $status = PaymentStatus::create();

            // commit changes
            DB::commit();
            return response()->json([
                'message' => Lang::get('crud.create'),
                'paymentStatusId' => $status->id
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
     * Show payment_status.
     *
     * @param $locale
     * @param PaymentStatus $status
     * @return PaymentStatusEditResource|JsonResponse
     */
    public function show($locale, PaymentStatus $status)
    {
        App::setLocale($locale);

        try {
            return new PaymentStatusEditResource($status);
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
     * @param PaymentStatus $status
     * @return JsonResponse
     * @throws AuthorizationException
     * @throws \Illuminate\Validation\ValidationException
     */
    public function update(Request $request, $locale, PaymentStatus $status)
    {
        App::setLocale($locale);

        $this->authorize('update payment_status');

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
     * @param PaymentStatus $status
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function destroy($locale, PaymentStatus $status)
    {
        App::setLocale($locale);

        $this->authorize('delete payment_status');

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
     * Get trashed payment_statuses
     *
     * @param Request $request
     * @param $locale
     * @return AnonymousResourceCollection
     * @throws AuthorizationException
     */
    public function getTrashed(Request $request, $locale)
    {
        App::setLocale($locale);

        $this->authorize('viewAny payment_status');

        $per_page = empty($request->query('per_page')) ? 10 : (int)$request->query('per_page');

        $statuses = PaymentStatus::onlyTrashed()->latest()->paginate($per_page);

        return PaymentStatusResource::collection($statuses);
    }

    /**
     * Restore all trashed payment_statuses
     *
     * @param Request $request
     * @param $locale
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function restoreTrashed(Request $request, $locale)
    {
        App::setLocale($locale);

        $this->authorize('restore payment_status');

        $ids = explode(',', $request->get('ids'));

        // begin database transaction
        DB::beginTransaction();
        try {
            if (isset($ids)) {
                PaymentStatus::onlyTrashed()
                    ->whereIn('id', $ids)
                    ->restore();
            } else {
                PaymentStatus::onlyTrashed()->restore();
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
     * Restore single trashed payment_status
     *
     * @param $locale
     * @param $id
     * @return mixed
     * @throws AuthorizationException
     */
    public function restoreSingleTrashed($locale, $id)
    {
        App::setLocale($locale);

        $this->authorize('restore payment_status');

        // begin database transaction
        DB::beginTransaction();
        try {
            PaymentStatus::onlyTrashed()
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
     * Permanently delete all trashed payment_statuses
     *
     * @param Request $request
     * @param $locale
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function forceDelete(Request $request, $locale)
    {
        App::setLocale($locale);

        $this->authorize('forceDelete payment_status');

        $ids = explode(',', $request->get('ids'));

        // begin database transaction
        DB::beginTransaction();
        try {
            if (isset($ids)) {
                $statuses = PaymentStatus::onlyTrashed()
                    ->whereIn('id', $ids)
                    ->get();
            } else {
                $statuses = PaymentStatus::onlyTrashed()->get();
            }
            foreach ($statuses as $status) {
                // delete payment_status
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
     * Permanently delete single trashed payment_status
     *
     * @param $locale
     * @param $id
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function forceSingleDelete($locale, $id)
    {
        App::setLocale($locale);

        $this->authorize('forceDelete payment_status');

        // begin database transaction
        DB::beginTransaction();
        try {
            $status = PaymentStatus::onlyTrashed()
                ->where('id', '=', $id);

            // delete payment_status
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
     * Rebuild payment_status parent children.
     *
     * @param Request $request
     * @param $locale
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function rebuildTree(Request $request, $locale)
    {
        App::setLocale($locale);

        $this->authorize('update payment_status');

        // begin database transaction
        DB::beginTransaction();
        try {
            // rearrange payment_status
            PaymentStatus::rebuildTree($request->all());

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
