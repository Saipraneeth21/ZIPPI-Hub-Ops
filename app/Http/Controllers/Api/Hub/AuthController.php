<?php

namespace App\Http\Controllers\Api\Hub;

use App\Models\Rental\HubStaff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/** Hub Operations API auth: employee_code + password → Sanctum token. */
class AuthController extends HubController
{
    public function login(Request $r)
    {
        $data = $r->validate([
            'employee_code' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $staff = HubStaff::where('employee_code', $data['employee_code'])->first();

        if (! $staff || ! Hash::check($data['password'], $staff->password)) {
            return $this->fail('Invalid credentials.', null, 401);
        }
        if (! $staff->is_active) {
            return $this->fail('Account is inactive. Contact your administrator.', null, 403);
        }

        $token = $staff->createToken('hub-app', ['hub'])->plainTextToken;

        return $this->ok([
            'token' => $token,
            'token_type' => 'Bearer',
            'staff' => $this->profile($staff),
        ], 'Login successful');
    }

    public function me(Request $r)
    {
        return $this->ok($this->profile($this->staff()));
    }

    public function logout(Request $r)
    {
        $r->user()->currentAccessToken()->delete();

        return $this->ok(null, 'Logged out');
    }

    private function profile(HubStaff $staff): array
    {
        $staff->loadMissing('hub');

        return [
            'id' => $staff->id,
            'name' => $staff->name,
            'role' => $staff->role,
            'employee_code' => $staff->employee_code,
            'is_active' => (bool) $staff->is_active,
            'hub' => $staff->hub ? [
                'id' => $staff->hub->id,
                'name' => $staff->hub->name,
                'address' => $staff->hub->address,
            ] : null,
        ];
    }
}
