<?php

namespace App\Http\Controllers\Shipping;

use App\Http\Resources\Shipping\DivisionTreeResource;
use App\Models\Shipping\Division;
use App\Http\Resources\Shipping\DivisionCollection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Throwable;

class DivisionController extends Controller
{
    /**
     * @param Request $request
     * @return DivisionCollection
     */
    public function index(Request $request): DivisionCollection
    {
        $query = $request->query('query');
        $sortBy = $request->query('sortBy');
        $direction = $request->query('direction');
        $per_page = $request->query('per_page', 10);

        $divisions = Division::query();

        if (isset($query)) {
            $divisions = Division::search($query);
        }

        if (isset($direction)) {
            $divisions = Division::orderBy($sortBy, $direction);
        }

        if ($per_page === '-1') {
            $results = $divisions->get();
            $divisions = new LengthAwarePaginator($results, $results->count(), -1);
        } else {
            $divisions = $divisions->paginate($per_page);
        }

        return new DivisionCollection($divisions);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): JsonResponse
    {
        $this->validate($request, [
            'name' => 'required'
        ]);
        DB::beginTransaction();
        try {
            Division::create($request->all());

            DB::commit();

            return response()->json([
                'message' => Lang::get('crud.create')
            ], 201);

        } catch (Throwable $exception) {
            report($exception);
            DB::rollBack();

            return response()->json([
                'message' => Lang::get('crud.error')
            ], 400);
        }
    }

    /**
     * @param Request $request
     * @param Division $division
     * @return JsonResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function update(Request $request, Division $division): JsonResponse
    {
        $this->validate($request, [
            'name' => 'required'
        ]);
        DB::beginTransaction();
        try {
            $division->update($request->all());

            DB::commit();

            return response()->json([
                'message' => Lang::get('crud.update')
            ]);

        } catch (Throwable $exception) {
            report($exception);
            DB::rollBack();

            return response()->json([
                'message' => Lang::get('crud.error')
            ], 400);
        }
    }

    /**
     * @param Division $division
     * @return JsonResponse
     */
    public function destroy(Division $division): JsonResponse
    {
        DB::beginTransaction();
        try {
            $division->delete();

            DB::commit();

            return response()->json([
                'message' => Lang::get('crud.delete')
            ]);

        } catch (Throwable $exception) {
            report($exception);
            DB::rollBack();

            return response()->json([
                'message' => Lang::get('crud.error')
            ], 400);
        }
    }

    /**
     * Get divisions.
     *
     * @return JsonResponse
     */
    public function getDivisions(): JsonResponse
    {
        try {
            switch (app()->getLocale()) {
                case 'en':
                    $name = 'name';
                    break;
                case 'bn':
                    $name = 'bn_name';
                    break;
                default:
                    $name = 'name';
            }
            $divisions = DB::table('divisions')
                ->where('status', '=', 'active')
                ->select('id', $name)
                ->get();

            return response()->json([
                'data' => $divisions
            ]);
        } catch (Throwable $exception) {
            report($exception);
            return response()->json(collect());
        }
    }

    /**
     * Get divisions as tree view with district, area.
     *
     * @return AnonymousResourceCollection
     */
    public function getTreeView(): AnonymousResourceCollection
    {
        $divisions = Division::with('children')->get();

        return DivisionTreeResource::collection($divisions);
    }

    /**
     * Get districts by division name.
     *
     * @param $division
     * @return JsonResponse
     */
    // public function getDistrictsByName($division): JsonResponse
    public function getDivisionByName($division)
    {
        try {
            switch (app()->getLocale()) {
                case 'en':
                    $name = 'name';
                    break;
                case 'bn':
                    $name = 'bn_name';
                    break;
                default:
                    $name = 'name';
            }

            $div = DB::table('divisions')
            ->where('name', '=', $division)
            ->where('status', '=', 'active')
            ->select('id')
            ->get();

            return response()->json([
                'data' => $div
            ]);
        } catch (Throwable $exception) {
            report($exception);
            return response()->json(collect());
        }
    }
}
