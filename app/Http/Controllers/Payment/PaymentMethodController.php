<?php

namespace App\Http\Controllers\Payment;

use App\Models\Payment\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Http\Resources\Payment\PaymentMethodEditResource;
use App\Http\Resources\Payment\PaymentMethodResource;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Throwable;

class PaymentMethodController extends Controller
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

        $this->authorize('viewAny payment_method');

        // $per_page = empty($request->query('per_page')) ? 10 : (int)$request->query('per_page');
        // $payment_methods = PaymentMethod::whereIsRoot()->defaultOrder()->paginate($per_page);

        $query = $request->query('query');
        $sortBy = $request->query('sortBy');
        $direction = $request->query('direction');
        $per_page = $request->query('per_page', 10);

        $payment_methods = PaymentMethod::latest();
        if ($query) {
            $payment_methods = PaymentMethod::whereTranslationLike('name', '%' . $query . '%');
        }
        if ($sortBy) {
            $payment_methods = PaymentMethod::orderBy($sortBy, $direction);
        }
        if ($per_page === '-1') {
            $results = $payment_methods->get();
            $payment_methods = new LengthAwarePaginator($results, $results->count(), -1);
        } else {
            $payment_methods = $payment_methods->paginate($per_page);
        }

        return PaymentMethodResource::collection($payment_methods);
    }

    /**
     * Get all payment_methods
     *
     * @param $locale
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function getAll($locale)
    {
        App::setLocale($locale);

        $this->authorize('viewAny payment_method');

        $payment_methods = DB::table('payment_methods as sp')
            ->join('payment_method_translations as spt', 'sp.id', '=', 'spt.payment_method_id')
            ->select('sp.id', 'spt.name')
            ->where('spt.locale', '=', $locale)
            ->get();

        return response()->json([
            'data' => $payment_methods
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

        $this->authorize('create payment_method');

        // begin database transaction
        DB::beginTransaction();
        try {
            $payment_method = PaymentMethod::create();

            // commit changes
            DB::commit();
            return response()->json([
                'message' => Lang::get('crud.create'),
                'paymentMethodId' => $payment_method->id
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
     * Show payment_method.
     *
     * @param $locale
     * @param PaymentMethod $payment_method
     * @return PaymentMethodEditResource|JsonResponse
     */
    public function show($locale, PaymentMethod $payment_method)
    {
        App::setLocale($locale);

        try {
            return new PaymentMethodEditResource($payment_method);
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
     * @param PaymentMethod $payment_method
     * @return JsonResponse
     * @throws AuthorizationException
     * @throws \Illuminate\Validation\ValidationException
     */
    public function update(Request $request, $locale, PaymentMethod $payment_method)
    {
        App::setLocale($locale);

        $this->authorize('update payment_method');

        $this->validate($request, [
            'name' => 'required',
            'code' => 'required'
        ]);

        // begin database transaction
        DB::beginTransaction();
        try {
            $payment_method->update($request->all());

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
     * @param PaymentMethod $payment_method
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function destroy($locale, PaymentMethod $payment_method)
    {
        App::setLocale($locale);

        $this->authorize('delete payment_method');

        // begin database transaction
        DB::beginTransaction();
        try {
            $payment_method->delete();

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
     * Get trashed payment_methods
     *
     * @param Request $request
     * @param $locale
     * @return AnonymousResourceCollection
     * @throws AuthorizationException
     */
    public function getTrashed(Request $request, $locale)
    {
        App::setLocale($locale);

        $this->authorize('viewAny payment_method');

        $per_page = empty($request->query('per_page')) ? 10 : (int)$request->query('per_page');

        $payment_methods = PaymentMethod::onlyTrashed()->latest()->paginate($per_page);

        return PaymentMethodResource::collection($payment_methods);
    }

    /**
     * Restore all trashed payment_methods
     *
     * @param Request $request
     * @param $locale
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function restoreTrashed(Request $request, $locale)
    {
        App::setLocale($locale);

        $this->authorize('restore payment_method');

        $ids = explode(',', $request->get('ids'));

        // begin database transaction
        DB::beginTransaction();
        try {
            if (isset($ids)) {
                PaymentMethod::onlyTrashed()
                    ->whereIn('id', $ids)
                    ->restore();
            } else {
                PaymentMethod::onlyTrashed()->restore();
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
     * Restore single trashed payment_method
     *
     * @param $locale
     * @param $id
     * @return mixed
     * @throws AuthorizationException
     */
    public function restoreSingleTrashed($locale, $id)
    {
        App::setLocale($locale);

        $this->authorize('restore payment_method');

        // begin database transaction
        DB::beginTransaction();
        try {
            PaymentMethod::onlyTrashed()
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
     * Permanently delete all trashed payment_methods
     *
     * @param Request $request
     * @param $locale
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function forceDelete(Request $request, $locale)
    {
        App::setLocale($locale);

        $this->authorize('forceDelete payment_method');

        $ids = explode(',', $request->get('ids'));

        // begin database transaction
        DB::beginTransaction();
        try {
            if (isset($ids)) {
                $payment_methods = PaymentMethod::onlyTrashed()
                    ->whereIn('id', $ids)
                    ->get();
            } else {
                $payment_methods = PaymentMethod::onlyTrashed()->get();
            }
            foreach ($payment_methods as $payment_method) {
                // delete payment_method
                $payment_method->forceDelete();
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
     * Permanently delete single trashed payment_method
     *
     * @param $locale
     * @param $id
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function forceSingleDelete($locale, $id)
    {
        App::setLocale($locale);

        $this->authorize('forceDelete payment_method');

        // begin database transaction
        DB::beginTransaction();
        try {
            $payment_method = PaymentMethod::onlyTrashed()
                ->where('id', '=', $id);

            // delete payment_method
            $payment_method->forceDelete();

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
     * Rebuild payment_method parent children.
     *
     * @param Request $request
     * @param $locale
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function rebuildTree(Request $request, $locale)
    {
        App::setLocale($locale);

        $this->authorize('update payment_method');

        // begin database transaction
        DB::beginTransaction();
        try {
            // rearrange payment_method
            PaymentMethod::rebuildTree($request->all());

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
