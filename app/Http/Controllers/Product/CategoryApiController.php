<?php

namespace App\Http\Controllers\Product;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryTreeResource;
use App\Http\Resources\Product\CategorySingleApiResource;
use App\Models\Product\Category;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Throwable;

class CategoryApiController extends Controller
{
    /**
     * @param $locale
     * @return AnonymousResourceCollection
     */
    public function getAll($locale): AnonymousResourceCollection
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
     * @param $locale
     * @param $slug
     * @return CategorySingleApiResource
     */
    public function getSingleCategory($locale, $slug): CategorySingleApiResource
    {
        App::setLocale($locale);

        $category = Category::with('translations')
            ->whereTranslation('slug', $slug)
            ->firstOrFail();

        $ancestors = Category::with('translations')->ancestorsAndSelf($category)->pluck('id');
        $descendants = Category::with('translations')->descendantsAndSelf($category)->pluck('id');

        // get category ids
        $category_ids = collect($ancestors)->merge($descendants)->unique()->values()->toArray();
        $products = DB::table('product_category_product')
            ->whereIn('category_id', $category_ids)
            ->count('product_id');
        if ($category) {
            $category->products_count = $products;
        }

        return new CategorySingleApiResource($category);
    }

    /**
     * @param $locale
     * @param $category
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAncestorsDescendants($locale, $category): \Illuminate\Http\JsonResponse
    {
        App::setLocale($locale);

        $category_id = Category::with('translations')
            ->whereTranslation('slug', $category)
            ->first();

        $ancestors = Category::with('translations')->ancestorsAndSelf($category_id)->pluck('id');
        $descendants = Category::with('translations')->descendantsAndSelf($category_id)->pluck('id');
        $category_ids = collect($ancestors)->merge($descendants)->unique()->values()->toArray();

        return response()->json([
            'ancestors' => $ancestors,
            'descendants' => $descendants,
            'ancestors_count' => collect($ancestors)->count(),
            'descendants_count' => collect($descendants)->count(),
            'ids' => collect($ancestors)->merge($descendants)->unique()->values()->toArray(),
            'products' => DB::table('product_category_product')
                ->whereIn('category_id', $category_ids)
                ->pluck('product_id')
                ->toArray()
        ]);
    }
}
