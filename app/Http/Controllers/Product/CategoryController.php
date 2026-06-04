<?php

namespace App\Http\Controllers\Product;

use App\Models\Product\Category;
use App\Models\Product\CategoryTranslation;
use App\Http\Controllers\Controller;
use App\Http\Resources\Product\CategoryEditResource;
use App\Http\Resources\Product\CategoryResource;
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

class CategoryController extends Controller
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

        $this->authorize('viewAny product_category');

        $per_page = (int)$request->query('per_page', 10);
        $categories = Category::with('children')->whereIsRoot()->defaultOrder()->paginate($per_page);

        return CategoryResource::collection($categories);
    }

    /**
     * Get all categories
     *
     * @param $locale
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function getAll($locale): JsonResponse
    {
        $categories = DB::table('product_categories as c')
            ->join('product_category_translations as ct', 'c.id', '=', 'ct.category_id')
            ->select('c.id', 'ct.name')
            ->whereNull('c.parent_id')
            ->where('ct.locale', '=', $locale)
            ->get();

        return response()->json([
            'data' => $categories
        ]);
    }

    /**
     * @param $locale
     * @return JsonResponse
     */
    public function getAllChild($locale): JsonResponse
    {
        $categories = DB::table('product_categories as c')
            ->join('product_category_translations as ct', 'c.id', '=', 'ct.category_id')
            ->select('c.id', 'ct.name')
            ->whereNotNull('c.parent_id')
            ->where('ct.locale', '=', $locale)
            ->get();

        return response()->json([
            'data' => $categories
        ]);
    }

    /**
     * @param $locale
     * @return AnonymousResourceCollection
     */
    public function getAllAsTree($locale): AnonymousResourceCollection
    {
        App::setLocale($locale);

        $categories = Category::with(['translations', 'children' => function ($query) {
            $query->with(['translations', 'children' => function ($q) {
                $q->with(['translations', 'children'])
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
     * @param $locale
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function store($locale): JsonResponse
    {
        App::setLocale($locale);

        $this->authorize('create product_category');

        // begin database transaction
        DB::beginTransaction();
        try {
            $category = Category::create();

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
     * @param $locale
     * @param Category $category
     * @return CategoryEditResource|JsonResponse
     */
    public function show($locale, Category $category)
    {
        App::setLocale($locale);

        try {
            return new CategoryEditResource($category);
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
     * @param Category $category
     * @return JsonResponse
     * @throws AuthorizationException
     * @throws \Illuminate\Validation\ValidationException
     */
    public function update(Request $request, $locale, Category $category): JsonResponse
    {
        App::setLocale($locale);

        $this->authorize('update product_category');

        $translation = DB::table('product_category_translations')->where('category_id', '=', $category->id)->where('locale', '=', $locale)->first();
        $translation_id = null;
        if ($translation) {
            $translation_id = $translation->id;
        }

        $this->validate($request, [
            'name' => 'required',
            'slug' => 'required|alpha_dash|unique:product_category_translations,slug,' . $translation_id,
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
     * @param $locale
     * @param Category $category
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function destroy($locale, Category $category): JsonResponse
    {
        App::setLocale($locale);

        $this->authorize('delete product_category');

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
     * Get trashed categories
     *
     * @param Request $request
     * @param $locale
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function getTrashed(Request $request, $locale): JsonResponse
    {
        App::setLocale($locale);

        $this->authorize('viewAny product_category');

        $per_page = empty($request->query('per_page')) ? 10 : (int)$request->query('per_page');

        $categories = Category::onlyTrashed()->latest()->paginate($per_page);

        return response()->json([
            'data' => $categories
        ]);
    }

    /**
     * Restore all trashed categories
     *
     * @param Request $request
     * @param $locale
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function restoreTrashed(Request $request, $locale): JsonResponse
    {
        App::setLocale($locale);

        $this->authorize('restore product_category');

        $ids = explode(',', $request->get('ids'));

        // begin database transaction
        DB::beginTransaction();
        try {
            if (isset($ids)) {
                Category::onlyTrashed()
                    ->whereIn('id', $ids)
                    ->restore();
            } else {
                Category::onlyTrashed()->restore();
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
     * Restore single trashed category
     *
     * @param $locale
     * @param $id
     * @return mixed
     * @throws AuthorizationException
     */
    public function restoreSingleTrashed($locale, $id)
    {
        App::setLocale($locale);

        $this->authorize('restore product_category');

        // begin database transaction
        DB::beginTransaction();
        try {
            Category::onlyTrashed()
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
     * Permanently delete all trashed categories
     *
     * @param Request $request
     * @param $locale
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function forceDelete(Request $request, $locale): JsonResponse
    {
        App::setLocale($locale);

        $this->authorize('forceDelete product_category');

        $ids = explode(',', $request->get('ids'));

        // begin database transaction
        DB::beginTransaction();
        try {
            if (isset($ids)) {
                $categories = Category::onlyTrashed()
                    ->whereIn('id', $ids)
                    ->get();
            } else {
                $categories = Category::onlyTrashed()->get();
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
     * @param $locale
     * @param $id
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function forceSingleDelete($locale, $id): JsonResponse
    {
        App::setLocale($locale);

        $this->authorize('forceDelete product_category');

        // begin database transaction
        DB::beginTransaction();
        try {
            $category = Category::onlyTrashed()
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
     * @param $locale
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function rebuildTree(Request $request, $locale): JsonResponse
    {
        App::setLocale($locale);

        $this->authorize('update product_category');

        // begin database transaction
        DB::beginTransaction();
        try {
            // rearrange category
            Category::rebuildTree($request->all());

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

    /**
     * Create slug for category.
     *
     * @param $locale
     * @param $name
     * @return JsonResponse
     */
    public function checkSlug($locale, $name): JsonResponse
    {
        try {
            $slug = Str::slug($name, '-', $locale);

            # slug repeat check
            $latest = CategoryTranslation::where('slug', '=', $slug)
                ->latest('id')
                ->value('slug');

            if ($latest) {
                $pieces = explode('-', $latest);
                $number = intval(end($pieces));
                $slug .= '-' . ($number + 1);
            }

            return response()->json([
                'slug' => $slug
            ]);
        } catch (Throwable $exception) {
            // log exception
            report($exception);
            // return failed message
            return response()->json([
                'message' => Lang::get('crud.error')
            ], 400);
        }
    }
}
