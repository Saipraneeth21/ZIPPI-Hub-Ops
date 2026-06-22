<?php

namespace App\Models\Rental;

use App\Enums\DurationType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Walk-in "Instant Dispatch": ops captures a customer's details at the counter
 * for an immediate rental. Aadhaar is stored encrypted (sensitive PII) and only
 * ever shown masked in the UI.
 */
class InstantDispatch extends Model
{
    use HasFactory;

    protected $table = 'rental_instant_dispatches';
    protected $guarded = [];

    protected $casts = [
        'aadhar_number' => 'encrypted',
        'pickup_date' => 'date',
        'rental_type' => DurationType::class,
    ];

    /** Aadhaar shown as XXXX XXXX 1234. */
    public function getAadharMaskedAttribute(): ?string
    {
        // Guard against ciphertext encrypted with a different APP_KEY: the
        // 'encrypted' cast throws DecryptException on access. Don't let one
        // bad row 500 the whole list — show it as unreadable instead.
        try {
            $raw = (string) $this->aadhar_number;
        } catch (\Throwable) {
            return '••• unreadable';
        }

        $digits = preg_replace('/\D/', '', $raw);
        if (strlen($digits) < 4) {
            return $digits ? str_repeat('X', strlen($digits)) : null;
        }

        return 'XXXX XXXX ' . substr($digits, -4);
    }
}
