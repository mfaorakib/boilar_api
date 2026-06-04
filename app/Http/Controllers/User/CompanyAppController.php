<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\User\Company;
use App\Http\Resources\User\CompanyResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Lang;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Throwable;

class CompanyAppController extends Controller
{
    /**
     * @param Request $request
     * @return JsonResponse | AnonymousResourceCollection
     */
    public function getCompanies(Request $request): JsonResponse | AnonymousResourceCollection
    {
        try {

            $limit = $request->query('limit') ?: '5';
            $sort = $request->query('short');
            $query = $request->query('query')?:'';

            $company = Company::with('translations')
                ->whereTranslationLike('name', '%' . $query . '%')
                ->where('status', '=', 'active');

            $company = $company->paginate($limit);
            $company->appends([
                'limit' => $limit,
                'sort' => $sort,
            ]);

            return CompanyResource::collection($company);
        } catch (Throwable $exception) {
            report($exception);
            return response()->json([
                'message' => Lang::get('crud.error')
            ], 404);
        }
    }

}
