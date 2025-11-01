<?php

namespace App\Http\Controllers;

use App\Models\User;
use Firebase\JWT\JWT;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $this->validate($request, [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $roles = $user->roles()->pluck('name')->toArray();

        $payload = [
            'iss' => 'lumen-jwt',
            'sub' => $user->id,
            'email' => $user->email,
            'roles' => $roles,
            'iat' => time(),
            'exp' => time() + 60 * 60,
            // 'exp' => time() + 5,
        ];

        $token = JWT::encode($payload, env('JWT_SECRET'), 'HS256');

        return response()->json([
            'message' => 'Login success',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'department_id' => $user->department_id,
                'roles' => $roles,
            ],
        ]);
    }
}
