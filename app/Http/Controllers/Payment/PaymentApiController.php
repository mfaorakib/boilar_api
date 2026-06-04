<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class PaymentApiController extends Controller
{
    /**
     * Get available payment methods.
     *
     * @param $locale
     * @return JsonResponse
     */
    public function getPaymentMethods($locale): JsonResponse
    {
        try {
            $methods = DB::table('payment_methods as pm')
                ->join('payment_method_translations as pmt', 'pm.id', '=', 'pmt.payment_method_id')
                ->select('pm.id', 'pm.code', 'pmt.name', 'pmt.description', 'pm.image')
                ->where('pm.status', '=', 'active')
                ->where('pmt.locale', '=', $locale)
                ->get();

            return response()->json($methods);
        } catch (Throwable $exception) {
            report($exception);
            return response()->json(collect());
        }
    }

}
