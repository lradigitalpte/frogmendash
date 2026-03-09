# Multi-Tenant & Per-Company Plugins – Implementation Summary

This document describes the multi-tenant behaviour and per-company plugin system implemented in the application.

---

## 1. Multi-Tenancy (Data Isolation)

### 1.1 Company scope on models

- **`CompanyScope`** (`plugins/webkul/security/src/Models/Scopes/CompanyScope.php`) filters queries by the authenticated user’s `default_company_id`.
- Applied to most business models (e.g. Orders, Invoices, Projects, Partners) so each tenant only sees their own data.
- The **User** and **Company** models are not scoped by `CompanyScope` (companies are shared for admin; users are filtered separately).

### 1.2 User list: tenant-scoped

- **Users** list is scoped per tenant so tenant admins only see users belonging to their company.
- **Platform admin (installer)** still sees all users.
- Implemented via:
  - **`User::scopeForCurrentTenant()`** – restricts to users whose `default_company_id` is in the current user’s allowed companies (and platform admin bypass).
  - **`User::isPlatformAdmin()`** – true for user id 1, or `is_default`, or `super_admin`, or fallback when no `is_default` user exists.
- **UserResource** uses `getEloquentQuery()->forCurrentTenant()` and list tab badges use `User::forCurrentTenant()->count()`.

### 1.3 User form: company dropdowns scoped

- On Create/Edit User, **Allowed Companies** and **Default Company** dropdowns (and their table filters) only show companies the current user is allowed to see.
- **Platform admin:** sees all companies.
- **Tenant user:** sees only their allowed companies and sub-companies (branches), not other tenants.
- Implemented via **`UserResource::scopeCompaniesVisibleToCurrentUser()`** and `modifyQueryUsing` on the relevant Select/SelectFilter components. Column names in queries use `companies.id` / `companies.parent_id` to avoid ambiguity.

---

## 2. Tenant Creation (No Self-Service Signup)

### 2.1 Who can create tenants

- Only the **platform admin (installer)** can create new tenants. Tenant admins cannot create other tenants (“SaaS leverage”).
- **Companies** menu and **Add tenant** are visible only to platform admin (user id 1, or `is_default`, or `super_admin`, or fallback).

### 2.2 Add tenant (UI)

- **Settings → Companies** → **Add tenant (company + first user)**.
- Single form: company name, tenant admin name, email, password.
- Flow:
  1. Create company’s **Partner** first (required by `companies.partner_id` FK), outside the main transaction.
  2. In a transaction: create **Company** with `partner_id`, create **User** (without model events), attach company, assign Admin role, set `creator_id`.
  3. After commit: set partner’s `company_id`, then `$user->save()` to create the user’s partner.
- Prevents lock timeouts by not inserting into `partners_partners` inside the transaction.

### 2.3 CLI: create tenant

- **`php artisan erp:tenant:create`** (options: `--company-name`, `--admin-name`, `--admin-email`, `--admin-password`).
- Same logic as UI: partner first, then company + user in transaction, then partner update and user partner creation.

### 2.4 Platform admin

- **`php artisan erp:platform-admin user@example.com`** – sets `is_default = true` for that user so they see Companies and can create tenants.
- Without args, lists users and hints the command.

---

## 3. Settings Per Company

### 3.1 Goal

- Each company has its own settings (e.g. Manage Tasks, Manage Time, Manage Currency). Changing settings for one company does not affect others.

### 3.2 Schema

- **Migration** `database/migrations/2026_03_08_add_company_id_to_settings_table.php`:
  - Added nullable **`company_id`** to `settings`.
  - Unique key changed from `(group, name)` to `(group, name, company_id)`.
- Existing rows keep `company_id = null` (global defaults).

### 3.3 Repository

- **`CompanyScopedDatabaseSettingsRepository`** (`plugins/webkul/security/src/Settings/CompanyScopedDatabaseSettingsRepository.php`) extends Spatie’s database repository.
- **Read:** Prefer row for current user’s `default_company_id`; fallback to `company_id` null (global).
- **Write:** Always use current user’s `company_id` so each tenant has its own rows.
- **Config:** `config/settings.php` – default repository set to `CompanyScopedDatabaseSettingsRepository`.

---

## 4. Plugins Per Company

### 4.1 Goal

- Each company can enable only the plugins it needs (e.g. Sales + Invoices). Enabling/disabling is per company and does not affect other tenants.

### 4.2 Schema

- **Migration** `plugins/webkul/plugin-manager/database/migrations/2026_03_08_create_company_plugins_table.php`:
  - Table **`company_plugins`**: `company_id`, `plugin_name`, unique `(company_id, plugin_name)`, FK to `companies`.

### 4.3 Model and helper

- **`CompanyPlugin`** (`plugins/webkul/plugin-manager/src/Models/CompanyPlugin.php`):
  - **`isEnabledForCompany($pluginName, $companyId = null)`** – returns whether that plugin is enabled for the company (or current user’s company). Accepts both config-style names (e.g. `webkul.contacts`) and DB-style names (e.g. `contacts`) so nav and DB stay in sync.
  - **`enabledPluginNamesForCompany($companyId = null)`** – list of enabled plugin names for the company.

### 4.4 Plugin UI (Settings → Plugins)

- **Install** = “Enable for your company”: inserts into `company_plugins` for current user’s company (no global `:install` or migrations).
- **Uninstall** = “Disable for your company”: deletes that company’s row from `company_plugins`.
- **Plugin model:** **`isEnabledForCurrentCompany()`** uses `CompanyPlugin::isEnabledForCompany($this->name)`.
- Table shows “Enabled for your company” and tabs: **Enabled for your company**, **Apps**, **Extra**, **Not enabled for your company**.

### 4.5 Sidebar and topbar filtering

- **`PluginNavigationHelper::filterNavigationForCompany($navigation)`** – filters navigation groups so only groups for enabled plugins (and core) are shown.
- **Config** `config/plugin-navigation-groups.php`: maps navigation group labels (e.g. “Sales”, “Contact”, “Invoices”) to plugin names (e.g. `webkul.sales`, `webkul.contacts`).
- **Always shown:** Dashboard, Settings, Plugins (and any group not in the config, or whose plugin is in `$alwaysShowPlugins`: `webkul.support`, `webkul.plugin-manager`, `webkul.security`).
- **Views:**  
  - `resources/views/vendor/filament-panels/livewire/sidebar.blade.php`  
  - `resources/views/vendor/filament-panels/livewire/topbar.blade.php`  
  Use `filterNavigationForCompany(filament()->getNavigation())` so the sidebar and topbar only show allowed groups.

### 4.6 Access control (403)

- **Middleware** `EnsurePluginEnabledForCompany` (admin panel):
  - On **GET** requests, resolves the Filament resource/page/cluster from the route name.
  - Derives plugin from the class namespace (e.g. `Webkul\Sales\...` → `webkul.sales`).
  - If that plugin is not enabled for the current company (and not in the always-allowed list) → **403** with message: “This module is not enabled for your company. Enable it in Settings → Plugins.”
- **Always allowed:** `webkul.support`, `webkul.plugin-manager`, `webkul.pluginmanager`, `webkul.security` (so Settings and Plugins are never blocked).

### 4.7 Name mismatches

- **Plugins page 403:** Resolved by allowing **`webkul.pluginmanager`** (from class namespace `Webkul\PluginManager\...`) in addition to `webkul.plugin-manager` in the always-allowed list.
- **Contact not in sidebar:** Resolved by **`CompanyPlugin::isEnabledForCompany()`** checking both the given name and the part after the last dot (e.g. `webkul.contacts` and `contacts`), because the UI stores the plugin id (e.g. `contacts`) while the nav config uses `webkul.contacts`.

---

## 5. File Reference

| Purpose | File(s) |
|--------|--------|
| Company scope | `plugins/webkul/security/src/Models/Scopes/CompanyScope.php` |
| User tenant scope | `plugins/webkul/security/src/Models/User.php` (`scopeForCurrentTenant`, `isPlatformAdmin`) |
| User list/form company scoping | `plugins/webkul/security/src/Filament/Resources/UserResource.php` |
| Companies visibility (platform admin) | `plugins/webkul/security/src/Filament/Resources/CompanyResource.php` |
| Add tenant (UI) | `plugins/webkul/security/src/Filament/Resources/CompanyResource/Pages/ListCompanies.php` |
| Tenant CLI | `plugins/webkul/plugin-manager/src/Console/Commands/CreateTenant.php`, `MakePlatformAdmin.php` |
| Settings company_id | `database/migrations/2026_03_08_add_company_id_to_settings_table.php` |
| Settings repository | `plugins/webkul/security/src/Settings/CompanyScopedDatabaseSettingsRepository.php` |
| Company plugins table | `plugins/webkul/plugin-manager/database/migrations/2026_03_08_create_company_plugins_table.php` |
| CompanyPlugin model | `plugins/webkul/plugin-manager/src/Models/CompanyPlugin.php` |
| Plugin enable/disable UI | `plugins/webkul/plugin-manager/src/Filament/Resources/PluginResource.php`, `ListPlugins.php` |
| Nav group → plugin config | `config/plugin-navigation-groups.php` |
| Nav filter helper | `plugins/webkul/plugin-manager/src/Support/PluginNavigationHelper.php` |
| Sidebar/topbar views | `resources/views/vendor/filament-panels/livewire/sidebar.blade.php`, `topbar.blade.php` |
| Plugin access middleware | `plugins/webkul/plugin-manager/src/Http/Middleware/EnsurePluginEnabledForCompany.php` |

---

## 6. Production Checklist

- Run migrations (including `company_id` on settings and `company_plugins`).
- Run `php artisan config:clear` after deploy so the company-scoped settings repository is used.
- No extra env vars required for multi-tenancy or per-company plugins.
