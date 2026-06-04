<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\VerifiesEmails;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Lang;

class VerificationController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Email Verification Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling email verification for any
    | user that recently registered with the application. Emails may also
    | be re-sent if the user didn't receive the original email message.
    |
    */

    use VerifiesEmails;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('signed')->only('verify');
        $this->middleware('throttle:6,1')->only('verify', 'resend');
    }

    /**
     * Show the email verification notice.
     *
     */
    public function show()
    {
        //
    }

    /**
     * Mark the authenticated user's email address as verified.
     *
     * @param  Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function verify(Request $request)
    {
        $user_id = $request->route('id');
        $user = User::findOrFail($user_id);
        if ($user) {
            if (!hash_equals((string)$request->route('hash'), sha1($user->getEmailForVerification()))) {
                throw new AuthorizationException;
            }

            if ($user->hasVerifiedEmail()) {
                return response()->json([
                    'message' => Lang::get('auth.email_valid')
                ], 200);
            }

            if ($user->markEmailAsVerified()) {
                event(new Verified($user));
            }

            return response()->json([
                'message' => Lang::get('auth.email_valid')
            ], 200);
        } else {
            return response()->json([
                'message' => Lang::get('auth.is_email_available')
            ], 404);
        }
    }

    /**
     * Resend the email verification notification.
     *
     * @param  $email
     * @return \Illuminate\Http\JsonResponse
     */
    public function resend($email)
    {
        $user = User::where('email', $email)->first();
        if ($user) {
            if ($user->hasVerifiedEmail()) {
                return response()->json([
                    'message' => Lang::get('auth.email_valid')
                ], 200);
            }

            $user->sendEmailVerificationNotification();

            return response()->json([
                'message' => Lang::get('auth.check_email')
            ], 200);
        } else {
            return response()->json([
                'message' => Lang::get('auth.is_email_available')
            ], 404);
        }
    }
}
