<?php

namespace App\Http\Controllers\Shipping;

use App\Http\Controllers\Controller;
use App\Models\Order\Order;
use Illuminate\Http\Client\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class ShippingPathaoController extends Controller
{

    /**
     * @return mixed
     */
    public function getAccessToken(): mixed
    {
        $endpoint = config('services.pathao.endpoint') . '/aladdin/api/v1/issue-token';
        $client_id = config('services.pathao.client_id');
        $client_secret = config('services.pathao.client_secret');
        $username = config('services.pathao.username');
        $password = config('services.pathao.password');

        $response = Http::post($endpoint, [
            'client_id' => $client_id,
            'client_secret' => $client_secret,
            'username' => $username,
            'password' => $password,
            'grant_type' => 'password'
        ]);

        return $this->getTokenFromResponse($response);
    }

    /**
     * @return mixed
     */
    public function refreshAccessToken(): mixed
    {
        $code = 'pathao';
        $endpoint = config('services.pathao.endpoint') . '/aladdin/api/v1/issue-token';
        $client_id = config('services.pathao.client_id');
        $client_secret = config('services.pathao.client_secret');
        $refresh_token = Cache::get($code . '_refresh_token');

        $response = Http::post($endpoint, [
            'client_id' => $client_id,
            'client_secret' => $client_secret,
            'refresh_token' => $refresh_token,
            'grant_type' => 'refresh_token'
        ]);

        return $this->getTokenFromResponse($response);
    }

    /**
     * @return JsonResponse
     */
    public function getAvailableCities(): JsonResponse
    {
        $endpoint = config('services.pathao.endpoint') . '/aladdin/api/v1/countries/1/city-list';

        return $this->getCommonData($endpoint);
    }

    /**
     * @param $city_id
     * @return JsonResponse
     */
    public function getAvailableZones($city_id): JsonResponse
    {
        $endpoint = config('services.pathao.endpoint') . '/aladdin/api/v1/cities/' . $city_id . '/zone-list';

        return $this->getCommonData($endpoint);
    }

    /**
     * @param $zone_id
     * @return JsonResponse
     */
    public function getAvailableAreas($zone_id): JsonResponse
    {
        $endpoint = config('services.pathao.endpoint') . '/aladdin/api/v1/zones/' . $zone_id . '/area-list';

        return $this->getCommonData($endpoint);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function createStore(Request $request): JsonResponse
    {
        $this->validate($request, [
            'name' => 'required',
            'contact_name' => 'required',
            'contact_number' => 'required',
            'address' => 'required',
            'secondary_contact' => 'nullable',
            'city_id' => 'required',
            'zone_id' => 'required',
            'area_id' => 'required',
        ]);

        $endpoint = config('services.pathao.endpoint') . '/aladdin/api/v1/stores';
        $access_token = $this->getTokenFromCache('pathao');

        $res = Http::withToken($access_token)->post($endpoint, [
            'name' => $request->get('name'),
            'contact_name' => $request->get('contact_name'),
            'contact_number' => $request->get('contact_number'),
            'address' => $request->get('address'),
            'secondary_contact' => $request->get('secondary_contact'),
            'city_id' => $request->get('city_id'),
            'zone_id' => $request->get('zone_id'),
            'area_id' => $request->get('area_id'),
        ]);
        if ($res->successful()) {
            $body = $res->json();
            $data = $body['data'];

            return response()->json($data);
        } else {
            return response()->json([]);
        }
    }


    /**
     * @return JsonResponse
     */
    public function getStores(): JsonResponse
    {
        $endpoint = config('services.pathao.endpoint') . '/aladdin/api/v1/stores';

        return $this->getCommonData($endpoint);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function createOrder(Request $request): JsonResponse
    {
        $this->validate($request, [
            'store_id' => 'required',
            'order_id' => 'required',
            'recipient_city' => 'required',
            'recipient_zone' => 'required',
            'recipient_area' => 'required',
            'delivery_option' => 'required|in:12,48',
            'item_type' => 'required',
            'collectMoney' => 'boolean',
        ]);

        $endpoint = config('services.pathao.endpoint') . '/aladdin/api/v1/orders';
        $access_token = $this->getTokenFromCache('pathao');

        // get order from table
        $order = Order::with('products')
            ->where('id', '=', $request->get('order_id'))
            ->first();

        // get shipping address of customer
        $shipping = $order?->static_address['shipping'];

        // get products to calculate weight
        $product_ids = $order->products()->pluck('product_id')->toArray();
        $total_weight = 0;
        $products = DB::table('products')
            ->whereIn('id', $product_ids)
            ->select('id', 'weight')
            ->get();
        collect($products)->each(function ($item) use (&$total_weight) {
            $total_weight += $item->weight;
        });

        $res = Http::withToken($access_token)->post($endpoint, [
            'store_id' => $request->get('store_id'),
            'merchant_order_id' => $order->order_no,
            'recipient_name' => $shipping['name'],
            'recipient_phone' => $shipping['mobile'],
            'recipient_address' => "{$shipping['house']}, {$shipping['street']}",
            'recipient_city' => $request->get('recipient_city'),
            'recipient_zone' => $request->get('recipient_zone'),
            'recipient_area' => $request->get('recipient_area'),
            'delivery_type' => (int)$request->get('delivery_option'),
            'item_type' => $request->get('item_type'),
            'special_instruction' => $order->comment,
            'item_quantity' => $order->total_quantity,
            'item_weight' => $total_weight,
            'item_description' => $request->get('item_description'),
            'amount_to_collect' => $request->get('collectMoney') ? $order->total_amount : 0,
        ]);

        if ($res->successful()) {

            // Update order shipping_method field for shipping provider
            $provider_code = $request->get('provider_code');
            DB::table('orders')
            ->where('id', '=', $request->get('order_id'))
            ->update([
                'shipping_method' => $provider_code
            ]);

            $body = $res->json();
            $data = $body['data'];
            return response()->json($data);
        } else {
            return response()->json($res->json(), 422);
        }
    }

    /**
     * @param Response $response
     * @return mixed
     */
    private function getTokenFromResponse(Response $response): mixed
    {
        $code = 'pathao';

        if ($response->successful()) {
            $body = $response->json();
            $access_token = $body['access_token'];
            $refresh_token = $body['refresh_token'];
            $expires_in = $body['expires_in'];

            Cache::put($code . '_access_token', $access_token, $expires_in);
            Cache::forever($code . '_refresh_token', $refresh_token);

            return $access_token;
        } else {
            return null;
        }
    }

    /**
     * @param $endpoint
     * @return JsonResponse
     */
    private function getCommonData($endpoint): JsonResponse
    {
        $code = 'pathao';
        $access_token = $this->getTokenFromCache($code);

        $res = Http::withToken($access_token)->get($endpoint);
        if ($res->successful()) {
            $body = $res->json();
            $data = $body['data'];

            return response()->json($data);
        } else {
            return response()->json([]);
        }
    }

    /**
     * @param $code
     * @return mixed
     */
    private function getTokenFromCache($code): mixed
    {
        $token = Cache::get($code . '_access_token');
        if ($token !== null) {
            return $token;
        } else {
            return $this->refreshAccessToken() ?? $this->getAccessToken();
        }
    }
}
