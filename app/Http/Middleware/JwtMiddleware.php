<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\Auth;

class JwtMiddleware
{
    public function handle($request, Closure $next)
    {
        try {
            $token = $request->bearerToken();

            if (! $token) {
                return response()->json(['message' => 'Token not provided'], 401);
            }

            $decoded = JWT::decode($token, new Key(env('JWT_SECRET'), 'HS256'));

            $user = User::find($decoded->sub ?? null);
            if (! $user) {
                return response()->json(['message' => 'User not found'], 401);
            }

            // set user ke auth()
            Auth::setUser($user);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Invalid token',
                'error' => $e->getMessage(),
            ], 401);
        }

        return $next($request);
    }
}
