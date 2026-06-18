<?php

return [
    // GST percentage applied to the rental base amount.
    'gst_percent' => env('RENTAL_GST_PERCENT', 18),

    // Flat platform/convenience fee in paise.
    'platform_fee_paise' => env('RENTAL_PLATFORM_FEE', 2000),

    // Checkout hold time-to-live in minutes (reserves the bike during payment).
    'hold_ttl_minutes' => env('RENTAL_HOLD_TTL', 10),

    // OTP behaviour.
    'otp' => [
        'length' => 6,
        'ttl_seconds' => 300,
        'max_attempts' => 5,
        'resend_cooldown_seconds' => 30,
        'max_resends' => 3,
    ],

    // Cancellation refund policy by lead time (hours before start => fraction of rental refunded).
    // Deposit is always fully refunded on cancellation.
    'cancellation_tiers' => [
        ['min_hours_before' => 24, 'rental_refund_fraction' => 1.0],
        ['min_hours_before' => 6, 'rental_refund_fraction' => 0.5],
        ['min_hours_before' => 0, 'rental_refund_fraction' => 0.0],
    ],

    // Late return penalty per started hour (paise).
    'late_penalty_per_hour_paise' => env('RENTAL_LATE_PENALTY', 5000),

    // Admin refunds at or above this amount (paise) require a super_admin.
    // Default ₹5,000. See Admin-Dashboard/01-Admin-Modules.md §3.6.
    'refund_super_admin_threshold_paise' => env('RENTAL_REFUND_SUPER_ADMIN_THRESHOLD', 500000),

    'razorpay' => [
        'key_id' => env('RAZORPAY_KEY_ID', 'rzp_test_key'),
        'key_secret' => env('RAZORPAY_KEY_SECRET', 'rzp_test_secret'),
        'webhook_secret' => env('RAZORPAY_WEBHOOK_SECRET', 'whsec_test'),
        'live_mode' => env('RAZORPAY_LIVE_MODE', false),
    ],
];
