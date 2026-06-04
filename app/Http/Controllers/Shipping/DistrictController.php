<?php

namespace App\Http\Controllers\Shipping;

use App\Models\Shipping\District;
use App\Http\Resources\Shipping\DistrictCollection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Throwable;

class DistrictController extends Controller
{
    /**
     * @param Request $request
     * @return DistrictCollection
     */
    public function index(Request $request): DistrictCollection
    {
        $query = $request->query('query');
        $sortBy = $request->query('sortBy');
        $direction = $request->query('direction');
        $per_page = $request->query('per_page', 10);

        $districts = District::query();

        if (isset($query)) {
            $districts = District::search($query);
        }

        if (isset($direction)) {
            $districts = District::orderBy($sortBy, $direction);
        }

        if ($per_page === '-1') {
            $results = $districts->get();
            $districts = new LengthAwarePaginator($results, $results->count(), -1);
        } else {
            $districts = $districts->paginate($per_page);
        }

        return new DistrictCollection($districts);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): JsonResponse
    {
        $this->validate($request, [
            'division_id' => 'required',
            'name' => 'required',
        ]);
        DB::beginTransaction();
        try {
            District::create($request->all());

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
     * @param District $district
     * @return JsonResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function update(Request $request, District $district): JsonResponse
    {
        $this->validate($request, [
            'division_id' => 'required',
            'name' => 'required',
        ]);
        DB::beginTransaction();
        try {
            $district->update($request->all());

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
     * @param District $district
     * @return JsonResponse
     */
    public function destroy(District $district): JsonResponse
    {
        DB::beginTransaction();
        try {
            $district->delete();

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

    public function getDistrictByName($district)
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

            $div = DB::table('districts')
            ->where('name', '=', $district)
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

    /**
     * Get districts by district.
     *
     * @param $division
     * @return JsonResponse
     */
    public function getDistricts($division): JsonResponse
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
            $districts = DB::table('districts')
                ->where('division_id', '=', $division)
                ->where('status', '=', 'active')
                ->select('id', $name)
                ->get();

            return response()->json([
                'data' => $districts
            ]);
        } catch (Throwable $exception) {
            report($exception);
            return response()->json(collect());
        }
    }


    /**
     * @return JsonResponse
     */
    public function getAllDistricts(): JsonResponse
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
            $districts = DB::table('districts')
                ->where('status', '=', 'active')
                ->select('id', $name)
                ->get();

            return response()->json([
                'data' => $districts
            ]);
        } catch (Throwable $exception) {
            report($exception);
            return response()->json(collect());
        }
    }
}
