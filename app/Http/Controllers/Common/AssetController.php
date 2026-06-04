<?php

namespace App\Http\Controllers\Common;

use App\Models\Common\Asset;
use App\Models\Common\AssetCategory;
use App\Http\Resources\Common\AssetResource;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Illuminate\Support\Facades\Storage;
use Throwable;

class AssetController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return Response
     * @return AnonymousResourceCollection
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $files = Asset::with('categories')
            ->where('name', 'like', '%' . $request->get('query') . '%')
            ->orderByDesc('id');

        // Query by category
        $category_id = $request->query('category');
        if ($request->has('category') && $category_id !== 'all') {
            
            $ancestors = AssetCategory::ancestorsAndSelf($category_id)->pluck('id');
            $descendants = AssetCategory::descendantsAndSelf($category_id)->pluck('id');

            // get category ids
            $category_ids = collect($ancestors)->merge($descendants)->unique()->values()->toArray();
            $asset_ids = DB::table('asset_category_asset')
                ->whereIn('asset_category_id', $category_ids)
                ->pluck('asset_id')
                ->toArray();

            $files = $files->whereIn('id', $asset_ids);
        }

        $files = $files->cursorPaginate(20);

        // return response()->json($files);
        return AssetResource::collection($files);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|max:' . config('media-library.max_file_size')
        ]);

        // begin database transaction
        DB::beginTransaction();
        try {
            $file = $request->file('file');
            $asset = new Asset();
            $asset->save();

            $extension = $file->getClientOriginalExtension();
            $originalName = $file->getClientOriginalName();
            $name = Str::slug(pathinfo($originalName, PATHINFO_FILENAME));
            $images = ['jpeg', 'jpg', 'png', 'gif', 'svg+xml', 'webp', 'bmp'];
            $media = ['mpeg', 'ogg', '3gpp', '3gpp2', 'wav', 'webm', 'amr', 'mp4'];
            if (in_array($extension, $images)) {
                $collection = 'images';
                $type = 'Image';
            } elseif (in_array($extension, $media)) {
                $collection = 'media';
                $type = 'Media';
            } else {
                $collection = 'files';
                $type = 'File';
            }

            $asset->addMedia($file)
                ->usingName($name)
                ->usingFileName($name . '.' . $extension)
                ->toMediaCollection($collection);
            $asset->update([
                'src' => $asset->getFirstMediaUrl($collection),
                'type' => $type,
                'name' => $originalName 
            ]);

            // Asset categories   
            if ($request->filled('categories')) {
                $categories = json_decode($request->get('categories'));
                $items = collect($categories)->pluck('id');
                $asset->categories()->sync($items);
            }

            // commit changes
            DB::commit();
            return response()->json([
                'message' => Lang::get('crud.upload'),
                'location' => $asset->src,
                'id' => $asset->id,
                'name' => $originalName,
            ], 201);
        } catch (Throwable $exception) {
            report($exception);
            // rollback changes
            DB::rollBack();
            return response()->json([
                'message' => Lang::get('crud.error'),
                'error' => $exception->getMessage()
            ], 400);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param \App\Models\Common\Asset $asset
     * @return Response
     */
    public function show(Asset $asset)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param \App\Models\Common\Asset $asset
     * @return Response
     */
    public function update(Request $request, Asset $asset)
    {
        $request->validate([
            'name' => 'required',
            'categories' => 'required',
        ]);

        // begin database transaction
        DB::beginTransaction();
        try {
          

            if ($request->filled('name')) {
                $file_name = $request->get('name');
                $asset->update([
                    'name' => $file_name 
                ]);

                Media::where('model_id', '=', $asset->id)
                    ->update([ 'name' => $file_name ]);
            }

            // Asset categories   
            if ($request->filled('categories')) {
                $categories = $request->get('categories');
                $items = collect($categories)->pluck('id');
                $asset->categories()->sync($items);
            }

            // commit changes
            DB::commit();
            return response()->json([
                'message' => Lang::get('crud.update'),
                'location' => $asset->src,
                'id' => $asset->id,
                'name' => $asset->name,
            ], 200);
        } catch (Throwable $exception) {
            report($exception);
            // rollback changes
            DB::rollBack();
            return response()->json([
                'message' => Lang::get('crud.error'),
                'error' => $exception->getMessage()
            ], 400);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Models\Common\Asset $asset
     * @return Response
     */
    public function destroy(Asset $asset)
    {
         // begin database transaction
         DB::beginTransaction();
         try {
             $asset->delete();
 
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
                 'message' => Lang::get('crud.error'),
                 'error' => $exception->getMessage(),
             ], 400);
 
        }
    }
}
