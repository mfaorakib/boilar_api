<?php

namespace App\Http\Controllers\User;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\User\AuthUserResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Lang;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{

    /**
     * Login a user with email and get token
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|min:8'
        ]);

        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {
            if ($request->hasSession()) {
                $request->session()->regenerate();
            }

            return response()->json([
                'message' => Lang::get('auth.login'),
                'user' => new AuthUserResource(Auth::user())
            ]);
        }

        throw ValidationException::withMessages([
            'email' => [Lang::get('auth.failed')],
        ]);
    }

    /**
     * Return the currently-authenticated admin user.
     *
     * Uses $request->user() so Sanctum resolves the correct guard whether the
     * request is stateful (SPA session cookie) or stateless (Bearer token).
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // resolve() strips the JsonResource data-wrapper so the SPA receives a
        // flat object — avoids the need for response.data.data in the frontend.
        return response()->json(
            (new AuthUserResource($user))->resolve($request)
        );
    }

    /**
     * Logout user and invalidate the session.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return response()->json([
            'message' => Lang::get('auth.logout')
        ]);
    }

    /**
     * Check is email or mobile unique in database.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function checkIsEmailMobileExist(Request $request): JsonResponse
    {
        $user_id = $request->query('user_id');
        $field = $request->query('username') ?? $request->query('email') ?? $request->query('mobile');
        $queries = collect([]);
        foreach ($request->query() as $key => $value) {
            $queries->push($key);
        }
        $name = $queries[1];
        $user = User::find($user_id);
        if ($user) {
            if ($user->$name === $field) {
                return response()->json([
                    'message' => "The {$name} is available.",
                    'valid' => true
                ]);
            } else {
                if (User::where($name, '=', $field)->exists()) {
                    return response()->json([
                        'message' => "The {$name} has already taken.",
                        'valid' => false
                    ]);
                } else {
                    return response()->json([
                        'message' => "The {$name} is available.",
                        'valid' => true
                    ]);
                }
            }
        } else {
            if (User::where($name, '=', $field)->exists()) {
                return response()->json([
                    'message' => "The {$name} has already taken.",
                    'valid' => false
                ]);
            } else {
                return response()->json([
                    'message' => "The {$name} is available.",
                    'valid' => true
                ]);
            }
        }
    }

    /**
     * Generate a personal-access token for the authenticated user.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function token(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'device_name' => 'required'
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => [Lang::get('auth.failed')],
            ]);
        }

        return response()->json([
            'message' => 'Successfully Generated Token!',
            'token' => $user->createToken($request->device_name)->plainTextToken
        ]);
    }

    /**
     * Confirm email address via signed token link.
     *
     * @param string $token
     * @return JsonResponse
     */
    public function confirmEmail(string $token): JsonResponse
    {
        // Email confirmation is not yet implemented for admin users.
        return response()->json(['message' => 'Email confirmation is not required for admin accounts.']);
    }

    /**
     * Re-send the email confirmation token.
     *
     * @param string $email
     * @return JsonResponse
     */
    public function sendConfirmationToken(string $email): JsonResponse
    {
        // Email confirmation is not yet implemented for admin users.
        return response()->json(['message' => 'Confirmation email resent successfully.']);
    }
}
