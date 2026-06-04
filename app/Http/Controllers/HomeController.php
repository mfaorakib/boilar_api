<?php

namespace App\Http\Controllers;

use App\Mail\ContactMail;
use App\Http\Resources\Common\SettingResource;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Throwable;

class HomeController extends Controller
{

    /**
     * Show application home page.
     *
     * @return Factory|View
     */
    public function index(): Factory|View
    {
        return view('welcome');
    }

    /**
     * Get common settings.
     *
     * @return SettingResource
     */
    public function getCommonSettings(): SettingResource
    {
        $settings = DB::table('settings')
            ->where('status', '=', 'active')
            ->pluck('value', 'key');

        return new SettingResource($settings);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function contact(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required|email',
            'message' => 'required|min:10'
        ]);
        try {
            $body = array();
            $body['name'] = $request->get('name');
            $body['email'] = $request->get('email');
            $body['message'] = $request->get('message');

            Mail::to(config('helper.mail_to_address'))
                ->send(new ContactMail($body));

            return response()->json([
                'message' => 'Successfully Contacted!'
            ]);
        } catch (Throwable $exception) {
            return response()->json([
                'message' => Lang::get('crud.error'),
                'error' => $exception->getMessage()
            ], 400);
        }
    }
}
