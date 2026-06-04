<?php

namespace App\Http\Controllers\Product;

use App\Models\Product\ProductSlider;
use App\Http\Controllers\Controller;
use App\Http\Resources\Product\ProductSliderEditResource;
use App\Http\Resources\Product\ProductSliderResource;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Throwable;

class ProductSliderController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @param $locale
     * @return AnonymousResourceCollection
     * @throws AuthorizationException
     */
    public function index(Request $request, $locale)
    {
        App::setLocale($locale);

        $this->authorize('viewAny product_slider');

        $per_page = empty($request->query('per_page')) ? 10 : (int)$request->query('per_page');
        $sliders = ProductSlider::with('product')->latest()->paginate($per_page);

        return ProductSliderResource::collection($sliders);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param $locale
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function store($locale)
    {
        App::setLocale($locale);

        $this->authorize('create product_slider');

        // begin database transaction
        DB::beginTransaction();
        try {
            $slider = ProductSlider::create();

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
     * @param ProductSlider $slider
     * @return ProductSliderEditResource|JsonResponse
     */
    public function show($locale, ProductSlider $slider)
    {
        App::setLocale($locale);

        try {
            return new ProductSliderEditResource($slider);
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
     * @param ProductSlider $slider
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function update(Request $request, $locale, ProductSlider $slider)
    {
        App::setLocale($locale);

        $this->authorize('update product_slider');

        $request->validate([
            'product_id' => 'required',
            'image' => 'required|url',
        ]);

        // begin database transaction
        DB::beginTransaction();
        try {

            $slider->update($request->all());

            if ($request->has('images')) {
                $slider->images()->delete();
                $slider->images()->createMany($request->input('images'));
            }

            // commit changes
            DB::commit();
            return response()->json([
                'message' => Lang::get('crud.update')
            ], 200);
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
     * @param ProductSlider $slider
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function destroy($locale, ProductSlider $slider)
    {
        App::setLocale($locale);

        $this->authorize('delete product_slider');

        // begin database transaction
        DB::beginTransaction();
        try {
            $slider->delete();

            // commit changes
            DB::commit();
            return response()->json([
                'message' => Lang::get('crud.delete')
            ], 200);
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
