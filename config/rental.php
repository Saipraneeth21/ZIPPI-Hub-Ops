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

        // SMS delivery provider: 'log' (dev — writes to storage/logs), 'edumarc', or 'msg91'.
        'provider' => env('OTP_PROVIDER', 'log'),

        'edumarc' => [
            'api_key' => env('EDUMARC_API_KEY'),
            'sender_id' => env('EDUMARC_SENDER_ID'),
            // DLT-approved template id registered with Edumarc.
            'template_id' => env('EDUMARC_TEMPLATE_ID'),
            'endpoint' => env('EDUMARC_ENDPOINT', 'https://smsapi.edumarcsms.com/api/v1/sendsms'),
            // The OTP is substituted for the {otp} placeholder. Must match the DLT template text.
            'message_template' => env('EDUMARC_MESSAGE_TEMPLATE', 'Your ZIPPI OTP is {otp}. Valid for 5 minutes. Do not share it with anyone.'),
        ],

        'msg91' => [
            'auth_key' => env('MSG91_AUTH_KEY'),
            'template_id' => env('MSG91_TEMPLATE_ID'),
        ],
    ],

    // KYC verification provider: 'auto' (manual admin review — default) or 'quickkyc'.
    'kyc' => [
        'provider' => env('KYC_PROVIDER', 'auto'),
        'quickkyc' => [
            'api_key' => env('QUICKKYC_API_KEY'),
            'base_url' => env('QUICKKYC_BASE_URL', 'https://api.quickkyc.com'),
            'dl_path' => env('QUICKKYC_DL_PATH', '/verify/driving-license'),
            // Where in the JSON response the pass/fail signal lives, and its success value.
            'success_path' => env('QUICKKYC_SUCCESS_PATH', 'status'),
            'success_value' => env('QUICKKYC_SUCCESS_VALUE', 'success'),
        ],
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
