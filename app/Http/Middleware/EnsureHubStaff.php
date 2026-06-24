<?php

namespace App\Http\Middleware;

use App\Models\Rental\HubStaff;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Hub Operations gate: the Sanctum-authenticated token must belong to an active
 * HubStaff. Keeps rider routes untouched while hard-isolating the Hub Ops API.
 * Usage: `->middleware(['auth:sanctum', 'hub.staff'])`.
 */
class EnsureHubStaff
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof HubStaff || ! $user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Hub staff access required.',
                'data' => null,
                'errors' => null,
            ], 403);
        }

        return $next($request);
    }
}
