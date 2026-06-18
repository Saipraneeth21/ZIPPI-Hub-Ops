<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Rental\KycDocument;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Streams a KYC document behind a short-TTL signed URL (Admin-Dashboard
 * §3.3). Access requires both a valid signature AND an authenticated admin
 * with the `kyc.review` ability — the signed link alone is not enough.
 */
class KycDocumentController extends Controller
{
    /** TTL for generated document links, in minutes. */
    public const LINK_TTL_MINUTES = 5;

    /** Build a signed, expiring URL for a document (used by the Filament UI). */
    public static function signedUrl(KycDocument $document): string
    {
        return URL::temporarySignedRoute(
            'admin.kyc.document',
            now()->addMinutes(self::LINK_TTL_MINUTES),
            ['document' => $document->id],
        );
    }

    public function __invoke(KycDocument $document): StreamedResponse
    {
        abort_unless(auth('admin')->user()?->can('kyc.review'), 403);

        $disk = Storage::disk(config('filesystems.default'));
        abort_unless($disk->exists($document->file_path), 404);

        return $disk->response($document->file_path);
    }
}
