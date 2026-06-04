<?php

namespace App\Http\Controllers\Order;
 
use Throwable;
use Carbon\Carbon;
use App\Models\Order\Order;
use Illuminate\Http\Request;
use App\Jobs\OrderProcessing;
use App\Models\Product\Product;
use App\Models\Marketing\Coupon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Jobs\OrderAssignedToDealer;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Lang;
use App\Jobs\OrderAssignedForCustomer;
use App\Http\Resources\Order\OrderResource;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OrderController extends Controller
{
    /**
     * @param Request $request
     * @return AnonymousResourceColl
     * ection
     * @throws AuthorizationException
     */
    public function index(Request $request):AnonymousResourceCollection
    {
        $this->authorize('viewAny order');

        $query = $request->query('query');
        $sortBy = $request->query('sortBy');
        $direction = $request->query('direction');
        $per_page = $request->query('per_page', 10);

        $orders = Order::with('customer', 'orderStatus', 'paymentStatus')->latest();
        if ($query) {

            $ordersList = $orders->where('order_no', 'like', '%' . $query . '%')
                        ->orWhere('delivery_mobile', 'like', '%' . $query . '%')
                        ->orWhere('delivery_email', 'like', '%' . $query . '%');
            if(!($ordersList->count()>0)) {
                $orders = Order::with('customer', 'orderStatus', 'paymentStatus')->latest()
                ->whereHas('customer', function ($queryStr) use ($query){
                    $queryStr->where('name', 'like', '%'.$query.'%')
                    ->orWhere('email', 'like', '%'.$query.'%')
                    ->orWhere('mobile', 'like', '%'.$query.'%');
                });
            } else {
                $orders = $ordersList;
            }  
        }

        if ($sortBy) {
            $orders = $orders->orderBy($sortBy, $direction);
        }
        if ($per_page === '-1') {
            $results = $orders->get();
            $orders = new LengthAwarePaginator($results, $results->count(), -1);
        } else {
            $orders = $orders->paginate($per_page);
        }
        return OrderResource::collection($orders);
    }

    /**
     * @param Order $order
     * @return JsonResponse
     */
    public function show(Order $order): JsonResponse
    {
        try {
            $order->load('customer', 'orderStatus', 'paymentStatus', 'products', 'products.product', 'processes');

            return response()->json([
                'data' => $order
            ]);
        } catch (Throwable $exception) {
            report($exception);
            return response()->json([
                'message' => $exception->getMessage()
            ], 404);
        }
    }

    /**
     * @param Request $request
     * @param Order $order
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function updateStatus(Request $request, Order $order): JsonResponse
    {
        $this->authorize('update order');

        DB::beginTransaction();
        try {
            $order_status = $request->get('order_status');
            $payment_status = $request->get('payment_status');
            $updatedOrderStatus = false;
            $updatedPaymentStatus = false;
            // check if these are changed
            if ($order_status !== $order->order_status) {
                $updatedOrderStatus = true;
            }
            if ($payment_status !== $order->payment_status) {
                $updatedPaymentStatus = true;
            }

            $order->update([
                'order_status' => $order_status,
                'payment_status' => $payment_status,
            ]);
            DB::commit();

            return response()->json([
                'message' => Lang::get('crud.update'),
                'updatedOrderStatus' => $updatedOrderStatus,
                'updatedPaymentStatus' => $updatedPaymentStatus,
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
     * @param Request $request
     * @param Order $order
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function updateProcess(Request $request, Order $order): JsonResponse
    {
        $this->authorize('update order');

        $request->validate([
            'order_status' => 'required',
        ]);
        DB::beginTransaction();
        try {
            $request->merge(['user_id' => auth()->id()]);
            $order_status = $request->get('order_status');
            $payment_status = $request->get('payment_status');
            $updatedOrderStatus = false;
            // check if these are changed
            if ($order_status !== $order->order_status) {
                $updatedOrderStatus = true;
            }

            $order->update([
                'order_status' => $order_status,
                'payment_status' => $payment_status,
            ]);
            // insert into order processes table
            $order->processes()->create($request->all());

            DB::commit();

            return response()->json([
                'message' => Lang::get('crud.update'),
                'updatedOrderStatus' => $updatedOrderStatus,
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
     * @param Order $order
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function destroy(Order $order): JsonResponse
    {
        $this->authorize('delete order');

        // begin database transaction
        DB::beginTransaction();
        try {
            $order->delete();

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

    public function importAddress()
    {
        DB::beginTransaction();
        try {
            $collections = DB::table('orders')
                ->select('id', 'static_address')
                ->get()
                ->map(function ($item) {
                    $static_address = json_decode($item->static_address);
                    $billing = collect($static_address->billing_address)->flatten(1)->values();
                    $shipping = collect($static_address->delivery_address)->flatten(1)->values();
                    $item->address = collect([
                        'billing' => [
                            'name' => (isset($billing[7]) === true ? $billing[7] : ''),
                            'email' => '',
                            'mobile' => (isset($billing[6]) === true ? $billing[6] : ''),
                            'house' => (isset($billing[2]) === true ? $billing[2] : ''),
                            'street' => (isset($billing[3]) === true ? $billing[3] : ''),
                            'zip' => (isset($billing[5]) === true ? $billing[5] : ''),
                            'area' => (isset($billing[0]) === true ? $billing[0] : ''),
                            'district' => (isset($billing[1]) === true ? $billing[1] : ''),
                            'division' => '',
                            'country' => 'Bangladesh'
                        ],
                        'shipping' => [
                            'name' => (isset($shipping[7]) === true ? $shipping[7] : ''),
                            'email' => '',
                            'mobile' => (isset($shipping[6]) === true ? $shipping[6] : ''),
                            'house' => (isset($shipping[2]) === true ? $shipping[2] : ''),
                            'street' => (isset($shipping[3]) === true ? $shipping[3] : ''),
                            'zip' => (isset($shipping[5]) === true ? $shipping[5] : ''),
                            'area' => (isset($shipping[0]) === true ? $shipping[0] : ''),
                            'district' => (isset($shipping[1]) === true ? $shipping[1] : ''),
                            'division' => '',
                            'country' => 'Bangladesh'
                        ]
                    ]);
                    return $item;
                });
            collect($collections)->each(function ($item) {
                DB::table('orders')
                    ->where('id', '=', $item->id)
                    ->update(
                        ['static_address' => $item->address]
                    );
            });
            DB::commit();

            return response()->json([
                'message' => Lang::get('crud.update'),
                'collections' => $collections,
            ]);
        } catch (Throwable $exception) {
            DB::rollBack();
            // return failed message
            return response()->json([
                'message' => $exception->getMessage()
            ], 400);
        }
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'payment_method' => 'required',
            'static_address' => 'required',
            'items' => 'required',
        ]);

        DB::beginTransaction();
        try {
            $order_number = $this->generateOrderNumber();
            $invoice_number = $this->generateInvoiceNumber();
            $customer_id = $request->get('customerId');
            $payment_method = $request->get('payment_method');
            $area_id = $request->get('areaId');
            $platform = $request->get('platform', 'web');
            $coupon = $request->get('coupon');
            $static_address = $request->input('static_address');
            $shipping_address = $static_address['shipping'];

            $order_id = DB::table('orders')->insertGetId([
                'customer_id' => $customer_id,
                'order_no' => $order_number,
                'invoice_no' => $invoice_number,
                'comment' => $request->get('comment'),
                'platform' => $platform,
                'static_address' => json_encode($static_address),
                'order_status' => 'pending',
                'payment_method' => $payment_method,
                'payment_status' => 'pending',
                'coupon' => $coupon,
                'delivery_mobile' => $shipping_address['mobile']?:null,
                'delivery_email' => $shipping_address['email']?:null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $cart_items = collect($request->input('items'));
            $products = collect();
            // get total amount
            $total_amount = 0;

            // get special discount
            $special_discount = 0;
            // get coupon discount
            $coupon_discount = 0;
            // get total quantity
            $total_quantity = 0;
            
            foreach ($cart_items as $item) {
                // update total quantity
                $total_quantity += $item['quantity'];
                // product price
                $selling_price = 0;
                // purchased price
                $purchased_price = 0;

                // Added regular price when had special price
                $regular_price = 0;

                // calculate total amount
                $product =  DB::table('products')
                    ->where('id', '=', $item['productId'])
                    ->first();

                if ($product) {
                    $regular_price = $product->price;
                    $price = $product->price;
                    // check if special price end date passed
                    $special_start_date = Carbon::parse($product->special_start_date);
                    $special_end_date = Carbon::parse($product->special_end_date);
                    if (now()->betweenIncluded($special_start_date, $special_end_date)) {
                        $price = $product->special_price;
                        $special_discount += ($product->price - $product->special_price)* $item['quantity'];
                    }
                    $selling_price = $price;
                    $purchased_price = $product->purchased_price;
                    // update total
                    $total_amount += $price * $item['quantity'];
                }

                // update sales count
                DB::table('products')
                    ->where('id', '=', $item['productId'])
                    ->increment('sales_count', $item['quantity']);

               DB::table('products')
                ->where('id', '=', $item['productId'])
                ->decrement('quantity', $item['quantity']);

                $products->push([
                    'order_id' => $order_id,
                    'product_id' => $item['productId'],
                    'quantity' => $item['quantity'],
                    'purchased_price' => $purchased_price,
                    'selling_price' => $selling_price,
                    'regular_unit_price' => $regular_price,
                    'selling_unit_price' => $selling_price,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // calculate coupon
            if ($request->filled('coupon')) {
                $discount = $this->checkCoupon($coupon, $customer_id, $total_amount, $platform, $area_id);
                if ($discount > 0) {
                    // update product selling price
                    $products = $products->map(function ($item) use ($discount, $total_amount) {
                        $discounted_price = ($discount * $item['selling_price']) / $total_amount;
                        $item['selling_price'] -= $discounted_price;

                        return $item;
                    });
                    $total_amount -= $discount;
                    $coupon_discount = $discount;
                    // update coupon history
                    DB::table('coupon_histories')->insert([
                        'order_id' => $order_id,
                        'customer_id' => $customer_id,
                        'code' => $coupon,
                        'discount' => $discount,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }
            }

            // get shipping cost
            $cost_info = DB::table('shipping_costs')
            ->where('area_id', '=', $area_id)
            ->first();

            $shipping_cost = $cost_info->cost?:0;
            $shipping_cart_amount = $cost_info->cart_amount?:0;
    
            if( $total_amount && $shipping_cart_amount && $total_amount >= (float)$shipping_cart_amount ) {
                $shipping_cost = $cost_info->special_delivery_cost?:0;
            }

            // update total amount
            $total_amount += $shipping_cost;

            // update total amount, quantity
            DB::table('orders')
                ->where('id', '=', $order_id)
                ->update([
                    'total_amount' => $total_amount,
                    'special_discount' => $special_discount,
                    'coupon_discount' => $coupon_discount,
                    'total_quantity' => $total_quantity,
                    'shipping_cost' => $shipping_cost,
                ]);

            // insert products
            DB::table('order_products')->insert($products->toArray());

            DB::commit();

            // send order sms
//            $data = array();
//            $data['order_no'] = $order_number;
//            $data['mobile'] = Auth::user()->mobile;
//            $data['id'] = $order_id;
//
//            OrderCreated::dispatch($data);

            return response()->json([
                'message' => Lang::get('crud.create'),
                'orderId' => $order_id,
            ], 201);

        } catch (Throwable $exception) {
            report($exception);
            DB::rollBack();
            return response()->json([
                'message' => Lang::get('crud.error'),
                'error' => $exception->getMessage()
            ], 400);
        }
    }

    /**
     * @return string
     */
    private function generateOrderNumber(): string
    {
        $today = date("Ymd");
        $rand = strtoupper(substr(uniqid(sha1(time())), 0, 4));

        return "TGMG_{$today}{$rand}";
    }

    /**
     * @return string
     */
    private function generateInvoiceNumber(): string
    {
        $today = date("Ymd");
        $rand = strtoupper(substr(uniqid(sha1(time())), 0, 4));

        return "TGMG_INV_{$today}{$rand}";
    }

    /**
     * @param $code
     * @param $customer_id
     * @param $cart_amount
     * @param $platform
     * @param $area_id
     * @return mixed
     */
    private function checkCoupon($code, $customer_id, $cart_amount, $platform, $area_id): mixed
    {
        // find coupon
        $coupon = Coupon::query()
            ->where('code', '=', $code)
            ->where('status', '=', 'active')
            ->first();
        // if exist
        if ($coupon) {
            // get coupon history
            $total_uses = DB::table('coupon_histories')
                ->where('code', '=', $code)
                ->count();
            $customer_uses = DB::table('coupon_histories')
                ->where('code', '=', $code)
                ->where('customer_id', '=', $customer_id)
                ->count();

            // get area ids
            $area_ids = $coupon->area;
            // get platforms
            $platforms = $coupon->platforms;

            // if cart amount less then
            if ($cart_amount < $coupon->total_amount) {
                return 0;
            } elseif (!now()->betweenIncluded($coupon->start_date, $coupon->end_date)) {
                return 0;
            } elseif ($total_uses > $coupon->uses_per_coupon) {
                return 0;
            } elseif ($customer_uses > $coupon->uses_per_customer) {
                return 0;
            } elseif (!empty($area_ids) && !in_array($area_id, $area_ids)) {
                return 0;
            } elseif (!in_array($platform, $platforms)) {
                return 0;
            } else {
                // calculate discount if coupon type is percentage
                if ($coupon->type === 'percentage') {
                    $discount = ceil(($coupon->discount * $cart_amount) / 100);
                } else {
                    // fixed coupon type
                    $discount = $coupon->discount;
                }

                return (float)$discount;
            }
        } else {
            return 0;
        }
    }

}
