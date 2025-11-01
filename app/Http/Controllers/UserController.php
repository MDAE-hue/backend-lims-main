<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Helpers\ActivityLogger;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with(['department', 'roles', 'superiorUser'])->get();
        return response()->json($users);
    }

    public function show($id)
    {
        $user = User::with(['department', 'roles', 'superiorUser'])->findOrFail($id);
        return response()->json($user);
    }

    public function store(Request $request)
{
    try {
        // Validasi input
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'department_id' => 'required|exists:departments,id',
            'roles' => 'array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed, check your input.',
                'messages' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        // Buat user baru
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => app('hash')->make($validated['password']),
            'department_id' => $validated['department_id'],
            'npk' => $request->npk ?? null,
            'job_title' => $request->job_title ?? null,
            'superior' => $request->superior ?? null,
        ]);

        // Assign roles jika ada
        if (!empty($validated['roles'])) {
            $user->roles()->attach($validated['roles']);
        }

        // Log aktivitas
        ActivityLogger::log(
            'CREATE',
            'users',
            $user->id,
            null,
            $user->toArray(),
            Auth::user()->name . " created a new user [{$user->id}]"
        );

        // Return sukses
        return response()->json([
            'message' => 'User created successfully',
            'user' => $user->load(['department', 'roles']),
        ], 201);

    } catch (\Illuminate\Database\QueryException $e) {
        // Error terkait database, misal duplicate key
        Log::error('Database error while creating user: ' . $e->getMessage());
        return response()->json([
            'error' => 'Database error',
            'details' => env('APP_DEBUG') ? $e->getMessage() : null,
        ], 500);

    } catch (\Exception $e) {
        // Error umum lainnya
        Log::error('Error while creating user: ' . $e->getMessage());
        return response()->json([
            'error' => 'Failed to create user',
            'details' => env('APP_DEBUG') ? $e->getMessage() : null,
        ], 500);
    }
}

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $oldData = $user->toArray();

        $this->validate($request, [
            'email' => 'email|unique:users,email,'.$id,
            'department_id' => 'nullable|exists:departments,id',
            'roles' => 'array',
        ]);

        $data = $request->only(['name', 'email', 'department_id', 'npk', 'job_title', 'superior']);
        $data['department_id'] = $data['department_id'] ?: null;
        $data['superior'] = $request->input('superior', null);


        $user->update($data);

        if ($request->filled('password')) {
            $user->password = app('hash')->make($request->password);
            $user->save();
        }

        if ($request->has('roles')) {
    if (!empty($request->roles)) {
        $user->roles()->sync($request->roles);
    }
}


        ActivityLogger::log(
            'UPDATE',
            'users',
            $user->id,
            $oldData,
            $user->toArray(),
            auth()->user()->name . " updated user [{$user->id}]"
        );

        return response()->json([
    'message' => 'User updated successfully',
    'data' => $user->load(['department', 'roles']),
]);

    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $oldData = $user->toArray();
        $user->roles()->detach();
        $user->delete();

        ActivityLogger::log(
            'DELETE',
            'users',
            $id,
            $oldData,
            null,
            auth()->user()->name . " deleted user [{$id}]"
        );

        return response()->json(['message' => 'User deleted successfully']);
    }

    public function assignRole(Request $request, $id)
    {
        $this->validate($request, [
            'roles' => 'required|array',
            'roles.*' => 'exists:roles,id',
        ]);

        $user = User::findOrFail($id);
        $oldData = $user->roles->pluck('id')->toArray();

        $user->roles()->sync($request->roles);

        ActivityLogger::log(
            'ASSIGN_ROLE',
            'users',
            $user->id,
            ['roles' => $oldData],
            ['roles' => $request->roles],
            auth()->user()->name . " updated roles for user [{$user->id}]"
        );

        return response()->json([
            'message' => 'Roles updated successfully',
            'user' => $user->load(['department', 'roles']),
        ]);
    }

    public function changePassword(Request $request)
    {
        $this->validate($request, [
            'oldPassword' => 'required',
            'newPassword' => 'required|min:6',
        ]);

        $user = auth()->user();
        if (! $user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        if (! app('hash')->check($request->oldPassword, $user->password)) {
            return response()->json(['error' => 'Old password is incorrect'], 400);
        }

        $oldData = $user->toArray();
        $user->password = app('hash')->make($request->newPassword);
        $user->save();

        ActivityLogger::log(
            'CHANGE_PASSWORD',
            'users',
            $user->id,
            $oldData,
            $user->toArray(),
            $user->name . " changed their password"
        );

        return response()->json(['message' => 'Password successfully updated']);
    }
}
