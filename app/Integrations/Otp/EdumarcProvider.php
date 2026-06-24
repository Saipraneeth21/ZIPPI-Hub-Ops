<?php

namespace App\Integrations\Otp;

use App\Integrations\Contracts\OtpProvider;
use Illuminate\Support\Facades\Http;

/**
 * Production Edumarc SMS OTP provider. Builds the message from a DLT-approved
 * template (the OTP replaces the {otp} placeholder) and posts it to Edumarc.
 * Mirrors Msg91Provider; swap the binding in RentalServiceProvider per env.
 */
class EdumarcProvider implements OtpProvider
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $senderId,
        private readonly string $templateId,
        private readonly string $messageTemplate = 'Your ZIPPI OTP is {otp}. Valid for 5 minutes. Do not share it with anyone.',
        private readonly string $endpoint = 'https://smsapi.edumarcsms.com/api/v1/sendsms',
    ) {}

    public function send(string $mobile, string $otp): void
    {
        $message = str_replace('{otp}', $otp, $this->messageTemplate);

        Http::withHeaders(['apikey' => $this->apiKey])->post($this->endpoint, [
            'number' => $mobile,
            'message' => $message,
            'senderId' => $this->senderId,
            'templateId' => $this->templateId,
        ]);
    }
}
