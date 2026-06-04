<?php

namespace App\Http\Controllers\Common;

use App\Models\Activity;
use App\Http\Controllers\Controller;
use App\Http\Resources\Common\ActivityResource;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;

class CacheManagementController extends Controller
{
    /**
     * Get all supported artisan commands.
     *
     * @return JsonResponse|AnonymousResourceCollection
     * @throws AuthorizationException
     */
    public function getArtisanCommands()
    {
        $this->authorize('view cache_management');

        try {
            $commands = collect();
            foreach (config('helper.artisan_commands') as $command => $details) {
                $commands->push(['key' => $command, 'text' => $details['text'], 'class' => $details['class']]);
            }

            return response()->json($commands, 200);
        } catch (\Exception $exception) {
            report($exception);
            return response()->json([
                'message' => Lang::get('crud.error')
            ], 400);
        }
    }

    public function runArtisanCommand($command)
    {
        $this->authorize('update cache_management');

        try {
            Artisan::call($command);

            return response()->json([
                'message' => Lang::get('crud.update')
            ], 200);
        } catch (\Exception $exception) {
            report($exception);
            return response()->json([
                'message' => Lang::get('crud.error')
            ], 400);
        }
    }

    /**
     * Delete activity log.
     *
     * @param Activity $activity
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function destroy(Activity $activity)
    {
        $this->authorize('delete activity');

        try {
            // delete activity log
            $activity->delete();

            return response()->json([
                'message' => Lang::get('crud.delete')
            ], 200);
        } catch (\Exception $exception) {
            report($exception);
            return response()->json([
                'message' => Lang::get('crud.error')
            ], 400);
        }
    }

}
