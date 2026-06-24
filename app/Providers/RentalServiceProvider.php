<?php

namespace App\Providers;

use App\Integrations\Contracts\KycProvider;
use App\Integrations\Contracts\OtpProvider;
use App\Integrations\Contracts\PaymentGateway;
use App\Integrations\Contracts\PushProvider;
use App\Integrations\Kyc\AutoKycProvider;
use App\Integrations\Kyc\QuickkycProvider;
use App\Integrations\Otp\EdumarcProvider;
use App\Integrations\Otp\LogOtpProvider;
use App\Integrations\Otp\Msg91Provider;
use App\Integrations\Payment\RazorpayGateway;
use App\Integrations\Push\LogPushProvider;
use Illuminate\Support\ServiceProvider;

/**
 * Binds rental integration contracts to concrete adapters. Swap the bindings
 * here (e.g. LogOtpProvider -> Msg91Provider) per environment without touching
 * the services that depend on the interfaces.
 */
class RentalServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PaymentGateway::class, function () {
            $cfg = config('rental.razorpay');

            return new RazorpayGateway(
                $cfg['key_id'], $cfg['key_secret'], $cfg['webhook_secret'], (bool) $cfg['live_mode']
            );
        });

        // OTP delivery: selected by config('rental.otp.provider'). Defaults to
        // the log provider for local dev (OTP is written to storage/logs).
        $this->app->singleton(OtpProvider::class, function () {
            $otp = config('rental.otp');

            return match ($otp['provider'] ?? 'log') {
                'edumarc' => new EdumarcProvider(
                    (string) ($otp['edumarc']['api_key'] ?? ''),
                    (string) ($otp['edumarc']['sender_id'] ?? ''),
                    (string) ($otp['edumarc']['template_id'] ?? ''),
                    (string) ($otp['edumarc']['message_template'] ?? 'Your ZIPPI OTP is {otp}. Valid for 5 minutes. Do not share it with anyone.'),
                    (string) ($otp['edumarc']['endpoint'] ?? 'https://smsapi.edumarcsms.com/api/v1/sendsms'),
                ),
                'msg91' => new Msg91Provider(
                    (string) ($otp['msg91']['auth_key'] ?? ''),
                    (string) ($otp['msg91']['template_id'] ?? ''),
                ),
                default => new LogOtpProvider,
            };
        });

        // KYC verification: selected by config('rental.kyc.provider'). Defaults to
        // 'auto' (AutoKycProvider) which routes every submission to manual admin review.
        $this->app->singleton(KycProvider::class, function () {
            return match (config('rental.kyc.provider', 'auto')) {
                'quickkyc' => new QuickkycProvider(config('rental.kyc.quickkyc', [])),
                default => new AutoKycProvider,
            };
        });

        // Dev/test bindings; replace with FCM in production.
        $this->app->singleton(PushProvider::class, LogPushProvider::class);
    }
}
