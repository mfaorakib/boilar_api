<?php

namespace App\Http\Controllers\Common;

use App\Models\Common\Tag;
use App\Models\Common\TagTranslation;
use App\Http\Controllers\Controller;
use App\Http\Resources\Common\TagResource;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Str;
use Throwable;

class TagController extends Controller
{

    /**
     * Display a listing of the resource.
     * @param Request $request
     * @param $locale
     * @return AnonymousResourceCollection
     * @throws AuthorizationException
     */
    public function index(Request $request, $locale)
    {
        App::setLocale($locale);

        $this->authorize('viewAny tag');

        // $per_page = empty($request->query('per_page')) ? 10 : (int)$request->query('per_page');
        // $tags = Tag::latest()->paginate($per_page);

        $query = $request->query('query');
        $sortBy = $request->query('sortBy');
        $direction = $request->query('direction');
        $per_page = $request->query('per_page', 10);

        $tags = Tag::latest();
        if ($query) {
            $tags = Tag::whereTranslationLike('name', '%' . $query . '%');
        }
        if ($sortBy) {
            $tags = Tag::orderBy($sortBy, $direction);
        }
        if ($per_page === '-1') {
            $results = $tags->get();
            $tags = new LengthAwarePaginator($results, $results->count(), -1);
        } else {
            $tags = $tags->paginate($per_page);
        }

        return TagResource::collection($tags);
    }

    /**
     * Get all tags
     *
     * @param $locale
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function getAll($locale)
    {
        App::setLocale($locale);

        $this->authorize('viewAny tag');

        $tags = DB::table('tags as t')
            ->join('tag_translations as tt', 't.id', '=', 'tt.tag_id')
            ->select('t.id', 'tt.name')
            ->where('tt.locale', '=', $locale)
            ->get();

        return response()->json([
            'data' => $tags
        ], 200);
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

        $this->authorize('create tag');

        // begin database transaction
        DB::beginTransaction();
        try {
            $tag = Tag::create();

            // commit changes
            DB::commit();
            return response()->json([
                'message' => Lang::get('crud.create'),
                'tagId' => $tag->id
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
     * Show tag.
     *
     * @param $locale
     * @param Tag $tag
     * @return TagResource|JsonResponse
     */
    public function show($locale, Tag $tag)
    {
        App::setLocale($locale);

        try {
            return new TagResource($tag);
        } catch (Throwable $exception) {
            report($exception);
            return response()->json([
                'message' => Lang::get('crud.error')
            ], 404);
        }
    }

    /**
     * Edit tag.
     *
     * @param $locale
     * @param Tag $tag
     * @return TagResource|JsonResponse
     */
    public function edit($locale, Tag $tag)
    {
        App::setLocale($locale);

        try {
            return new TagResource($tag);
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
     * @param Tag $tag
     * @return JsonResponse
     * @throws AuthorizationException
     * @throws \Illuminate\Validation\ValidationException
     */
    public function update(Request $request, $locale, Tag $tag)
    {
        App::setLocale($locale);

        $this->authorize('update tag');

        $this->validate($request, [
            'name' => 'required'
        ]);

        // begin database transaction
        DB::beginTransaction();
        try {
            $slug = $this->checkSlug($locale, $request->get('name'));
            $request->merge([
                'slug' => $slug
            ]);

            $tag->update($request->all());

            // commit changes
            DB::commit();
            return response()->json([
                'message' => Lang::get('crud.update'),
                'all' => $request->all()
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
     * @param Tag $tag
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function destroy($locale, Tag $tag)
    {
        App::setLocale($locale);

        $this->authorize('delete tag');

        // begin database transaction
        DB::beginTransaction();
        try {
            $tag->delete();

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
     * Get trashed tags
     *
     * @param Request $request
     * @param $locale
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function getTrashed(Request $request, $locale)
    {
        App::setLocale($locale);

        $this->authorize('viewAny tag');

        $per_page = empty($request->query('per_page')) ? 10 : (int)$request->query('per_page');

        $tags = Tag::onlyTrashed()->latest()->paginate($per_page);

        return response()->json([
            'data' => $tags
        ], 200);
    }

    /**
     * Restore all trashed tags
     *
     * @param Request $request
     * @param $locale
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function restoreTrashed(Request $request, $locale)
    {
        App::setLocale($locale);

        $this->authorize('restore tag');

        $ids = explode(',', $request->get('ids'));

        // begin database transaction
        DB::beginTransaction();
        try {
            if (isset($ids)) {
                Tag::onlyTrashed()
                    ->whereIn('id', $ids)
                    ->restore();
            } else {
                Tag::onlyTrashed()->restore();
            }

            // commit database
            DB::commit();
            // return success message
            return response()->json([
                'message' => Lang::get('crud.restore')
            ], 200);
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
     * Restore single trashed tag
     *
     * @param $locale
     * @param $id
     * @return mixed
     * @throws AuthorizationException
     */
    public function restoreSingleTrashed($locale, $id)
    {
        App::setLocale($locale);

        $this->authorize('restore tag');

        // begin database transaction
        DB::beginTransaction();
        try {
            Tag::onlyTrashed()
                ->where('id', '=', $id)
                ->restore();

            // commit database
            DB::commit();
            // return success message
            return response()->json([
                'message' => Lang::get('crud.restore')
            ], 200);
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
     * Permanently delete all trashed tags
     *
     * @param Request $request
     * @param $locale
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function forceDelete(Request $request, $locale)
    {
        App::setLocale($locale);

        $this->authorize('forceDelete tag');

        $ids = explode(',', $request->get('ids'));

        // begin database transaction
        DB::beginTransaction();
        try {
            if (isset($ids)) {
                $tags = Tag::onlyTrashed()
                    ->whereIn('id', $ids)
                    ->get();
            } else {
                $tags = Tag::onlyTrashed()->get();
            }
            foreach ($tags as $tag) {
                // delete related products
                $tag->products()->detach();
                // delete tag
                $tag->forceDelete();
            }

            // commit database
            DB::commit();
            // return success message
            return response()->json([
                'message' => Lang::get('crud.delete')
            ], 200);
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
     * Permanently delete single trashed tag
     *
     * @param $locale
     * @param $id
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function forceSingleDelete($locale, $id)
    {
        App::setLocale($locale);

        $this->authorize('forceDelete tag');

        // begin database transaction
        DB::beginTransaction();
        try {
            $tag = Tag::onlyTrashed()
                ->where('id', '=', $id);

            // delete related products
            $tag->products()->detach();
            // delete tag
            $tag->forceDelete();

            // commit database
            DB::commit();
            // return success message
            return response()->json([
                'message' => Lang::get('crud.delete')
            ], 200);
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
     * @param $locale
     * @param $title
     * @return string
     */
    public function checkSlug($locale, $title)
    {
        try {
            $slug = Str::slug($title, '-', $locale);
            # slug repeat check
            $latest = TagTranslation::where('slug', '=', $slug)
                ->latest('id')
                ->value('slug');

            if ($latest) {
                $pieces = explode('-', $latest);
                $number = intval(end($pieces));
                $slug .= '-' . ($number + 1);
            }
            return $slug;
        } catch (Throwable $exception) {
            // log exception
            report($exception);
            // return failed message
            return null;
        }
    }
}
