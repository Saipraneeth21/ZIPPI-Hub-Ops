<?php
namespace App\Support\Concerns;

use Illuminate\Http\JsonResponse;

/** Consistent response envelope used by all rental API controllers. */
trait ApiResponse
{
    protected function ok($data = null, string $message = '', array $meta = [], int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'errors' => null,
            'meta' => (object) $meta,
        ], $status);
    }

    protected function created($data = null, string $message = 'Created'): JsonResponse
    {
        return $this->ok($data, $message, [], 201);
    }

    protected function fail(string $message, $errors = null, int $status = 422): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data' => null,
            'errors' => $errors,
        ], $status);
    }
}
