<?php

namespace App\Http\Controllers\Shipping;

use App\Models\Shipping\ShippingProvider;
use App\Http\Controllers\Controller;
use App\Http\Resources\Shipping\ShippingProviderEditResource;
use App\Http\Resources\Shipping\ShippingProviderResource;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Throwable;

class ShippingProviderController extends Controller
{
    /**
     * Display a listing of the resource.
     * @param Request $request
     * @param $locale
     * @return AnonymousResourceCollection
     * @throws AuthorizationException
     */
    public function index(Request $request, $locale): AnonymousResourceCollection
    {
        App::setLocale($locale);

        $this->authorize('viewAny shipping_provider');

        // $per_page = (int)$request->query('per_page', 10);
        // $providers = ShippingProvider::with('children')->whereIsRoot()->defaultOrder()->paginate($per_page);

        $query = $request->query('query');
        $sortBy = $request->query('sortBy');
        $direction = $request->query('direction');
        $per_page = $request->query('per_page', 10);

        $providers = ShippingProvider::latest();
        if ($query) {
            $providers = ShippingProvider::whereTranslationLike('name', '%' . $query . '%');
        }
        if ($sortBy) {
            $providers = ShippingProvider::orderBy($sortBy, $direction);
        }
        if ($per_page === '-1') {
            $results = $providers->get();
            $providers = new LengthAwarePaginator($results, $results->count(), -1);
        } else {
            $providers = $providers->paginate($per_page);
        }

        return ShippingProviderResource::collection($providers);
    }

    /**
     * Get all shipping_providers
     *
     * @param $locale
     * @return AnonymousResourceCollection
     * @throws AuthorizationException
     */
    public function getAll($locale): AnonymousResourceCollection
    {
        App::setLocale($locale);

        $this->authorize('viewAny shipping_provider');

        $providers = ShippingProvider::query()
            ->where('status', '=', 'active')
            ->latest()->get();

        return ShippingProviderResource::collection($providers);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @param $locale
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function store(Request $request, $locale): JsonResponse
    {
        App::setLocale($locale);

        $this->authorize('create shipping_provider');

        // begin database transaction
        DB::beginTransaction();
        try {
            $provider = ShippingProvider::create($request->all());

            // commit changes
            DB::commit();
            return response()->json([
                'message' => Lang::get('crud.create'),
                'providerId' => $provider->id
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
     * Show shipping_provider.
     *
     * @param $locale
     * @param ShippingProvider $provider
     * @return ShippingProviderEditResource|JsonResponse
     */
    public function show($locale, ShippingProvider $provider): ShippingProviderEditResource|JsonResponse
    {
        App::setLocale($locale);

        try {
            return new ShippingProviderEditResource($provider);
        } catch (Throwable $exception) {
            report($exception);
            return response()->json([
                'message' => Lang::get('crud.error')
            ], 404);
        }
    }

    /**
     * Edit shipping_provider.
     *
     * @param $locale
     * @param ShippingProvider $provider
     * @return ShippingProviderEditResource|JsonResponse
     */
    public function edit($locale, ShippingProvider $provider): ShippingProviderEditResource|JsonResponse
    {
        App::setLocale($locale);

        try {
            return new ShippingProviderEditResource($provider);
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
     * @param ShippingProvider $provider
     * @return JsonResponse
     * @throws AuthorizationException
     * @throws \Illuminate\Validation\ValidationException
     */
    public function update(Request $request, $locale, ShippingProvider $provider): JsonResponse
    {
        App::setLocale($locale);

        $this->authorize('update shipping_provider');

        $this->validate($request, [
            'name' => 'required',
            'code' => 'required'
        ]);

        // begin database transaction
        DB::beginTransaction();
        try {
            $provider->update($request->all());

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
     * @param $locale
     * @param ShippingProvider $provider
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function destroy($locale, ShippingProvider $provider): JsonResponse
    {
        App::setLocale($locale);

        $this->authorize('delete shipping_provider');

        // begin database transaction
        DB::beginTransaction();
        try {
            $provider->delete();

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
     * Get trashed shipping_providers
     *
     * @param Request $request
     * @param $locale
     * @return AnonymousResourceCollection
     * @throws AuthorizationException
     */
    public function getTrashed(Request $request, $locale): AnonymousResourceCollection
    {
        App::setLocale($locale);

        $this->authorize('viewAny shipping_provider');

        $per_page = empty($request->query('per_page')) ? 10 : (int)$request->query('per_page');

        $providers = ShippingProvider::onlyTrashed()->latest()->paginate($per_page);

        return ShippingProviderResource::collection($providers);
    }

    /**
     * Restore all trashed shipping_providers
     *
     * @param Request $request
     * @param $locale
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function restoreTrashed(Request $request, $locale): JsonResponse
    {
        App::setLocale($locale);

        $this->authorize('restore shipping_provider');

        $ids = explode(',', $request->get('ids'));

        // begin database transaction
        DB::beginTransaction();
        try {
            if (isset($ids)) {
                ShippingProvider::onlyTrashed()
                    ->whereIn('id', $ids)
                    ->restore();
            } else {
                ShippingProvider::onlyTrashed()->restore();
            }

            // commit database
            DB::commit();
            // return success message
            return response()->json([
                'message' => Lang::get('crud.restore')
            ]);
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
     * Restore single trashed shipping_provider
     *
     * @param $locale
     * @param $id
     * @return mixed
     * @throws AuthorizationException
     */
    public function restoreSingleTrashed($locale, $id): mixed
    {
        App::setLocale($locale);

        $this->authorize('restore shipping_provider');

        // begin database transaction
        DB::beginTransaction();
        try {
            ShippingProvider::onlyTrashed()
                ->where('id', '=', $id)
                ->restore();

            // commit database
            DB::commit();
            // return success message
            return response()->json([
                'message' => Lang::get('crud.restore')
            ]);
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
     * Permanently delete all trashed shipping_providers
     *
     * @param Request $request
     * @param $locale
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function forceDelete(Request $request, $locale): JsonResponse
    {
        App::setLocale($locale);

        $this->authorize('forceDelete shipping_provider');

        $ids = explode(',', $request->get('ids'));

        // begin database transaction
        DB::beginTransaction();
        try {
            if (isset($ids)) {
                $providers = ShippingProvider::onlyTrashed()
                    ->whereIn('id', $ids)
                    ->get();
            } else {
                $providers = ShippingProvider::onlyTrashed()->get();
            }
            foreach ($providers as $provider) {
                // delete shipping_provider
                $provider->forceDelete();
            }

            // commit database
            DB::commit();
            // return success message
            return response()->json([
                'message' => Lang::get('crud.delete')
            ]);
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
     * Permanently delete single trashed shipping_provider
     *
     * @param $locale
     * @param $id
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function forceSingleDelete($locale, $id): JsonResponse
    {
        App::setLocale($locale);

        $this->authorize('forceDelete shipping_provider');

        // begin database transaction
        DB::beginTransaction();
        try {
            $provider = ShippingProvider::onlyTrashed()
                ->where('id', '=', $id);

            // delete shipping_provider
            $provider->forceDelete();

            // commit database
            DB::commit();
            // return success message
            return response()->json([
                'message' => Lang::get('crud.delete')
            ]);
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
     * Rebuild shipping_provider parent children.
     *
     * @param Request $request
     * @param $locale
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function rebuildTree(Request $request, $locale): JsonResponse
    {
        App::setLocale($locale);

        $this->authorize('update shipping_provider');

        // begin database transaction
        DB::beginTransaction();
        try {
            // rearrange shipping_provider
            ShippingProvider::rebuildTree($request->all());

            // commit database
            DB::commit();
            // return success message
            return response()->json([
                'message' => Lang::get('crud.update')
            ]);
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
