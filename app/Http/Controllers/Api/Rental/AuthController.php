<?php
namespace App\Http\Controllers\Api\Rental;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Rental\AuthService;
use App\Support\Concerns\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly AuthService $auth) {}

    public function register(Request $r)
    {
        $data = $r->validate([
            'mobile' => ['required', 'digits:10'],
            'name' => ['required', 'string', 'min:2', 'max:100'],
        ]);
        if (User::where('mobile', $data['mobile'])->exists()) {
            return $this->fail('Mobile already registered. Please log in.', null, 409);
        }
        return $this->ok($this->auth->requestRegister($data['mobile'], $data['name']), 'OTP sent successfully');
    }

    public function login(Request $r)
    {
        $data = $r->validate(['mobile' => ['required', 'digits:10']]);
        $user = User::where('mobile', $data['mobile'])->first();
        if (! $user) {
            return $this->fail('Mobile not registered.', null, 404);
        }
        if ($user->isBlocked()) {
            return $this->fail('Account is blocked.', null, 403);
        }
        return $this->ok($this->auth->requestLogin($data['mobile']), 'OTP sent');
    }

    public function verifyOtp(Request $r)
    {
        $data = $r->validate([
            'otp_token' => ['required', 'string'],
            'otp' => ['required', 'digits:6'],
            'fcm_token' => ['nullable', 'string'],
            'platform' => ['nullable', 'in:android,ios'],
        ]);
        try {
            $result = $this->auth->verifyAndIssueToken(
                $data['otp_token'], $data['otp'], $data['platform'] ?? null, $data['fcm_token'] ?? null
            );
        } catch (\RuntimeException $e) {
            return $this->fail($e->getMessage(), null, 422);
        }
        $u = $result['user'];
        return $this->ok([
            'token' => $result['token'],
            'token_type' => 'Bearer',
            'is_new_user' => $result['is_new'],
            'user' => [
                'id' => $u->id, 'name' => $u->name, 'mobile' => $u->mobile,
                'email' => $u->email, 'kyc_status' => $u->profile?->kyc_status ?? 'none',
                'is_blocked' => $u->isBlocked(),
            ],
        ], 'Login successful');
    }

    public function me(Request $r)
    {
        $u = $r->user()->load('profile');
        return $this->ok([
            'id' => $u->id, 'name' => $u->name, 'mobile' => $u->mobile,
            'email' => $u->email, 'kyc_status' => $u->profile?->kyc_status ?? 'none',
            'is_blocked' => $u->isBlocked(),
        ]);
    }

    public function logout(Request $r)
    {
        $r->user()->currentAccessToken()->delete();
        return $this->ok(null, 'Logged out');
    }

    public function logoutAll(Request $r)
    {
        $r->user()->tokens()->delete();
        return $this->ok(null, 'Logged out from all devices');
    }
}
