<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Library\SslCommerzNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SslCommerzPaymentController extends Controller
{

    /**
     * @param Request $request
     */
    public function success(Request $request)
    {
        echo "Transaction is Successful, Redirecting...";

        $order_number = $request->input('tran_id');
        $amount = $request->input('amount');
        $currency = $request->input('currency');

        $sslc = new SslCommerzNotification();

        #Check order status in order table against the transaction id or order id.
        $order_details = DB::table('orders')
            ->where('order_no', '=', $order_number)
            ->select('id', 'order_no', 'payment_status', 'order_status', 'total_amount', 'platform')
            ->first();

        if ($order_details->payment_status === 'pending') {
            $validation = $sslc->orderValidate($order_number, $amount, $currency, $request->all());

            if ($validation == TRUE) {
                /*
                That means IPN did not work or IPN URL was not set in your merchant panel. Here you need to update order status
                in order table as Processing or Complete.
                Here you can also sent sms or email for successful transaction to customer
                */
                DB::table('orders')
                    ->where('order_no', '=', $order_number)
                    ->update([
                        'order_status' => 'processing',
                        'payment_status' => 'paid',
                    ]);

                // redirect to frontend
                $url = config('helper.url') . '/user/orders/' . $order_details->id;
                // check for app
                if ($order_details->platform !== 'web') {
                    // $url = $this->generateFirebaseLink($url);
                    $url = 'https://togumogu.page.link/order-success/' . $order_details->id;
                }
                header('Location:' . $url);
                exit();
            } else {
                /*
                That means IPN did not work or IPN URL was not set in your merchant panel and Transaction validation failed.
                Here you need to update order status as Failed in order table.
                */
                DB::table('orders')
                    ->where('order_no', '=', $order_number)
                    ->update([
                        'order_status' => 'pending',
                        'payment_status' => 'failed',
                    ]);

                // redirect to frontend
                $url = config('helper.url') . '/checkout/fail';
                // check for app
                if ($order_details->platform !== 'web') {
                    $url = 'https://togumogu.page.link/order-fail';
                }
                header('Location:' . $url);
                exit();
            }
        } else if ($order_details->payment_status === 'paid') {
            /*
             That means through IPN Order status already updated. Now you can just show the customer that transaction is completed. No need to update database.
             */
            // redirect to frontend
            $url = config('helper.url') . '/user/orders/' . $order_details->id;
            // check for app
            if ($order_details->platform !== 'web') {
                $url = $this->generateFirebaseLink($url);
            }
            header('Location:' . $url);
            exit();
        } else {
            #That means something wrong happened. You can redirect customer to your product page.
            // redirect to frontend
            $url = config('helper.url') . '/checkout/fail';
            // check for app
            if ($order_details->platform !== 'web') {
                $url = 'https://togumogu.page.link/order-fail';
            }
            header('Location:' . $url);
            exit();
        }
    }

    /**
     * @param Request $request
     */
    public function fail(Request $request)
    {
        $order_number = $request->input('tran_id');

        $order_details = DB::table('orders')
            ->where('order_no', '=', $order_number)
            ->select('id', 'order_no', 'payment_status', 'order_status', 'total_amount', 'platform')
            ->first();

        if ($order_details->payment_status === 'pending') {
            DB::table('orders')
                ->where('order_no', '=', $order_number)
                ->update([
                    'order_status' => 'pending',
                    'payment_status' => 'failed',
                ]);

            // redirect to frontend
            $url = config('helper.url') . '/checkout/fail';
            // check for app
            if ($order_details->platform !== 'web') {
                $url = 'https://togumogu.page.link/order-fail';
            }
            header('Location:' . $url);
            exit();
        } else if ($order_details->payment_status === 'paid') {
            // redirect to frontend
            $url = config('helper.url') . '/user/orders/' . $order_details->id;
            // check for app
            if ($order_details->platform !== 'web') {
                $url = $this->generateFirebaseLink($url);
            }
            header('Location:' . $url);
            exit();
        } else {
            // redirect to frontend
            $url = config('helper.url') . '/checkout/fail';
            // check for app
            if ($order_details->platform !== 'web') {
                $url = 'https://togumogu.page.link/order-fail';
            }
            header('Location:' . $url);
            exit();
        }
    }

    /**
     * @param Request $request
     */
    public function cancel(Request $request)
    {
        $order_number = $request->input('tran_id');

        $order_details = DB::table('orders')
            ->where('order_no', '=', $order_number)
            ->select('id', 'order_no', 'payment_status', 'order_status', 'total_amount', 'platform')
            ->first();

        if ($order_details->payment_status === 'pending') {
            DB::table('orders')
                ->where('order_no', '=', $order_number)
                ->update([
                    'order_status' => 'pending',
                    'payment_status' => 'canceled',
                ]);

            // redirect to frontend
            $url = config('helper.url') . '/checkout/cancel';
            // check for app
            if ($order_details->platform !== 'web') {
                $url = 'https://togumogu.page.link/order-cancel';
            }
            header('Location:' . $url);
            exit();
        } else if ($order_details->payment_status === 'paid') {
            // redirect to frontend
            $url = config('helper.url') . '/user/orders/' . $order_details->id;
            // check for app
            if ($order_details->platform !== 'web') {
                $url = $this->generateFirebaseLink($url);
            }
            header('Location:' . $url);
            exit();
        } else {
            // redirect to frontend
            $url = config('helper.url') . '/checkout/fail';
            // check for app
            if ($order_details->platform !== 'web') {
                $url = 'https://togumogu.page.link/order-fail';
            }
            header('Location:' . $url);
            exit();
        }
    }

    /**
     * @param Request $request
     */
    public function ipn(Request $request)
    {
        #Received all the payment information from the gateway
        if ($request->input('tran_id')) #Check transaction id is posted or not.
        {

            $order_number = $request->input('tran_id');

            #Check order status in order table against the transaction id or order id.
            $order_details = DB::table('orders')
                ->where('order_no', '=', $order_number)
                ->select('id', 'order_no', 'payment_status', 'order_status', 'total_amount', 'platform')
                ->first();

            if ($order_details->payment_status === 'pending') {
                $sslc = new SslCommerzNotification();
                $validation = $sslc->orderValidate($order_number, $order_details->total_amount, 'BDT', $request->all());
                if ($validation == TRUE) {
                    /*
                    That means IPN worked. Here you need to update order status
                    in order table as Processing or Complete.
                    Here you can also sent sms or email for successful transaction to customer
                    */
                    DB::table('orders')
                        ->where('order_no', '=', $order_number)
                        ->update([
                            'order_status' => 'processing',
                            'payment_status' => 'paid',
                        ]);

                    // redirect to frontend
                    $url = config('helper.url') . '/user/orders/' . $order_details->id;
                    // check for app
                    if ($order_details->platform !== 'web') {
                        $url = $this->generateFirebaseLink($url);
                    }
                    header('Location:' . $url);
                    exit();
                } else {
                    /*
                    That means IPN worked, but Transaction validation failed.
                    Here you need to update order status as Failed in order table.
                    */
                    DB::table('orders')
                        ->where('order_no', '=', $order_number)
                        ->update([
                            'order_status' => 'pending',
                            'payment_status' => 'failed',
                        ]);

                    echo "validation Fail";
                }

            } else if ($order_details->payment_status == 'paid') {

                #That means Order status already updated. No need to update database.

                // redirect to frontend
                $url = config('helper.url') . '/user/orders/' . $order_details->id;
                // check for app
                if ($order_details->platform !== 'web') {
                    $url = $this->generateFirebaseLink($url);
                }
                header('Location:' . $url);
                exit();
            } else {
                #That means something wrong happened. You can redirect customer to your product page.

                echo "Invalid Transaction";
            }
        } else {
            echo "Invalid Data";
        }
    }

    /**
     * Generate dynamic link to redirect to mobile app.
     *
     * @param string $url
     * @return mixed
     */
    private function generateFirebaseLink(string $url): mixed
    {
        $response = Http::asJson()->post('https://firebasedynamiclinks.googleapis.com/v1/shortLinks?key=' . config('helper.firebase_api_key'), [
            'dynamicLinkInfo' => [
                'domainUriPrefix' => 'https://togumogu.page.link',
                'link' => $url,
                'androidInfo' => [
                    'androidPackageName' => 'com.togumogu',
                ]
            ]
        ]);
        if ($response->successful()) {
            $body = $response->json();

            return $body['shortLink'];
        }

        return $url;
    }

}
