<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class RoleMiddleware
{
    protected $requiredRoles;

    public function __construct(...$roles)
    {
        $this->requiredRoles = $roles;
    }

    /**
     * Handle an incoming request.
     */
    public function handle($request, Closure $next, ...$roles)
    {
        // Ambil token dari header
        $authHeader = $request->header('Authorization');
        if (! $authHeader) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $token = str_replace('Bearer ', '', $authHeader);

        try {
            $decoded = JWT::decode($token, new Key(env('JWT_SECRET'), 'HS256'));
            $user = User::find($decoded->sub);
            if (! $user) {
                return response()->json(['error' => 'User not found'], 404);
            }

            // Ambil roles user
            $userRoles = $user->roles()->pluck('name')->toArray();

            // Admin bisa akses semua
            if (in_array('Admin', $userRoles)) {
                return $next($request);
            }

            // Periksa apakah ada role yang cocok
            $allowed = array_intersect($roles, $userRoles);
            if (empty($allowed)) {
                return response()->json(['error' => 'Forbidden'], 403);
            }

            $request->user = $user;

            return $next($request);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Invalid token: '.$e->getMessage()], 401);
        }
    }
}
