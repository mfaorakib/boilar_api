<?php

namespace App\Http\Controllers;
use App\Http\Resources\Product\ProductApiResource;
use App\Models\Product\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;

class SearchController extends Controller
{
    /**
     * @param Request $request
     * @param $locale
     * @return JsonResponse
     */
    public function search(Request $request, $locale): JsonResponse
    {
        App::setLocale($locale);
        $type = $request->query('type', 'product');
        $query = $request->query('query');
        $limit = $request->query('limit', 20);

        $products = collect([]);

        switch ($type) {
            case 'product':
                $products = $this->getProducts($query, $limit);
                break;
        }

        return response()->json([
            'products' => ProductApiResource::collection($products),
        ]);
    }

    /**
     * @param $query
     * @param $limit
     * @return mixed
     */
    private function getProducts($query, $limit): mixed
    {
        return Product::with(['translations'])
            ->where('status', '=', 1)
            ->where('approved_status', '=', 'approved')
            ->where('datetime', '<=', now()->toDateTimeString())
            ->whereTranslationLike('name', '%' . $query . '%')
            ->orWhereTranslationLike('excerpt', '%' . $query . '%')
            ->limit($limit)
            ->get();
    }

}
