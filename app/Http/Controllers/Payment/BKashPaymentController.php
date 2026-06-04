<?php

namespace App\Http\Controllers\Payment;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

class BKashPaymentController
{
    public function getToken()
    {
        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'username' => config('helper.bkash_username'),
                'password' => config('helper.bkash_password')
            ])
                ->post(config('helper.bkash_base_url') . '/' . config('helper.bkash_api_version') . '/checkout/token/grant', [
                    'app_key' => config('helper.bkash_app_key'),
                    'app_secret' => config('helper.bkash_app_secret')
                ]);

            $body = $response->json();
            // put it cache
            Cache::put('bkash_token', $body['id_token'], $body['expires_in']);
            Cache::forever('bkash_refresh_token', $body['refresh_token']);

            return $body['id_token'];
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => $exception->getMessage(),
                'status' => $exception->getCode()
            ], 400);
        }
    }

    public function getRefreshToken()
    {
        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'username' => config('helper.bkash_username'),
                'password' => config('helper.bkash_password')
            ])
                ->post(config('helper.bkash_base_url') . '/' . config('helper.bkash_api_version') . '/checkout/token/refresh', [
                    'app_key' => config('helper.bkash_app_key'),
                    'app_secret' => config('helper.bkash_app_secret'),
                    'refresh_token' => Cache::get('bkash_refresh_token'),
                ]);

            $body = $response->json();
            // put it cache
            Cache::put('bkash_token', $body['id_token'], $body['expires_in']);
            Cache::forever('bkash_refresh_token', $body['refresh_token']);

            return $body['id_token'];
        } catch (Throwable $exception) {
            report($exception);

            return null;
        }
    }

    public function createPayment(Request $request)
    {
        try {
            $token = $this->getTokenFromCache();

            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => $token,
                'X-APP-Key' => config('helper.bkash_app_key')
            ])
                ->post(config('helper.bkash_base_url') . '/' . config('helper.bkash_api_version') . '/checkout/payment/create', [
                    'amount' => $request->get('amount'),
                    'currency' => config('helper.bkash_currency'),
                    'intent' => 'sale',
                    'merchantInvoiceNumber' => $request->get('order_no'),
                ]);

            return $response->json();
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => $exception->getMessage(),
                'status' => $exception->getCode()
            ], 400);
        }
    }

    public function executePayment(Request $request)
    {
        try {
            $token = $this->getTokenFromCache();

            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => $token,
                'X-APP-Key' => config('helper.bkash_app_key')
            ])
                ->post(config('helper.bkash_base_url') . '/' . config('helper.bkash_api_version') . '/checkout/payment/execute/' . $request->get('paymentID'));

            return $response->json();
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => $exception->getMessage(),
                'status' => $exception->getCode()
            ], 400);
        }
    }

    private function getTokenFromCache()
    {
        $token = Cache::get('bkash_token');
        if ($token !== null) {
            return $token;
        } else {
            return $this->getToken() ?? $this->getRefreshToken();
        }
    }
}
