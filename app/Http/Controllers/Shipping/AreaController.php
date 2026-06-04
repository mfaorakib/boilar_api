<?php

namespace App\Http\Controllers\Shipping;

use App\Models\Shipping\Area;
use App\Http\Resources\Shipping\UpazilaCollection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Throwable;

class AreaController extends Controller
{

    /**
     * @param Request $request
     * @return UpazilaCollection
     */
    public function index(Request $request): UpazilaCollection
    {
        $query = $request->query('query');
        $sortBy = $request->query('sortBy');
        $direction = $request->query('direction');
        $per_page = $request->query('per_page', 10);

        $areas = Area::query();

        if (isset($query)) {
            $areas = Area::search($query);
        }

        if (isset($direction)) {
            $areas = Area::orderBy($sortBy, $direction);
        }

        if ($per_page === '-1') {
            $results = $areas->get();
            $areas = new LengthAwarePaginator($results, $results->count(), -1);
        } else {
            $areas = $areas->paginate($per_page);
        }

        return new UpazilaCollection($areas);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): JsonResponse
    {
        $this->validate($request, [
            'district_id' => 'required',
            'name' => 'required'
        ]);
        DB::beginTransaction();
        try {
            Area::create($request->all());

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
     * @param Area $area
     * @return JsonResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function update(Request $request, Area $area): JsonResponse
    {
        $this->validate($request, [
            'district_id' => 'required',
            'name' => 'required'
        ]);
        DB::beginTransaction();
        try {
            $area->update($request->all());

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
     * @param Area $area
     * @return JsonResponse
     */
    public function destroy(Area $area): JsonResponse
    {
        DB::beginTransaction();
        try {
            $area->delete();

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
     * Get area by district.
     *
     * @param $district
     * @return JsonResponse
     */
    public function getAreas($district): JsonResponse
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
            $areas = DB::table('areas')
                ->where('district_id', '=', $district)
                ->where('status', '=', 'active')
                ->select('id', $name)
                ->get();

            return response()->json([
                'data' => $areas
            ]);
        } catch (Throwable $exception) {
            report($exception);
            return response()->json(collect());
        }
    }

    /**
     * Get all areas.
     *
     * @return JsonResponse
     */
    public function getAllAreas(): JsonResponse
    {
        try {
            $areas = DB::table('areas')
                ->where('status', '=', 'active')
                ->select('id', 'name')
                ->get();

            return response()->json([
                'data' => $areas
            ]);
        } catch (Throwable $exception) {
            report($exception);
            return response()->json(collect());
        }
    }
}
