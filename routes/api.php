<?php

use App\Http\Controllers\Api\Rental\Admin\BikeAdminController;
use App\Http\Controllers\Api\Rental\Admin\KycAdminController;
use App\Http\Controllers\Api\Rental\AuthController;
use App\Http\Controllers\Api\Rental\BookingController;
use App\Http\Controllers\Api\Rental\CatalogController;
use App\Http\Controllers\Api\Rental\KycController;
use App\Http\Controllers\Api\Rental\NotificationController;
use App\Http\Controllers\Api\Rental\PaymentController;
use App\Http\Controllers\Api\Rental\ProfileController;
use App\Http\Controllers\Api\Rental\WalletController;
use App\Http\Controllers\Api\Hub\AuthController as HubAuthController;
use App\Http\Controllers\Api\Hub\BookingController as HubBookingController;
use App\Http\Controllers\Api\Hub\DashboardController as HubDashboardController;
use App\Http\Controllers\Api\Hub\FleetController as HubFleetController;
use App\Http\Controllers\Api\Hub\IncidentController as HubIncidentController;
use App\Http\Controllers\Api\Hub\MaintenanceController as HubMaintenanceController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| ZIPPI Rental API — v1
|--------------------------------------------------------------------------
| Mirrors the contracts documented in APIs/*.md. All routes are prefixed
| with /api (Laravel) + /rental/v1 here.
*/

Route::prefix('rental/v1')->group(function () {

    // ---- Public auth ----
    Route::post('auth/register', [AuthController::class, 'register']);
    Route::post('auth/login', [AuthController::class, 'login']);
    Route::post('auth/verify-otp', [AuthController::class, 'verifyOtp']);

    // ---- Public catalog (browsing allowed pre-auth) ----
    Route::get('catalog/categories', [CatalogController::class, 'categories']);
    Route::get('catalog/bikes', [CatalogController::class, 'bikes']);
    Route::get('catalog/bikes/{bike}', [CatalogController::class, 'show']);
    Route::get('catalog/bikes/{bike}/availability', [CatalogController::class, 'availability']);

    // ---- Authenticated rider routes ----
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('auth/me', [AuthController::class, 'me']);
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::post('auth/logout-all', [AuthController::class, 'logoutAll']);

        // Profile
        Route::get('profile', [ProfileController::class, 'show']);
        Route::put('profile', [ProfileController::class, 'update']);
        Route::get('profile/addresses', [ProfileController::class, 'addresses']);
        Route::post('profile/addresses', [ProfileController::class, 'storeAddress']);
        Route::delete('profile/addresses/{address}', [ProfileController::class, 'destroyAddress']);
        Route::get('profile/notification-preferences', [ProfileController::class, 'notificationPreferences']);
        Route::put('profile/notification-preferences', [ProfileController::class, 'updateNotificationPreferences']);
        Route::post('profile/device-token', [ProfileController::class, 'deviceToken']);

        // KYC
        Route::get('kyc', [KycController::class, 'show']);
        Route::post('kyc/documents', [KycController::class, 'upload']);
        Route::post('kyc/submit', [KycController::class, 'submit']);

        // Pricing quote (auth, but KYC not required to see price)
        Route::post('bookings/quote', [BookingController::class, 'quote']);

        // Booking create is KYC-gated + idempotent
        Route::post('bookings', [BookingController::class, 'store'])
            ->middleware(['kyc.approved', 'idempotency']);

        Route::get('bookings', [BookingController::class, 'index']);
        Route::get('bookings/{booking}', [BookingController::class, 'show']);
        Route::post('bookings/{booking}/cancel', [BookingController::class, 'cancel']);
        Route::post('bookings/{booking}/unlock', [BookingController::class, 'unlock']);
        Route::post('bookings/{booking}/return', [BookingController::class, 'returnBike']);

        // Payments
        Route::post('payments/verify', [PaymentController::class, 'verify'])->middleware('idempotency');
        Route::get('payments/{reference}', [PaymentController::class, 'get']);

        // Wallet
        Route::get('wallet', [WalletController::class, 'show']);
        Route::get('wallet/transactions', [WalletController::class, 'transactions']);

        // Notifications
        Route::get('notifications', [NotificationController::class, 'index']);
        Route::get('notifications/unread-count', [NotificationController::class, 'unreadCount']);
        Route::post('notifications/{notification}/read', [NotificationController::class, 'markRead']);
        Route::post('notifications/read-all', [NotificationController::class, 'markAllRead']);
    });

    // ---- Admin routes (Sanctum + role) ----
    Route::prefix('admin')->middleware(['auth:sanctum'])->group(function () {
        Route::middleware('role:kyc_reviewer,super_admin')->group(function () {
            Route::get('kyc', [KycAdminController::class, 'index']);
            Route::get('kyc/{userId}', [KycAdminController::class, 'show']);
            Route::post('kyc/{userId}/approve', [KycAdminController::class, 'approve']);
            Route::post('kyc/{userId}/reject', [KycAdminController::class, 'reject']);
        });
        Route::middleware('role:ops,super_admin')->group(function () {
            Route::post('bikes', [BikeAdminController::class, 'store']);
            Route::put('bikes/{bike}/status', [BikeAdminController::class, 'updateStatus']);
        });
    });
});

/*
|--------------------------------------------------------------------------
| ZIPPI Hub Operations API — v1 (additive, hub-staff only)
|--------------------------------------------------------------------------
| Consumed by the Hub Operations mobile app. All protected routes require a
| HubStaff Sanctum token (auth:sanctum + hub.staff) and are scoped to the
| authenticated staff member's hub. Does not touch the rider API above.
*/
Route::prefix('hub/v1')->group(function () {

    // Public auth (employee_code + password)
    Route::post('auth/login', [HubAuthController::class, 'login']);

    Route::middleware(['auth:sanctum', 'hub.staff'])->group(function () {
        Route::get('auth/me', [HubAuthController::class, 'me']);
        Route::post('auth/logout', [HubAuthController::class, 'logout']);

        // Dashboard (counts + upcoming pickups + due returns)
        Route::get('dashboard', [HubDashboardController::class, 'index']);

        // Bookings — lookup + pickup/return workflows
        Route::get('bookings/search', [HubBookingController::class, 'search']);
        Route::get('pickups', [HubBookingController::class, 'pickups']);
        Route::get('rentals/active', [HubBookingController::class, 'activeRentals']);
        Route::get('bookings/{booking}', [HubBookingController::class, 'show']);
        Route::post('bookings/{booking}/handover', [HubBookingController::class, 'handover']);
        Route::post('bookings/{booking}/return', [HubBookingController::class, 'returnBike']);

        // Fleet (read-only visibility)
        Route::get('fleet', [HubFleetController::class, 'index']);
        Route::get('fleet/{bike}', [HubFleetController::class, 'show']);

        // Maintenance + Incident reporting
        Route::post('maintenance', [HubMaintenanceController::class, 'store']);
        Route::post('incidents', [HubIncidentController::class, 'store']);
    });
});
