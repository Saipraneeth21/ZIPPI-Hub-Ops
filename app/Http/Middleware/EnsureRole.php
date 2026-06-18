<?php
namespace App\Http\Middleware;

use App\Enums\AdminRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Admin RBAC: usage `->middleware('role:ops,super_admin')`. */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $admin = $request->user();
        // AdminUser casts `role` to AdminRole; normalise to its string value
        // so this matches the string roles declared on the route.
        $role = $admin?->role instanceof AdminRole ? $admin->role->value : ($admin->role ?? null);
        if (! $admin || ! in_array($role, $roles, true)) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient permissions.',
                'data' => null,
                'errors' => null,
            ], 403);
        }
        return $next($request);
    }
}
