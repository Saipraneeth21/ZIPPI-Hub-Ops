# How to Create the Bike Rental Mobile Application

A **detailed, build-ready** developer guide for the rider mobile app that consumes the ZIPPI
Rental API. Built directly from the *Rental User Flow* whiteboard, every screen is explained
step by step and mapped to the existing backend endpoints in [`routes/api.php`](../routes/api.php)
(`/api/rental/v1/*`).

Each step and section carries an **ETC** (Estimated Time to Complete) so this doc doubles as a
delivery plan. See [§10 Delivery plan](#10-delivery-plan-rollup) for the rolled-up schedule.

> Client counterpart to [How to Create the Bike Rental Application](./how-to-create-the-bike-rental-application.md)
> (the backend, already built & tested).

---

## How to read this document

- **ETC legend:** estimates are in **developer-days** for **one mid-level mobile engineer**
  (1 day = 6 focused hours). Ranges show *optimistic–realistic*. They assume the backend is
  done (it is) and exclude design/QA time unless stated.
- Each screen section has a fixed shape: **Purpose → UI elements → Step-by-step logic →
  API calls → Validation & errors → Done-when (acceptance) → ETC**.
- "Done-when" lines are acceptance criteria you can paste straight into tickets.

---

## Table of contents

1. [The user flow (from the whiteboard)](#1-the-user-flow-from-the-whiteboard)
2. [Technology stack](#2-technology-stack)
3. [Section A — App-wide foundations](#3-section-a--app-wide-foundations)
4. [Section B — Authentication & onboarding](#4-section-b--authentication--onboarding)
5. [Section C — KYC](#5-section-c--kyc)
6. [Section D — Browse & discovery](#6-section-d--browse--discovery)
7. [Section E — Booking & payment (core loop)](#7-section-e--booking--payment-core-loop)
8. [Section F — Post-ride, wallet & ambient](#8-section-f--post-ride-wallet--ambient)
9. [Section G — Cross-cutting features](#9-section-g--cross-cutting-features)
10. [Delivery plan (rollup)](#10-delivery-plan-rollup)
11. [Endpoint quick reference](#11-endpoint-quick-reference)
12. [Assumptions & risks](#12-assumptions--risks)

---

## 1. The user flow (from the whiteboard)

```
                          Loading
                             │
                          Phone no ───────────────┐ (New user)
                             │ (Old user)          ▼
                           Login                 Signup ──► KYC ◄── Selfie
                          ╱  │                                  ▲
                   History   │                                  │
                             ▼                                  │
                         Dashboard                              │
            ┌──────────┬────────────┬───────────────┐          │
            ▼          ▼            ▼                ▼          │
       Bike Catalog  Wallet   Available Bikes     Support      │
                              (Search + date filter)           │
                                   │ Select Bike               │
                                   ▼                           │
                            Show hub location                  │
                              │        │                       │
                          helmet?   check if KYC verified ─────┘ (not verified → KYC)
                                   │ (verified)
                                   ▼
                             Payment Screen
                          failure │      │ success
                             ▼     │      ▼
                       Hold Bike   │   Show Booking details ──► Cancel option
                        (3 min)    │
                          │  failure → back to Available Bikes
                          └─ success → Show Booking details
```

**Right-column notes:** Geofence · Deposit · Flat speed limit · Dashboard without login →
covered in [§9](#9-section-g--cross-cutting-features).

---

## 2. Technology stack

| Concern | Recommended | Notes |
|---------|-------------|-------|
| Framework | Flutter (or React Native) | Single codebase, iOS + Android |
| Networking | Dio / Retrofit · Axios | Interceptors for auth + idempotency |
| State | Riverpod / Bloc · Redux Toolkit / Zustand | Booking flow is a real state machine |
| Secure storage | `flutter_secure_storage` · Keychain | Stores the Sanctum token |
| Maps | Google Maps SDK | Hub location + geofence |
| Push | Firebase Cloud Messaging | Register device token with backend |

**API base URL:** `https://<host>/api/rental/v1` · **Auth:** `Authorization: Bearer <token>`
(Laravel Sanctum) on every authenticated request.

---

## 3. Section A — App-wide foundations

Build these **once**; every screen depends on them. Do this section first — skipping it forces
rework later.

### A1. Project scaffold & environments — **ETC: 0.5–1 day**
- **Step 1.** Create the app project; set bundle/package IDs for iOS + Android.
- **Step 2.** Add build flavors/configs: `dev`, `staging`, `prod`, each with its own
  `API_BASE_URL`.
- **Step 3.** Add the dependency set from §2 and verify a clean run on a simulator and a device.
- **Done-when:** the app launches blank on both platforms in all three flavors.

### A2. API client with interceptors — **ETC: 1–1.5 days**
- **Step 1.** Create a single HTTP client with the base URL injected from the flavor config.
- **Step 2.** **Request interceptor:** attach `Authorization: Bearer <token>` whenever a token
  exists in the session store.
- **Step 3.** **Idempotency:** for `POST bookings` and `POST payments/verify`, generate and
  attach an `Idempotency-Key` (client UUID) so a retried request is a safe no-op server-side.
- **Step 4.** **Response interceptor / error mapper:**
  - `401` → clear token, route to **Phone no**.
  - `403` with KYC reason → route to **KYC**.
  - `422` → parse field errors into a form-error map.
  - `5xx`/timeout → standard retry-with-backoff + user-friendly message.
- **Done-when:** a unit test confirms each status code maps to the right action and the auth
  header is attached.

### A3. Session & secure token store — **ETC: 0.5 day**
- **Step 1.** Wrap secure storage with `saveToken / readToken / clearToken`.
- **Step 2.** Expose an auth state stream (`unauthenticated / authenticated`) the router listens to.
- **Done-when:** killing and reopening the app keeps the user logged in.

### A4. Money & formatting utilities — **ETC: 0.25 day**
- **Step 1.** The API returns **integer paise**. Add `formatPaise(paise)` → `₹125.00`
  (divide by 100 only at render).
- **Step 2.** Forbid float math on money anywhere in the app (lint rule or code-review note).
- **Done-when:** `12500 → ₹125.00`, `99 → ₹0.99` covered by tests.

### A5. Navigation shell & route guards — **ETC: 0.75–1 day**
- **Step 1.** Define named routes for every screen in §1.
- **Step 2.** Add an **auth guard** (redirect to Phone no when a protected route is hit without a
  token) and a **KYC guard** flag (`kycApproved`) used by the booking action.
- **Step 3.** Build the bottom-nav / dashboard shell hosting the main tabs.
- **Done-when:** deep-linking to a protected route while logged out redirects correctly.

**Section A subtotal: ETC ≈ 3.25–4.25 days**

---

## 4. Section B — Authentication & onboarding

### B1. Loading (splash) — **ETC: 0.5 day**
- **Purpose:** bootstrap — restore session, fetch remote config.
- **UI elements:** logo, spinner.
- **Step-by-step:**
  1. Read the stored token.
  2. If present, call `GET auth/me` to validate it.
  3. Valid → route to **Dashboard**; missing/invalid → route to **Phone no**.
- **API:** `GET auth/me` (optional but recommended).
- **Validation & errors:** treat `401` as "no session" → Phone no.
- **Done-when:** cold start routes correctly within ~2s on a mid device.

### B2. Phone no — **ETC: 0.75 day**
- **Purpose:** one entry point for both existing and new users.
- **UI elements:** country code + mobile field, "Continue" button.
- **Step-by-step:**
  1. Validate the number format client-side.
  2. Submit; backend decides whether this is an existing or new user and sends an OTP.
  3. Route to the OTP screen, carrying the mobile number and the old/new flag.
- **API:** existing → `POST auth/login` `{ mobile }`; new → `POST auth/register` `{ mobile }`.
- **Validation & errors:** invalid format inline; rate-limit message on `429`.
- **Done-when:** a valid number triggers an OTP and advances; invalid is blocked inline.

### B3. Login (OTP entry) — old user — **ETC: 1 day**
- **Purpose:** verify OTP and obtain a token.
- **UI elements:** 4–6 digit OTP boxes, resend timer, edit-number link.
- **Step-by-step:**
  1. Auto-read OTP where the platform allows; otherwise manual entry.
  2. Submit OTP.
  3. On success store the token, then register the push token.
  4. Route to **Dashboard** (History reachable from there).
- **API:** `POST auth/verify-otp` `{ mobile, otp }` → token; then `POST profile/device-token`.
- **Validation & errors:** wrong OTP → inline error + attempts left; expired → resend.
- **Done-when:** correct OTP logs the user in and persists the session.

### B4. Signup — new user — **ETC: 1–1.5 days**
- **Purpose:** collect new-account profile details and verify OTP.
- **UI elements:** name, email (optional), OTP step, T&C checkbox.
- **Step-by-step:**
  1. Verify OTP (same as B3) to create the session.
  2. Collect profile details and save them.
  3. Route to **KYC** (a new user must complete KYC before booking).
- **API:** `POST auth/register` → `POST auth/verify-otp` → `PUT profile`.
- **Validation & errors:** required-field validation; `422` mapped to fields.
- **Done-when:** a brand-new number completes signup and lands on KYC.

**Section B subtotal: ETC ≈ 3.25–3.75 days**

---

## 5. Section C — KYC

### C1. KYC capture (documents + selfie) — **ETC: 2–2.5 days**
- **Purpose:** capture identity document(s) **and a selfie**, then submit for admin review.
- **UI elements:** document picker/camera, **selfie camera** with face guide, upload progress,
  status banner.
- **Step-by-step:**
  1. Capture/select the required document image(s).
  2. Capture the **selfie** (front camera, liveness/face-guide overlay).
  3. Upload each file (multipart); show per-file progress.
  4. Submit for review; move UI to "under review".
  5. Poll status; reflect `approved` / `rejected` (with reason) when it changes.
- **API:** `POST kyc/documents` (multipart, per file) → `POST kyc/submit` → `GET kyc` (poll).
- **Validation & errors:** image too large/blurry → client guidance; upload failure → retry per
  file; `rejected` → show reason + allow re-upload.
- **Done-when:** documents + selfie upload, status becomes `under_review`, and the screen reacts
  to an admin approval/rejection.

### C2. KYC gate integration — **ETC: 0.5 day**
- **Purpose:** the reusable "check if KYC verified" decision used before payment.
- **Step-by-step:**
  1. Read `kycApproved` from `GET kyc` / `GET auth/me`.
  2. Verified → continue to payment; not verified → push KYC, then return to the booking flow.
- **Backend note:** `POST bookings` is protected by `kyc.approved` middleware — the client gate
  is UX; the server is authoritative and 403s otherwise.
- **Done-when:** an unverified user is routed to KYC and resumes booking after approval.

**Section C subtotal: ETC ≈ 2.5–3 days**

---

## 6. Section D — Browse & discovery

### D1. Dashboard (home) — **ETC: 1–1.5 days**
- **Purpose:** home hub; per "Dashboard without login", catalog browsing is allowed
  unauthenticated.
- **UI elements:** search entry, category chips, featured bikes, nav to Wallet/History/Support.
- **Step-by-step:** load categories + featured bikes; expose the branches to Catalog, Wallet,
  Available Bikes, Support, History.
- **API:** `GET catalog/categories`, `GET catalog/bikes`.
- **Done-when:** dashboard renders content for both logged-in and logged-out users.

### D2. Bike Catalog — **ETC: 1 day**
- **Purpose:** browse all bikes/categories (no date constraint).
- **UI elements:** category filter, paginated bike grid/list, bike cards with price-from.
- **Step-by-step:** fetch categories → fetch bikes by category with pagination → tap to detail.
- **API:** `GET catalog/categories`, `GET catalog/bikes?category=&page=`.
- **Done-when:** scrolling paginates and category filtering works.

### D3. Available Bikes (search + date filter) — **ETC: 1.5 days**
- **Purpose:** find bikes available for a chosen date/time window.
- **UI elements:** date/time range picker, results list, "available" badges.
- **Step-by-step:**
  1. Collect the rental window (start/end).
  2. Query bikes with the date filter.
  3. Before advancing, confirm the chosen bike's slot is free.
  4. Select bike → **Show hub location**. On later hold/payment **failure**, return here.
- **API:** `GET catalog/bikes` (date filter) → `GET catalog/bikes/{bike}/availability`.
- **Validation & errors:** invalid window (end ≤ start) blocked; "no longer available" → refresh.
- **Done-when:** searching a window lists only bookable bikes and availability is re-checked.

### D4. Show hub location — **ETC: 1–1.5 days**
- **Purpose:** show pickup hub on a map + bike detail; offer add-ons.
- **UI elements:** map with hub pin, bike detail, **helmet?** add-on toggle, terms (speed limit),
  "Proceed" button.
- **Step-by-step:**
  1. Load bike detail incl. hub coordinates.
  2. Render map + bike info + speed-limit/usage terms.
  3. Let the user toggle the **helmet** add-on (feeds the price quote).
  4. On Proceed → run the **KYC gate** (C2) → **Payment Screen**.
- **API:** `GET catalog/bikes/{bike}`.
- **Done-when:** hub shows on the map, helmet toggle persists into the quote, proceed hits the
  KYC gate.

**Section D subtotal: ETC ≈ 4.5–5.5 days**

---

## 7. Section E — Booking & payment (core loop)

This is the highest-risk section — money, holds, idempotency. Budget review/QA time here.

### E1. Quote — **ETC: 0.5–1 day**
- **Purpose:** show the price breakdown before paying.
- **UI elements:** itemized breakdown — base (by hourly/daily/weekly/monthly) + GST + platform
  fee + coupon − discount + **deposit** — and a total.
- **Step-by-step:** build the quote payload (bike, window, helmet add-on, coupon) → display the
  returned paise amounts via `formatPaise`.
- **API:** `POST bookings/quote`.
- **Validation & errors:** invalid coupon → inline message, recompute total.
- **Done-when:** the breakdown matches the backend response exactly and deposit is shown
  separately.

### E2. Create booking (hold) — **ETC: 1 day**
- **Purpose:** place a time-boxed hold and obtain the payment order.
- **Step-by-step:**
  1. Generate an `Idempotency-Key`.
  2. `POST bookings` with the validated quote → returns a `pending` booking (hold) + payment
     order.
  3. Start the **3-min hold timer** (E4) immediately.
- **API:** `POST bookings` (headers: `Idempotency-Key`; KYC-gated).
- **Validation & errors:** `403` → KYC gate; `409`/conflict (slot taken) → back to Available
  Bikes.
- **Done-when:** a hold is created and the same key never creates a second booking.

### E3. Payment Screen — **ETC: 1.5–2 days**
- **Purpose:** take payment and verify it.
- **UI elements:** payment method (Razorpay checkout), amount, pay button, processing state.
- **Step-by-step:**
  1. Launch the gateway checkout with the order from E2.
  2. On gateway callback, `POST payments/verify` (with `Idempotency-Key`) to verify the HMAC
     signature server-side.
  3. **success** → **Show Booking details**; **failure** → **Hold Bike (3 min)** retry.
- **API:** `POST payments/verify`, `GET payments/{reference}` (status poll).
- **Validation & errors:** gateway failure/cancel → failure path; never trust the client result —
  confirmation is webhook-authoritative on the backend.
- **Done-when:** a successful payment confirms the booking; a failed one routes to the hold/retry
  path without double-charging.

### E4. Hold Bike (3 min) — **ETC: 1 day**
- **Purpose:** keep the bike reserved ~3 minutes while payment is retried.
- **UI elements:** countdown timer, retry-payment button, cancel.
- **Step-by-step:**
  1. Show the countdown for the `pending` hold.
  2. **success** (payment completes) → **Show Booking details**.
  3. **failure/timeout** → hold expires server-side (`BookingStatus::Expired`) → back to
     Available Bikes.
- **API:** retry `POST payments/verify`; expiry handled by the backend.
- **Validation & errors:** if the timer hits 0, disable retry and refetch status.
- **Done-when:** retry within the window can still succeed; after expiry the user is sent back.

### E5. Show Booking details — **ETC: 1 day**
- **Purpose:** confirmed booking summary + ride actions.
- **UI elements:** booking summary, hub map, **Unlock**, **Return**, **Cancel** buttons,
  status chip.
- **Step-by-step:** load the booking; expose Unlock (start), Return (end → auto deposit refund),
  and Cancel; refetch after every action.
- **API:** `GET bookings/{booking}`, `POST .../unlock`, `POST .../return`.
- **Done-when:** status transitions reflect server truth after each action.

### E6. Cancel option — **ETC: 0.5 day**
- **Purpose:** cancel a booking (per policy) with refund.
- **Step-by-step:** confirm intent → cancel → show refund result in Wallet.
- **API:** `POST bookings/{booking}/cancel` (idempotent).
- **Done-when:** cancellation is reflected in status and the refund appears in the wallet ledger.

**Section E subtotal: ETC ≈ 5.5–6.5 days**

---

## 8. Section F — Post-ride, wallet & ambient

### F1. History — **ETC: 0.75 day**
- **Purpose:** past & active bookings list.
- **Step-by-step:** list bookings → tap → Booking details.
- **API:** `GET bookings`, `GET bookings/{booking}`.
- **Done-when:** all of a user's bookings list with correct statuses.

### F2. Wallet — **ETC: 1 day**
- **Purpose:** balance + immutable ledger.
- **UI elements:** balance card, transaction list with running `balance_after`.
- **API:** `GET wallet`, `GET wallet/transactions`.
- **Done-when:** balance and ledger match the backend; refunds show up here.

### F3. Notifications (ambient) — **ETC: 1–1.5 days**
- **Purpose:** in-app list + push badge.
- **Step-by-step:** register device token at login; show badge from unread-count; mark read.
- **API:** `GET notifications`, `/unread-count`, `POST /{id}/read`, `/read-all` + FCM handling.
- **Done-when:** a push arrives, the badge updates, and items mark read.

### F4. Support — **ETC: 0.5 day**
- **Purpose:** help/contact (in-app chat, email, or help URL).
- **Done-when:** support entry opens the chosen channel.

### F5. Profile & settings — **ETC: 1 day**
- **Purpose:** profile, addresses, notification preferences, logout.
- **API:** `GET/PUT profile`, addresses endpoints, notification-preferences, `POST auth/logout`.
- **Done-when:** edits persist and logout clears the session.

**Section F subtotal: ETC ≈ 4.25–4.75 days**

---

## 9. Section G — Cross-cutting features

From the whiteboard's right-hand notes. Schedule these alongside the relevant screens.

### G1. Deposit handling — **ETC: 0.25 day** (folded into E1/F2)
- Show the refundable deposit as its own quote line; surface the **auto-refund on return** in
  the wallet ledger.

### G2. Flat / speed-limit & terms display — **ETC: 0.5 day**
- Show each bike's speed-limit/usage terms (`bike_terms`) on the hub and booking-details screens.

### G3. Geofence (active ride) — **ETC: 2–3 days**
- During an active ride, watch location vs. the bike's allowed zone; warn near boundaries. Backend
  tracks `geofence_alerts` + `bike_telemetry`. *(Higher effort: background location, battery,
  platform permissions.)*

### G4. Dashboard without login — **ETC: 0.25 day** (folded into D1/A5)
- Allow catalog browsing unauthenticated; defer the login wall to the booking action.

**Section G subtotal: ETC ≈ 3–4 days** (G3 dominates)

---

## 10. Delivery plan (rollup)

### State machine to implement (Section E backbone)
Mirror the backend `BookingStatus` enum — model it explicitly, not with ad-hoc flags:

```
quote → (create) pending/hold ──pay success──► confirmed ──unlock──► active ──return──► completed
            │                                       │
       3-min timeout                            cancel
            ▼                                       ▼
         expired                                cancelled
```
Client rules: server is authoritative (refetch after each action); `Idempotency-Key` on
`POST bookings` + `POST payments/verify`; money in paise throughout.

### ETC by section

| Section | Scope | ETC (dev-days) |
|---------|-------|----------------|
| A | App-wide foundations | 3.25 – 4.25 |
| B | Authentication & onboarding | 3.25 – 3.75 |
| C | KYC | 2.5 – 3 |
| D | Browse & discovery | 4.5 – 5.5 |
| E | Booking & payment (core loop) | 5.5 – 6.5 |
| F | Post-ride, wallet & ambient | 4.25 – 4.75 |
| G | Cross-cutting (Geofence-heavy) | 3 – 4 |
| **Subtotal (build)** | | **26.25 – 31.75** |
| QA, bug-fix, device matrix | +25% | 6.5 – 8 |
| Design polish & UX pass | +15% | 4 – 4.75 |
| Store submission & release setup | flat | 2 – 3 |
| **Grand total (1 engineer)** | | **≈ 39 – 47.5 dev-days** |

**Calendar estimate:** ~**8–10 weeks** for one engineer; ~**5–6 weeks** with two engineers
working A/B/C and D/E in parallel after foundations land.

### Suggested sprint sequence (2-week sprints)

| Sprint | Goal | Sections |
|--------|------|----------|
| 1 | Foundations + auth working end to end | A, B |
| 2 | KYC + browse/discovery | C, D |
| 3 | **Core booking loop** (quote → hold → pay → details) | E |
| 4 | Wallet, history, notifications, cross-cutting | F, G |
| 5 | Hardening, QA, polish, store submission | (buffer) |

> **Critical path:** A → B → C → D4 → E. Section E cannot start until A (idempotency client) and
> C (KYC gate) are done. Front-load A; it unblocks everything.

---

## 11. Endpoint quick reference

| Screen / step | Method & path |
|---------------|---------------|
| Loading / session check | `GET auth/me` |
| Phone no (existing) | `POST auth/login` |
| Phone no (new) | `POST auth/register` |
| Login / Signup OTP | `POST auth/verify-otp` |
| Logout | `POST auth/logout` · `POST auth/logout-all` |
| Profile | `GET/PUT profile`, addresses, notification prefs, `POST profile/device-token` |
| KYC | `GET kyc`, `POST kyc/documents`, `POST kyc/submit` |
| Catalog | `GET catalog/categories`, `GET catalog/bikes`, `GET catalog/bikes/{bike}` |
| Availability | `GET catalog/bikes/{bike}/availability` |
| Quote | `POST bookings/quote` |
| Create booking (hold) | `POST bookings` *(KYC-gated + idempotent)* |
| Pay | `POST payments/verify` *(idempotent)*, `GET payments/{reference}` |
| Bookings | `GET bookings`, `GET bookings/{booking}` |
| Ride actions | `POST bookings/{booking}/unlock` · `/return` · `/cancel` |
| Wallet | `GET wallet`, `GET wallet/transactions` |
| Notifications | `GET notifications`, `/unread-count`, `/{id}/read`, `/read-all` |

All paths are relative to `/api/rental/v1`. See [`routes/api.php`](../routes/api.php) for the
authoritative list and middleware.

---

## 12. Assumptions & risks

**Assumptions baked into the ETC**
- Backend is complete and stable (it is — 23 tests passing).
- One mid-level mobile engineer, 6 productive hours/day; designs are available before each screen.
- Razorpay is the gateway and its mobile SDK is used directly.
- KYC approval stays a manual admin action (no automated provider in the loop yet).

**Risks that can extend the ETC**
- **Geofence (G3)** — background location, battery, and OS permission reviews are the biggest
  unknown; could grow to 4–5 days. Consider shipping v1 without it.
- **Payment edge cases** — gateway timeouts vs. webhook confirmation races; budget extra QA in E3.
- **OTP auto-read** — platform restrictions (esp. iOS) may force manual entry; minor.
- **App store review** — KYC/selfie capture and location use can trigger extra review rounds.

> **De-scoping for a faster MVP:** drop Geofence (G3) and ship speed-limit display only, keep
> Support as a simple `mailto:`/URL, and defer notification preferences — that trims roughly
> **4–5 dev-days** and pulls the core loop forward.
