<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Blocks booking/payment routes until the rider's KYC is approved. */
class EnsureKycApproved
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user || ! $user->isKycApproved()) {
            return response()->json([
                'success' => false,
                'message' => 'KYC verification is required before booking.',
                'data' => null,
                'errors' => ['kyc' => ['Complete KYC to proceed.']],
            ], 403);
        }
        return $next($request);
    }
}
