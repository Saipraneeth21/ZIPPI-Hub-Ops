<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Requires an Idempotency-Key header on money-mutating POSTs and exposes it to
 * controllers via the request attribute bag. Replay protection itself is
 * enforced at the data layer (unique idempotency_key columns).
 */
class IdempotencyKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $key = $request->header('Idempotency-Key');
        if (! $key) {
            return response()->json([
                'success' => false,
                'message' => 'Idempotency-Key header is required for this operation.',
                'data' => null,
                'errors' => ['idempotency' => ['Missing Idempotency-Key header.']],
            ], 422);
        }
        $request->attributes->set('idempotency_key', $key);
        return $next($request);
    }
}
