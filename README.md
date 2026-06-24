# ZIPPI Rental — Laravel Backend (MVP)

Production-structured Laravel 13 backend implementing the ZIPPI Rental API documented in [`../APIs/`](../APIs/) and the schema in [`../Database/`](../Database/). Layering follows [`../Architecture/03-Laravel-Backend-Architecture.md`](../Architecture/03-Laravel-Backend-Architecture.md): **Controller → FormRequest/validation → Service → (Repository) → Model**, with Sanctum auth, middleware, API Resources, and integration adapters behind interfaces.

> **Status:** runnable and tested. `php artisan test` → **23 passing tests, 76 assertions** covering the full critical loop (auth, KYC gate + lifecycle, pricing, booking, payment idempotency, no double-booking, deposit auto-refund, cancellation refund, admin RBAC).

## Requirements
- **PHP 8.4.1+** (built/tested on 8.5), Composer 2 — the committed `composer.lock`
  resolves to packages that require ≥ 8.4.1, so `composer install` will fail on older PHP.
- PHP `sqlite3`/`pdo_sqlite` extension (enabled by default in most PHP builds)
- SQLite (default, zero-config) **or** MariaDB 10.6+ for production

## Quick start
Run these from the repository root (where `composer.json` lives):
```bash
composer install                 # installs vendor/ (not committed)
cp .env.example .env             # create your local env file
php artisan key:generate         # writes your own APP_KEY
php artisan migrate:fresh --seed # build a clean, fully-seeded database
php artisan serve                # http://127.0.0.1:8000
```
API base path: `http://127.0.0.1:8000/api/rental/v1`.
Admin dashboard: `http://127.0.0.1:8000/admin` — log in with `admin@zippi.in` / `password`.

> **Always run `migrate:fresh --seed` on a fresh clone.** A `database/database.sqlite`
> file is committed for convenience, but any encrypted columns in it (e.g. Aadhaar on
> Instant Dispatch) were encrypted with the original author's `APP_KEY` and will read as
> `••• unreadable` under your own key. `migrate:fresh --seed` rebuilds the DB under your
> key with no encrypted rows, so you start clean and error-free.

### Run the tests
```bash
php artisan test                     # full suite (sqlite :memory:, see phpunit.xml)
php artisan test tests/Feature/Hub   # Hub Operations suite only
```

## Hub Operations App (`/hub`)

An **additive** operational layer for on-site **hub staff**, built on top of the
existing platform — it does **not** change any rider, admin, pricing, payment,
refund or KYC behaviour. It ships as both a **REST API** (for a future hub-ops
mobile app) and a dedicated **Filament panel**.

### Access
- **Web panel:** `http://127.0.0.1:8000/hub` — sign in with **employee code + password**.
- **API base:** `http://127.0.0.1:8000/api/hub/v1` (Sanctum bearer token).
- Hub staff are managed in the admin dashboard under **Account → Hub Staff Login**
  (a hub is required and auto-applied when only one exists).

### Demo login
After `migrate:fresh --seed`, a demo staff account is created:

| Employee code | Password   | Hub             |
|---------------|------------|-----------------|
| `HUB001`      | `password` | Hitech City Hub |

### What hub staff can do (all scoped to their own hub)
- **Dashboard** — KPI cards (Available Bikes, Active Rentals, Expected Returns
  Today, Bikes Under Maintenance, Maintenance Due) + Upcoming Pickups & Due Returns.
- **Pickup handover** — inspect the bike, capture battery %, checklist and photos,
  then hand over (reuses `BookingService::unlock`, `confirmed → active`).
- **Bike return** — capture odometer, battery, photos and damage notes, then
  complete the return (reuses `BookingService::returnBike` — late penalty and
  deposit refund unchanged).
- **Handovers log** — every handover with its live status (On rent / Returned).
- **Fleet** — read-only bike list with status filters (incl. *Maintenance due*).
- **Maintenance Due** — service worklist (status = maintenance, or next service
  within 14 days), mirroring the admin worklist.
- **Maintenance & Incident reporting** — reuse the existing entities.
- **Profile** — edit own name; employee code / role / hub are read-only.

### Hub API endpoints (under `/api/hub/v1`, Sanctum + `hub.staff` guard)
```
POST auth/login            # employee_code + password -> token
GET  auth/me   POST auth/logout
GET  dashboard
GET  bookings/search?q=    GET pickups   GET rentals/active   GET bookings/{id}
POST bookings/{id}/handover    POST bookings/{id}/return
GET  fleet?status=available|reserved|active|maintenance|maintenance_due
GET  fleet/{bike}
POST maintenance           POST incidents
```

### Architecture notes
- New `hub` auth guard + `hub_staff` provider; `HubStaff` is an auth identity
  (`HasApiTokens` + `FilamentUser`).
- Lifecycle/money logic is reused via a thin `HubOpsService` → `BookingService`
  (no duplicated business logic).
- New additive tables `hub_handovers` / `hub_returns`; additive nullable columns
  on maintenance/incident tables. Nothing existing was renamed or dropped.

### Switch to MariaDB (production)
Set in `.env`:
```dotenv
DB_CONNECTION=mariadb
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=zippi_rental
DB_USERNAME=zippi
DB_PASSWORD=secret
```
Migrations are additive and use the `rental_*` prefix, so they coexist with an existing ZIPPI schema (the shared `users` table is extended, not duplicated).

## What's implemented
| Area | Detail |
|------|--------|
| Auth | Mobile + OTP register/login, Sanctum tokens, logout / logout-all |
| Profile | Profile, address book, notification preferences, device tokens |
| KYC | Upload → submit → admin approve/reject → booking gate (`kyc.approved`) |
| Catalog | Categories, bikes (filter/paginate), bike detail, availability |
| Pricing | `PricingService` — hourly/daily/monthly + GST + fee + coupon + deposit (integer paise via `Money`) |
| Booking | Quote → create (hold + `lockForUpdate` overlap check) → confirm → unlock → return |
| Payments | Razorpay adapter w/ real HMAC signature verification; idempotent confirm |
| Refunds | Auto deposit refund on return (minus late penalty); cancellation refunds; idempotent |
| Wallet | Immutable ledger with running `balance_after`, never negative |
| Coupons | Validity window, min amount, per-user/global usage limits |
| Notifications | Persisted in-app + push fan-out (preference-gated) |
| Admin | KYC review + bike management, role middleware (`role:ops,super_admin`) |

## Key design decisions
- **Money in integer paise** — `App\Support\Money`; no floats anywhere.
- **Idempotency** — unique `idempotency_key` on payments/refunds; duplicate webhooks/callbacks are no-ops.
- **No double-booking** — transactional `lockForUpdate` overlap query in `AvailabilityService`.
- **Webhook-authoritative payments** — confirmation idempotent on gateway payment id.
- **Integration adapters behind interfaces** — `PaymentGateway`, `OtpProvider`, `PushProvider`, `KycProvider`, bound in `RentalServiceProvider`. Dev uses log/auto fakes; swap to MSG91/FCM/Digio in prod without touching services.

## Project layout
```
app/
├── Enums/Enums.php              # KycStatus, BookingStatus, PaymentStatus, ...
├── Support/Money.php            # integer-paise value object
├── Models/Rental/*              # 30 Eloquent models
├── Services/Rental/*            # Pricing, Availability, Booking, Payment, Refund, Wallet, Coupon, Kyc, Auth, Otp, Notification
├── Integrations/{Contracts,Payment,Otp,Push,Kyc}/*
├── Http/Controllers/Api/Rental/*  (+ Admin/*)
├── Http/Middleware/*            # EnsureKycApproved, IdempotencyKey, EnsureRole
├── Http/Resources/Rental/*
└── Providers/RentalServiceProvider.php
database/migrations/2026_06_12_11*  # all rental tables (grouped)
database/factories/Rental/*         # City, Hub, Category, Bike, Pricing, Coupon
database/seeders/DatabaseSeeder.php # 1 city, 1 hub, 3 categories x 4 bikes, coupon, admins
routes/api.php                      # /api/rental/v1/* (42 routes)
tests/{Unit,Feature}/*              # 23 tests
```

## Seeded demo data
After `migrate:fresh --seed`: Launch City + MG Road Hub, 12 bikes across 3 categories with pricing, coupon `ZIPPI100` (₹100 off), admin logins `admin@zippi.in` / `kyc@zippi.in` (password `password`).

See [`../APIs/`](../APIs/) for the complete request/response contracts.
