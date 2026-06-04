<?php

namespace App\Http\Controllers\Product;

use App\Jobs\DeepLink\AddProductDeepLink;
use App\Models\Common\TagTranslation;
use App\Models\Product\Product;
use App\Models\Product\Category;
use App\Models\Product\ProductTranslation;
use App\Http\Controllers\Controller;
use App\Http\Requests\Product\ProductRequest;
use App\Http\Resources\Product\ProductEditResource;
use App\Http\Resources\Product\ProductResource;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Str;
use Throwable;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @param $locale
     * @return AnonymousResourceCollection
     * @throws AuthorizationException
     */
    public function index(Request $request, $locale): AnonymousResourceCollection
    {
        App::setLocale($locale);

        $this->authorize('viewAny product');

        $query = $request->query('query');
        $sortBy = $request->query('sortBy');
        $direction = $request->query('direction');
        $per_page = $request->query('per_page', 10);
        $category_slug = $request->query('category');

        $products = Product::latest();

        // Query by category
        if ($request->has('category') && $category_slug !== 'all') {
            $category_id = Category::with('translations')
                ->whereTranslation('slug', $category_slug)
                ->first();

            $ancestors = Category::with('translations')->ancestorsAndSelf($category_id)->pluck('id');
            $descendants = Category::with('translations')->descendantsAndSelf($category_id)->pluck('id');

            // get category ids
            $category_ids = collect($ancestors)->merge($descendants)->unique()->values()->toArray();
            $product_ids = DB::table('product_category_product')
                ->whereIn('category_id', $category_ids)
                ->pluck('product_id')
                ->toArray();

            $products = $products
                ->whereIn('id', $product_ids);
        }

        // Query by search value
        if ($query) {
            $products = Product::whereTranslationLike('name', '%' . $query . '%');
        }
        if ($sortBy) {
            $products = Product::orderBy($sortBy, $direction);
        }
        if ($per_page === '-1') {
            $results = $products->get();
            $products = new LengthAwarePaginator($results, $results->count(), -1);
        } else {
            $products = $products->paginate($per_page);
        }

        return ProductResource::collection($products);
    }

    /**
     * Get all products.
     *
     * @param $locale
     * @return JsonResponse
     */
    public function getAll($locale): JsonResponse
    {
        $products = DB::table('products as p')
            ->join('product_translations as pt', 'p.id', '=', 'pt.product_id')
            ->select('p.id', 'pt.name')
            ->where('pt.locale', '=', $locale)
            ->get();

        return response()->json([
            'data' => $products
        ]);
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

        $this->authorize('create product');

        // begin database transaction
        DB::beginTransaction();
        try {
            $product = Product::create([
                'user_id' => auth()->id(),
                'datetime' => now()
            ]);

            // commit database
            DB::commit();
            // return success message
            return response()->json([
                'message' => Lang::get('crud.create'),
                'productId' => $product->id
            ], 201);
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
     * Display the specified resource.
     *
     * @param $locale
     * @param Product $product
     * @return ProductEditResource|JsonResponse
     * @throws AuthorizationException
     */
    public function show($locale, Product $product)
    {
        App::setLocale($locale);

        $this->authorize('view product');

        try {
            return new ProductEditResource($product);
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
     * @param ProductRequest $request
     * @param $locale
     * @param Product $product
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function update(ProductRequest $request, $locale, Product $product): JsonResponse
    {
        App::setLocale($locale);

        $this->authorize('update product');
        $request->validate([
            'name' => 'required|max:150',
            'sku' => 'required|max:100'
        ]);

        // begin database transaction
        DB::beginTransaction();
        try {

            $request->merge([
                'approved_status' => 'approved'
            ]);

            $product->update($request->all());

            // update dynamic tabs
            if ($request->has('tabs')) {
                // delete existing tabs
                $product->tabs()
                    ->where('locale', '=', $locale)
                    ->delete();
                // create tabs
                $product->tabs()->createMany($request->input('tabs'));
            }

            if ($request->filled('categories')) {
                $items = collect($request->input('categories'))->pluck('id');
                $product->categories()->sync($items);
            }

            if ($request->filled('tags')) {
                $items = collect($request->input('tags'))->pluck('id');
                $product->tags()->sync($items);
            }

            if ($request->has('filters')) {
                $items = collect($request->input('filters'))->pluck('id');

                $product->filters()->sync($items);
            }

            if ($request->has('images')) {
                // delete existing images
                $product->images()->delete();

                $product->images()->createMany($request->input('images'));
            }

            // commit database
            DB::commit();

            // generate dynamic link
            AddProductDeepLink::dispatch($product);

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
//                'message' => Lang::get('crud.error')
            ], 400);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param $locale
     * @param Product $product
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function destroy($locale, Product $product): JsonResponse
    {
        App::setLocale($locale);

        $this->authorize('delete product');

        // begin database transaction
        DB::beginTransaction();
        try {
            // delete product
            $product->delete();

            // commit database
            DB::commit();
            // return success message
            return response()->json([
                'message' => Lang::get('crud.trash')
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
     * Get trashed products
     *
     * @param Request $request
     * @param $locale
     * @return AnonymousResourceCollection
     * @throws AuthorizationException
     */
    public function getTrashed(Request $request, $locale): AnonymousResourceCollection
    {
        App::setLocale($locale);

        $this->authorize('viewAny product');

        $query = $request->query('query');
        $sortBy = $request->query('sortBy');
        $direction = $request->query('direction');
        $per_page = $request->query('per_page', 10);

        $products = Product::onlyTrashed();

        if ($query) {
            $products = $products->whereTranslationLike('name', '%' . $query . '%');
        }
        if ($sortBy) {
            $products = $products->orderBy($sortBy, $direction);
        }
        if ($per_page === '-1') {
            $results = $products->get();
            $products = new LengthAwarePaginator($results, $results->count(), -1);
        } else {
            $products = $products->paginate($per_page);
        }

        return ProductResource::collection($products);
    }

    /**
     * Restore all trashed products
     *
     * @param Request $request
     * @param $locale
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function restoreTrashed(Request $request, $locale): JsonResponse
    {
        App::setLocale($locale);

        $this->authorize('restore product');

        $ids = explode(',', $request->get('ids'));

        // begin database transaction
        DB::beginTransaction();
        try {
            if (isset($ids)) {
                Product::onlyTrashed()
                    ->whereIn('id', $ids)
                    ->restore();
            } else {
                Product::onlyTrashed()->restore();
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
     * Restore single trashed product
     *
     * @param $locale
     * @param $id
     * @return mixed
     * @throws AuthorizationException
     */
    public function restoreSingleTrashed($locale, $id)
    {
        App::setLocale($locale);

        $this->authorize('restore product');

        // begin database transaction
        DB::beginTransaction();
        try {
            Product::onlyTrashed()
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
     * Permanently delete all trashed products
     *
     * @param Request $request
     * @param $locale
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function forceDelete(Request $request, $locale): JsonResponse
    {
        App::setLocale($locale);

        $this->authorize('forceDelete product');

        $ids = explode(',', $request->get('ids'));

        // begin database transaction
        DB::beginTransaction();
        try {
            if (isset($ids)) {
                $products = Product::onlyTrashed()
                    ->whereIn('id', $ids)
                    ->get();
            } else {
                $products = Product::onlyTrashed()->get();
            }
            foreach ($products as $product) {
                // delete tag
                $product->tags()->detach();
                // delete product
                $product->forceDelete();
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
     * Permanently delete single trashed product
     *
     * @param $locale
     * @param $id
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function forceSingleDelete($locale, $id): JsonResponse
    {
        App::setLocale($locale);

        $this->authorize('forceDelete product');

        // begin database transaction
        DB::beginTransaction();
        try {
            $product = Product::onlyTrashed()
                ->where('id', '=', $id)
                ->first();

            // delete tag
            $product->tags()->detach();
            // delete product
            $product->forceDelete();

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
     * Create slug for product.
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
            $latest = ProductTranslation::where('slug', '=', $slug)
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

    /**
     * Get all products.
     * @param Request $request
     * @param $locale
     * @return JsonResponse
     */
    public function getProducts(Request $request, $locale): JsonResponse
    {
        App::setLocale($locale);
        $query = $request->query('query');

        $products = DB::table('products as p')
            ->join('product_translations as pt', 'p.id', '=', 'pt.product_id')
            ->where('sku', 'like', '%' . $query . '%')
            ->orWhere('pt.name', 'like', '%' . $query . '%')
            ->where('p.status', '=', 1)
            ->where('p.approved_status', '=', 'approved')
            ->select('pt.name', 'p.id', 'p.sku', 'p.image', 'p.quantity', 'p.price', 'p.special_price', 'p.special_start_date', 'p.special_end_date')
            ->get();

        return response()->json([
            'data' => $products
        ]);
    }

    /**
     * @return JsonResponse
     */
    public function generateDynamicLinks(): JsonResponse
    {
        Product::with(['translations'])
            ->where('status', '=', 1)
            ->where('approved_status', '=', 'approved')
            ->where('datetime', '<=', now()->toDateTimeString())
            ->get()
            ->map(function ($product) {
                // generate dynamic link
                AddProductDeepLink::dispatch($product);
            });

        return response()->json('Generated Successfully');
    }
}
