# How to Create the Bike Rental Application

A step-by-step developer guide to building the **ZIPPI Rental** application from an empty
directory to a running, tested bike-rental backend with a REST API and an admin panel.

This document explains *how the application is put together* — the stack, the layering, the
domain model, and the order in which to build each piece. If you just want to run the existing
code, see the [README](../README.md) Quick Start. Read this when you want to understand or
reproduce the design.

---

## 1. What you are building

A bike-rental platform with two faces:

- **A REST API** (`/api/rental/v1/*`) consumed by the rider mobile app — auth, KYC, catalog
  browsing, pricing quotes, booking, payments, wallet, and notifications.
- **An admin dashboard** (`/admin`) for operations staff — KYC review, fleet management, live
  map, and push campaigns.

The critical business loop is:

```
register → verify OTP → upload KYC → admin approves KYC
        → browse catalog → get price quote → create booking (hold)
        → pay → confirm → unlock bike → ride → return → auto deposit refund
```

Every step in that loop is enforced by the application (KYC gate before booking, no
double-booking, idempotent payments, automatic refunds).

---

## 2. Technology stack

| Layer | Choice | Why |
|-------|--------|-----|
| Language | PHP 8.4.1+ | Typed enums, readonly properties, modern syntax |
| Framework | Laravel 13 | Routing, Eloquent ORM, validation, DI container |
| API auth | Laravel Sanctum | Token-based auth for the mobile client |
| Admin UI | Filament | Rapid CRUD/dashboard panels over Eloquent |
| Database | SQLite (dev) / MariaDB 10.6+ (prod) | Zero-config locally, scalable in prod |
| Frontend assets | Vite + Tailwind CSS 4 | Admin panel styling/build |
| Payments | Razorpay (adapter) | HMAC-verified, webhook-authoritative |
| OTP / Push / KYC | MSG91 / FCM / Digio (adapters) | Swappable behind interfaces |

The defining architectural decision: **every external integration sits behind an interface**,
so development uses fake/log implementations and production swaps in the real provider without
touching business logic.

---

## 3. Prerequisites

```bash
php -v        # need 8.4.1+ with pdo_sqlite enabled
composer -V   # Composer 2
node -v       # for Vite asset build (admin panel)
```

---

## 4. Scaffold the project

```bash
composer create-project laravel/laravel zippi-rental
cd zippi-rental

# API token auth for the mobile client
composer require laravel/sanctum
php artisan install:api

# Admin panel
composer require filament/filament
php artisan filament:install --panels

# Payments SDK (or your gateway of choice)
composer require razorpay/razorpay
```

Configure your environment:

```bash
cp .env.example .env
php artisan key:generate
```

For local development keep the default SQLite connection — it needs no server:

```dotenv
DB_CONNECTION=sqlite
# leave DB_DATABASE pointing at database/database.sqlite
```

---

## 5. Architecture: the layering rule

The single most important convention in this codebase. **Requests flow downward through
fixed layers, and each layer only talks to the one below it:**

```
Controller            thin — parse request, call a service, return a Resource
   ↓
FormRequest           validation rules live here, not in the controller
   ↓
Service               ALL business logic (pricing, booking, refunds, ...)
   ↓
(Repository)          data access where queries get non-trivial
   ↓
Model (Eloquent)      tables, relationships, casts
```

Cross-cutting concerns live beside this stack:

- **Middleware** guards routes (`auth:sanctum`, `kyc.approved`, `idempotency`, `role:...`).
- **API Resources** shape every JSON response (never return a raw model).
- **Integration adapters** are bound to interfaces in a service provider.
- **Enums** centralize every domain state (`KycStatus`, `BookingStatus`, ...).
- **Value objects** (`Money`) enforce invariants — money is always integer paise, never a float.

Keeping controllers thin and pushing logic into services is what makes the booking and payment
flows testable without HTTP.

---

## 6. Model the domain

Build the database from the bottom up. Group migrations by domain so they read in order
(this project uses a `rental_*` table prefix so the schema can coexist with an existing system).

**Build order (mirrors the migration groups):**

1. **Identity** — extend the shared `users` table; add `personal_access_tokens` (Sanctum),
   `otp_verifications`, `admin_users`.
2. **Profile & KYC** — `user_profiles`, `addresses`, `notification_preferences`,
   `device_tokens`, `kyc_documents`, `kyc_reviews`.
3. **Catalog** — `cities`, `hubs`, `bike_categories`, `bikes`, `bike_images`, `bike_pricing`,
   `bike_terms`.
4. **Booking** — `bookings`, `booking_status_history`, `coupons`, `coupon_redemptions`,
   `reviews`.
5. **Payments** — `payments`, `refunds`, `wallets`, `wallet_transactions`.
6. **Telemetry & admin** — `bike_telemetry`, `lock_commands`, `geofence_alerts`,
   `audit_logs`, `notifications`.

Define the **states as enums first** ([`app/Enums/Enums.php`](../app/Enums/Enums.php)) so models,
validation, and services all reference the same source of truth:

```php
enum BookingStatus: string {
    case Pending = 'pending';      // hold placed, awaiting payment
    case Confirmed = 'confirmed';  // paid
    case Active = 'active';        // bike unlocked, ride in progress
    case Completed = 'completed';  // returned
    case Cancelled = 'cancelled';
    case Expired = 'expired';      // hold timed out unpaid
}
```

Other state machines to define the same way: `KycStatus`, `PaymentStatus`, `RefundStatus`,
`DurationType` (hourly/daily/weekly/monthly), and `BikeStatus`.

Then create one Eloquent model per table under `app/Models/Rental/`, with relationships and
casts (cast status columns to their enums, money columns to integers).

---

## 7. Build the integration adapters first

Before any business logic, define the contracts your services depend on. This is what lets you
develop and test without real third-party accounts.

```
app/Integrations/
├── Contracts/
│   ├── PaymentGateway.php   # createOrder(), verifySignature(), refund()
│   ├── OtpProvider.php      # send(), verify()
│   ├── PushProvider.php     # send()
│   └── KycProvider.php      # verify()
├── Payment/RazorpayGateway.php   # real HMAC signature verification
├── Otp/{Msg91Provider, LogOtpProvider}.php
├── Push/LogPushProvider.php
└── Kyc/AutoKycProvider.php
```

Bind interface → implementation in a dedicated provider (`RentalServiceProvider`), switching by
environment:

```php
$this->app->bind(OtpProvider::class, fn ($app) =>
    $app->environment('production')
        ? new Msg91Provider(...)
        : new LogOtpProvider()   // dev: "sends" OTP to the log
);
```

Now every service depends on `OtpProvider` (the interface), and the container injects the right
one. Tests inject fakes; production gets MSG91/FCM/Digio with no code change.

---

## 8. Implement the services (business logic)

This is the heart of the application. Each service owns one responsibility:

| Service | Responsibility & key rule |
|---------|---------------------------|
| `AuthService` / `OtpService` | Mobile + OTP register/login; issue Sanctum tokens |
| `KycService` | Upload → submit → review lifecycle; gates booking |
| `PricingService` | hourly/daily/weekly/monthly base + GST + platform fee + coupon + deposit — **all in integer paise via `Money`** |
| `AvailabilityService` | **No double-booking:** transactional `lockForUpdate` overlap query |
| `BookingService` | Quote → create (hold) → confirm → unlock → return state transitions |
| `PaymentService` | Razorpay verify; **idempotent** on the gateway payment id |
| `RefundService` | Auto deposit refund on return (minus late penalty); cancellation refunds; idempotent |
| `WalletService` | Immutable ledger with running `balance_after`; never negative |
| `CouponService` | Validity window, min amount, per-user/global usage limits |
| `NotificationService` | Persist in-app + preference-gated push fan-out |

Three rules to enforce inside these services — they are the difference between a demo and a
correct system:

1. **Money is integer paise, everywhere.** Use the `Money` value object; no floats touch money.
2. **No double-booking.** The overlap check runs inside a DB transaction with `lockForUpdate`
   so two concurrent requests can't both win the same bike/time slot.
3. **Idempotency.** Payments and refunds carry a unique `idempotency_key`; a duplicate webhook
   or retried callback is a safe no-op. Payment confirmation is authoritative on the webhook,
   not the client redirect.

---

## 9. Expose the API

Wire the layers together. Controllers stay thin — validate via a FormRequest, call a service,
return a Resource.

```php
// app/Http/Controllers/Api/Rental/BookingController.php
public function store(CreateBookingRequest $request, BookingService $bookings)
{
    $booking = $bookings->create($request->user(), $request->validated());
    return new BookingResource($booking);
}
```

Define routes in [`routes/api.php`](../routes/api.php) under the `rental/v1` prefix, applying
middleware where business rules require it:

```php
Route::middleware('auth:sanctum')->group(function () {
    Route::post('bookings/quote', [BookingController::class, 'quote']);   // price visible pre-KYC

    Route::post('bookings', [BookingController::class, 'store'])          // creating a booking is gated
        ->middleware(['kyc.approved', 'idempotency']);

    Route::post('payments/verify', [PaymentController::class, 'verify'])
        ->middleware('idempotency');
});
```

Note the deliberate split: you can *see a price quote* before KYC, but you cannot *create a
booking* until KYC is approved — enforced by the `kyc.approved` middleware, not by controller
code.

**Custom middleware to build:**

- `EnsureKycApproved` (`kyc.approved`) — 403 unless the user's KYC status is `Approved`.
- `IdempotencyKey` (`idempotency`) — dedupes mutating requests by an `Idempotency-Key` header.
- `EnsureRole` (`role:...`) — RBAC for admin endpoints.

---

## 10. Build the admin panel

Filament gives you the operations dashboard with minimal code. Register an
`AdminPanelProvider`, then add:

- **Resources** — CRUD for bikes, categories, pricing, coupons, bookings, users/staff.
- **Pages** — KYC review queue, `LiveMap`, `PushCampaign`, configuration/account settings.
- **Widgets** — overview stats, revenue trend chart, bookings-by-status, active-rides table.

Protect the panel with role-based access (`AdminAccess` / role middleware) so KYC reviewers and
super admins see only what their role allows.

Build assets when needed:

```bash
npm install
npm run dev     # or: npm run build
```

---

## 11. Seed demo data

Make a fresh clone usable in one command. The seeder should create a launch city + hub, a few
categories of bikes with pricing, a sample coupon, and admin logins.

```bash
php artisan migrate:fresh --seed
```

This project's seeder produces: 1 city + hub, 3 categories × 4 bikes with pricing, coupon
`ZIPPI100` (₹100 off), and admin logins `admin@zippi.in` / `kyc@zippi.in` (password
`password`).

> **On a fresh clone always run `migrate:fresh --seed`.** Any encrypted columns (e.g. Aadhaar)
> in a committed SQLite file were encrypted with the original `APP_KEY` and will read as
> unreadable under your own key. Rebuilding gives you a clean DB under your key.

---

## 12. Test the critical loop

Write tests as you build each service — they double as executable documentation of the rules.
Use SQLite `:memory:` (configured in `phpunit.xml`) so the suite is fast and isolated.

```bash
php artisan test
```

Cover the full loop end to end: auth, KYC gate + lifecycle, pricing, booking, payment
idempotency, **no double-booking**, deposit auto-refund, cancellation refund, and admin RBAC.
The existing suite is 23 tests / 76 assertions — treat that as the minimum bar for the loop.

---

## 13. Run it

```bash
php artisan serve     # http://127.0.0.1:8000
```

- API base: `http://127.0.0.1:8000/api/rental/v1`
- Admin: `http://127.0.0.1:8000/admin` (`admin@zippi.in` / `password`)

---

## 14. Going to production

1. **Database** — switch to MariaDB in `.env` (`DB_CONNECTION=mariadb`, host/credentials).
   Migrations are additive with the `rental_*` prefix, so they coexist with an existing schema.
2. **Integrations** — set production credentials; the service provider auto-binds the real
   MSG91 / FCM / Digio / Razorpay adapters when `APP_ENV=production`.
3. **Secrets** — generate a fresh `APP_KEY`; never reuse a key across environments (it controls
   encryption of sensitive columns).
4. **Payments** — point Razorpay webhooks at your endpoint; remember confirmation is
   webhook-authoritative and idempotent.
5. **Deploy** — provision a PHP 8.4+ host, run migrations + seeders, and `npm run build` for
   admin assets. (No platform is wired in: choose your own host/container setup.)

---

## 15. Where things live (quick map)

```
app/
├── Enums/Enums.php                 # all domain state machines
├── Support/Money.php               # integer-paise value object
├── Models/Rental/*                 # Eloquent models (one per table)
├── Services/Rental/*               # ALL business logic
├── Integrations/{Contracts,...}/*  # swappable provider adapters
├── Http/Controllers/Api/Rental/*   # thin controllers (+ Admin/*)
├── Http/Middleware/*               # KYC gate, idempotency, RBAC
├── Http/Resources/Rental/*         # JSON response shapes
├── Filament/*                      # admin panel resources/pages/widgets
└── Providers/RentalServiceProvider.php   # interface → implementation bindings
database/migrations/2026_06_12_11*  # rental tables, grouped by domain
database/seeders/DatabaseSeeder.php # demo data
routes/api.php                      # /api/rental/v1/* routes
tests/{Unit,Feature}/*              # the critical-loop test suite
```

---

## Summary checklist

- [ ] Scaffold Laravel + Sanctum + Filament
- [ ] Define enums (state machines) first
- [ ] Build migrations bottom-up by domain group
- [ ] Create Eloquent models with casts/relationships
- [ ] Define integration **contracts** and bind fakes for dev
- [ ] Implement services — money in paise, no double-booking, idempotency
- [ ] Wire thin controllers + FormRequests + Resources + middleware
- [ ] Build the Filament admin panel with RBAC
- [ ] Seed demo data (`migrate:fresh --seed`)
- [ ] Test the full critical loop
- [ ] Swap integrations + DB for production
