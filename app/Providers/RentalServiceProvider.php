<?php

namespace App\Providers;

use App\Integrations\Contracts\KycProvider;
use App\Integrations\Contracts\OtpProvider;
use App\Integrations\Contracts\PaymentGateway;
use App\Integrations\Contracts\PushProvider;
use App\Integrations\Kyc\AutoKycProvider;
use App\Integrations\Otp\LogOtpProvider;
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

        // Dev/test bindings; replace with Msg91Provider / FCM / Digio in production.
        $this->app->singleton(OtpProvider::class, LogOtpProvider::class);
        $this->app->singleton(PushProvider::class, LogPushProvider::class);
        $this->app->singleton(KycProvider::class, AutoKycProvider::class);
    }
}
