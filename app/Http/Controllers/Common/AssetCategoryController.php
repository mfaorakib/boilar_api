<?php

namespace App\Http\Controllers\Common;

use App\Models\Common\AssetCategory;
use App\Models\Common\AssetCategoryTranslation;
use App\Http\Controllers\Controller;
use App\Http\Resources\Common\AssetCategoryEditResource;
use App\Http\Resources\Common\AssetCategoryResource;
use App\Http\Resources\CategoryTreeResource;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Str;
use Throwable;

class AssetCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     * @param Request $request
     * @return AnonymousResourceCollection
     * @throws AuthorizationException
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny asset_category');

        $per_page = (int)$request->query('per_page', 10);
        $categories = AssetCategory::with('children')->whereIsRoot()->defaultOrder()->paginate($per_page);

        return AssetCategoryResource::collection($categories);
    }

    /**
     * Get all categories
     *
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function getAll(): JsonResponse
    {
        $categories = DB::table('asset_categories as c')
            ->select('c.id', 'c.name')
            ->whereNull('c.parent_id')
            ->get();

        return response()->json([
            'data' => $categories
        ]);
    }

    /**
     * @return JsonResponse
     */
    public function getAllChild(): JsonResponse
    {
        $categories = DB::table('asset_categories as c')
            ->select('c.id', 'c.name')
            ->whereNotNull('c.parent_id')
            ->get();

        return response()->json([
            'data' => $categories
        ]);
    }

    /**
     * @return AnonymousResourceCollection
     */
    public function getAllAsTree(): AnonymousResourceCollection
    {
        $categories = AssetCategory::with(['children' => function ($query) {
            $query->with(['children' => function ($q) {
                $q->with(['children'])
                    ->where('status', '=', 'active')
                    ->defaultOrder();
            }])
                ->where('status', '=', 'active')
                ->defaultOrder();
        }])
            ->where('status', '=', 'active')
            ->whereIsRoot()->defaultOrder()->get();

        return CategoryTreeResource::collection($categories);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function store(): JsonResponse
    {

        $this->authorize('create asset_category');

        // begin database transaction
        DB::beginTransaction();
        try {
            $category = AssetCategory::create();

            // commit changes
            DB::commit();
            return response()->json([
                'message' => Lang::get('crud.create'),
                'categoryId' => $category->id
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
     * Show category.
     *
     * @param AssetCategory $category
     * @return AssetCategoryEditResource|JsonResponse
     */
    public function show(AssetCategory $category)
    {

        try {
            return new AssetCategoryEditResource($category);
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
     * @param AssetCategory $category
     * @return JsonResponse
     * @throws AuthorizationException
     * @throws \Illuminate\Validation\ValidationException
     */
    public function update(Request $request, AssetCategory $category): JsonResponse
    {
        $this->authorize('update asset_category');

        $this->validate($request, [
            'name' => 'required',
        ]);

        // begin database transaction
        DB::beginTransaction();
        try {
            $category->update($request->all());

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
     * @param AssetCategory $category
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function destroy(AssetCategory $category): JsonResponse
    {
        $this->authorize('delete asset_category');

        // begin database transaction
        DB::beginTransaction();
        try {
            $category->delete();

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
     * Permanently delete all trashed categories
     *
     * @param Request $request
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function forceDelete(Request $request): JsonResponse
    {
        $this->authorize('forceDelete asset_category');

        $ids = explode(',', $request->get('ids'));

        // begin database transaction
        DB::beginTransaction();
        try {
            if (isset($ids)) {
                $categories = AssetCategory::onlyTrashed()
                    ->whereIn('id', $ids)
                    ->get();
            } else {
                $categories = AssetCategory::onlyTrashed()->get();
            }
            foreach ($categories as $category) {
                // delete category
                $category->forceDelete();
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
     * Permanently delete single trashed category
     *
     * @param $id
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function forceSingleDelete($id): JsonResponse
    {
        $this->authorize('forceDelete asset_category');

        // begin database transaction
        DB::beginTransaction();
        try {
            $category = AssetCategory::onlyTrashed()
                ->where('id', '=', $id);

            // delete category
            $category->forceDelete();

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
     * Rebuild category parent children.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function rebuildTree(Request $request): JsonResponse
    {
        $this->authorize('update asset_category');

        // begin database transaction
        DB::beginTransaction();
        try {
            // rearrange category
            AssetCategory::rebuildTree($request->all());

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
                'message' => $exception->getMessage()
            ], 400);
        }
    }
}
