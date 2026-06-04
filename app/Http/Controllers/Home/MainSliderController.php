<?php

namespace App\Http\Controllers\Home;

use App\Models\Home\MainSlider;
use App\Http\Controllers\Controller;
use App\Http\Resources\Home\MainSliderEditResource;
use App\Http\Resources\Home\MainSliderResource;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Throwable;

class MainSliderController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @param $locale
     * @return AnonymousResourceCollection
     * @throws AuthorizationException
     */
    public function index(Request $request, $locale): AnonymousResourceCollection
    {
        App::setLocale($locale);

        $this->authorize('viewAny main_slider');
        $per_page = empty($request->query('per_page')) ? 10 : (int)$request->query('per_page');
        $sliders = MainSlider::latest()
            ->whereIsRoot()
            ->paginate($per_page);

        return \App\Http\Resources\Home\MainSliderResource::collection($sliders);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param $locale
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function store($locale): JsonResponse
    {
        App::setLocale($locale);

        $this->authorize('create main_slider');

        // begin database transaction
        DB::beginTransaction();
        try {
            $slider = MainSlider::create();

            // commit changes
            DB::commit();
            return response()->json([
                'message' => Lang::get('crud.create'),
                'sliderId' => $slider->id
            ], 201);
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
     * Show slider.
     *
     * @param $locale
     * @param MainSlider $slider
     * @return MainSliderEditResource|JsonResponse
     */
    public function show($locale, MainSlider $slider)
    {
        App::setLocale($locale);

        try {
            return new MainSliderEditResource($slider);
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
     * @param MainSlider $slider
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function update(Request $request, $locale, MainSlider $slider): JsonResponse
    {
        App::setLocale($locale);

        $this->authorize('update main_slider');

        $request->validate([
            'title' => 'nullable',
            'subtitle' => 'nullable',
            'url' => 'required|url',
            'type' => 'required',
            'link' => 'nullable|url',
        ]);

        // begin database transaction
        DB::beginTransaction();
        try {

            $slider->update($request->all());

            // commit changes
            DB::commit();
            return response()->json([
                'message' => Lang::get('crud.update')
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
     * Remove the specified resource from storage.
     *
     * @param $locale
     * @param MainSlider $slider
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function destroy($locale, MainSlider $slider): JsonResponse
    {
        App::setLocale($locale);

        $this->authorize('delete main_slider');

        // begin database transaction
        DB::beginTransaction();
        try {
            $slider->delete();

            // commit changes
            DB::commit();
            return response()->json([
                'message' => Lang::get('crud.delete')
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
     * Rebuild slider parent children.
     *
     * @param Request $request
     * @param $locale
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function rebuildTree(Request $request, $locale): JsonResponse
    {
        App::setLocale($locale);

        $this->authorize('update main_slider');

        // begin database transaction
        DB::beginTransaction();
        try {
            // rearrange slider
            MainSlider::rebuildTree($request->all());

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
                'message' => Lang::get('crud.error')
            ], 400);
        }
    }

    /**
     * Main slider.
     *
     * @param $locale
     * @return AnonymousResourceCollection
     */
    public function getAllSliders($locale):AnonymousResourceCollection
    {
        App::setLocale($locale);

        try {

            $sliders = MainSlider::latest()
                    ->whereIsRoot()
                    ->where('status', '=', 'active')
                    ->paginate();

                return MainSliderResource::collection($sliders);
        } catch (Throwable $exception) {
            report($exception);
            return response()->json([
                'message' => Lang::get('crud.error')
            ], 404);
        }
    }

}
