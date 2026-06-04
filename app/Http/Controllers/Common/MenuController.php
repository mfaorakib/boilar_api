<?php

namespace App\Http\Controllers\Common;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Http\Resources\Common\MenuPublicResource;
use App\Http\Resources\Common\MenuResource;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Illuminate\Validation\ValidationException;

class MenuController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @return AnonymousResourceCollection
     * @throws AuthorizationException
     */
    public function index()
    {
        $this->authorize('viewAny menu');

        $menus = Menu::with(['roles', 'children' => function($q) {
            $q->with('roles')->defaultOrder()->get();
        }])->whereIsRoot()->defaultOrder()->get();

        return MenuResource::collection($menus);
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException|Exception
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create menu');

        $this->validate($request, [
            'title' => 'required|min:3'
        ]);
        // begin database transaction
        DB::beginTransaction();
        try {
            // create menu
            $menu = Menu::create($request->all());
            $menu->roles()->attach($request->input('roles'));

            // commit database
            DB::commit();
            // return success message
            return response()->json([
                'message' => Lang::get('crud.create')
            ], 201);
        } catch (Exception $exception) {
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
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param Menu $menu
     * @return JsonResponse
     * @throws ValidationException|Exception
     */
    public function update(Request $request, Menu $menu): JsonResponse
    {
        $this->authorize('update menu');

        $this->validate($request, [
            'title' => 'required|min:3'
        ]);
        // begin database transaction
        DB::beginTransaction();
        try {
            // update menu
            $roles = $request->input('roles');
            if ($request->has('parent_id')) {
                $menu->children()->create($request->except('children', 'parent_id'));
                foreach ($menu->children as $child) {
                    $child->roles()->sync($roles);
                }
                $menu->roles()->syncWithoutDetaching($roles);
                // commit database
                DB::commit();
                // return success message
                return response()->json([
                    'message' => Lang::get('crud.create')
                ]);
            } else {
                $menu->update($request->except('children'));
                $menu->roles()->sync($roles);
                // Only propagate role access to the parent when the menu
                // actually has a parent (i.e. it is a child node). Root nodes
                // have no parent — calling $menu->parent->roles() on a root
                // node returns null and throws a fatal error, rolling back the
                // entire transaction and silently discarding the saved data.
                if ($request->filled('link') && $menu->parent !== null) {
                    $menu->parent->roles()->syncWithoutDetaching($roles);
                }
                // commit database
                DB::commit();
                // return success message
                return response()->json([
                    'message' => Lang::get('crud.update')
                ]);
            }
        } catch (Exception $exception) {
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
     * Remove the specified resource from storage.
     *
     * @param Menu $menu
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function destroy(Menu $menu): JsonResponse
    {
        $this->authorize('delete menu');

        try {
            // delete menu
            $menu->roles()->detach();
            $menu->delete();

            return response()->json([
                'message' => Lang::get('crud.delete')
            ]);
        } catch (Exception $exception) {
            report($exception);
            return response()->json([
                'message' => Lang::get('crud.error')
            ], 400);
        }
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function rebuildTree(Request $request): JsonResponse
    {
        $this->authorize('update menu');

        // begin database transaction
        DB::beginTransaction();
        try {
            // rearrange menu
            Menu::rebuildTree($request->all());

            // commit database
            DB::commit();
            // return success message
            return response()->json([
                'message' => Lang::get('crud.update')
            ]);
        } catch (Exception $exception) {
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
     * Get all menus by user permissions.
     *
     * @return JsonResponse|AnonymousResourceCollection
     */
    public function getMenus()
    {

        try {
            // get user role
            $role = Auth::guard('web')->user()->roles()->first();
     
            $ids = DB::table('menu_role')
                ->where('role_id', '=', $role->id)
                ->pluck('menu_id');
            // get menus
            $menus = $role->menus()->with(['children' => function ($child) use ($ids) {
                $child->active()->defaultOrder()->whereIn('id', $ids)->get();
            }])->whereIsRoot()
                ->defaultOrder()
                ->active()
                ->get();

            // return success message
            return MenuPublicResource::collection($menus);
        } catch (Exception $exception) {
            // log exception
            report($exception);
            // return failed message
            return response()->json([
                'message' => Lang::get('crud.error')
            ], 400);
        }
    }
}
