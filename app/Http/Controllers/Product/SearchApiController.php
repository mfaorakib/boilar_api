<?php

namespace App\Http\Controllers\Product;

use App\Models\Product\Product;
use App\Http\Controllers\Controller;
use App\Http\Resources\Product\ProductFeaturedApiResource;
use App\Http\Resources\Product\ProductSingleApiResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Throwable;

class SearchApiController extends Controller
{

    public function getProductsBySearch(Request $request, $locale)
    {
        try {
            $query = $request->query('query');

            $columns = [
                'p.id',
                'p.sku',
                'p.model',
                'p.color',
                'p.type',
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


            if (isset($query)) {
                $products = DB::table('products as p')
                    ->join('product_translations as pt', 'pt.product_id', '=', 'p.id')
                    ->select($columns)
                    ->where('p.status', '=', 'active')
                    ->where('p.datetime', '<=', now()->toDateTimeString())
                    ->where('pt.locale', '=', $locale)
                    ->where('pt.name', 'like', '%' . $query . '%')
                    ->orderByDesc('p.datetime')
                    ->get();
                return response()->json([
                    'products' => ProductFeaturedApiResource::collection($products),
                    'categories' => collect([])
                ]);
            }
            return response()->json([
                'products' => collect([]),
                'categories' => collect([])
            ]);

        } catch (Throwable $exception) {
            report($exception);
            return response()->json([
                'message' => Lang::get('crud.error')
            ], 404);
        }
    }

    public function getFeaturedProducts(Request $request, $locale, $type)
    {
        try {
            $limit = $request->query('limit');

            $columns = [
                'p.id',
                'p.sku',
                'p.model',
                'p.color',
                'p.cc',
                'p.image',
                'p.selling_price',
                'p.discount',
                'p.additional_discount',
                'pt.name',
                'pt.slug',
                'pt.excerpt'
            ];
            $products = DB::table('products as p')
                ->join('product_translations as pt', 'pt.product_id', '=', 'p.id')
                ->select($columns)
                ->where('p.status', '=', 'active')
                ->where('p.datetime', '<=', now()->toDateTimeString())
                ->where('p.is_featured', '=', 1)
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

}
