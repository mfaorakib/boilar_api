<?php

namespace App\Http\Controllers\Product;

use App\Http\Resources\Product\ProductApiResource;
use App\Models\Product\Category;
use App\Models\Product\Product;
use App\Http\Controllers\Controller;
use App\Http\Resources\Product\ProductFeaturedApiResource;
use App\Http\Resources\Product\ProductSingleApiResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Throwable;

class ProductAppController extends Controller
{
    /**
     * @param $locale
     * @param $slug
     * @return ProductSingleApiResource|JsonResponse
     */
    public function getProductBySlug($locale, $slug): JsonResponse|ProductSingleApiResource
    {
        App::setLocale($locale);

        $product = Product::with('translations', 'categories', 'images', 'tabs', 'tags')
            ->whereTranslation('slug', $slug)
            ->first();

        if ($product) {
            return new ProductSingleApiResource($product);
        } else {
            return response()->json(collect());
        }
    }

    /**
     * Get products by pagination.
     *
     * @param Request $request
     * @param $locale
     * @return AnonymousResourceCollection|JsonResponse
     */
    public function getProducts(Request $request, $locale): AnonymousResourceCollection|JsonResponse
    {
        try {
            App::setLocale($locale);
            $limit = (int)$request->query('limit', 8);
            $sort = $request->query('sort', 'latest');

            $category_slug = $request->query('category');

            $products = Product::with(['translations'])
                ->where('status', '=', 1)
                ->where('approved_status', '=', 'approved')
                ->where('datetime', '<=', now()->toDateTimeString());

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

            // filter products
            if ($request->filled('filters')) {
                $filter_ids = explode(',', $request->query('filters'));
                $product_ids = DB::table('product_filter_product')
                    ->whereIn('filter_id', $filter_ids)
                    ->pluck('product_id')
                    ->toArray();
                $products = $products
                    ->whereIn('id', $product_ids);
            }

            // filter by price
            $price = (int)$request->query('price');
            if ($request->has('price') && $price > 0) {
                $products = $products->whereBetween('price', [0, $price]);
            }

            $products = match ($sort) {
                'featured' => $products->where('is_featured', '=', true),
                'bestsellers' => $products->orderByDesc('sales_count'),
                default => $products->orderByDesc('datetime'),
            };
            $products = $products->paginate($limit);
            $products->appends([
                'limit' => $limit,
                'sort' => $sort,
            ]);

            return ProductApiResource::collection($products);

        } catch (Throwable $exception) {
            report($exception);
            return response()->json([
                'message' => $exception->getMessage()
//                'message' => Lang::get('crud.error')
            ], 404);
        }
    }

    /**
     * @param Request $request
     * @param $locale
     * @return JsonResponse
     */
    public function getTrackerProduct(Request $request, $locale): JsonResponse
    {
        App::setLocale($locale);
        $limit = (int)$request->query('limit', 1);
        $products = Product::with(['translations'])
            ->where('tracker', '=', $request->get('tracker_type'))
            ->where('tracker_start_day', '<=', $request->get('tracker_day'))
            ->where('tracker_end_day', '>=', $request->get('tracker_day'))
            ->where('status', '=', 1);

        if($products->count() === 0 && !($request->get('tracker_type') ==='other')) {
            $products = Product::with(['translations'])
                ->where('tracker_end_day', '>', $request->get('tracker_day'))
                ->where('status', '=', 1);
        };

        $products = $products->cursorPaginate($limit);

        return response()->json([
            'data' => ProductApiResource::collection($products->items()),
            'nextPageUrl' => $products->nextPageUrl(),
            'prevPageUrl' => $products->previousPageUrl(),
            'limit' => $products->perPage(),
            'hasMorePages' => $products->hasMorePages(),
            'hasPages' => $products->hasPages(),
        ]);
    }
}
