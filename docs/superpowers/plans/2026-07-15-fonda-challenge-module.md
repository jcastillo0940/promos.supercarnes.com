# Fonda Challenge Module Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build Fonda Challenge as an isolated promo module inside the existing Super Carnes hub, with public registration, admin review, event operations, jury scoring, photo handling, ranking, and auditability.

**Architecture:** Reuse the existing campaign hub for discovery and isolation, but add a dedicated Fonda Challenge module with its own tables, controller, views, and role-gated operations. The first release should be a thin vertical slice that already works end-to-end for registration and admin review, then extend into operations, jury scoring, ranking, and media workflows without cross-campaign coupling.

**Tech Stack:** Laravel 13, Blade views for the module UI, existing campaign/auth models, MySQL/InnoDB, server-side validation, audit logs, and the existing admin role system.

---

### Task 1: Module boundary and data model

**Files:**
- Create: `backend/database/migrations/2026_07_15_000001_create_fonda_challenge_module_tables.php`
- Create: `backend/app/Models/FondaRegistration.php`
- Modify: `backend/app/Models/Campaign.php`
- Modify: `backend/routes/web.php`

- [ ] **Step 1: Write the failing test**

```php
public function test_fonda_registration_table_and_model_are_available(): void
{
    $this->assertTrue(Schema::hasTable('fonda_registrations'));
    $this->assertSame('fonda_registrations', (new FondaRegistration())->getTable());
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=test_fonda_registration_table_and_model_are_available -v`
Expected: FAIL because the table/model do not exist yet.

- [ ] **Step 3: Write minimal implementation**

```php
// migration creates fonda_registrations with core fields, code, state, and audit timestamps
// model fills the core registration attributes and casts date/time fields
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=test_fonda_registration_table_and_model_are_available -v`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add backend/database/migrations/2026_07_15_000001_create_fonda_challenge_module_tables.php backend/app/Models/FondaRegistration.php backend/app/Models/Campaign.php backend/routes/web.php
git commit -m "feat: add fonda challenge module boundary"
```

### Task 2: Public registration flow

**Files:**
- Create: `backend/app/Http/Controllers/FondaChallengeController.php`
- Create: `backend/resources/views/fonda-challenge/landing.blade.php`
- Modify: `backend/routes/web.php`

- [ ] **Step 1: Write the failing test**

```php
public function test_public_fonda_challenge_page_loads(): void
{
    $this->get('/fonda-challenge')->assertOk()->assertSee('Fonda Challenge');
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=test_public_fonda_challenge_page_loads -v`
Expected: FAIL because the route/controller/view do not exist yet.

- [ ] **Step 3: Write minimal implementation**

```php
// controller renders the landing page and stores submissions with validation + generated code
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=test_public_fonda_challenge_page_loads -v`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add backend/app/Http/Controllers/FondaChallengeController.php backend/resources/views/fonda-challenge/landing.blade.php backend/routes/web.php
git commit -m "feat: add public fonda challenge landing"
```

### Task 3: Admin review workspace

**Files:**
- Create: `backend/resources/views/admin/fonda-challenge.blade.php`
- Modify: `backend/app/Http/Controllers/Admin/InvoiceBackofficeController.php`
- Modify: `backend/resources/views/admin/partials/sidebar.blade.php`
- Modify: `backend/routes/web.php`

- [ ] **Step 1: Write the failing test**

```php
public function test_admin_fonda_challenge_dashboard_loads(): void
{
    $this->actingAs($admin)->get('/adminrepus1car/fonda-challenge')->assertOk();
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=test_admin_fonda_challenge_dashboard_loads -v`
Expected: FAIL because the route/view do not exist yet.

- [ ] **Step 3: Write minimal implementation**

```php
// admin page lists registrations, statuses, and review actions with campaign isolation
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=test_admin_fonda_challenge_dashboard_loads -v`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add backend/app/Http/Controllers/Admin/InvoiceBackofficeController.php backend/resources/views/admin/fonda-challenge.blade.php backend/resources/views/admin/partials/sidebar.blade.php backend/routes/web.php
git commit -m "feat: add fonda challenge admin review"
```

### Task 4: Event operations, jury scoring, photos, ranking, and hardening

**Files:**
- Create: `backend/app/Http/Controllers/Admin/FondaOperationController.php`
- Create: `backend/app/Http/Controllers/Admin/FondaJuryController.php`
- Create: `backend/app/Http/Controllers/Admin/FondaMediaController.php`
- Create: `backend/app/Support/FondaRankingService.php`
- Modify: `backend/routes/web.php`
- Modify: `backend/app/Models/User.php`
- Modify: `backend/app/Support/Audit.php`

- [ ] **Step 1: Write the failing test**

```php
public function test_fonda_ranking_excludes_unapproved_entries(): void
{
    $ranking = app(FondaRankingService::class)->buildForCampaign($campaign);
    $this->assertCount(0, $ranking->entries());
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=test_fonda_ranking_excludes_unapproved_entries -v`
Expected: FAIL because the service does not exist yet.

- [ ] **Step 3: Write minimal implementation**

```php
// build check-in, jury scoring, photo session, and ranking services with audit hooks
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=test_fonda_ranking_excludes_unapproved_entries -v`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add backend/app/Http/Controllers/Admin/FondaOperationController.php backend/app/Http/Controllers/Admin/FondaJuryController.php backend/app/Http/Controllers/Admin/FondaMediaController.php backend/app/Support/FondaRankingService.php backend/routes/web.php backend/app/Models/User.php backend/app/Support/Audit.php
git commit -m "feat: add fonda challenge operations and ranking"
```

### Task 5: Verification and release hardening

**Files:**
- Modify: `backend/tests/**`
- Modify: `backend/.env.example` if needed
- Modify: `backend/README.md` if needed

- [ ] **Step 1: Run the full relevant test suite**

Run: `php artisan test`
Expected: green or only pre-existing failures unrelated to Fonda Challenge.

- [ ] **Step 2: Verify routes and permissions**

Run: `php artisan route:list`
Expected: Fonda Challenge routes appear and are role-gated.

- [ ] **Step 3: Commit release state**

```bash
git add -A
git commit -m "feat: ship fonda challenge module v1"
```
