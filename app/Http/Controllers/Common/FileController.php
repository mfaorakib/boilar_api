<?php

namespace App\Http\Controllers\Common;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Throwable;

class FileController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     */
    public function index()
    {
        $collections = Media::latest()
                      ->where('collection_name','!=', 'avatar')
                        ->get();

        $files = collect($collections)->map(function ($item) {
            return [
                'id' => $item->id,
                'name' => $item->name,
                'url' => $item->getUrl(),
                'src' => $item->getUrl(),
                'srcset' => $item->getSrcset(),
                'lazy' => $item->responsiveImages()->getPlaceholderSvg(),
                'size' => $item->human_readable_size,
                'mime_type' => $item->mime_type,
                'type' => Str::ucfirst(Str::plural(Str::beforeLast($item->mime_type, '/')))
            ];
        })->groupBy('type');

        return response()->json([
            'data' => $files
        ]);
    }

    /**
     * Get a single media.
     *
     * @param Media $file
     * @return JsonResponse
     */
    public function show(Media $file)
    {
        try {
            return response()->json([
                'src' => $file->getFullUrl(),
                'srcset' => $file->getSrcset(),
                'lazy' => $file->responsiveImages()->getPlaceholderSvg()
            ], 200);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => Lang::get('crud.fetch_error')
            ], 200);
        }
    }

    /**
     * Delete media.
     *
     * @param Media $file
     * @return JsonResponse
     */
    public function destroy(Media $file)
    {
        // begin database transaction
        DB::beginTransaction();
        try {
            if ($file->model_type === 'App\Models\Common\Asset') {
                DB::table('assets')
                    ->where('id', '=', $file->model_id)
                    ->delete();
            }
            $file->delete();

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

    /**
     * Download media.
     *
     * @param Media $file
     * @return JsonResponse|\Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function downloadFile(Media $file)
    {
        try {
            return response()->download($file->getPath(), $file->file_name);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => Lang::get('crud.fetch_error')
            ], 200);
        }
    }
}
