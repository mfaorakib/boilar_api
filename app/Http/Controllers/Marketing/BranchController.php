<?php

namespace App\Http\Controllers\Marketing;

use App\Models\Common\TagTranslation;
use App\Http\Controllers\Controller;
use App\Http\Requests\Product\ProductRequest;
use App\Models\Marketing\Branch;
use App\Http\Resources\Marketing\BranchEditResource;
use App\Http\Resources\Marketing\BranchResource;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Str;
use Throwable;

class BranchController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return AnonymousResourceCollection
     * @throws AuthorizationException
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny branch');

        $query = $request->query('query');
        $sortBy = $request->query('sortBy');
        $direction = $request->query('direction');
        $per_page = $request->query('per_page', 10);
        $category_slug = $request->query('category');

        $branches = Branch::latest();

        // Query by search value
        if ($query) {
            $branches = Branch::where('name', 'like', '%' . $query . '%');
        }

        if ($sortBy) {
            $branches = $branches->orderBy($sortBy, $direction);
        }
        
        if ($per_page === '-1') {
            $results = $branches->get();
            $branches = new LengthAwarePaginator($results, $results->count(), -1);
        } else {
            $branches = $branches->paginate($per_page);
        }

        return BranchResource::collection($branches);
    }

    /**
     * Get all branches.
     *
     * @param $locale
     * @return JsonResponse
     */
    public function getAll($locale): JsonResponse
    {
        $branches = Branch::latest()
            ->where('status', '=', 'active')
            ->get();

        return response()->json([
            'data' => $branches
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function store(Request $request): JsonResponse
    {

        $this->authorize('create branch');

        // begin database transaction
        DB::beginTransaction();
        try {
            $branch = Branch::create($request->all());

            // commit database
            DB::commit();
            // return success message
            return response()->json([
                'message' => Lang::get('crud.create'),
                'productId' => $branch->id
            ], 201);
        } catch (Throwable $exception) {
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
     * Display the specified resource.
     *
     * @param $locale
     * @param Branches $branch
     * @return BranchEditResource|JsonResponse
     * @throws AuthorizationException
     */
    public function show($locale, Branch $branch)
    {
        App::setLocale($locale);

        $this->authorize('view branch');

        try {
            return new BranchEditResource($branch);
        } catch (Throwable $exception) {
            report($exception);
            return response()->json([
                'message' => Lang::get('crud.error')
            ], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param $locale
     * @param Branch $branch
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function update(Request $request, Branch $branch): JsonResponse
    {
        $this->authorize('update branch');

        // begin database transaction
        DB::beginTransaction();
        try {

            $branch->update($request->all());

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
                'message' => $exception->getMessage()
//                'message' => Lang::get('crud.error')
            ], 400);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param $locale
     * @param Branch $branch
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function destroy(Branch $branch): JsonResponse
    {

        $this->authorize('delete branch');

        // begin database transaction
        DB::beginTransaction();
        try {
            // delete branch
            $branch->delete();

            // commit database
            DB::commit();
            // return success message
            return response()->json([
                'message' => Lang::get('crud.trash')
            ]);
        } catch (Throwable $exception) {
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
    
}
