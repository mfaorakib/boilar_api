<?php

namespace App\Http\Controllers\Order;

use Carbon\Carbon;
use App\Models\Marketing\Coupon;
use App\Models\Product\Product;
use App\Http\Controllers\Controller;
use App\Library\SslCommerzNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Throwable;

class OrderAppController extends Controller
{
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
            $customer_id = Auth::id();
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
                'send_as_gift' => $request->get('send_as_gift', 'no'),
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
                    ->where('id', '=', $item['product_id'])
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
                    ->where('id', '=', $item['product_id'])
                    ->increment('sales_count', $item['quantity']);

                $products->push([
                    'order_id' => $order_id,
                    'product_id' => $item['product_id'],
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
    
            if( $total_amount && $shipping_cart_amount && (float)$total_amount >= (float)$shipping_cart_amount ) {
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

            if ($payment_method !== 'cod') {
                $billing = $request->input('static_address')['billing'];
                $shipping = $request->input('static_address')['shipping'];
                $res = $this->makePayment($total_amount, $order_number, $billing, $shipping);
                return response()->json([
                    'message' => Lang::get('crud.create'),
                    'orderId' => $order_id,
                    'paymentMethod' => $payment_method,
                    'sslMethod' => json_decode($res),
                ], 201);
            } else {
                return response()->json([
                    'message' => Lang::get('crud.create'),
                    'orderId' => $order_id,
                    'paymentMethod' => 'cod',
                ], 201);
            }
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
     * @param $total_amount
     * @param $order_no
     * @param $billing
     * @param $shipping
     * @return mixed
     */
    private function makePayment($total_amount, $order_no, $billing, $shipping): mixed
    {
        try {
            $customer = Auth::user();

            $data = array();
            $data['total_amount'] = "{$total_amount}";
            $data['currency'] = 'BDT';
            $data['tran_id'] = $order_no;

            # CUSTOMER INFORMATION
            if(!$billing['email']){
                $billing_email = $customer->email?:$customer->shipping_email?:"orders.spark@gmail.com";
            }
           
            $data['cus_name'] = $billing['name'];
            $data['cus_email'] = $billing['email']?:$billing_email;
            $data['cus_add1'] = $billing['address_line'];
            $data['cus_add2'] = "";
            $data['cus_city'] = $billing['area'];
            $data['cus_state'] = $billing['district'];
            $data['cus_postcode'] = "{$billing['zip']}";
            $data['cus_country'] = "Bangladesh";
            $data['cus_phone'] = $billing['mobile'];
            $data['cus_fax'] = "";

            # SHIPMENT INFORMATION
            $data['ship_name'] = $shipping['name'];
            $data['ship_add1'] = $shipping['address_line'];
            $data['ship_add2'] = "";
            $data['ship_city'] = $shipping['area'];
            $data['ship_state'] = $shipping['district'];
            $data['ship_postcode'] = "{$shipping['zip']}";
            $data['ship_phone'] = $shipping['mobile'];
            $data['ship_country'] = "Bangladesh";

            $data['shipping_method'] = "NO";
            $data['product_name'] = "Book";
            $data['product_category'] = "Book";
            $data['product_profile'] = "physical-goods";

            $sslc = new SslCommerzNotification();

            return $sslc->makePayment($data);
        } catch (Throwable $exception) {
            report($exception);
            return response()->json([
                'message' => $exception->getMessage()
            ], 400);
        }
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
}
