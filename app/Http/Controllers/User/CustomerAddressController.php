<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\User\CustomerAddress;
use App\Rules\Mobile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Throwable;

class CustomerAddressController extends Controller
{
    /**
     * Get customer address.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        try {
            $customer_id = Auth::id();
            $addresses = CustomerAddress::with('customer')
                ->where('status', '=', 'active')
                ->where('customer_id', '=', $customer_id)
                ->get();

            return response()->json($addresses);
        } catch (Throwable $exception) {
            report($exception);
            return response()->json([
                'message' => Lang::get('crud.error')
            ], 404);
        }

    }

    /**
     * Store new customer address.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'customer_id' => 'filled',
            'default' => 'nullable',
            'type' => 'nullable',
            'name' => 'required',
            'email' => 'nullable|email',
            'mobile' => ['required', new Mobile()],
            'address_line' => 'required',
            'area' => 'required',
            'district' => 'required',
            'division' => 'required',
            'zip' => 'nullable',
            'address_line' => 'nullable',
        ]);

        DB::beginTransaction();
        try {

            $customer_id = $request->get('customer_id');

            if(!$customer_id) {
                $customer_id = Auth::guard('customer')->id();
            }

            $request->merge(['customer_id' => $customer_id, 'status' => 'active']);

            if ($request->get('default')) {
                DB::table('customer_addresses')
                    ->where('customer_id', '=', $customer_id)
                    ->where('type', '=', $request->get('type'))
                    ->update(['default' => 0]);
            }

            $address = CustomerAddress::create($request->all());

            DB::commit();
            return response()->json($address, 201);
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
     * @param $id
     * @return JsonResponse
     */
    public function edit($id): JsonResponse
    {
        try {
            $address = DB::table('customer_addresses')
                ->select(
                    'id',
                    'default',
                    'type',
                    'name',
                    'email',
                    'mobile',
                    'address_line',
                    'zip',
                    'area',
                    'district',
                    'division'
                )
                ->where('status', '=', 'active')
                ->where('customer_id', '=', Auth::id())
                ->where('id', '=', $id)
                ->first();

            if ($address) {
                $address->default = (boolean)$address->default;
            }

            return response()->json($address);
        } catch (Throwable $exception) {
            report($exception);
            return response()->json([
                'message' => Lang::get('crud.error')
            ], 404);
        }
    }

    /**
     * @param Request $request
     * @param CustomerAddress $address
     * @return JsonResponse
     */
    public function update(Request $request, CustomerAddress $address): JsonResponse
    {
        $request->validate([
            'default' => 'nullable',
            'type' => 'nullable',
            'name' => 'required',
            'email' => 'required|email',
            'mobile' => ['required', new Mobile()],
            'area' => 'required',
            'address_line' => 'required',
            'district' => 'required',
            'division' => 'required',
            'zip' => 'nullable',
        ]);

        DB::beginTransaction();
        try {
            if ($request->get('default')) {
                DB::table('customer_addresses')
                    ->where('customer_id', '=', Auth::id())
                    ->where('type', '=', $request->get('type'))
                    ->update(['default' => 0]);
            }

            $address->update($request->all());

            DB::commit();
            return response()->json([
                'message' => Lang::get('crud.update'),
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
     * @param CustomerAddress $address
     * @return JsonResponse
     */
    public function delete(CustomerAddress $address): JsonResponse
    {
        DB::beginTransaction();
        try {

            $address->delete();

            DB::commit();
            return response()->json([
                'message' => Lang::get('crud.delete'),
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
     * @return JsonResponse
     */
    public function getDefaultAddress(): JsonResponse
    {
        try {
            $customer_id = Auth::guard('customer')->id();
            $addresses = DB::table('customer_addresses')
                ->select(
                    'id',
                    'default',
                    'type',
                    'name',
                    'email',
                    'mobile',
                    'address_line',
                    'zip',
                    'area',
                    'district',
                    'division',
                    'country',
                    'area_id',
                    'district_id',
                    'division_id'
                )
                ->where('status', '=', 'active')
//                ->where('default', '=', 1)
                ->where('customer_id', '=', $customer_id)
                ->get();

            return response()->json($addresses);

        } catch (Throwable $exception) {
            report($exception);
            return response()->json([
                'message' => Lang::get('crud.error')
            ], 404);
        }
    }
}
