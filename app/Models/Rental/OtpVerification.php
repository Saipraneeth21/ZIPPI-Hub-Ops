<?php
namespace App\Models\Rental;
use Illuminate\Database\Eloquent\Model;
class OtpVerification extends Model
{
    protected $table = 'otp_verifications';
    protected $guarded = [];
    protected $casts = ['expires_at' => 'datetime', 'verified_at' => 'datetime'];
    public function isExpired(): bool { return $this->expires_at->isPast(); }
}
