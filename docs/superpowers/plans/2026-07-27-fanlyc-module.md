# Fanlyc Module Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build "Fanlyc" (`promos.supercarnes.com/fanlyc`) as a new, isolated promo module: a customer registers a Supercarnes invoice, the system validates issuer + branch + SKU, issues a QR coupon per qualifying invoice (a customer can accumulate several), and the coupon is exchanged in person for a physical ticket at one of three zoned events (Azuero / Santiago / Panamá). A customer whose qualifying invoice comes from a branch in one zone can only redeem in that zone.

**Architecture:** Same isolation pattern as the Fonda Challenge module — new dedicated tables/model/controllers/views, reusing only shared low-level primitives (CUFE parsing, the DGI/invoice verifier HTTP client, the official-issuer-RUC whitelist, `BlacklistService`, `endroid/qr-code`, `Audit::log`, the branch model, and the existing role-middleware mechanism). Does **not** modify `ContestInvoiceRegistrationService`, `RegisteredInvoice`, or the generic `Campaign` participation-mode logic — only a single seeded `campaigns` row is used, purely for date-window/status/listing metadata, exactly as Fonda Challenge does.

**Tech Stack:** Laravel 13, Blade views (server-rendered, not the React SPA — deliberate choice to keep the module isolated and reuse the existing `BarcodeDetector`-based camera-scan pattern from the prize-delivery screen instead of building new SPA routing), MySQL/InnoDB, `endroid/qr-code` (already installed), existing admin role system.

**Decisions confirmed with client (2026-07-27):**
- SKU validation happens via the same internal invoice-verification API used for CUFE checks (`http://10.128.0.12/api/verificar`, wrapped by `ContestInvoiceVerifier`). **Open risk:** nothing in the current code/response confirms this API returns line-item/SKU data today (it currently only surfaces `cufe, total_pagado, fecha_autorizacion, emisor_nombre, emisor_ruc`). Per client instruction, we proceed with a **defensive design**: if the API response doesn't contain parseable item/SKU data, the invoice is routed to `pending_review` for manual admin approval rather than auto-rejecting or auto-approving. Client should independently confirm the real contract with whoever operates that endpoint; `FanlycSkuChecker` is built so only its own internal candidate-path config needs updating once the contract is confirmed.
- Zone Azuero = branches `LAS_TABLAS`, `CHITRE` (confirmed).
- Zone Santiago = seeded with the 4 currently known `_SGO` branches (`CALLE10_SGO`, `CENTRAL_SGO`, `MERCADO_SGO`, `PALERMO_SGO`) as a starting default, client-confirmed as acceptable, editable later from the admin with no redeploy.
- Zone Panamá = left empty at migration time; no Panamá invoice can be approved until an admin loads the branch list via the admin zone editor.
- Redemption role: new dedicated `staff_fanlyc` role, scoped only to the venue redemption screen (mirrors how `jurado` only sees the jury screen) — not the existing admin/supervisor/manager roles.
- Qualification requires SKU presence **and** the invoice's branch being mapped to an active zone — no separate minimum purchase amount.
- Customer "my coupons" lookup requires cédula **+ phone** (not cédula alone), to avoid exposing another person's coupons/QRs.

---

### Task 1: Module boundary and data model

**Files:**
- Create: `backend/database/migrations/2026_07_27_000001_create_fanlyc_zones_and_branch_map.php`
- Create: `backend/database/migrations/2026_07_27_000002_create_fanlyc_invoices_table.php`
- Create: `backend/database/migrations/2026_07_27_000003_create_fanlyc_coupons_table.php`
- Create: `backend/database/migrations/2026_07_27_000004_seed_fanlyc_campaign_and_zones.php`
- Create: `backend/app/Models/FanlycZone.php`
- Create: `backend/app/Models/FanlycZoneBranch.php`
- Create: `backend/app/Models/FanlycInvoice.php`
- Create: `backend/app/Models/FanlycCoupon.php`
- Create: `backend/config/fanlyc.php` (target SKU, candidate JSON paths/field names for the SKU checker — see Task 2)

- [ ] **Step 1: Write the failing test**

```php
public function test_fanlyc_schema_is_available(): void
{
    $this->assertTrue(Schema::hasTable('fanlyc_zones'));
    $this->assertTrue(Schema::hasTable('fanlyc_zone_branches'));
    $this->assertTrue(Schema::hasTable('fanlyc_invoices'));
    $this->assertTrue(Schema::hasTable('fanlyc_coupons'));
    $this->assertDatabaseHas('fanlyc_zones', ['code' => 'azuero']);
    $this->assertDatabaseHas('campaigns', ['slug' => 'fanlyc']);
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=test_fanlyc_schema_is_available -v`
Expected: FAIL — tables/seed data do not exist yet.

- [ ] **Step 3: Write minimal implementation**

```php
// fanlyc_zones: id, code (unique), name, is_active, sort_order, timestamps
// fanlyc_zone_branches: id, fanlyc_zone_id (FK cascade), branch_id (FK cascade), is_active, timestamps
//   - unique(['branch_id','fanlyc_zone_id']); "one active zone per branch" enforced in FanlycZoneResolver, not schema
//   - index(['fanlyc_zone_id','is_active'])
// fanlyc_invoices: id, user_id (FK), campaign_id (FK), branch_id (FK nullable), fanlyc_zone_id (FK nullable, frozen at creation),
//   cufe, qr_raw_text, invoice_number, issuer_ruc, issuer_name, issued_at, purchase_amount,
//   sku_check_status (matched|not_matched|undetermined), sku_check_payload (json),
//   status (pending_review|approved|rejected_issuer|rejected_branch_not_in_promo|rejected_sku_not_found|rejected_duplicate_cufe|rejected_blacklisted|disqualified),
//   validation_notes, dgi_response_payload (json), reviewed_by_user_id, reviewed_at, timestamps
//   - unique(['campaign_id','cufe']); index(['user_id','status']); index(['fanlyc_zone_id','status'])
// fanlyc_coupons: id, fanlyc_invoice_id (FK cascade), user_id (FK cascade), fanlyc_zone_id (FK cascade, frozen),
//   code (unique, format FLY-XXXXX), status (issued|redeemed|void),
//   redeemed_at, redeemed_by_user_id, redemption_notes, void_reason, voided_by_user_id, voided_at, timestamps
//   - unique(code); index(['user_id','status']); index(['fanlyc_zone_id','status'])
// seed migration inserts fanlyc_zones (azuero/santiago/panama), fanlyc_zone_branches per client-confirmed defaults above,
//   and one campaigns row (slug 'fanlyc', participation_mode 'external_module', status/dates from client event window)
// all migrations wrapped in if (! Schema::hasTable(...)) per repo convention
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=test_fanlyc_schema_is_available -v`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add backend/database/migrations/2026_07_27_00000*.php backend/app/Models/Fanlyc*.php backend/config/fanlyc.php
git commit -m "feat: add fanlyc module data model"
```

### Task 2: Eligibility validator (the "backend validator")

**Files:**
- Create: `backend/app/Support/Fanlyc/FanlycEligibilityValidator.php`
- Create: `backend/app/Support/Fanlyc/FanlycEligibilityResult.php`
- Create: `backend/app/Support/Fanlyc/FanlycZoneResolver.php`
- Create: `backend/app/Support/Fanlyc/FanlycSkuChecker.php`
- Create: `backend/app/Support/Fanlyc/FanlycSkuCheckResult.php`

- [ ] **Step 1: Write the failing test**

```php
public function test_validator_rejects_non_supercarnes_issuer(): void
{
    // fake ContestInvoiceVerifier::resolve() to return a non-whitelisted issuer_ruc
    $result = app(FanlycEligibilityValidator::class)->evaluate($rawQrText);
    $this->assertSame('rejected_issuer', $result->outcome);
}

public function test_validator_marks_pending_when_sku_undetermined(): void
{
    // fake resolver payload with no parseable item/sku data at all
    $result = app(FanlycEligibilityValidator::class)->evaluate($rawQrText);
    $this->assertSame('pending_review', $result->outcome);
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=FanlycEligibilityValidator -v`
Expected: FAIL — class does not exist yet.

- [ ] **Step 3: Write minimal implementation**

```php
// FanlycEligibilityValidator::evaluate(string $rawQrOrCufeText): FanlycEligibilityResult
// Order (mirrors the client's stated checklist):
//   1. CufeParser::extract() -> null => rejected_invalid_cufe
//   2. ContestInvoiceVerifier::resolve($cufe) cached 15min via same 'dgi_v2_cufe_'.$cufe key used elsewhere
//   3. issuer_ruc in config('contest.official_issuer_rucs') => else rejected_issuer
//   4. Branch::where('store_number', $resolved['issuer_branch_number'])->first() => null => pending_review (fragile parse, don't hard-reject)
//   5. FanlycZoneResolver::zoneForBranch($branch->id) => null => rejected_branch_not_in_promo
//   6. FanlycSkuChecker::check($resolved) => not_matched => rejected_sku_not_found; undetermined => pending_review; matched => continue
//   7. else => approved, carrying branchId/fanlycZoneId forward
// No persistence/side effects inside the validator - pure function, easy to unit test.

// FanlycZoneResolver::zoneForBranch(int $branchId): ?FanlycZone
// FanlycZoneResolver::assignBranchToZone(int $branchId, int $zoneId): void
//   - deactivates any other active fanlyc_zone_branches row for that branch inside a transaction before activating the new one

// FanlycSkuChecker::check(array $resolvedInvoice): FanlycSkuCheckResult
//   - reads $resolvedInvoice['payload'] (full raw body already returned by ContestInvoiceVerifier::resolve(), untouched)
//   - tries a configurable list of candidate paths (config('fanlyc.sku_item_list_paths')) via data_get()
//   - for whichever path yields an array, scans items using configurable field-name candidates (config('fanlyc.sku_code_field_candidates'))
//     against config('fanlyc.sku_target')
//   - no candidate path yields a parseable array => status 'undetermined' (never 'not_matched') - critical defensive behavior
//   - always returns the matched/raw fragment as payload for audit/debugging
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=FanlycEligibilityValidator -v`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add backend/app/Support/Fanlyc/
git commit -m "feat: add fanlyc eligibility validator"
```

### Task 3: Registration orchestration + public flow

**Files:**
- Create: `backend/app/Support/Fanlyc/FanlycRegistrationService.php`
- Create: `backend/app/Support/Fanlyc/FanlycCouponIssuer.php`
- Create: `backend/app/Http/Controllers/FanlycController.php`
- Create: `backend/app/Mail/FanlycRegistrationConfirmation.php`
- Create: `backend/resources/views/fanlyc/landing.blade.php`
- Create: `backend/resources/views/fanlyc/status.blade.php`
- Create: `backend/resources/views/emails/fanlyc-registration.blade.php`
- Modify: `backend/routes/web.php`

- [ ] **Step 1: Write the failing test**

```php
public function test_public_fanlyc_landing_loads(): void
{
    $this->get('/fanlyc')->assertOk()->assertSee('Fanlyc');
}

public function test_registering_second_invoice_accumulates_a_second_coupon(): void
{
    // approve two distinct qualifying CUFEs for the same cedula
    $this->assertSame(2, FanlycCoupon::where('user_id', $user->id)->count());
}

public function test_customer_cannot_register_invoice_from_a_different_zone_branch_and_still_stack(): void
{
    // registering from a branch in a different zone than an existing coupon's zone
    // is allowed to be evaluated on its own merits (each invoice is independently zoned);
    // the exclusivity rule is enforced at REDEMPTION time (Task 5), not at registration time -
    // confirm this expectation matches the client's "no puede participar en azuero ni panama" wording
    // (see Task 5 zone-scoped redemption test for the actual block)
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=test_public_fanlyc_landing_loads -v`
Expected: FAIL — route/controller/view do not exist yet.

- [ ] **Step 3: Write minimal implementation**

```php
// FanlycRegistrationService::registerInvoice(array $participantData, string $rawQrOrCufeText): FanlycRegistrationOutcome
//   1. resolve+validate active 'fanlyc' campaign via CampaignManager::bySlugOrFail('fanlyc') + date window
//   2. findOrCreateParticipant() - modeled on ContestInvoiceRegistrationService's version, no wallet/points bootstrapping
//   3. BlacklistService::isBlocked($cedula, $phone) -> ValidationException before any HTTP call
//   4. duplicate check: FanlycInvoice::where('campaign_id',...)->where('cufe', $canonicalCufe)->exists()
//   5. FanlycEligibilityValidator::evaluate()
//   6. DB::transaction: persist FanlycInvoice row for EVERY outcome (approved/rejected/pending all kept for audit + status page)
//   7. if approved: FanlycCouponIssuer::issueFor($invoice) creates FanlycCoupon (code format FLY-XXXXX, unique-retry loop like Fonda's generateCode()),
//      Audit::log('fanlyc.invoice.approved', ...), Audit::log('fanlyc.coupon.issued', ...)
//   8. if pending_review: Audit::log('fanlyc.invoice.pending_review', ...), no coupon yet
//   9. QueryException code 23000 on the unique index -> friendly ValidationException (race-condition backstop, same pattern as ContestInvoiceRegistrationService)
//  10. sendRegistrationEmail() best-effort, try/catch(\Throwable) + report(), never blocks the response

// FanlycController
//   landing(): loads 'fanlyc' campaign, renders fanlyc.landing (zone explainer + registration form)
//   store(Request): validates full_name/cedula/email/phone/qr_raw_text, calls FanlycRegistrationService, redirects to fanlyc.status
//   status(string $cedula, Request $request): requires ?phone= query/post match before showing coupons (client-confirmed 2-factor lookup);
//     loads all FanlycCoupon rows for the participant with('fanlycInvoice','fanlycZone'), renders fanlyc.status
//   couponQr(string $code): on-demand SVG via endroid/qr-code encoding the BARE coupon code (not a URL - matches how
//     prizeDeliveryLookup already scans bare codes, and keeps the QR meaningful without app context at the venue)

// Routes added in web.php directly after the existing /fonda-challenge/* block, BEFORE the SPA catch-all:
//   GET  /fanlyc                          fanlyc.landing
//   POST /fanlyc/registro                 fanlyc.store
//   GET  /fanlyc/estado/{cedula}           fanlyc.status   (?phone= required)
//   GET  /fanlyc/cupon/{code}/qr           fanlyc.coupon.qr

// Registration form UI: reuse the BarcodeDetector-with-manual-fallback script pattern from
// resources/views/admin/prize-delivery.blade.php (feature-detected camera scan writes into the qr_raw_text field,
// manual paste always available) instead of any SPA/html5-qrcode component, since this is a Blade module.
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=Fanlyc -v`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add backend/app/Support/Fanlyc/FanlycRegistrationService.php backend/app/Support/Fanlyc/FanlycCouponIssuer.php backend/app/Http/Controllers/FanlycController.php backend/app/Mail/FanlycRegistrationConfirmation.php backend/resources/views/fanlyc/ backend/resources/views/emails/fanlyc-registration.blade.php backend/routes/web.php
git commit -m "feat: add fanlyc public registration flow"
```

### Task 4: Admin review + zone/branch mapping editor

**Files:**
- Create: `backend/app/Http/Controllers/Admin/FanlycController.php`
- Create: `backend/app/Http/Controllers/Admin/FanlycZoneController.php`
- Create: `backend/resources/views/admin/fanlyc.blade.php`
- Create: `backend/resources/views/admin/fanlyc-zones.blade.php`
- Modify: `backend/resources/views/admin/partials/sidebar.blade.php`
- Modify: `backend/routes/web.php`

- [ ] **Step 1: Write the failing test**

```php
public function test_admin_fanlyc_dashboard_loads(): void
{
    $this->actingAs($admin)->get('/adminrepus1car/fanlyc')->assertOk();
}

public function test_admin_can_manually_approve_a_pending_review_invoice_and_a_coupon_is_issued(): void
{
    $invoice = FanlycInvoice::factory()->create(['status' => 'pending_review']);
    $this->actingAs($admin)->post("/adminrepus1car/fanlyc/{$invoice->id}/aprobar")->assertRedirect();
    $this->assertDatabaseHas('fanlyc_coupons', ['fanlyc_invoice_id' => $invoice->id]);
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=test_admin_fanlyc_dashboard_loads -v`
Expected: FAIL — route/view do not exist yet.

- [ ] **Step 3: Write minimal implementation**

```php
// Admin\FanlycController (modeled on Admin\FondaChallengeController)
//   STATUSES const = [pending_review, approved, rejected_issuer, rejected_branch_not_in_promo,
//                     rejected_sku_not_found, rejected_duplicate_cufe, rejected_blacklisted, disqualified]
//   index(): paginated FanlycInvoice list, filters by status/zone/branch/search, status-count summary
//   show(FanlycInvoice $invoice): detail view with full dgi_response_payload + sku_check_payload for manual review
//   approve(Request, FanlycInvoice $invoice): only from pending_review; calls FanlycCouponIssuer::issueFor() (shared with
//     the registration service); sets reviewed_by_user_id/reviewed_at; Audit::log('fanlyc.invoice.manually_approved', ...)
//   reject(Request, FanlycInvoice $invoice): requires reason; Audit::log('fanlyc.invoice.manually_rejected', ...)
//   voidCoupon(Request, FanlycCoupon $coupon): role:admin only; sets status=void + void_reason/voided_by/voided_at;
//     Audit::log('fanlyc.coupon.voided', ...)

// Admin\FanlycZoneController
//   index(): lists the 3 zones with currently mapped branches + a dropdown of all active Branch rows to add
//   assignBranch(Request): FanlycZoneResolver::assignBranchToZone(); Audit::log('fanlyc.zone_branch.assigned', ...)
//   unassignBranch(Request, int $mappingId): sets is_active=false (never hard-delete, preserves history for
//     already-issued invoices/coupons that reference the frozen zone) - this is the mechanism that lets the client
//     load the final Santiago/Panama lists later with zero code changes

// Routes (role:admin group, alongside existing blocks, before SPA catch-all):
//   GET  /adminrepus1car/fanlyc                          admin.fanlyc
//   GET  /adminrepus1car/fanlyc/{invoice}                admin.fanlyc.show
//   POST /adminrepus1car/fanlyc/{invoice}/aprobar        admin.fanlyc.approve
//   POST /adminrepus1car/fanlyc/{invoice}/rechazar       admin.fanlyc.reject
//   POST /adminrepus1car/fanlyc/cupones/{coupon}/anular  admin.fanlyc.void-coupon
//   GET  /adminrepus1car/fanlyc-zonas                    admin.fanlyc.zones
//   POST /adminrepus1car/fanlyc-zonas/asignar            admin.fanlyc.zones.assign
//   POST /adminrepus1car/fanlyc-zonas/{mapping}/quitar   admin.fanlyc.zones.unassign

// Sidebar: add "Fanlyc" link in the isAdmin() block using @class(['active' => request()->routeIs('admin.fanlyc*')])
// pattern, same as the existing Fonda Challenge / Blacklist entries.
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=Fanlyc -v`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add backend/app/Http/Controllers/Admin/Fanlyc*.php backend/resources/views/admin/fanlyc*.blade.php backend/resources/views/admin/partials/sidebar.blade.php backend/routes/web.php
git commit -m "feat: add fanlyc admin review and zone editor"
```

### Task 5: `staff_fanlyc` role + venue redemption screen

**Files:**
- Modify: `backend/app/Models/User.php` (add `isFanlycStaff()` helper, mirrors `isJury()`)
- Create: `backend/app/Http/Controllers/Admin/FanlycStaffController.php` (account management, mirrors `JurorController`)
- Create: `backend/app/Http/Controllers/Admin/FanlycRedemptionController.php`
- Create: `backend/resources/views/admin/fanlyc-redeem.blade.php`
- Create: `backend/resources/views/admin/fanlyc-staff.blade.php`
- Modify: `backend/resources/views/admin/partials/sidebar.blade.php`
- Modify: `backend/routes/web.php`

- [ ] **Step 1: Write the failing test**

```php
public function test_redemption_rejects_coupon_from_a_different_zone(): void
{
    $coupon = FanlycCoupon::factory()->create(['fanlyc_zone_id' => $santiagoZone->id, 'status' => 'issued']);
    $response = $this->actingAs($staffFanlyc)
        ->post("/adminrepus1car/fanlyc-canje/azuero", ['coupon_code' => $coupon->code]);
    $response->assertSee('zone_mismatch');
    $this->assertDatabaseHas('fanlyc_coupons', ['id' => $coupon->id, 'status' => 'issued']);
}

public function test_redemption_marks_coupon_redeemed_and_blocks_reuse(): void
{
    $coupon = FanlycCoupon::factory()->create(['fanlyc_zone_id' => $azueroZone->id, 'status' => 'issued']);
    $this->actingAs($staffFanlyc)->post("/adminrepus1car/fanlyc-canje/azuero/cupones/{$coupon->id}");
    $this->assertDatabaseHas('fanlyc_coupons', ['id' => $coupon->id, 'status' => 'redeemed']);

    $second = $this->actingAs($staffFanlyc)->post("/adminrepus1car/fanlyc-canje/azuero", ['coupon_code' => $coupon->code]);
    $second->assertSee('coupon_reused_or_void');
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=test_redemption_rejects_coupon_from_a_different_zone -v`
Expected: FAIL — role/controller/routes do not exist yet.

- [ ] **Step 3: Write minimal implementation**

```php
// User::isFanlycStaff(): bool { return $this->role === 'staff_fanlyc'; }  // role column is a plain string, no enum migration needed

// Admin\FanlycStaffController - near-identical copy of JurorController but role 'staff_fanlyc'
//   index()/store()/toggleStatus() for creating/disabling venue staff accounts

// Admin\FanlycRedemptionController (modeled directly on InvoiceBackofficeController's prizeDelivery* methods)
//   index(string $zoneCode): renders scan screen scoped to one zone (route segment, not just a filter -
//     this is what makes "coupon is for a different zone" enforceable even if a rogue staff member tries to force it)
//   lookup(Request, string $zoneCode): finds FanlycCoupon::where('code', $normalized)->with('fanlycZone','user')->first()
//     - not found                         -> Audit::log('fanlyc.redemption_rejected', reason: code_not_found), rejected view
//     - status !== 'issued'               -> Audit::log(..., reason: coupon_reused_or_void), rejected view
//     - fanlyc_zone_id != zone for $zoneCode -> Audit::log(..., reason: zone_mismatch), rejected view showing the correct zone
//     - else -> render coupon confirmation detail
//   findAjax(Request, string $zoneCode): live-lookup-while-scanning variant of lookup(), same pattern as prizeDeliveryFind
//   store(Request, string $zoneCode, FanlycCoupon $coupon): forceFill(status=redeemed, redeemed_at=now(), redeemed_by_user_id=auth()->id())
//     Audit::log('fanlyc.coupon_redeemed', ...). No photo evidence required (unlike prize-delivery) per current scope -
//     flagged as an easy future add if the client wants extra fraud prevention.

// Routes:
//   role:admin group additions -> admin.fanlyc-staff (index/store/toggle-status)
//   role:admin,staff_fanlyc group (new) ->
//     GET  /adminrepus1car/fanlyc-canje/{zoneCode}                  admin.fanlyc.redeem
//     POST /adminrepus1car/fanlyc-canje/{zoneCode}                  admin.fanlyc.redeem.lookup
//     POST /adminrepus1car/fanlyc-canje/{zoneCode}/buscar           admin.fanlyc.redeem.find
//     POST /adminrepus1car/fanlyc-canje/{zoneCode}/cupones/{coupon} admin.fanlyc.redeem.store

// Sidebar: new @if(auth()->user()?->isFanlycStaff()) block (mirrors the existing isJury() block) linking only to
// admin.fanlyc.redeem - staff_fanlyc users see no other admin nav items, same UX as jurado today.

// View: admin/fanlyc-redeem.blade.php - copy the BarcodeDetector+manual-fallback script structure from
// admin/prize-delivery.blade.php, rename qr_code -> coupon_code, drop the photo-upload fields (not needed here).
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=Fanlyc -v`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add backend/app/Models/User.php backend/app/Http/Controllers/Admin/FanlycStaffController.php backend/app/Http/Controllers/Admin/FanlycRedemptionController.php backend/resources/views/admin/fanlyc-redeem.blade.php backend/resources/views/admin/fanlyc-staff.blade.php backend/resources/views/admin/partials/sidebar.blade.php backend/routes/web.php
git commit -m "feat: add fanlyc staff role and venue redemption screen"
```

### Task 6: Verification and release hardening

**Files:**
- Modify: `backend/tests/**`
- Modify: `backend/.env.example` if `config/fanlyc.php` needs new env keys (`FANLYC_SKU_TARGET`, etc.)

- [ ] **Step 1: Run the full relevant test suite**

Run: `php artisan test`
Expected: green or only pre-existing failures unrelated to Fanlyc.

- [ ] **Step 2: Verify routes and permissions**

Run: `php artisan route:list`
Expected: all `admin.fanlyc*` routes appear and are role-gated (`admin` for management, `admin,staff_fanlyc` for redemption only); public `fanlyc.*` routes registered before the SPA catch-all.

- [ ] **Step 3: Manual smoke test end-to-end**

- Register a qualifying invoice from an Azuero branch (Las Tablas or Chitré) with the target SKU present in the (real or stubbed) verifier response → confirm a coupon + QR is issued and emailed.
- Register a second qualifying invoice for the same person → confirm a second coupon appears on the status page (accumulation works).
- As `staff_fanlyc`, attempt to redeem an Azuero-zoned coupon from the Santiago redemption screen → confirm `zone_mismatch` rejection.
- Redeem the same coupon from the Azuero redemption screen → confirm success, then attempt a second redemption → confirm `coupon_reused_or_void` rejection.
- Confirm a non-Supercarnes-issuer invoice is rejected, and an invoice with no parseable SKU data lands in `pending_review` and can be manually approved from `/adminrepus1car/fanlyc`.

- [ ] **Step 4: Commit release state**

```bash
git add -A
git commit -m "feat: ship fanlyc module v1"
```

---

## Open items requiring client follow-up before/at launch (not blockers to start building)

1. **SKU-detail API contract** — confirm with whoever operates `http://10.128.0.12/api/verificar` whether/how it can return line-item data. Until confirmed, expect most or all registrations to land in `pending_review` for manual approval.
2. **Santiago branch list** — currently defaulted to the 4 known `_SGO` branches; confirm this is the final list before go-live.
3. **Panamá branch list** — must be supplied and loaded via the admin zone editor before that zone can approve anything.
4. **Rejection email UX** — whether to email customers whose invoice is outright rejected (wrong issuer/branch/SKU), or stay silent. Currently planned: send a status email for `approved` and `pending_review`; rejection email is a follow-up decision.
5. **Photo evidence at redemption** — not in current scope (unlike prize-delivery's ID+photo requirement); flagged as an easy addition if fraud becomes a concern.
6. **Zone-mismatch messaging to venue staff** — currently the rejection view tells staff which zone the coupon *is* valid for; confirm this is acceptable rather than withheld.
