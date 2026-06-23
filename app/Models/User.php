<?php

namespace App\Models;

use App\Models\Rental\Address;
use App\Models\Rental\Booking;
use App\Models\Rental\KycDocument;
use App\Models\Rental\NotificationPreference;
use App\Models\Rental\Payment;
use App\Models\Rental\UserProfile;
use App\Models\Rental\Wallet;
use App\Models\Rental\WalletTransaction;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name', 'mobile', 'country_code', 'email', 'password',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function profile(): HasOne
    {
        return $this->hasOne(UserProfile::class);
    }

    public function wallet(): HasOne
    {
        return $this->hasOne(Wallet::class);
    }

    public function notificationPreference(): HasOne
    {
        return $this->hasOne(NotificationPreference::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function kycDocuments(): HasMany
    {
        return $this->hasMany(KycDocument::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function walletTransactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class);
    }

    public function isKycApproved(): bool
    {
        return $this->profile?->kyc_status === 'approved';
    }

    public function isBlocked(): bool
    {
        return (bool) ($this->profile?->is_blocked ?? false);
    }

    public function isRedFlagged(): bool
    {
        return (bool) ($this->profile?->is_red_flagged ?? false);
    }
}
