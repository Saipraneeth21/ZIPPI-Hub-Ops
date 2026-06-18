<?php
namespace App\Integrations\Otp;

use App\Integrations\Contracts\OtpProvider;
use Illuminate\Support\Facades\Log;

/** Dev/test OTP provider — logs the OTP instead of sending SMS. Swap for Msg91Provider in prod. */
class LogOtpProvider implements OtpProvider
{
    public function send(string $mobile, string $otp): void
    {
        Log::info("[OTP] {$otp} -> {$mobile}");
    }
}
