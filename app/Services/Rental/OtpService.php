<?php
namespace App\Services\Rental;

use App\Integrations\Contracts\OtpProvider;
use App\Models\Rental\OtpVerification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

/** Issues and verifies OTPs. OTP is hashed at rest and never returned/logged in prod. */
class OtpService
{
    public function __construct(private readonly OtpProvider $provider) {}

    /** @return array{otp_token: string, expires_in: int, resend_after: int} */
    public function issue(string $mobile, string $purpose = 'login'): array
    {
        $cfg = config('rental.otp');
        $otp = $this->generateCode($cfg['length']);
        $token = (string) Str::uuid();

        OtpVerification::create([
            'otp_token' => $token,
            'mobile' => $mobile,
            'otp_hash' => Hash::make($otp),
            'purpose' => $purpose,
            'expires_at' => Carbon::now()->addSeconds($cfg['ttl_seconds']),
        ]);

        $this->provider->send($mobile, $otp);

        return [
            'otp_token' => $token,
            'expires_in' => $cfg['ttl_seconds'],
            'resend_after' => $cfg['resend_cooldown_seconds'],
            // exposed only so non-prod/tests can complete the flow; remove in prod
            'debug_otp' => app()->environment('production') ? null : $otp,
        ];
    }

    /** Verifies the OTP for a token. Returns the verified mobile or throws. */
    public function verify(string $token, string $otp): string
    {
        $cfg = config('rental.otp');
        $record = OtpVerification::where('otp_token', $token)->first();

        if (! $record) {
            throw new RuntimeException('Invalid OTP token.');
        }
        if ($record->verified_at) {
            throw new RuntimeException('OTP already used.');
        }
        if ($record->isExpired()) {
            throw new RuntimeException('OTP expired.');
        }
        if ($record->attempts >= $cfg['max_attempts']) {
            throw new RuntimeException('Maximum OTP attempts exceeded.');
        }

        $record->increment('attempts');

        if (! Hash::check($otp, $record->otp_hash)) {
            throw new RuntimeException('Incorrect OTP.');
        }

        $record->update(['verified_at' => Carbon::now()]);

        return $record->mobile;
    }

    private function generateCode(int $length): string
    {
        $min = (int) str_pad('1', $length, '0');
        $max = (int) str_pad('', $length, '9', STR_PAD_LEFT) ?: 999999;
        return (string) random_int($min, (int) str_repeat('9', $length));
    }
}
