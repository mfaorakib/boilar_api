<?php

namespace App\Http\Controllers\User;

use App\Models\User\Customer;
use App\Models\User\MessageCode;
use App\Http\Controllers\Controller;
use App\Http\Resources\User\CustomerAuthResource;
use App\Jobs\SendResetPasswordMessage;
use App\Jobs\SendVerificationCode;
use App\Jobs\SendWelcomeMessage;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Mockery\Generator\StringManipulation\Pass\Pass;
use Throwable;

class CustomerAuthController extends Controller
{
    /**
     * Login a customer with email and get token
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function login(Request $request): JsonResponse
    {

        $mobile = $request->get('mobile');
        $email = $request->get('email');

        $request->validate([
            'mobile' => !$email?'required':'nullable',
            'email' => !$mobile?'required|email':'nullable|email',
            'password' => 'required|min:8'
        ]);

        if($mobile) {
            $credentials = $request->only('mobile', 'password');
        } else if($email) {
            $credentials = $request->only('email', 'password');
        } 

        if (Auth::guard('customer')->attempt($credentials)) {

            return response()->json([
                'message' => 'Successfully Logged In',
                'user' => new CustomerAuthResource(Auth::guard('customer')->user()),
                'expires' => config('session.lifetime') / (60 * 24)
            ]);
        }
        throw ValidationException::withMessages([
            'email' => ['The provided credentials do not match our records.'],
        ]);
    }

    /**
     * Register customer.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required',
            'email' => !$request->get('mobile')?'required|email|unique:customers':'nullable|email|unique:customers',
            'mobile' => !$request->get('email')?'required|unique:customers':'nullable|unique:customers',
            'password' => 'required|min:8'
        ]);

        // begin database transaction
        DB::beginTransaction();
        try {

            $customer = Customer::create($data);

            Auth::guard('customer')->login($customer);

            // commit changes
            DB::commit();

            return response()->json([
                'message' => 'Successfully Registered',
                'user' => new CustomerAuthResource(Auth::guard('customer')->user()),
                'expires' => config('session.lifetime') / (60 * 24)
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
     * Social login.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function socialLogin(Request $request): JsonResponse
    {
        $email = $request->get('email');
        $mobile = $request->get('mobile');

        $request->validate([
            'email' => 'nullable|email',
            'mobile' => 'nullable',
            'provider' => 'required',
            'provider_id' => 'required'
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
            Auth::guard('customer')->login($customer);

            return response()->json([
                'message' => 'Successfully Logged In',
                'user' => new CustomerAuthResource(Auth::guard('customer')->user()),
                'expires' => config('session.lifetime') / (60 * 24)
            ]);
        } else {
            // begin database transaction
            DB::beginTransaction();
            try {

                $customer = Customer::create($request->all());

                $customer->providers()->create($request->all());
                // commit changes
                DB::commit();

                Auth::guard('customer')->login($customer);

                return response()->json([
                    'message' => 'Successfully Registered',
                    'user' => new CustomerAuthResource(Auth::guard('customer')->user()),
                    'expires' => config('session.lifetime') / (60 * 24)
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

    }

    /**
     * Get auth user response.
     *
     * @return JsonResponse
     */
    public function me(): JsonResponse
    {
        return response()->json([
            'user' => new CustomerAuthResource(Auth::guard('customer')->user()),
            'expires' => config('session.lifetime') / (60 * 24)
        ]);
    }

    /**
     * Logout user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        Auth::guard('customer')->logout();
        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return response()->json([
            'message' => 'Successfully Logged Out'
        ]);
    }

    /**
     * Generate token for auth user.
     *
     * @param Request $request
     * @return mixed
     * @throws ValidationException
     */
    public function token(Request $request): mixed
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'device_name' => 'required'
        ]);

        $user = Customer::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        return $user->createToken($request->device_name)->plainTextToken;
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
     * @param $mobile
     * @return JsonResponse
     */
    public function sendMessageCode($mobile)
    {
        DB::beginTransaction();
        try {
            $customer = $this->findCustomerByMobile($mobile);
            if ($customer) {
                return response()->json([
                    'message' => "The mobile number has already taken.",
                    'valid' => false
                ]);
            } else {
                // create code
                $code = MessageCode::create([
                    'mobile' => $mobile,
                    'created_at' => now(),
                    'code' => mt_rand(100000, 999999)
                ]);
                DB::commit();
                // send sms
                SendVerificationCode::dispatch($code);

                return response()->json([
                    'message' => Lang::get('auth.check_mobile')
                ], 200);
            }
        } catch (Throwable $exception) {
            report($exception);
            DB::rollBack();

            return response()->json([
                'message' => $exception->getMessage()
//                'message' => Lang::get('crud.error')
            ], 400);
        }
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function checkIsEmailMobileExist(Request $request): JsonResponse
    {
        $field = $request->query('username') ?? $request->query('email') ?? $request->query('mobile');
        $queries = collect([]);
        foreach ($request->query() as $key => $value) {
            $queries->push($key);
        }
        $name = $queries[0];
        if (Customer::query()->where($name, '=', $field)->exists()) {
            return response()->json([
                'message' => "The {$name} has already been taken.",
                'valid' => false
            ]);
        } else {
            return response()->json([
                'message' => "The {$name} is available.",
                'valid' => true
            ]);
        }
    }

    private function sendResetMessageCode($mobile)
    {
        DB::beginTransaction();
        try {
            // create code
            $code = MessageCode::create([
                'mobile' => $mobile,
                'created_at' => now(),
                'code' => mt_rand(100000, 999999)
            ]);
            DB::commit();
            // send sms
            SendVerificationCode::dispatch($code);

            return response()->json([
                'message' => Lang::get('auth.check_mobile')
            ], 200);
        } catch (Throwable $exception) {
            report($exception);
            DB::rollBack();

            return response()->json([
                'message' => $exception->getMessage()
//                'message' => Lang::get('crud.error')
            ], 400);
        }
    }

    /**
     * @param $email
     * @return mixed
     */
    private function findCustomerByEmail($email)
    {
        return Customer::query()->where('email', '=', $email)->first();
    }

    /**
     * @param $mobile
     * @return mixed
     */
    private function findCustomerByMobile($mobile)
    {
        return Customer::query()->where('mobile', '=', $mobile)->first();
    }

}
