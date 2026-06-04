<?php

namespace App\Http\Controllers\User;

use App\Models\Role;
use App\Http\Controllers\Controller;
use App\Http\Resources\User\RoleEditResource;
use App\Http\Resources\User\RoleResource;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Spatie\Permission\Models\Permission;

class RoleController extends Controller
{

    /**
     * Get all permissions as tree.
     *
     * @return JsonResponse
     */
    public function getPermissions()
    {
        try {
            $tree = [];

            $permissions = Permission::all();

            foreach ($permissions as $permission) {
                list($action, $model) = explode(' ', $permission->name);


                if (!isset($tree[$model])) {
                    $tree[$model] = [];
                }
                $tree[$model][$permission->id] = $action;
            }

            return response()->json($tree, 200);
        } catch (\Exception $exception) {
            report($exception);
            return response()->json(collect());
        }
    }

    /**
     * Get all roles.
     *
     * @return JsonResponse
     */
    public function getAllRoles()
    {
        $roles = Role::select('label', 'name')->get();
        return response()->json($roles, 200);
    }

    /**
     * Get all roles for menu page.
     *
     * @return JsonResponse
     */
    public function getRoles()
    {
        $roles = Role::select('label', 'id')->get();
        return response()->json($roles, 200);
    }

    /**
     * Get latest roles by pagination.
     *
     * @param Request $request
     * @return AnonymousResourceCollection
     * @throws AuthorizationException
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny role');

        $query = $request->query('query');
        $sortBy = $request->query('sortBy');
        $direction = $request->query('direction');
        $per_page = $request->query('per_page', 10);

        $roles = Role::latest();
        if ($query) {
            $roles = Role::search($request->query('query'));
        }
        if ($sortBy) {
            $roles = Role::orderBy($sortBy, $direction);
        }
        if ($per_page === '-1') {
            $results = $roles->get();
            $roles = new LengthAwarePaginator($results, $results->count(), -1);
        } else {
            $roles = $roles->paginate($per_page);
        }
        return RoleResource::collection($roles);
    }

    /**
     * Get roles by search results
     *
     * @param Request $request
     * @return AnonymousResourceCollection
     * @throws AuthorizationException
     */
    public function getBySearch(Request $request)
    {
        $this->authorize('view role');

        $per_page = empty($request->query('per_page')) ? 10 : (int)$request->query('per_page');
        $roles = Role::search($request->query('query'))->paginate($per_page);
        return RoleResource::collection($roles);
    }

    /**
     * Get roles by sorting
     *
     * @param Request $request
     * @return AnonymousResourceCollection
     * @throws AuthorizationException
     */
    public function getByOrder(Request $request)
    {
        $this->authorize('view role');

        $per_page = empty($request->query('per_page')) ? 10 : (int)$request->query('per_page');
        $direction = $request->query('direction');
        $sortBy = $request->query('sortBy');
        $roles = Role::withCount('users')->orderBy($sortBy, $direction)->paginate($per_page);
        return RoleResource::collection($roles);
    }

    /**
     * Store new role into database.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws \Illuminate\Validation\ValidationException|\Exception
     */
    public function store(Request $request)
    {
        $this->authorize('create role');

        // validate request
        $data = $this->validate($request, [
            'name' => 'required',
            'label' => 'required',
            'disable_login' => 'nullable'
        ]);

        // begin database transaction
        DB::beginTransaction();
        try {
            $data['guard_name'] = 'api';
            // create role
            $role = Role::create($data);
            // check, if request has permissions
            if ($request->filled('permissions')) {
                // assign permissions to role
                $role->syncPermissions($request->input('permissions'));
            }
            // commit database
            DB::commit();
            // return success message
            return response()->json([
                'message' => Lang::get('crud.create')
            ], 201);
        } catch (\Exception $exception) {
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
     * Edit role.
     *
     * @param Role $role
     * @return RoleEditResource
     * @throws AuthorizationException
     */
    public function edit(Role $role)
    {
        $this->authorize('update role');

        return new RoleEditResource($role);
    }

    /**
     * Update record into database.
     *
     * @param Request $request
     * @param Role $role
     * @return JsonResponse
     * @throws \Illuminate\Validation\ValidationException|\Exception
     */
    public function update(Request $request, Role $role)
    {
        $this->authorize('update role');

        // validate request
        $data = $this->validate($request, [
            'name' => 'required',
            'label' => 'required',
            'disable_login' => 'nullable'
        ]);

        // begin database transaction
        DB::beginTransaction();
        try {
            // update role
            $role->update($data);
            // check, if request has permissions
            if ($request->filled('permissions')) {
                // assign permissions to role
                $role->syncPermissions($request->input('permissions'));
            }
            // commit database
            DB::commit();
            // return success message
            return response()->json([
                'message' => Lang::get('crud.update')
            ], 200);
        } catch (\Exception $exception) {
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
     * Delete role from database.
     *
     * @param Role $role
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function destroy(Role $role)
    {
        $this->authorize('delete role');

        try {
            // delete role
            $role->delete();
            // delete permissions associated with this role.
            $role->syncPermissions([]);

            // return success message
            return response()->json([
                'message' => Lang::get('crud.delete')
            ], 200);
        } catch (\Exception $exception) {
            // log exception
            report($exception);
            // return failed message
            return response()->json([
                'message' => Lang::get('crud.error')
            ], 400);
        }
    }

    /**
     * Add permission to associate role.
     *
     * @param Request $request
     * @param Role $role
     * @return JsonResponse
     * @throws AuthorizationException
     * @throws \Illuminate\Validation\ValidationException
     */
    public function addPermissions(Request $request, Role $role)
    {
        $this->authorize('update role');

        // validate request
        $this->validate($request, [
            'permissions' => 'required',
        ]);

        // begin database transaction
        DB::beginTransaction();
        try {
            // assign permissions to role
            $role->syncPermissions($request->input('permissions'));
            // commit database
            DB::commit();
            // return success message
            return response()->json([
                'message' => Lang::get('crud.update')
            ], 201);
        } catch (\Exception $exception) {
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

    public function getPermissionsByRole($role)
    {
        $permissions = DB::table('role_has_permissions')
            ->where('role_id', '=', $role)
            ->pluck('permission_id');

        return response()->json($permissions, 200);
    }
}
