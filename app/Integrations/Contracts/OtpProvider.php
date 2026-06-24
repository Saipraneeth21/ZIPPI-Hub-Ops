<?php

namespace App\Integrations\Contracts;

interface OtpProvider
{
    /** Send an OTP code to the given mobile number. */
    public function send(string $mobile, string $otp): void;
}
