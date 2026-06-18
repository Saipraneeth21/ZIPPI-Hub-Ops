<?php
namespace App\Services\Rental;

use App\Models\Rental\NotificationPreference;
use App\Models\Rental\UserProfile;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/** Registration / login orchestration on top of OtpService + Sanctum. */
class AuthService
{
    public function __construct(private readonly OtpService $otp) {}

    public function requestRegister(string $mobile, string $name): array
    {
        $payload = $this->otp->issue($mobile, 'register');
        // Stash the intended name so verify() can set it on first creation.
        cache()->put("rental:reg_name:{$payload['otp_token']}", $name, now()->addMinutes(10));
        return $payload;
    }

    public function requestLogin(string $mobile): array
    {
        return $this->otp->issue($mobile, 'login');
    }

    /** @return array{user: User, token: string, is_new: bool} */
    public function verifyAndIssueToken(string $token, string $otp, ?string $platform = null, ?string $fcmToken = null): array
    {
        $mobile = $this->otp->verify($token, $otp);

        return DB::transaction(function () use ($mobile, $token, $platform, $fcmToken) {
            $isNew = ! User::where('mobile', $mobile)->exists();
            $name = cache()->pull("rental:reg_name:{$token}") ?? 'ZIPPI Rider';

            $user = User::firstOrCreate(
                ['mobile' => $mobile],
                ['name' => $name, 'country_code' => '+91']
            );

            // Ensure the rental satellite records exist.
            UserProfile::firstOrCreate(['user_id' => $user->id], ['kyc_status' => 'none']);
            NotificationPreference::firstOrCreate(['user_id' => $user->id]);

            if ($fcmToken) {
                \App\Models\Rental\DeviceToken::updateOrCreate(
                    ['fcm_token' => $fcmToken],
                    ['user_id' => $user->id, 'platform' => $platform ?? 'android', 'last_seen_at' => now()]
                );
            }

            $plain = $user->createToken('rental-app')->plainTextToken;

            return ['user' => $user->load('profile'), 'token' => $plain, 'is_new' => $isNew];
        });
    }
}
