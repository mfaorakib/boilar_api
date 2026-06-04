<?php

namespace App\Http\Controllers\Common;

use App\Models\Activity;
use App\Http\Controllers\Controller;
use App\Http\Resources\Common\ActivityResource;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;

class ActivityController extends Controller
{
    /**
     * Get all activity logs.
     *
     * @param Request $request
     * @return JsonResponse|AnonymousResourceCollection
     * @throws AuthorizationException
     */
    public function getActivityLogs(Request $request)
    {
        $this->authorize('view activity');

        try {
            $per_page = $request->query('per_page');
            $query = $request->query('query');
            if (!$query) {
                $activities = Activity::with('causer')->latest()->paginate($per_page);

                return ActivityResource::collection($activities);
            } else {
                $activities = Activity::search($query)->paginate($per_page);

                return ActivityResource::collection($activities);
            }


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

    /**
     * Delete all activities log.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function destroyAll(Request $request)
    {
        $this->authorize('delete activity');

        try {
            // delete all activity log
            $ids = explode(',', $request->query('ids'));
            DB::table(config('activitylog.table_name'))
                ->whereIn('id', $ids)
                ->delete();

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
