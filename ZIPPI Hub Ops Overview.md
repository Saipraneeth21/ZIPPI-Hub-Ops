# ZIPPI Hub Ops — Overview

> The **Hub Operations** module: a complete, additive operational layer for
> on-site hub staff, built on top of the existing ZIPPI Rental platform.
> This document is the single-source picture of everything in the module.

---

## 1. What it is

A day-to-day operations tool for **hub staff** (the people at a physical hub who
hand bikes to riders and take them back). It ships in two forms that share the
same business logic:

1. A **Filament web panel** at **`/hub`** — usable today by staff.
2. A **REST API** at **`/api/hub/v1`** — for a future Hub Ops mobile app.

It is an **additive layer**: it does **not** change any rider flow, admin
dashboard behaviour, pricing, payments, refunds, KYC, or existing database
relationships. Everything new is backward-compatible and hub-scoped.

---

## 2. How it fits the platform

```
Rider mobile app  ─────►  /api/rental/v1   ┐
Admin staff       ─────►  /admin (Filament)├─►  Services (Booking, Pricing, …)  ─►  Models / DB
Hub staff (NEW)   ─────►  /hub  (Filament) │        ▲
Hub Ops app (NEW) ─────►  /api/hub/v1      ┘        └── HubOpsService delegates here
```

- **Same services, no duplicated logic.** Pickup/return reuse
  `BookingService::unlock()` / `returnBike()` via a thin `HubOpsService`, so the
  hub panel, the hub API, the rider app and the admin dashboard never drift.
- **Isolated auth.** Hub staff are a separate identity (`hub` guard) — they
  cannot reach rider or admin surfaces, and vice-versa.

---

## 3. Authentication & access control

- **Identity:** the existing `HubStaff` model, upgraded to an auth identity
  (`HasApiTokens` + Filament `FilamentUser`). Login is **employee code +
  password** (no email).
- **Web panel:** new `hub` session guard + `hub_staff` provider; a custom
  Filament login page swaps the email field for **Employee code**.
- **API:** `POST /api/hub/v1/auth/login` issues a Sanctum token; all protected
  routes use `auth:sanctum` + the `hub.staff` middleware (`EnsureHubStaff`),
  which 403s anything that isn't an active hub-staff token.
- **Hub scoping (enforced everywhere):**
  - Booking-centric screens scope by `bookings.pickup_hub_id = staff.hub_id`.
  - Bike-centric screens scope by `bikes.hub_id = staff.hub_id`.
  - A staff member from hub A gets **404** on hub B's bookings/bikes.

---

## 4. Screens — Filament `/hub` panel

| Group | Screen | What it does |
|-------|--------|--------------|
| — | **Dashboard** | 5 KPI cards (Available Bikes, Active Rentals, Expected Returns Today, Bikes Under Maintenance, **Maintenance Due**) + **Upcoming Pickups** and **Due Returns** tables |
| Operations | **Upcoming Pickups** | Confirmed bookings awaiting handover → **Hand over** action |
| Operations | **Active Rentals** | Bikes currently out → **Complete return** action (overdue highlighted) |
| Operations | **Handovers** | Log of every handover with live status (On rent / Returned), filter + view |
| Fleet | **Fleet** | Read-only bikes with status filters (Available / Reserved / Active Ride / Maintenance / **Maintenance due**) + Report Maintenance / Report Incident |
| Fleet | **Maintenance Due** | Service worklist (in maintenance, or next service ≤ 14 days) with a nav badge |
| Account | **Profile** | Edit own name; employee code / role / hub read-only |
| Account | **Logout** | Ends the hub session |

Brand: ZIPPI turquoise `#40E0D0`, brand name "ZIPPI Hub Ops". The header links
to the dashboard.

---

## 5. API surface — `/api/hub/v1`

All protected routes require `auth:sanctum` + `hub.staff` and are hub-scoped.

```
POST auth/login                      # employee_code + password -> token
GET  auth/me        POST auth/logout
GET  dashboard                       # cards + upcoming_pickups[] + due_returns[]
GET  bookings/search?q=              # by booking code or customer mobile
GET  pickups        GET rentals/active
GET  bookings/{id}                   # customer + booking + bike + KYC status
POST bookings/{id}/handover          # checklist + battery + photos
POST bookings/{id}/return            # odometer + battery + photos + damage notes
GET  fleet?status=available|reserved|active|maintenance|maintenance_due
GET  fleet/{bike}
POST maintenance                     # category, description, photos
POST incidents                       # type, severity, description, photos, booking
```

Responses use the platform's standard `ApiResponse` envelope
(`success / message / data / errors`).

---

## 6. Core workflows (reuse, not reinvention)

### Pickup handover  (`confirmed → active`)
Staff inspect the bike, capture **battery %**, a **checklist** (bike inspected,
helmet issued) and **photos** (photos are mandatory in the panel), then hand
over. Internally `HubOpsService::handover()`:
1. calls `BookingService::unlock()` (the existing, unchanged lifecycle step —
   sets booking active, marks the bike `booked`, notifies the rider), then
2. records a `hub_handovers` row (battery, checklist, photos, notes, staff).

### Bike return  (`active → completed`)
Staff capture **odometer**, **battery %**, **return photos** and **damage
notes**, then complete the return. `HubOpsService::completeReturn()`:
1. calls `BookingService::returnBike()` — **late penalty, deposit refund and
   bike-status reset are all the existing logic, untouched**, then
2. records a `hub_returns` row (odometer, battery, photos, damage, staff).

No money/lifecycle logic is re-implemented in the hub layer.

---

## 7. Maintenance & incident reporting

Both reuse the existing entities — the hub layer only adds the staff-facing entry
point:

- **Maintenance** → `MaintenanceRecord` (category = `maintenance_type`,
  description, photos = `attachments`, `status` open/in-progress/completed,
  `reported_by_hub_staff_id`).
- **Incident** → `IncidentReport` (type, severity, description, photos, linked
  `booking_id`, `reported_by_hub_staff_id`).

### Maintenance Due logic
A single reusable scope, `Bike::maintenanceDue()`, powers the dashboard card, the
Maintenance Due list, the Fleet filter and the API. A bike is "due" when its
**status = maintenance** OR its **latest record's next service is overdue / within
14 days** — identical to the admin "Maintenance Due" worklist.

> Note: "Bikes Under Maintenance" (status = maintenance, off the road) is
> deliberately distinct from "Maintenance Due" (needs servicing soon, still
> rentable).

---

## 8. Database changes (all additive, nullable, non-breaking)

| Migration | Change |
|-----------|--------|
| `…_create_hub_handovers_table` | New table: booking, staff, battery, checklist(json), photos(json), notes |
| `…_create_hub_returns_table` | New table: booking, staff, odometer, battery, photos(json), damage notes |
| `…_add_hub_ops_fields_to_maintenance_records` | + `status`, `description`, `reported_by_hub_staff_id` |
| `…_add_hub_ops_fields_to_incident_reports` | + `booking_id`, `reported_by_hub_staff_id`, `photos`(json) |

Nothing existing was renamed, dropped, or made stricter. Config adds a `hub`
guard + `hub_staff` provider; the panel is a second Filament panel discovered
from `app/Filament/Hub` — both purely additive.

---

## 9. Backward-compatibility guarantees

- Rider API (`/api/rental/v1`) and admin panel (`/admin`) are unchanged.
- The only existing model touched is `HubStaff` (base class upgraded to an auth
  identity — fully compatible; admin CRUD still works).
- `BookingService`, pricing, payment, refund, KYC code are **not modified** —
  the hub layer calls them.
- Full test suite stays green: **112 passing** overall, of which **17** are the
  dedicated Hub Operations tests.

---

## 10. File map

```
app/
├── Models/Rental/
│   ├── HubStaff.php            # auth identity (HasApiTokens + FilamentUser)
│   ├── HubHandover.php  HubReturn.php
│   └── Bike.php                # + maintenanceDue() scope
├── Services/Rental/HubOpsService.php       # handover / return / maintenance / incident
├── Http/
│   ├── Middleware/EnsureHubStaff.php       # hub.staff guard
│   └── Controllers/Api/Hub/                # Auth, Dashboard, Booking, Fleet, Maintenance, Incident
├── Providers/Filament/HubPanelProvider.php # the /hub panel
└── Filament/Hub/
    ├── Auth/Login.php                      # employee-code login
    ├── Pages/Profile.php
    ├── Support/HubActions.php              # handover/return/maintenance/incident actions
    ├── Widgets/                            # HubStatsOverview, HubUpcomingPickups, HubDueReturns
    └── Resources/                          # Pickups, ActiveRentals, Handovers, Fleet, MaintenanceDue
database/migrations/2026_06_24_*            # 4 additive migrations
routes/api.php   (/api/hub/v1 group)   routes/web.php (/hub/account/logout)
tests/Feature/Hub/HubOperationsTest.php     # 17 tests
```

---

## 11. Testing

`tests/Feature/Hub/HubOperationsTest.php` (17 tests, run with
`php artisan test tests/Feature/Hub`) covers: login + token, bad/inactive
credentials, rider-token rejection, hub-scoped dashboard counts, handover →
active (+ capture row), non-confirmed handover rejection, return → completed
(+ refund + capture row), cross-hub 404, hub-scoped search, maintenance &
incident creation (+ cross-hub rejection), fleet listing/filters, maintenance-due
counting, the handovers log, the profile page, and logout.

---

## 12. CI/CD

- **CI** (`.github/workflows/ci.yml`) — Pint lint (advisory) + full test suite on
  every PR and push to `main`.
- **CD** (`.github/workflows/cd.yml`) — on push to `main` / manual dispatch:
  **test → build** (composer `--no-dev` + `vite build`, uploads a release
  artifact) **→ deploy** (SSH/rsync + `migrate --force` + cache warmup). The
  deploy job is gated on `vars.DEPLOY_ENABLED` and skipped until a target +
  secrets (`DEPLOY_HOST/USER/PATH/SSH_KEY`) are configured, so the pipeline stays
  green by default.

---

## 13. Security hardening

- `.env` is gitignored and never committed; `.env.example` holds only blank
  placeholders; the Razorpay config values are dummy fallbacks.
- The SQLite database is **not committed** (it would ship demo data + password
  hashes) — fresh clones build it with `migrate:fresh --seed`.
- Demo credentials are not printed verbatim in the README; they live in the
  seeder and are flagged local-only.

---

## 14. Quick start

```bash
composer install
cp .env.example .env && php artisan key:generate
php artisan migrate:fresh --seed
php artisan storage:link
php artisan serve
```

- **Hub panel:** `http://127.0.0.1:8000/hub` — demo login `HUB001` /
  (password set in `database/seeders/DatabaseSeeder.php`).
- **Hub API:** `http://127.0.0.1:8000/api/hub/v1`.
- Create real staff (with their own passwords + a hub) in
  **Admin → Account → Hub Staff Login** (a hub is required and auto-applied when
  only one exists).

---

## 15. Out of scope (Phase 2)

Walk-in rentals, instant dispatch, Aadhaar capture, QR codes, charging/battery
swapping, live tracking, IoT, hub-to-hub transfers, inventory management, a
technician module, analytics dashboards, and any new payment/pricing/KYC flows.
