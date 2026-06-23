# ZIPPI Rental MVP — System Overview

> A portable architecture overview of the ZIPPI bike/scooter rental platform.
> Use this as context for brainstorming enhancements.

## What it is
A **bike/scooter rental platform** (India-focused, INR currency) built as a Laravel monolith. It has two faces:
1. A **mobile-facing REST API** for riders (the mobile app itself is documented but lives in a separate repo).
2. A **Filament admin dashboard** for staff/ops to manage the fleet, KYC, bookings, money, and incidents.

The repo is a *production-structured MVP*: real architecture, real business rules, swappable third-party integrations, ~23 passing tests, CI, and detailed build docs — but seeded with demo data and using dev "log" fakes for SMS/push/KYC by default.

## Tech stack
- **Laravel 13**, **PHP 8.3+**
- **Filament 4** (admin panel at `/admin`)
- **Laravel Sanctum** (API token auth)
- **SQLite** locally (zero-config) → **MariaDB** in production
- **Vite + Tailwind** for minimal admin assets (no React/Vue SPA)
- **GitHub Actions CI** (Pint lint + PHPUnit on SQLite in-memory); Render deploy was recently retired

## Core architecture (layers)
```
Mobile App / Filament UI
        │
HTTP Controllers (thin)  ──  middleware: kyc.approved, idempotency, role
        │
Service layer (all business logic)  ── Auth, Otp, Kyc, Pricing, Availability,
        │                              Booking, Payment, Refund, Wallet, Coupon, Notification
Integration adapters (swappable)  ──  Razorpay, MSG91, FCM, Digio (interface-bound)
        │
Eloquent models + Enums (state machines)
        │
DB (rental_* prefixed tables)
```
Key principle: **controllers and Filament actions both call the same services**, so the mobile API and the admin dashboard never drift apart.

---

## The rider journey (critical loop)
`register → OTP verify → KYC upload + submit → browse catalog → get price quote → book (hold) → pay (Razorpay) → unlock bike → ride → return → deposit auto-refund`

Each step is enforced server-side:
- **Auth:** mobile + OTP (6-digit, 300s TTL, 5 attempts) → issues a Sanctum bearer token. Email/password optional.
- **KYC gate:** `EnsureKycApproved` middleware blocks booking/unlock until profile `kyc_status == approved`.
- **Booking hold:** 10-minute hold (`hold_expires_at`) reserves the bike while payment completes.
- **No double-booking:** `AvailabilityService` uses a DB transaction + `lockForUpdate` overlap check.
- **Idempotency:** money-mutating POSTs (`/bookings`, `/payments/verify`) require an `Idempotency-Key` header, enforced by unique DB columns.
- **Payments:** Razorpay HMAC signature verification, idempotent on gateway payment ID (webhook-authoritative).

## Pricing engine
All money is stored as **integer paise** (never floats), via an `App\Support\Money` value object.

Quote = base rate (hourly/daily/weekly/monthly) + **18% GST** + **₹20 platform fee** + **security deposit** − coupon discount.

- Cancellation refund tiers: 24h before = 100%, 6h = 50%, 0h = 0%.
- Late penalty: ₹50/hr.
- Deposit auto-refunds on return minus penalties.

---

## API surface (~42 endpoints, all under `/api/rental/v1`)
- **Public auth:** register, login, verify-otp
- **Public catalog:** categories, bikes (filter/paginate), bike detail, availability check
- **Profile:** show/update, addresses CRUD, notification prefs, device-token (FCM)
- **KYC:** status, upload documents, submit for review
- **Bookings:** quote, create, list, detail, cancel, unlock, return
- **Payments:** verify (Razorpay), status
- **Wallet:** balance, immutable transaction ledger
- **Notifications:** list, unread-count, mark read/read-all
- **Admin (role-gated):** KYC list/approve/reject, bike create/status-update

---

## Domain model (30+ entities)
**Identity & access**
- `User` (shared users table, Sanctum tokens) → `UserProfile` (kyc_status, **is_blocked**, **is_red_flagged**), `Wallet`, `NotificationPreference`, `Address[]`
- `AdminUser` (separate guard, RBAC roles), `AdminLoginActivity` (login audit), `AuditLog` (before/after change tracking)
- `HubStaff` *(new)* — on-site staff login accounts tied to a hub

**Fleet**
- `City → Hub → Bike` hierarchy
- `Bike` (soft-deletes, status enum, IoT device id) → `BikeImage[]`, `BikePricing` (versioned), `BikeCategory`
- `MaintenanceRecord[]`, `IncidentReport[]` *(new — accident/damage/theft with severity + estimated cost)*
- Telemetry: `BikeTelemetry`, `LockCommand`, `GeofenceAlert`

**Bookings & money**
- `Booking` (full state machine, pickup/return hubs, computed amounts) → `Payment[]`, `Refund[]`, `BookingStatusHistory[]`
- `Wallet → WalletTransaction[]` (immutable, running balance_after)
- `Coupon`, `CouponRedemption`

**KYC & docs**
- `KycDocument` (gov_id / driving_license / selfie), `KycReview`
- `InstantDispatch` — walk-in rentals with **encrypted Aadhaar** (displayed masked)

**Misc:** `Review`, `Notification`, `DeviceToken`, `OtpVerification`, `RecentSearch`

**State-machine enums:** KycStatus, BookingStatus, PaymentStatus, RefundStatus, BikeStatus, DurationType, AdminRole.

---

## Admin dashboard (Filament — 22 resources)
Organized into nav groups: **Operations, Fleet, Customers, Finance, Marketing, System, Account**. Brand turquoise (#40E0D0).

- **Operations:** Bookings (view-only list + lifecycle actions: cancel, force-return, apply-deductions), **LiveMap** (Leaflet, polls fleet positions, geofence alerts)
- **Fleet:** Bikes (+ pricing/images relation managers, soft-delete guarded by active bookings), Categories, Pricing, Hubs, **Incident Reports** *(new)*, Maintenance, Cities
- **Customers:** Users (view-only, self-registered riders), **KYC Queue** (approve/reject with per-doc flagging, nav badge), Customer Documents, **Red Flagged Users** *(new — soft warning distinct from hard block; raising a flag revokes all API tokens)*
- **Finance:** Payments (initiate refund, ≥₹5000 needs super-admin), Refunds, Wallets (super-admin balance adjust, audited)
- **Marketing:** Coupons; **PushCampaign** page
- **System:** Admin Login Activities, Geofence Alerts, Instant Dispatches, Reviews (moderate)
- **Account:** Staff (AdminUser RBAC), **Hub Staff Login** *(new)*, MyProfile, Account Settings, Configuration

**Dashboard widgets:** OverviewStats (revenue + sparkline, active rentals, fleet utilization, KYC approval rate, new users), ActiveRidesTable (overdue highlighting), BookingsByStatusStats, RevenueTrendChart (14-day line).

**RBAC:** gate-based abilities (`bikes.manage`, `orders.manage`, `kyc.review`, `payments.view`, `wallet.adjust`, `users.block`, `audit.view`, `tracking.view`, etc.) mapped to 10 admin roles. Sensitive actions are audit-logged with actor + before/after + IP.

---

## Integrations (swappable adapters, env-bound)
Interfaces: `PaymentGateway`, `OtpProvider`, `PushProvider`, `KycProvider`.
- **Prod:** Razorpay, MSG91 (SMS OTP), FCM (push), Digio (KYC)
- **Dev:** LogOtpProvider, LogPushProvider, AutoKycProvider (auto-approves)

Swapping providers requires no service-layer changes — just rebind in `RentalServiceProvider`.

---

## What's NOT in this repo (yet)
- The **mobile app** itself (Flutter/RN) — only a screen-by-screen build guide with effort estimates (~8–10 weeks for one engineer) exists in `docs/`.
- Real-time push for the live map (currently polling; "ready for WebSocket/Pusher upgrade").
- Production payment/SMS/KYC credentials (using dev fakes).

---

## Recently added (in-flight work)
Three new feature clusters:
1. **Incident Reports** — bike accident/damage/theft tracking (model, migration, Filament resource).
2. **Hub Staff** — on-site staff login accounts per hub.
3. **Red-flag system** — soft warning on riders for repeated KYC/document issues, separate from hard blocking; toggling a flag revokes the rider's API tokens. Plus dashboard widget tweaks.

---

## Quick start
```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed
php artisan serve
```
- API base: `http://127.0.0.1:8000/api/rental/v1`
- Admin: `http://127.0.0.1:8000/admin` (`admin@zippi.in` / `password`)
- Tests: `php artisan test`
