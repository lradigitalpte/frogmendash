# Tenancy Redesign Plan

**Status:** Planning (approved direction — not yet implemented)
**Created:** 2026-06-25
**Owner:** platform admin

> Supersedes the manual-scope approach in [MULTI_TENANCY_IMPLEMENTATION.md](MULTI_TENANCY_IMPLEMENTATION.md).
> That retrofit treated AureusERP's soft "multi-company" feature as a hard tenant wall, which
> is why data leaks (see audit findings below). This plan moves to real, framework-enforced tenancy.

---

## Goal

Each company is a sealed box. A company is created (by admin) or signs up (self-service),
operates entirely within its own data, and nothing it does is visible to — or affects — any
other company. Tenant = **Company**.

## Approved decisions

| Decision | Choice |
|---|---|
| Isolation model | **Filament native tenancy** (shared DB, `company_id` on every tenant-owned table, framework-enforced on lists *and* record resolution) |
| Reference data (banks, currencies, units, taxes, catalogs) | **Each tenant fully separate** — own copies, seeded per tenant |
| Onboarding | **Both**: admin-provisioned *and* public self-signup, sharing one provisioning service |
| URL strategy | **Path-based** (`/admin/{company}/…`) + redirects from old `/admin/...` links so existing bookmarks don't break |
| Operator (platform) access | **Separated** from tenant workspaces — enter/impersonate a tenant rather than living in all of them |
| Self-signup gating | **Email verification + manual admin approval** before go-live. Temporary stand-in for billing; replaced/joined by the **payment hook** when built. |
| Existing data | **One** production company today (live, must not break). Backfill **all** existing rows to it. |

## Hard constraints

- **Do not lose or break the existing production company's data or workflows.**
- Every schema change: dedicated branch → DB backup → reversible migration → test on a prod-data copy → only then deploy.

---

## Audit findings being addressed (from the 2026-06-25 review)

- 🔴 Bank accounts have no `company_id` / no scope → every tenant sees all bank accounts (IDOR + global `unique`).
- 🔴 `CompanyScope` fails **open** when there is no authenticated user (jobs, commands, public routes run unscoped).
- 🔴 `CompanyScope` fails **open** when `default_company_id` is null (such a user sees all tenants).
- 🟠 Scope uses only `default_company_id`, ignores `allowedCompanies` (inconsistent with `User::scopeForCurrentTenant`).
- 🟠 ROV child models (`InspectionMedia`, `InspectionPoint`, `ProjectStructure`, `InspectionView`, `InspectionReport`, `ReportAccessLog`) have no `company_id`; S3 upload prefix not tenant-scoped.
- 🟡 `isPlatformAdmin()` heuristic can over-grant all-tenant access.
- 🟡 Global `unique()` rules leak existence across tenants.

---

## Phased roadmap

### Phase 0 — Safety net (do first, low risk)
- Harden `CompanyScope` to **fail closed**: no authenticated user OR null company ⇒ return no rows.
- Add an **explicit, intentional bypass** (e.g. a static "running as system" flag) used only by the
  specific commands/jobs/seeders that legitimately need all-tenant access.
- Goal: stop the active leaks immediately while the larger refactor proceeds.

### Phase 1 — Define the tenant
- `User implements Filament\Models\Contracts\HasTenants` (`getTenants()`, `canAccessTenant()`), backed by `allowedCompanies`.
- `Company` as the tenant model (+ slug column for URLs).
- Enable `->tenant(Company::class)` on the admin panel.
- Add redirects: old `/admin/...` → `/admin/{activeCompany}/...`.

### Phase 2 — Schema (biggest / riskiest)
- Inventory every table; classify tenant-owned vs truly global.
- Add `company_id` + FK + index to all tenant-owned tables (incl. reference data, per "fully separate").
- **Backfill** all existing rows to the single existing production company.
- Reversible migrations; backup + prod-copy test first.

### Phase 3 — Models
- One `BelongsToCompany` trait: `company()` relationship + auto-assign `company_id` on create.
- Apply to all tenant-owned models; wire each Filament Resource's tenant relationship.
- Keep hardened `CompanyScope` as defense-in-depth behind Filament's enforcement.

### Phase 4 — Per-tenant seeding
- A "seed a new tenant" routine: its own banks, currencies, units, taxes, journals, default settings, etc.
- Used by both onboarding paths.

### Phase 5 — Onboarding (both)
- **Provision-tenant service** (single source of truth): create Company + first admin user + seed data.
- Admin: keep `erp:tenant:create` + UI, now calling the service.
- Self-signup: public page → create pending tenant → email verification → **manual admin approval** → go-live.
  (Approval = temporary billing stand-in; payment hook to replace later.)

### Phase 6 — Cleanup & verify
- Fix `isPlatformAdmin()`; make `unique()` rules per-tenant.
- Bank-account `company_id`; ROV child models + S3 prefix scoping.
- Full per-resource sweep; IDOR/isolation tests.

---

## Progress log

- ✅ **Phase 0** — `CompanyScope` hardened to fail closed (no-auth / no-company no longer leak), with
  console + customer-guard + explicit `runWithout()` bypasses. Platform admin passes through.
- ✅ **Bank accounts** — `company_id` added (migration `2026_06_25_000001`, registered in PartnerServiceProvider),
  `CompanyScope` + auto-assign + per-company `account_number` uniqueness. Covers all 4 BankAccount models (they
  share the base). **Needs `php artisan migrate` + test.**
- ✅ **Inspection reports** — `company_id` added (migration `2026_06_25_000002`, registered in RovInspectionServiceProvider),
  `CompanyScope` + auto-assign from project. Public share-link lookup bypasses the scope (hash-gated).
  **Needs `php artisan migrate` + test.**
- ✅ **Project milestones** — `company_id` added (migration `2026_06_25_000003`, registered in ProjectServiceProvider),
  `CompanyScope` + auto-assign from project. Applied locally.
- ✅ **Coverage audit (resources → models)** — checked every Filament resource's model. Result: the **core
  financial/operational data is already scoped**, either directly (76 models with `CompanyScope`) or via
  inheritance — facades like Invoice/Bill/CreditNote/Refund→`BaseMove`, Vendor→`BasePartner`,
  Quotation/PurchaseOrder→`Order`, Receipt/Delivery/Dropship/InternalTransfer→`Operation`,
  Timesheet→`Record`, etc. all inherit the scope. The only genuine gaps were bank accounts, reports,
  milestones (now fixed).
- ⏳ **Local-dev infra fixes** (recorded): MySQL 8.4 `default-authentication-plugin` removal, `.dockerignore`
  for `public/storage` + `bootstrap/cache`, and `URL::forceRootUrl` for the trusted-proxy port drop
  (also fixed the broken logo + connection-refused redirect).
- ⏳ **Lower-priority unscoped models** — recruitment `Stage` (pipeline config, likely shared), website `Page`
  / blog `Post` (content; website not enabled). Reference/lookup data (Bank, Currency, Tag, Title, Incoterm,
  etc.) intentionally shared for now — revisit under "each tenant fully separate".
- ⏳ **Remaining big pieces** — per-tenant reference data → enable `->tenant()` switch → onboarding
  (self-signup + admin) + seeding → cleanup (isPlatformAdmin, per-tenant unique rules, IDOR tests).

- ✅ **Model B — hierarchical tenancy / branch self-management** ([CompanyResource](plugins/webkul/security/src/Filament/Resources/CompanyResource.php),
  [CreateCompany](plugins/webkul/security/src/Filament/Resources/CompanyResource/Pages/CreateCompany.php)):
  tenant admins can now open Companies (scoped via `forCurrentUser` to their own company + branches) and
  create branches; `parent_id` is force-set to their company on create, so they cannot make a top-level
  tenant or a branch under another company. Platform admin unchanged (creates top-level tenants, sees all).
  Verified: platform admin sees all + creates top-level; tenant (co.10) sees only Frogmen + branches and
  new companies are forced to branch of co.10.
  - Follow-ups: let tenants *switch into* a branch (company switcher / add branch to allowedCompanies);
    prevent a tenant deleting their own root company; optional role-permission gating (currently any user
    with a default company can manage branches).

- ✅ **Phase 4 — per-tenant seeding** (already existed): `Webkul\Account\TenantProvisioner::provisionAll()`
  seeds journals, payment terms, taxes, warranty, inventory for a new company. Verified: approving a tenant
  seeds 6 journals.
- ✅ **Phase 5 — onboarding**:
  - **Admin provisioning** (already existed): Companies → "Add tenant (company + first user)" and the
    `erp:tenant:create` CLI command, both calling TenantProvisioner.
  - **Self-signup** (new): public registration ([App\Filament\Auth\RegisterTenant](app/Filament/Auth/RegisterTenant.php),
    enabled via `->registration()` on the admin panel) creates a **pending** tenant (company + first admin user,
    both `is_active=false`), does **not** log them in, and shows a "submitted for approval" message.
  - **Approval gate**: `User::canAccessPanel()` now requires `is_active` (all pre-existing users are active, so
    no lockout). An **"Approve tenant"** action on the Companies list (platform admin only, shown for inactive
    companies) activates the company + its users and runs TenantProvisioner seeding.
  - Verified end-to-end: pending signup is blocked → approve → active + seeded → can log in.
  - Follow-ups: wire email verification to real SMTP (locally it logs); replace/augment manual approval with
    the **payment hook** when built.

### ROV child records (deferred)
InspectionMedia/Point/Structure/View/ReportAccessLog have no `company_id`; reached only via the scoped
project pages, so low active-leak risk. To get `company_id` during the per-tenant schema pass.

## Open items / to confirm later
- Payment hook design (replaces manual approval gate).
- Exact platform-admin / impersonation UX.
- Whether any currently-global reference rows should remain shared as system defaults vs cloned per tenant.
