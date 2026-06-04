<?php

namespace App\Http\Controllers\User;

use Carbon\Carbon;
use App\Http\Controllers\Controller;
use App\Http\Resources\User\CustomerAuthAppResource;
use App\Http\Resources\User\CustomerAuthResource;
use App\Models\Product\Product;
use App\Http\Resources\Product\ProductSingleApiResource;
use App\Models\User\Customer;
use App\Models\User\CustomerDevice;
use App\Models\User\Company;
use App\Rules\Mobile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Lang;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class CustomerAppController extends Controller
{
    /**
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'username' => 'required',
            'password' => 'required',
            'device_name' => 'required'
        ]);
        if (filter_var($request->get('username'), FILTER_VALIDATE_EMAIL)) {
            $field = 'email address';
            $customer = Customer::query()->where('email', '=', $request->get('username'))->first();
        } else {
            $field = 'mobile number';
            $customer = Customer::query()->where('mobile', '=', $request->get('username'))->first();
        }
        // check if customer exist
        if (!$customer) {
            throw ValidationException::withMessages([
                'username' => ["We can't find a user with that {$field}."],
            ]);
        }
        // check customer password
        if (!Hash::check($request->get('password'), $customer->password)) {
            throw ValidationException::withMessages([
                'password' => ["The provided password is incorrect."],
            ]);
        }

        return response()->json([
            'token' => $customer->createToken($request->get('device_name'))->plainTextToken,
            'user' => new CustomerAuthAppResource($customer)
        ]);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'nullable',
            'username' => 'required',
            'password' => 'required|min:8|confirmed',
            'device_name' => 'required'
        ]);
        if (filter_var($request->get('username'), FILTER_VALIDATE_EMAIL)) {
            $request->validate([
                'username' => 'email|unique:customers,email'
            ]);
            $data = [
                'name' => $request->get('name'),
                'email' => $request->get('username'),
                'password' => $request->get('password')
            ];
        } else {
            $request->validate([
                'username' => [new Mobile(), 'unique:customers,mobile']
            ]);
            $data = [
                'name' => $request->get('name'),
                'mobile' => $request->get('username'),
                'password' => $request->get('password')
            ];
        }
        // begin database transaction
        DB::beginTransaction();
        try {

            $customer = Customer::create($data);

            // commit changes
            DB::commit();

            return response()->json([
                'token' => $customer->createToken($request->get('device_name'))->plainTextToken,
                'user' => new CustomerAuthAppResource($customer)
            ]);
        } catch (Throwable $exception) {
            report($exception);
            // rollback changes
            DB::rollBack();
            return response()->json([
                'message' => Lang::get('crud.error'),
                'error' => $exception->getMessage(),
            ], 400);
        }
    }

    /**
     * Social login.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function socialLogin(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'nullable|email',
            'mobile' => 'nullable',
            'provider' => 'required',
            'provider_id' => 'required',
            'device_name' => 'required'
        ]);

        // find provider
        $provider = DB::table('customer_social_providers')
            ->where('provider_id', '=', $request->get('provider_id'))
            ->where('provider', '=', $request->get('provider'))
            ->first();

        // get customer
        $customer = null;
        if (isset($provider?->customer_id)) {
            $customer = Customer::find($provider?->customer_id);
        }
        if ($request->filled('email')) {
            $customer = Customer::where('email', '=', $request->get('email'))->first();
        }
        if ($request->filled('mobile')) {
            $customer = Customer::where('mobile', '=', $request->get('mobile'))->first();
        }
        if ($customer) {
            Auth::login($customer);

            return response()->json([
                'token' => $customer->createToken($request->get('device_name'))->plainTextToken,
                'user' => new CustomerAuthAppResource($customer)
            ]);

        } else {
            // begin database transaction
            DB::beginTransaction();
            try {

                $customer = Customer::create($request->all());
                $customer->providers()->create($request->all());

                // commit changes
                DB::commit();

                Auth::login($customer);

                return response()->json([
                    'token' => $customer->createToken($request->get('device_name'))->plainTextToken,
                    'user' => new CustomerAuthAppResource($customer)
                ]);
            } catch (Throwable $exception) {
                report($exception);
                // rollback changes
                DB::rollBack();
                return response()->json([
                    'message' => Lang::get('crud.error'),
                    'error' => $exception->getMessage()
                ], 400);
            }
        }

    }

    /**
     * @return JsonResponse
     */
    public function me(): JsonResponse
    {

        return response()->json([
            'user' => new CustomerAuthResource(Auth::user())
        ]);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getOrderList(Request $request): JsonResponse
    {
        try {
            $limit = (int)$request->query('limit', 10);
            $customer_id = Auth::id();
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
            $customer_id = Auth::id();
            $columns = [
                'id',
                'order_no as orderNo',
                'invoice_no as invoiceNo',
                'total_amount as total',
                'special_discount as specialDiscount',
                'coupon_discount as couponDiscount',
                'shipping_cost as shippingCost',
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
                $order->subtotal = round($subtotal, 2);
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
     * @return JsonResponse
     */
    public function storeParentQuestion(Request $request): JsonResponse
    {
        $customer = Auth::user();

        $request->validate([
            'name' => 'nullable',
            'email' => 'nullable|email|unique:customers,email,' . $customer->id,
            'mobile' => ['nullable', new Mobile(), 'unique:customers,mobile,' . $customer->id],
            'parent_type' => 'required',
            'child_status' => Rule::requiredIf(function () use ($request) {
                return $request->get('parent_type') !== 'other';
            }),
            'date_of_birth' => Rule::requiredIf(function () use ($request) {
                return $request->get('child_status') === 'parent';
            }),
            'expecting_date' => Rule::requiredIf(function () use ($request) {
                return $request->get('child_status') === 'expecting';
            }),
        ]);

        $dateOfBirth = $request->get('date_of_birth');
        $expectingDate = $request->get('expecting_date');

        // Date format
        if ($dateOfBirth) {
            $dateOfBirth = Carbon::parse($dateOfBirth);
        }

        if ($expectingDate) {
            $expectingDate = Carbon::parse($expectingDate);
        }

        // begin database transaction
        DB::beginTransaction();
        try {
            // Update customer table for parent type
            $customer->update([
                'parent_type' => $request->get('parent_type'),
                'name' => $request->get('name'),
                'mobile' => $request->get('mobile'),
                'email' => $request->get('email'),
            ]);

            // Insert document in customer/parent children table
            if($request->get('parent_type') !== 'other') {
                $customer->children()->create([
                    'date_of_birth' => $dateOfBirth ? $dateOfBirth : null,
                    'expecting_date' => $expectingDate ? $expectingDate : null
                ]);
            }

            // commit database
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
                'message' => Lang::get('crud.error'),
                'error' => $exception->getMessage(),
            ], 400);
        }
    }

    /**
     * @param Request $request
     * @param Customer $customer
     * @return JsonResponse
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $customer = Auth::user();

        $request->validate([
            'name' => 'nullable',
            'email' => 'nullable|email|unique:customers,email,' . $customer->id,
            'mobile' => ['nullable', new Mobile(), 'unique:customers,mobile,' . $customer->id],
            'shipping_email' => 'nullable|email',
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

            $company_name = $request->get('company');
            if (isset($company_name)) {
                $company_info = Company::query()
                ->where('id', $company_name)
                ->orWhereTranslation('name', $company_name)
                ->first();
                $company_id = $company_info?->id ?: null;

                if (!$company_id) {
                    $new_company = Company::create([
                        'status' => 'active',
                        'name' => $company_name
                    ]);
                    $company_id = $new_company->id;
                }

                $request->merge(['company_id' => $company_id]);
            }

            // Update customer info
            $customer->update($request->all());

            DB::commit();
            // return success message
            return response()->json([
                'message' => Lang::get('crud.update'),
                'user' => new CustomerAuthResource(Auth::user())
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
     * Upload customer avatar.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws \Illuminate\Validation\ValidationException|\Exception
     */
    public function updateAvatar(Request $request)
    {
        $customer = Auth::user();

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
                'id' => $customer->id,
                'avatar' =>  CustomerAuthResource::make($customer)->avatar,
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

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        if ($request->has('email')) {
            $request->validate([
                'email' => 'email'
            ]);

            $status = Password::broker('customers')
                ->sendResetLink($request->only('email'));

            return $status === Password::RESET_LINK_SENT
                ? response()->json([
                    'message' => __($status)
                ])
                : response()->json([
                    'message' => __($status)
                ], 404);
        }
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function resetPasswordByEmail(Request $request): JsonResponse
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        $status = Password::broker('customers')->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) use ($request) {
                $user->forceFill([
                    'password' => $password
                ])->save();

                $user->setRememberToken(Str::random(60));

                event(new PasswordReset($user));
            }
        );

        return $status == Password::PASSWORD_RESET
            ? response()->json([
                'message' => __($status)
            ])
            : response()->json([
                'message' => __($status)
            ], 404);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'code' => 'required|min:6',
            'mobile' => 'filled',
            'password' => 'required|min:6|regex:/[a-z]/|regex:/[A-Z]/|regex:/[0-9]/'
        ]);
        // begin database transaction
        DB::beginTransaction();
        try {
            // check if verification code is valid
            $code = $request->get('code');
            $message_code = MessageCode::where('mobile', '=', $request->get('mobile'))
                ->where('code', '=', $code)->first();
            if (empty($message_code)) {
                return response()->json([
                    'message' => Lang::get('auth.code_expired')
                ], 401);
            }
            $created_at = $message_code->created_at->addMinutes(2.5);
            if ($created_at < now()) {
                return response()->json([
                    'message' => Lang::get('auth.code_expired')
                ], 401);
            }

            $customer = $this->findCustomerByMobile($request->get('mobile'));

            if ($customer) {
                $customer->update([
                    'password' => $request->get('password')
                ]);

                // commit changes
                DB::commit();

                // send success message
                SendResetPasswordMessage::dispatch($customer);

                return response()->json([
                    'message' => Lang::get('auth.password_reset_success')
                ], 200);
            } else {
                // rollback changes
                DB::rollBack();
                throw ValidationException::withMessages([
                    'username' => [Lang::get('auth.is_mobile_available')],
                ]);
            }
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
     * @return JsonResponse
     * @throws ValidationException
     */
    public function changePassword(Request $request): JsonResponse
    {

        $customer = Auth::user();
        $request->validate([
            'password_current' => 'required|min:8',
            'password' => 'required|min:8|confirmed',
            'password_confirmation' => 'required|min:8',
        ]);
 
        //Check current password 
        if (!Hash::check($request->get('password_current'), $customer->password)) {
            throw ValidationException::withMessages([
                'password_current' => ['The current password is not correct!']
            ]);
        }

        // begin database transaction
        DB::beginTransaction();
        try {

            $customer = DB::table('customers')
                ->where('id', '=', $customer->id)
                ->update([
                    'password' => Hash::make($request->get('password'))
                ]);

            // commit changes
            DB::commit();

            return response()->json([
                'message' => Lang::get('auth.password_update_success')
            ], 200);
           
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
    public function logout(): JsonResponse
    {
        Auth::user()->tokens()->delete();

        return response()->json([
            'message' => 'Successfully Logged Out'
        ]);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function createDevice(Request $request): JsonResponse
    {
        $customer_id = Auth::id();

        $request->validate([
            'token' => 'required|unique:customer_devices',
            'platform' => 'required',
        ]);

        DB::beginTransaction();
        try {

            $request->merge(['customer_id' => $customer_id]);

            // Create customer device
           $device = CustomerDevice::create($request->all());

            DB::commit();
            // return success message
            return response()->json([
                'message' => Lang::get('crud.create'),
                'data' => $device
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
     * @return JsonResponse
     */
    public function getDevices(Request $request): JsonResponse
    {

        $platfrom = $request->get('platform');
        $customer_id = $request->get('customer_id');

        $devices = CustomerDevice::latest();

        if(isset($platfrom)) {
            $devices = $devices->where('platform', '=', $platfrom);
        }

        if(isset($customer_id)) {
            $devices = $devices->where('customer_id', '=', $customer_id);
        }

        $devices = $devices->get();

        return response()->json([
            'data' => $devices
        ]);
    }

}
