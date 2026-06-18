<?php
namespace App\Http\Controllers\Api\Rental;

use App\Http\Controllers\Controller;
use App\Models\Rental\Address;
use App\Models\Rental\DeviceToken;
use App\Models\Rental\NotificationPreference;
use App\Support\Concerns\ApiResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    use ApiResponse;

    public function show(Request $r)
    {
        $u = $r->user()->load('profile', 'wallet');
        return $this->ok([
            'id' => $u->id, 'name' => $u->name, 'mobile' => $u->mobile, 'email' => $u->email,
            'kyc_status' => $u->profile?->kyc_status ?? 'none',
            'wallet_balance' => (int) ($u->wallet?->balance ?? 0),
            'is_blocked' => $u->isBlocked(),
        ]);
    }

    public function update(Request $r)
    {
        $data = $r->validate([
            'name' => ['sometimes', 'string', 'min:2', 'max:100'],
            'email' => ['sometimes', 'email', 'unique:users,email,' . $r->user()->id],
        ]);
        $r->user()->update($data);
        return $this->ok($data, 'Profile updated');
    }

    public function addresses(Request $r)
    {
        return $this->ok($r->user()->addresses()->get());
    }

    public function storeAddress(Request $r)
    {
        $data = $r->validate([
            'label' => ['required', 'in:home,work,other'],
            'line1' => ['required', 'string', 'max:255'],
            'line2' => ['nullable', 'string', 'max:255'],
            'city_id' => ['required', 'exists:cities,id'],
            'pincode' => ['required', 'digits:6'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'is_default' => ['boolean'],
        ]);
        $address = $r->user()->addresses()->create($data);
        return $this->created(['id' => $address->id], 'Address added');
    }

    public function destroyAddress(Request $r, Address $address)
    {
        abort_unless($address->user_id === $r->user()->id, 403);
        $address->delete();
        return $this->ok(null, 'Address deleted');
    }

    public function notificationPreferences(Request $r)
    {
        $pref = NotificationPreference::firstOrCreate(['user_id' => $r->user()->id]);
        return $this->ok($pref);
    }

    public function updateNotificationPreferences(Request $r)
    {
        $data = $r->validate([
            'push_promotional' => ['boolean'],
            'sms_promotional' => ['boolean'],
            'email_promotional' => ['boolean'],
        ]);
        $pref = NotificationPreference::firstOrCreate(['user_id' => $r->user()->id]);
        $pref->update($data);
        return $this->ok(null, 'Preferences updated');
    }

    public function deviceToken(Request $r)
    {
        $data = $r->validate([
            'fcm_token' => ['required', 'string'],
            'platform' => ['required', 'in:android,ios'],
        ]);
        DeviceToken::updateOrCreate(
            ['fcm_token' => $data['fcm_token']],
            ['user_id' => $r->user()->id, 'platform' => $data['platform'], 'last_seen_at' => now()]
        );
        return $this->ok(null, 'Device registered');
    }
}
