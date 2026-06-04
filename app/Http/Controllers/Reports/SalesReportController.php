<?php

namespace App\Http\Controllers\Reports;

use App\Http\Resources\Reports\ReportsApiResource;
use App\Http\Resources\Product\ProductResource;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Throwable;

class SalesReportController extends Controller
{
    /**
     * @param Request $request
     * @return ReportCollection
     * @throws AuthorizationException
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny sales_report');

        $per_page = $request->query('per_page', 10);
        $direction = $request->query('direction');
        $sortBy = $request->query('sortBy');
        $query = $request->query('query');
        $groupType = $request->query('groupType');
        $orderStatus = $request->query('orderStatus');
        $platform = $request->query('platform');
        $fromDate = $request->query('fromDate');
        $toDate = $request->query('toDate');

         // Return products data
        if ($groupType ==='products') {
            
            $data = DB::table('products as p')
            ->join('product_translations as pt', 'pt.product_id', '=', 'p.id')
            ->join('order_products as op', 'op.product_id', '=', 'p.id')
            ->join('orders as o', 'o.id', '=', 'op.order_id')
            ->select('p.id', 'pt.name',
                DB::raw('SUM(op.quantity) as product_sale_quantity'),
                DB::raw('SUM(op.selling_price) as total_regular_price'),
                DB::raw('SUM(op.purchased_price) as total_purchase_price'),
                DB::raw('SUM(o.special_discount) + SUM(o.coupon_discount) as total_discount_amount'),
                DB::raw('SUM(o.special_discount) as special_discount_amount'),
                DB::raw('SUM(o.coupon_discount) as coupon_discount_amount'),
                DB::raw('SUM(op.selling_price) - (SUM(o.special_discount) + SUM(o.coupon_discount)) as total_sale_price'),
                );


            if (isset($query)) {
                $data = $data->where('pt.name', 'like', '%' .$query. '%');
            }
    
            if (isset($orderStatus)) {
                $data = $data->whereIn('o.order_status', explode(",",$orderStatus));
            };
    
            if (isset($platform)) {
                $data = $data->whereIn('o.platform', explode(",",$platform));
            };
            
            if (isset($toDate) && isset($fromDate)) {
                $data = $data->whereBetween('op.created_at', [strval($fromDate), strval($toDate)]);
            };

            $data = $data->groupBy('op.product_id', 'pt.name');

            if (isset($direction)) {
                $data = $data->orderBy($sortBy, $direction);
            }
            
            if ($per_page && $per_page !== '-1') {
                $data = $data->paginate($per_page);
            }                

            return response()->json($data, 200);
        }

        // Return summary data
        if ($groupType ==='summary') {
            return response()->json([
                'data' => [],
                'message' => 'Successfully found!'
            ], 200);
         }

        // Return categories data
        if ($groupType ==='categories') {

            $data = DB::table('product_categories as pc')
            ->join('product_category_translations as pct', 'pct.category_id', '=', 'pc.id')
            ->join('product_category_product as pcp', 'pcp.category_id', '=', 'pc.id')
            ->join('order_products as op', 'op.product_id', '=', 'pcp.product_id')
            ->join('orders as o', 'o.id', '=', 'op.order_id')
            ->select('pc.id', 'pct.name',
                DB::raw('SUM(op.quantity) as sales_quantity'),
                DB::raw('SUM(op.selling_price) as total_price'),
                DB::raw('SUM(op.selling_price) as total_regular_price'),
                // DB::raw('SUM(op.purchased_price) as total_purchase_price'),
                // DB::raw('SUM(o.special_discount) + SUM(o.coupon_discount) as total_discount_amount'),
                // DB::raw('SUM(o.special_discount) as special_discount_amount'),
                // DB::raw('SUM(o.coupon_discount) as coupon_discount_amount'),
                // DB::raw('SUM(op.selling_price) - (SUM(o.special_discount) + SUM(o.coupon_discount)) as total_sale_price'),
                );


            if (isset($query)) {
                $data = $data->where('pct.name', 'like', '%' .$query. '%');
            }
    
            if (isset($orderStatus)) {
                $data = $data->whereIn('o.order_status', explode(",",$orderStatus));
            };
    
            if (isset($platform)) {
                $data = $data->whereIn('o.platform', explode(",",$platform));
            };
            
            if (isset($toDate) && isset($fromDate)) {
                $data = $data->whereBetween('op.created_at', [strval($fromDate), strval($toDate)]);
            };

            $data = $data->groupBy('pc.id', 'pct.name');

            if (isset($direction)) {
                $data = $data->orderBy($sortBy, $direction);
            }
            
            if ($per_page && $per_page !== '-1') {
                $data = $data->paginate($per_page);
            }                

            return response()->json($data, 200);



            // return response()->json([
            //     'data' => [],
            //     'message' => 'Successfully found!'
            // ], 200);
         }

    }


    /**
     * @param Request $request
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function bestSelling(Request $request): JsonResponse
    {
        $this->authorize('viewAny sales_report');

        $fromDate = $request->query('fromDate');
        $toDate = $request->query('toDate');
        $groupType = $request->query('groupType');
        $data = [];

        // 10 best selling products
        if($groupType === 'products') {
            $data = DB::table('order_products as T1')
            ->join('products as T2', 'T2.id', '=', 'T1.product_id')
            ->join('product_translations as T3', 'T3.product_id', '=', 'T2.id')
            ->select('T3.product_id', 'T3.name', DB::raw('SUM(T1.quantity) as total_quantity'), DB::raw('SUM(T1.selling_price) as total_price'));

            if (isset($toDate) && isset($fromDate)) {
                $data = $data->whereBetween('T1.created_at', [strval($fromDate), strval($toDate)]);
            };

            $data= $data->groupBy('T3.name', 'T3.product_id')
            ->orderBy('total_quantity', 'desc')
            ->limit(10)
            ->get();

        }

        // 10 best selling categories
        if($groupType === 'category') {
            $data = DB::table('product_categories as pc')
            ->join('product_category_product as pcp', 'pcp.category_id', '=', 'pc.id')
            ->join('product_category_translations as pct', 'pct.category_id', '=', 'pc.id')
            ->join('order_products as op', 'op.product_id', '=', 'pcp.product_id')
            ->select('pc.id', 'pct.name', DB::raw('SUM(op.quantity) as total_quantity'), DB::raw('SUM(op.selling_price) as total_price'));
            
            if (isset($toDate) && isset($fromDate)) {
                $data = $data->whereBetween('op.created_at', [strval($fromDate), strval($toDate)]);
            };

            $data = $data->groupBy('pct.name', 'pc.id')
            ->orderBy('total_quantity', 'desc')
            ->limit(10)
            ->get();
        }

        return response()->json([
            'data' => $data,
            'message' => 'Successfully found!'
        ], 200);

    }
}
