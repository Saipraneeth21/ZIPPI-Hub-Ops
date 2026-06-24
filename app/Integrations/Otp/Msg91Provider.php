<?php

namespace App\Integrations\Otp;

use App\Integrations\Contracts\OtpProvider;
use Illuminate\Support\Facades\Http;

/** Production MSG91 OTP provider. */
class Msg91Provider implements OtpProvider
{
    public function __construct(
        private readonly string $authKey,
        private readonly string $templateId,
        private readonly string $endpoint = 'https://control.msg91.com/api/v5/otp',
    ) {}

    public function send(string $mobile, string $otp): void
    {
        Http::withHeaders(['authkey' => $this->authKey])->post($this->endpoint, [
            'template_id' => $this->templateId,
            'mobile' => '91'.$mobile,
            'otp' => $otp,
        ]);
    }
}
