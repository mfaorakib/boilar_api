<?php

namespace App\Http\Controllers\Common;

use App\Models\Common\Filter;
use App\Models\Common\FilterTranslation;
use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryTreeResource;
use App\Http\Resources\Common\FilterEditResource;
use App\Http\Resources\Common\FilterResource;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Str;
use Throwable;

class FilterController extends Controller
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

        $this->authorize('viewAny filter');

        // $per_page = (int)$request->query('per_page', 10);
        // $filters = Filter::with('children')->whereIsRoot()->defaultOrder()->paginate($per_page);
        
        $query = $request->query('query');
        $sortBy = $request->query('sortBy');
        $direction = $request->query('direction');
        $per_page = $request->query('per_page', 10);

        $filters = Filter::with('children');
        if ($query) {
            $filters = $filters->whereTranslationLike('name', '%' . $query . '%');
        }
        if ($sortBy) {
            $filters = $filters->orderBy($sortBy, $direction);
        }
        if ($per_page === '-1') {
            $results = $filters->whereIsRoot()->get();
            $filters = new LengthAwarePaginator($results, $results->count(), -1);
        } else {
            $filters = $filters->whereIsRoot()->paginate($per_page);
        }


        return FilterResource::collection($filters);
    }

    /**
     * Get all filters
     *
     * @return AnonymousResourceCollection
     */
    public function getAll()
    {
        $filters = Filter::with('children')->whereIsRoot()->defaultOrder()->get();

        return CategoryTreeResource::collection($filters);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param $locale
     * @return \Illuminate\Http\JsonResponse
     * @throws AuthorizationException
     */
    public function store($locale)
    {
        App::setLocale($locale);

        $this->authorize('create filter');

        // begin database transaction
        DB::beginTransaction();
        try {
            $filter = Filter::create();

            // commit changes
            DB::commit();
            return response()->json([
                'message' => Lang::get('crud.create'),
                'filterId' => $filter->id
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
     * Show filter.
     *
     * @param $locale
     * @param Filter $filter
     * @return FilterEditResource|JsonResponse
     */
    public function show($locale, Filter $filter)
    {
        App::setLocale($locale);

        try {
            $filter->load('children');

            return new FilterEditResource($filter);
        } catch (Throwable $exception) {
            report($exception);
            return response()->json([
                'message' => Lang::get('crud.error')
            ], 404);
        }
    }

    /**
     * Edit filter.
     *
     * @param $locale
     * @param Filter $filter
     * @return FilterEditResource|JsonResponse
     */
    public function edit($locale, Filter $filter)
    {
        App::setLocale($locale);

        try {
            return new FilterEditResource($filter);
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
     * @param Filter $filter
     * @return JsonResponse
     * @throws AuthorizationException
     * @throws \Illuminate\Validation\ValidationException
     */
    public function update(Request $request, $locale, Filter $filter)
    {
        App::setLocale($locale);

        $this->authorize('update filter');

        $this->validate($request, [
            'name' => 'required'
        ]);

        // begin database transaction
        DB::beginTransaction();
        try {
            $filter->update($request->all());

            if ($request->has('children')) {
                $filter->children()->forceDelete();

                collect($request->input('children'))->each(function ($item) use ($filter) {
                    $filter->children()->create([
                        'name' => $item['name'],
                        'status' => 'active'
                    ]);
                });
            }

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
     * @param Filter $filter
     * @return \Illuminate\Http\JsonResponse
     * @throws AuthorizationException
     */
    public function destroy($locale, Filter $filter)
    {
        App::setLocale($locale);

        $this->authorize('delete filter');

        // begin database transaction
        DB::beginTransaction();
        try {
            $filter->delete();

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
     * Get trashed filters
     *
     * @param Request $request
     * @param $locale
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function getTrashed(Request $request, $locale)
    {
        App::setLocale($locale);

        $this->authorize('viewAny filter');

        $per_page = empty($request->query('per_page')) ? 10 : (int)$request->query('per_page');

        $filters = Filter::onlyTrashed()->latest()->paginate($per_page);

        return response()->json([
            'data' => $filters
        ], 200);
    }

    /**
     * Restore all trashed filters
     *
     * @param Request $request
     * @param $locale
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function restoreTrashed(Request $request, $locale)
    {
        App::setLocale($locale);

        $this->authorize('restore filter');

        $ids = explode(',', $request->get('ids'));

        // begin database transaction
        DB::beginTransaction();
        try {
            if (isset($ids)) {
                Filter::onlyTrashed()
                    ->whereIn('id', $ids)
                    ->restore();
            } else {
                Filter::onlyTrashed()->restore();
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
     * Restore single trashed filter
     *
     * @param $locale
     * @param $id
     * @return mixed
     * @throws AuthorizationException
     */
    public function restoreSingleTrashed($locale, $id)
    {
        App::setLocale($locale);

        $this->authorize('restore filter');

        // begin database transaction
        DB::beginTransaction();
        try {
            Filter::onlyTrashed()
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
     * Permanently delete all trashed filters
     *
     * @param Request $request
     * @param $locale
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function forceDelete(Request $request, $locale)
    {
        App::setLocale($locale);

        $this->authorize('forceDelete filter');

        $ids = explode(',', $request->get('ids'));

        // begin database transaction
        DB::beginTransaction();
        try {
            if (isset($ids)) {
                $filters = Filter::onlyTrashed()
                    ->whereIn('id', $ids)
                    ->get();
            } else {
                $filters = Filter::onlyTrashed()->get();
            }
            foreach ($filters as $filter) {
                // delete filter
                $filter->forceDelete();
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
     * Permanently delete single trashed filter
     *
     * @param $locale
     * @param $id
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function forceSingleDelete($locale, $id)
    {
        App::setLocale($locale);

        $this->authorize('forceDelete filter');

        // begin database transaction
        DB::beginTransaction();
        try {
            $filter = Filter::onlyTrashed()
                ->where('id', '=', $id);

            // delete filter
            $filter->forceDelete();

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
     * Rebuild filter parent children.
     *
     * @param Request $request
     * @param $locale
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function rebuildTree(Request $request, $locale)
    {
        App::setLocale($locale);

        $this->authorize('update filter');

        // begin database transaction
        DB::beginTransaction();
        try {
            // rearrange filter
            Filter::rebuildTree($request->all());

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
