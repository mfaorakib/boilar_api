<?php

namespace App\Http\Controllers\User;

use Carbon\Carbon;
use App\Models\Product\Product;
use App\Http\Controllers\Controller;
use App\Http\Resources\Product\ProductSingleApiResource;
use App\Models\User\Customer;
use App\Http\Resources\User\CustomerAuthResource;
use App\Rules\Mobile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Throwable;

class CustomerApiController extends Controller
{
    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getOrderList(Request $request): JsonResponse
    {
        try {
            $limit = (int)$request->query('limit', 10);
            $customer_id = Auth::guard('customer')->id();
            $columns = [
                'id',
                'order_no as orderNo',
                'invoice_no as invoiceNo',
                'total_amount as total',
                'total_quantity as quantity',
                'payment_method as paymentMethod',
                'payment_status as paymentStatus',
                'order_status as orderStatus',
                'created_at as createdAt',
            ];
            $orders = DB::table('orders')
                ->select($columns)
                ->whereNull('deleted_at')
                ->where('customer_id', '=', $customer_id)
                ->latest()
                ->limit($limit)
                ->get();

            return response()->json($orders);
        } catch (Throwable $exception) {
            report($exception);
            return response()->json([
                'message' => Lang::get('crud.error')
            ], 404);
        }
    }

    /**
     * @param $order_id
     * @return JsonResponse
     */
    public function getOrder($order_id): JsonResponse
    {
        try {
            $customer_id = Auth::guard('customer')->id();
            $columns = [
                'id',
                'order_no as orderNo',
                'invoice_no as invoiceNo',
                'total_amount as total',
                'total_quantity as quantity',
                'static_address as staticAddress',
                'payment_method as paymentMethod',
                'payment_status as paymentStatus',
                'order_status as orderStatus',
                'created_at as createdAt',
            ];
            $order = DB::table('orders')
                ->select($columns)
                ->whereNull('deleted_at')
                ->where('customer_id', '=', $customer_id)
                ->where('id', '=', $order_id)
                ->first();
            if ($order) {
                $subtotal = 0;

                $products = DB::table('order_products')
                    ->where('order_id', '=', $order_id)
                    ->get()
                    ->map(function ($item) use (&$subtotal, &$total) {
                        // calculate subtotal
                        $subtotal += $item->selling_price * $item->quantity;

                        $product = Product::find($item->product_id);
                        $item->total = $item->selling_price * $item->quantity;
                        $item->product = $product ? new ProductSingleApiResource($product) : null;
                        return $item;
                    });
                $order->subtotal = $subtotal;
                $order->items = $products;
                $order->staticAddress = json_decode($order->staticAddress);
            }


            return response()->json($order);
        } catch (Throwable $exception) {
            report($exception);
            return response()->json([
                'message' => Lang::get('crud.error')
            ], 404);
        }
    }

    /**
     * @param Request $request
     * @param Customer $customer
     * @return JsonResponse
     */
    public function updateProfile(Request $request, Customer $customer): JsonResponse
    {
        $request->validate([
            'name' => 'nullable',
            'email' => 'nullable|email|unique:customers,email,' . $customer->id,
            'mobile' => ['nullable', new Mobile(), 'unique:customers,mobile,' . $customer->id],
        ]);

         // Check email is empty
         $email = $request->get('email');
         if($request->has(['email']) && ($email === '' || $email === null)) {
             return response()->json([ 'message' => 'Email is empty!'], 411);
         }
 
         // Check mobile is empty
         $mobile = $request->get('mobile');
         if($request->has(['mobile']) && ($mobile === '' || $mobile === null)) {
             return response()->json([ 'message' => 'Mobile is empty!'], 411);
         }

        DB::beginTransaction();
        try {
            $customer->update($request->all());

            DB::commit();
            // return success message
            return response()->json([
                'message' => Lang::get('crud.update')
            ]);
        } catch (Throwable $exception) {
            // log exception
            report($exception);
            // rollback database
            DB::rollBack();
            // return failed message
            return response()->json([
                'error' => $exception->getMessage(),
                'message' => Lang::get('crud.error')
            ], 400);
        }
    }

    /**
     * @param Request $request
     * @param Customer $customer
     * @return JsonResponse
     */
    public function updatePassword(Request $request, Customer $customer): JsonResponse
    {
        $request->validate([
            'password' => 'required|min:8|confirmed'
        ]);
        DB::beginTransaction();
        try {
            $customer->update($request->all());

            DB::commit();
            // return success message
            return response()->json([
                'message' => Lang::get('crud.update')
            ]);
        } catch (Throwable $exception) {
            // log exception
            report($exception);
            // rollback database
            DB::rollBack();
            // return failed message
            return response()->json([
                'error' => $exception->getMessage(),
                'message' => Lang::get('crud.error')
            ], 400);
        }
    }

    /**
     * @param Request $request
     * @param Customer $customer
     * @return JsonResponse
     */
    public function storeParentQuestion(Request $request, Customer $customer): JsonResponse
    {
        $dateFormat = 'Y-m-d H:i:s';
        // validate request
        $request->validate([
            'parent_type' => 'required'
        ]);

        $parentId = $request->get('id');
        $dateOfBirth = $request->get('date_of_birth');
        $expectingDate = $request->get('expecting_date');
        
        // Parent data
        $email = $request->get('email');
        $mobile = $request->get('mobile');

        // Date formating
        if($dateOfBirth) {
            $dateOfBirth = Carbon::parse($dateOfBirth)->format($dateFormat);
        };
        
        if($expectingDate) {
            $expectingDate = Carbon::parse($expectingDate)->format($dateFormat);
        };

        // begin database transaction
        DB::beginTransaction();
        try {
            
            // Update customer table for parent type
            DB::table('customers')
            ->where('id', $parentId)
            ->update([
                    'parent_type' => $request->get('parent_type'),
                    'email' => $email,
                    'mobile' => $mobile
                ]);

            // Insert document in customer/parent children table
            if($request->get('parent_type') !== 'other') {
                $customer->children()->create([
                    'date_of_birth'=> $dateOfBirth? $dateOfBirth:null,
                    'expecting_date'=> $expectingDate? $expectingDate:null
                ]);
            }

            // commit database
            DB::commit();
            // return success message
            return response()->json([
                'message' => Lang::get('crud.create')
            ], 201);
            
        } catch (\Exception $exception) {
            // log exception
            report($exception);
            // rollback database
            DB::rollBack();
            // return failed message
            return response()->json([
                'message' => Lang::get('crud.error')
            ], 400);
        }
    }

    /**
     * Upload customer avatar.
     *
     * @param Request $request
     * @param Customer $customer
     * @return JsonResponse
     * @throws \Illuminate\Validation\ValidationException|\Exception
     */
    public function updateAvatar(Request $request, Customer $customer)
    {
        $this->validate($request, [
            'avatar' => 'required|image'
        ]);
        // begin database transaction
        DB::beginTransaction();
        try {
            $image = $request->file('avatar');
            $url = $customer->addMedia($image)->toMediaCollection('avatar')->getFullUrl();
            // update customer
            $customer->update([
                'avatar' => $url
            ]);
            // commit changes
            DB::commit();
            // return success message
            return response()->json([
                'message' => Lang::get('crud.update'),
                'user' => new CustomerAuthResource($customer)
            ], 200);
        } catch (\Exception $exception) {
            // log exception
            report($exception);
            // rollback changes
            DB::rollBack();
            // return failed message
            return response()->json([
                'message' => Lang::get('crud.error')
            ], 400);
        }
    }
}
