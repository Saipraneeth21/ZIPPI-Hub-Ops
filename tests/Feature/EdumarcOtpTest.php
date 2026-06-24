<?php

namespace Tests\Feature;

use App\Integrations\Contracts\OtpProvider;
use App\Integrations\Otp\EdumarcProvider;
use App\Integrations\Otp\LogOtpProvider;
use App\Integrations\Otp\Msg91Provider;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class EdumarcOtpTest extends TestCase
{
    public function test_edumarc_provider_posts_otp_sms_with_expected_payload(): void
    {
        Http::fake(['*' => Http::response(['success' => true], 200)]);

        (new EdumarcProvider('test-key', 'ZIPPI', 'tmpl-1', 'Your ZIPPI OTP is {otp}.'))
            ->send('9876543210', '123456');

        Http::assertSent(fn ($request) => $request->hasHeader('apikey', 'test-key')
            && $request['number'] === '9876543210'
            && $request['senderId'] === 'ZIPPI'
            && $request['templateId'] === 'tmpl-1'
            && str_contains($request['message'], '123456'));
    }

    public function test_container_resolves_provider_from_config(): void
    {
        config()->set('rental.otp.provider', 'edumarc');
        config()->set('rental.otp.edumarc', [
            'api_key' => 'k', 'sender_id' => 's', 'template_id' => 't',
            'message_template' => 'OTP {otp}', 'endpoint' => 'https://example.test/send',
        ]);
        $this->assertInstanceOf(EdumarcProvider::class, app(OtpProvider::class));
    }

    public function test_container_defaults_to_log_provider(): void
    {
        config()->set('rental.otp.provider', 'log');
        $this->assertInstanceOf(LogOtpProvider::class, app(OtpProvider::class));
    }

    public function test_container_resolves_msg91_when_configured(): void
    {
        config()->set('rental.otp.provider', 'msg91');
        config()->set('rental.otp.msg91', ['auth_key' => 'a', 'template_id' => 'b']);
        $this->assertInstanceOf(Msg91Provider::class, app(OtpProvider::class));
    }
}
