<?php

namespace App\Http\Controllers\User;

use App\Models\User;
use App\Http\Resources\User\UserEditResource;
use App\Http\Resources\User\UserResource;
use App\Http\Resources\User\UserSingleResource;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;

class UserController extends Controller
{

    /**
     * Get all users.
     *
     * @param Request $request
     * @return AnonymousResourceCollection
     * @throws AuthorizationException
     */
    public function index(Request $request)
    {
        $this->authorize('view user');

        $query = $request->query('query');
        $sortBy = $request->query('sortBy');
        $direction = $request->query('direction');
        $per_page = $request->query('per_page', 10);

        $users = User::latest();
        if ($query) {
            $users = $users->where('name', 'like', '%' . $query . '%');
        }
        if ($sortBy) {
            $users = User::orderBy($sortBy, $direction);
        }
        if ($per_page === '-1') {
            $results = $users->get();
            $users = new LengthAwarePaginator($results, $results->count(), -1);
        } else {
            $users = $users->paginate($per_page);
        }

        return UserResource::collection($users);
    }

    /**
     * Get user by search results
     *
     * @param Request $request
     * @return AnonymousResourceCollection
     * @throws AuthorizationException
     */
    public function getBySearch(Request $request)
    {
        $this->authorize('view user');

        $per_page = empty($request->query('per_page')) ? 10 : (int)$request->query('per_page');
        $users = User::search($request->query('query'))
            ->paginate($per_page);

        return UserResource::collection($users);
    }

    /**
     * Get user by sorting
     *
     * @param Request $request
     * @return AnonymousResourceCollection
     * @throws AuthorizationException
     */
    public function getByOrder(Request $request)
    {
        $this->authorize('view user');

        $per_page = empty($request->query('per_page')) ? 10 : (int)$request->query('per_page');
        $direction = $request->query('direction');
        $sortBy = $request->query('sortBy');
        $users = User::orderBy($sortBy, $direction)
            ->paginate($per_page);
        return UserResource::collection($users);
    }

    /**
     * Store new user into database.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws \Illuminate\Validation\ValidationException|\Exception
     */
    public function store(Request $request)
    {
        $this->authorize('create user');

        // validate request
        $data = $this->validate($request, [
            'name' => 'required',
            'address' => 'nullable',
            'status' => 'required',
            'mobile' => 'nullable|unique:users',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8',
            'role' => 'required'
        ]);

        // begin database transaction
        DB::beginTransaction();
        try {
            // check if request has email verified option
            if ($request->input('email_verified_at')) {
                $data['email_verified_at'] = now()->toDateTimeString();
            } else {
                $data['email_verified_at'] = null;
            }
            // create user
            $user = User::create($data);
            // assign role to user
            $user->assignRole($request->input('role'));
            // fire register event to send email verification link
            if (!$request->input('email_verified_at')) {
                event(new Registered($user));
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
     * Get single user.
     *
     * @param User $user
     * @return UserSingleResource
     */
    public function show(User $user)
    {
        return new UserSingleResource($user);
    }

    /**
     * Edit user.
     *
     * @param User $user
     * @return UserEditResource
     * @throws AuthorizationException
     */
    public function edit(User $user)
    {
        $this->authorize('update user');

        return new UserEditResource($user);
    }

    /**
     * Update record into database.
     *
     * @param Request $request
     * @param User $user
     * @return JsonResponse
     * @throws \Illuminate\Validation\ValidationException|\Exception
     */
    public function update(Request $request, User $user)
    {
        $this->authorize('update user');

        // validate request
        $data = $this->validate($request, [
            'name' => 'required',
            'mobile' => 'nullable|unique:users,mobile,' . $user->id,
            'address' => 'nullable',
            'status' => 'required',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'password' => 'nullable|min:8',
            'role' => 'required'
        ]);

        // begin database transaction
        DB::beginTransaction();
        try {
            // check if request has email verified option
            if ($request->input('email_verified_at')) {
                $data['email_verified_at'] = now()->toDateTimeString();
            } else {
                $data['email_verified_at'] = null;
            }
            // update user
            $user->update($data);
            // sync role to user
            $user->syncRoles($request->input('role'));
            // fire register event to send email verification link
            if (!$request->input('email_verified_at')) {
                event(new Registered($user));
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
     * Delete user from database.
     *
     * @param User $user
     * @return JsonResponse
     * @throws AuthorizationException | \Exception
     */
    public function destroy(User $user)
    {
        $this->authorize('delete user');
        // begin database transaction
        DB::beginTransaction();
        try {
            // find all posts related to user and assign to current user.
            DB::table('posts')
                ->where('user_id', '=', $user->id)
                ->update(['user_id' => auth()->id()]);
            // find all audios related to user and assign to current user.
            DB::table('audios')
                ->where('user_id', '=', $user->id)
                ->update(['user_id' => auth()->id()]);
            // delete user
            $user->delete();
            // delete roles associated with this user.
            $user->syncRoles([]);
            // commit changes
            DB::commit();
            // return success message
            return response()->json([
                'message' => Lang::get('crud.delete')
            ], 200);
        } catch (\Exception $exception) {
            // log exception
            report($exception);
            // rollback changes
            DB::rollBack();
            // return failed message
            return response()->json([
                'message' => Lang::get('crud.error')
            ], 400);
        }
    }

    /**
     * @param Request $request
     * @param User $user
     * @return JsonResponse
     * @throws \Illuminate\Validation\ValidationException | \Exception
     */
    public function updateUserInfo(Request $request, User $user)
    {
        $data = $this->validate($request, [
            'name' => 'required',
            'mobile' => 'nullable|unique:users,mobile,' . $user->id,
            'address' => 'nullable',
            'email' => 'required|email|unique:users,email,' . $user->id,
        ]);
        // begin database transaction
        DB::beginTransaction();
        try {
            // update user
            $user->update($data);
            // commit changes
            DB::commit();
            JsonResource::withoutWrapping();
            // return success message
            return response()->json([
                'message' => Lang::get('crud.update'),
                'user' => new UserSingleResource($user)
            ], 200);
        } catch (\Exception $exception) {
            // log exception
            report($exception);
            // rollback changes
            DB::rollBack();
            // return failed message
            return response()->json([
                'message' => Lang::get('crud.error')
            ], 400);
        }
    }

    /**
     * Update user password.
     *
     * @param Request $request
     * @param User $user
     * @return JsonResponse
     * @throws \Illuminate\Validation\ValidationException|\Exception
     */
    public function updateUserPassword(Request $request, User $user)
    {
        $data = $this->validate($request, [
            'password' => 'required|min:8|confirmed'
        ]);
        // begin database transaction
        DB::beginTransaction();
        try {
            // update user
            $user->update($data);
            // commit changes
            DB::commit();
            // return success message
            return response()->json([
                'message' => Lang::get('crud.update'),
                'user' => new UserSingleResource($user)
            ], 200);
        } catch (\Exception $exception) {
            // log exception
            report($exception);
            // rollback changes
            DB::rollBack();
            // return failed message
            return response()->json([
                'message' => Lang::get('crud.error')
            ], 400);
        }
    }

    /**
     * Upload user avatar.
     *
     * @param Request $request
     * @param User $user
     * @return JsonResponse
     * @throws \Illuminate\Validation\ValidationException|\Exception
     */
    public function updateUserAvatar(Request $request, User $user)
    {
        $this->validate($request, [
            'avatar' => 'required|image'
        ]);
        // begin database transaction
        DB::beginTransaction();
        try {
            $image = $request->file('avatar');
            $url = $user->addMedia($image)->toMediaCollection('avatar')->getFullUrl();
            // update user
            $user->update([
                'avatar' => $url
            ]);
            // commit changes
            DB::commit();
            // return success message
            return response()->json([
                'message' => Lang::get('crud.update'),
                'user' => new UserSingleResource($user)
            ], 200);
        } catch (\Exception $exception) {
            // log exception
            report($exception);
            // rollback changes
            DB::rollBack();
            // return failed message
            return response()->json([
                'message' => Lang::get('crud.error')
            ], 400);
        }
    }
}
