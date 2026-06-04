<?php

namespace App\Http\Controllers\Product;

use App\Http\Resources\Product\ProductApiResource;
use App\Models\Product\Category;
use App\Models\Product\Product;
use App\Models\Common\Tag;
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
use Illuminate\Database\Eloquent\Builder;
use Throwable;

class ProductApiController extends Controller
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
     * @param Request $request
     * @param $locale
     * @param $type
     * @return AnonymousResourceCollection|JsonResponse
     */
    public function getLatestProducts(Request $request, $locale, $type)
    {
        try {
            $limit = $request->query('limit');

            $columns = [
                'p.id',
                'p.sku',
                'p.type',
                'p.model',
                'p.color',
                'p.cc',
                'p.image',
                'p.selling_price',
                'p.discount',
                'p.additional_discount',
                'p.ItemModelId',
                'pt.name',
                'pt.slug',
                'pt.excerpt'
            ];
            $products = DB::table('products as p')
                ->join('product_translations as pt', 'pt.product_id', '=', 'p.id')
                ->select($columns)
                ->where('p.status', '=', 'active')
                ->where('p.datetime', '<=', now()->toDateTimeString())
                ->where('pt.locale', '=', $locale)
                ->orderByDesc('p.datetime');

            if ($type === 'all') {
                $products = $products->limit($limit)->get();
            } else {
                $products = $products
                    ->where('type', '=', $type)
                    ->limit($limit)->get();
            }

            JsonResource::withoutWrapping();
            return ProductFeaturedApiResource::collection($products);
        } catch (Throwable $exception) {
            report($exception);
            return response()->json([
                'message' => Lang::get('crud.error')
            ], 404);
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
     * @param $type
     * @return AnonymousResourceCollection|JsonResponse
     */
    public function getPopularProducts(Request $request, $locale, $type)
    {
        try {
            $limit = $request->query('limit');

            $columns = [
                'p.id',
                'p.sku',
                'p.type',
                'p.model',
                'p.color',
                'p.cc',
                'p.image',
                'p.selling_price',
                'p.discount',
                'p.additional_discount',
                'p.ItemModelId',
                'pt.name',
                'pt.slug',
                'pt.excerpt'
            ];
            $products = DB::table('products as p')
                ->join('product_translations as pt', 'pt.product_id', '=', 'p.id')
                ->select($columns)
                ->where('p.status', '=', 'active')
                ->where('p.datetime', '<=', now()->toDateTimeString())
                ->where('pt.locale', '=', $locale)
                ->orderByDesc('p.sales_count');

            if ($type === 'all') {
                $products = $products->limit($limit)->get();
            } else {
                $products = $products
                    ->where('type', '=', $type)
                    ->limit($limit)->get();
            }

//            return response()->json($products, 200);
            JsonResource::withoutWrapping();
            return ProductFeaturedApiResource::collection($products);
        } catch (Throwable $exception) {
            report($exception);
            return response()->json([
                'message' => Lang::get('crud.error')
            ], 404);
        }
    }

    /**
     * @param Request $request
     * @param $locale
     * @param $type
     * @return JsonResponse|AnonymousResourceCollection
     */
    public function getMostViewedProducts(Request $request, $locale, $type)
    {
        try {
            $limit = $request->query('limit');

            $columns = [
                'p.id',
                'p.sku',
                'p.type',
                'p.model',
                'p.color',
                'p.cc',
                'p.image',
                'p.selling_price',
                'p.discount',
                'p.additional_discount',
                'p.ItemModelId',
                'pt.name',
                'pt.slug',
                'pt.excerpt'
            ];
            $products = DB::table('products as p')
                ->join('product_translations as pt', 'pt.product_id', '=', 'p.id')
                ->select($columns)
                ->where('p.status', '=', 'active')
                ->where('p.datetime', '<=', now()->toDateTimeString())
                ->where('pt.locale', '=', $locale)
                ->orderByDesc('p.views_count');

            if ($type === 'all') {
                $products = $products->limit($limit)->get();
            } else {
                $products = $products
                    ->where('type', '=', $type)
                    ->limit($limit)->get();
            }

//            return response()->json($products, 200);
            JsonResource::withoutWrapping();
            return ProductFeaturedApiResource::collection($products);
        } catch (Throwable $exception) {
            report($exception);
            return response()->json([
                'message' => Lang::get('crud.error')
            ], 404);
        }
    }

    /**
     * @param Request $request
     * @param $locale
     * @param $type
     * @return AnonymousResourceCollection|JsonResponse
     */
    public function getTopRatedProducts(Request $request, $locale, $type)
    {
        try {
            $limit = $request->query('limit');

            $columns = [
                'p.id',
                'p.sku',
                'p.type',
                'p.model',
                'p.color',
                'p.cc',
                'p.image',
                'p.selling_price',
                'p.discount',
                'p.additional_discount',
                'p.ItemModelId',
                'pt.name',
                'pt.slug',
                'pt.excerpt'
            ];
            $products = DB::table('products as p')
                ->join('product_translations as pt', 'pt.product_id', '=', 'p.id')
                ->select($columns)
                ->where('p.status', '=', 'active')
                ->where('p.datetime', '<=', now()->toDateTimeString())
                ->where('pt.locale', '=', $locale)
                ->orderByDesc('p.datetime');

            if ($type === 'all') {
                $products = $products->limit($limit)->get();
            } else {
                $products = $products
                    ->where('type', '=', $type)
                    ->limit($limit)->get();
            }

//            return response()->json($products, 200);
            JsonResource::withoutWrapping();
            return ProductFeaturedApiResource::collection($products);
        } catch (Throwable $exception) {
            report($exception);
            return response()->json([
                'message' => Lang::get('crud.error')
            ], 404);
        }
    }

    /**
     * @param Request $request
     * @param $locale
     * @return AnonymousResourceCollection|JsonResponse
     */
    public function getSpecialOfferProducts(Request $request, $locale)
    {
        try {
            $limit = $request->query('limit');

            $columns = [
                'p.id',
                'p.sku',
                'p.type',
                'p.model',
                'p.color',
                'p.cc',
                'p.image',
                'p.selling_price',
                'p.discount',
                'p.additional_discount',
                'p.ItemModelId',
                'pt.name',
                'pt.slug',
                'pt.excerpt'
            ];
            $products = DB::table('products as p')
                ->join('product_translations as pt', 'pt.product_id', '=', 'p.id')
                ->select($columns)
                ->where('p.status', '=', 'active')
                ->where('p.datetime', '<=', now()->toDateTimeString())
                ->where('p.has_special_offer', '=', 1)
                ->where('pt.locale', '=', $locale)
                ->orderByDesc('p.datetime')
                ->limit($limit)
                ->get();

            JsonResource::withoutWrapping();
            return ProductFeaturedApiResource::collection($products);
        } catch (Throwable $exception) {
            report($exception);
            return response()->json([
                'message' => Lang::get('crud.error')
            ], 404);
        }
    }

    /**
     * @param $locale
     * @param $product
     * @return JsonResponse
     */
    public function getProductSlider($locale, $product)
    {
        try {
            $slider = DB::table('product_sliders')
                ->where('status', '=', 'active')
                ->where('product_id', '=', $product)
                ->select('image')
                ->first();
            if ($slider) {
                $images = DB::table('product_slider_images as si')
                    ->join('product_slider_image_translations as sit', 'sit.product_slider_image_id', '=', 'si.id')
                    ->select('si.image', 'si.thumbnail', 'sit.title')
                    ->where('si.status', '=', 'active')
                    ->where('sit.locale', '=', $locale)
                    ->latest()
                    ->get();
                $slider->images = $images;
            }

            return response()->json($slider);
        } catch (Throwable $exception) {
            return response()->json(null);
        }
    }

    /**
     * Get minimum price of product.
     *
     * @return mixed
     */
    private function getMinPrice()
    {
        return DB::table('products')->min('selling_price');
    }

    /**
     * Get maximum price of product.
     *
     * @return mixed
     */
    private function getMaxPrice()
    {
        return DB::table('products')->max('selling_price');
    }

    /**
     * @param $type
     * @return int
     */
    private function getTypeCount($type)
    {
        return DB::table('products')
            ->where('type', '=', $type)
            ->whereNull('deleted_at')
            ->count();
    }

    /**
     * @param $type
     * @param $locale
     * @return \Illuminate\Support\Collection
     */
    private function getBrandCount($type, $locale)
    {
        $brands = DB::table('brands as b')
            ->join('brand_translations as bt', 'b.id', '=', 'bt.brand_id')
            ->select('b.code', 'bt.name')
            ->where('b.status', '=', 'active')
            ->where('bt.locale', '=', $locale)
            ->get();

        return collect($brands)->map(function ($brand) use ($type) {
            return [
                'name' => $brand->name,
                'slug' => $brand->code,
                'count' => DB::table('products')
                    ->where('BrandName', '=', $brand->code)
                    ->where('type', '=', $type)
                    ->where('status', '=', 'active')
                    ->whereNull('deleted_at')
                    ->count()
            ];
        });
    }

    /**
     * @param Request $request
     * @param $locale
     * @param $type
     * @return AnonymousResourceCollection|JsonResponse
     */
    public function recommendProductsForCustomer(Request $request, $locale)
    {       
        try {
            App::setLocale($locale);
            $customer_id = $request->get('customer_id');
            $limit = $request->get('limit');
            if(!!isset($customer_id)) {
                $category_ids = DB::table('orders as o')
                ->join('order_products as op', 'op.order_id', '=', 'o.id')
                ->join('product_category_product as pcp', 'pcp.product_id', '=', 'op.product_id')
                ->join('product_categories as pc', 'pc.id', '=', 'pcp.category_id')
                ->where('o.customer_id', '=', $customer_id)
                ->where('pc.status', '=', 'active')
                ->pluck('pcp.category_id');
            
                $category_ids = getUniqueArray($category_ids);

                // Get all recommend active categories ids
                $product_ids = DB::table('product_category_product')
                    ->whereIn('category_id', $category_ids)
                    ->pluck('product_id');
                $product_ids = getUniqueArray($product_ids);

                // Get all filters product ids
                $filter_ids = DB::table('product_filter_product as pfp')
                ->join('filters as f', 'f.id', '=', 'pfp.filter_id')
                ->where('f.status','=','active')
                ->whereIn('product_id', $product_ids)
                ->pluck('filter_id');
                $filter_ids = getUniqueArray($filter_ids);
                $filter_product_ids = DB::table('product_filter_product')
                ->whereIn('filter_id', $filter_ids)
                ->pluck('product_id');

                $filter_product_ids = getUniqueArray($filter_product_ids);
                $actualProductIds = array_merge($product_ids, $filter_product_ids);
            }
            
            $productsList = Product::query()->where('status','=', '1');

            if ( !!isset($actualProductIds) && count($actualProductIds) > 0 ) {
                $productsList = $productsList->whereIn('id', $actualProductIds);
            } 
            
            // Return 5 products for recommended
            $productsList = $productsList->inRandomOrder()->paginate($limit?$limit:'5');

            return ProductApiResource::collection($productsList);

        } catch (Throwable $exception) {
            report($exception);
            return response()->json([
                'message' => $exception->getMessage()
            ], 404);
        }
    }

    /**
     * @param $locale
     * @param $product_id
     * @return AnonymousResourceCollection
     */
    public function getRelatedProducts($locale, $id):AnonymousResourceCollection
    {
        App::setLocale($locale);

        $tags = Tag::with('products')
            ->whereHas('products', function (Builder $query) use ($id) {
                $query->where('taggable_id', '=', $id);
            })
            ->where('status', '=', 'active')
            ->get();

            $productIds = [];
            // Getting products ids from tags
            foreach ($tags as $tag) {
                $products = $tag->products;
                if($products) {
                    foreach ($products as $product) {
                        array_push($productIds, $product->id);
                    }
                }
            }

            // get unique ids
            $productIds = collect($productIds)->unique()->values()->toArray();

            $products = Product::with('translations')
                ->whereIn('id', $productIds)
                ->whereNotIn('id', [$id])
                ->where('status', '=', 1);

                $products = $products->inRandomOrder()->paginate(5);
                
           return ProductApiResource::collection($products);
    }

}

function getUniqueArray($list) {
   return collect($list)->unique()->values()->toArray();
}