# Multi-Tenancy Implementation Guide

**Status:** Implemented - CompanyScope added to company-scoped models  
**Last Updated:** March 8, 2026  
**Priority:** Medium - Implement after app is stable

---

## Current Architecture

### What's Already Built ✅

#### 1. Companies Table
- **Location:** `plugins/webkul/support/database/migrations/2024_12_10_092657_create_companies_table.php`
- **Fields:** name, company_id (unique), email, phone, mobile, website, tax_id, registration_number, founded_date, color, is_active
- **Relationships:** parent_id (hierarchical companies), currency_id, creator_id

#### 2. User-Company Model Relations
**File:** `plugins/webkul/security/src/Models/User.php`

```php
// User can belong to multiple companies
public function allowedCompanies(): BelongsToMany
{
    return $this->belongsToMany(Company::class, 'user_allowed_companies', 'user_id', 'company_id');
}

// User has one active/default company
public function defaultCompany(): BelongsTo
{
    return $this->belongsTo(Company::class, 'default_company_id');
}
```

**User Model Has:**
- `default_company_id` - The company user is currently working for
- `user_allowed_companies` table - Many-to-many relationship

#### 3. Company Foreign Keys on Models
The following models have `company_id` foreign keys:
- Projects (Project, ProjectStage)
- Tasks (Task, TaskStage)
- Sales (Order, OrderLine, Team, AdvancedPaymentInvoice)
- Purchases (Order, RequisitionLine)
- Products (PriceList, Packaging)
- Employees (TimeOff, LeaveType, LeaveAllocation, LeaveMandatoryDays, LeaveAccrualPlan)
- Recruitments (Candidate, Applicant)
- Support (ActivityPlan)

#### 4. Auto-Assignment on Creation
**Example in Task Model:**
```php
protected static function boot()
{
    parent::boot();
    
    static::creating(function ($task) {
        $task->company_id = filament()->auth()->user()->default_company_id;
    });
}
```

This ensures new records automatically get the current user's company_id.

#### 5. ExistingPermissionScoping
**File:** `plugins/webkul/security/src/Models/Scopes/UserPermissionScope.php`

Handles GLOBAL/INDIVIDUAL/GROUP filtering:
- GLOBAL - Users with global permission see all records
- INDIVIDUAL - Users see only their own records
- GROUP - Users see records from their team members

---

## Implemented ✅ (March 2026)

### Automatic Company-Based Query Scoping

- **CompanyScope** created at `plugins/webkul/security/src/Models/Scopes/CompanyScope.php`
  - Optional column parameter for models using `employee_company_id` (e.g. LeaveAllocation)
  - Skips when no user or `default_company_id` is null
- All models with `company_id` (or `employee_company_id`) now use `static::addGlobalScope(new CompanyScope)` (or `new CompanyScope('employee_company_id')`).
- **Excluded:** Currency (global + company-specific rows); Company model itself; Account (many-to-many with companies).
- To query across companies (e.g. admin): `Model::withoutGlobalScope(CompanyScope::class)->get()`

### Models with CompanyScope (full list)

**Accounts:** Move, MoveLine, MoveReversal, Journal, Payment, Reconcile, Tax, TaxPartition, TaxGroup, BankStatement, PaymentTerm, FiscalPosition, FiscalPositionAccount, FiscalPositionTax, PartialReconcile, PaymentRegister.

**Inventories:** Warehouse, Location, Operation, Move, MoveLine, PackageType, PackageLevel, Lot, StorageCategory, OrderPoint, Package, Rule, OperationType, Route, ProductQuantity, Scrap.

**Products:** Product, PriceList, Packaging, PriceRule, PriceRuleItem, ProductSupplier.

**Employees:** Employee, Department, Calendar, EmployeeJobPosition, WorkLocation, CalendarLeaves.

**Projects:** Project, ProjectStage, Task, TaskStage.

**Sales:** Order, OrderLine, Team, AdvancedPaymentInvoice, OrderTemplate, OrderTemplateProduct.

**Purchases:** Order, Requisition, RequisitionLine, OrderLine.

**Support:** ActivityPlan, UtmCampaign.

**Recruitments:** Candidate, Applicant.

**Time-off:** LeaveType, LeaveMandatoryDay, LeaveAccrualPlan, Leave, LeaveAllocation (column: `employee_company_id`).

**Chatter:** Message, Attachment.

**Partners:** Partner.

**Analytics:** Record.

---

## Adding new tenants (admin-created only, SaaS leverage)

Self-service registration is **disabled**. Only **you** (platform admin) create tenants. Tenant users **cannot** create other companies or other tenants — the **Companies** menu and create-company access are restricted to platform admins so you keep full control (SaaS leverage).

**Who can create companies**
- Users with **Super Admin** role, or the **original installer** user (`is_default = true`). Only they see **Companies** in the nav and can create companies/users for new tenants.
- Tenant users (any other role, e.g. **Admin** without `super_admin`) do **not** see Companies and cannot open company list/create — so they cannot create other tenants.

**Ways to add a tenant (installer-style = company + first user in one go):**

**Option A – From the admin UI (recommended)**  
1. Log in as platform admin.  
2. Go to **Companies**.  
3. Click **"Add tenant (company + first user)"**.  
4. Fill in: **Company name**, **Tenant admin name**, **Tenant admin email**, **Tenant admin password**.  
5. Submit. The app creates the company and the first user (with the same role as the installer’s admin, but not platform admin), so the tenant is ready like after an install.  
6. Send the tenant the login URL; they sign in with that email and password.

**Option B – From the command line**  
Run once per new tenant (same idea as the installer, but for an independent company):

```bash
php artisan erp:tenant:create --company-name="Acme Inc" --admin-name="Jane Doe" --admin-email="jane@acme.com" --admin-password="secret123"
```

Or run without options and answer the prompts. The command creates the company and first user with the correct role so they can log in; they do **not** get `super_admin` / platform powers.

**Option C – Manual (company + user separately)**  
1. **Companies** → **Create** → enter company name, save.  
2. **Users** → **Create** → set name, email, password, **Default company** = that company, **Roles** = e.g. Admin (not Super Admin). Save.  
3. Tenant logs in at `/admin/login`.

In all cases, the new tenant is independent (their own company, first user can use the app). Only you (platform) can create new tenants; tenant admins cannot see **Companies** or create other tenants.

---

## Implementation Plan

### Step 1: Create CompanyScope Class

**File to create:** `plugins/webkul/security/src/Models/Scopes/CompanyScope.php`

```php
<?php

namespace Webkul\Security\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class CompanyScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        $user = Auth::user();

        // Skip scoping if no user is authenticated
        if (!$user) {
            return;
        }

        // Filter by user's default company
        $builder->where('company_id', $user->default_company_id);
    }
}
```

### Step 2: Add Scope to All Models with company_id

For each model, add this in the `boot()` method:

```php
protected static function boot()
{
    parent::boot();
    
    // Add company scoping
    static::addGlobalScope(new CompanyScope());
    
    // ... other scopes and logic
}
```

**Models to update:**
- `plugins/webkul/projects/src/Models/Project.php`
- `plugins/webkul/projects/src/Models/ProjectStage.php`
- `plugins/webkul/projects/src/Models/Task.php`
- `plugins/webkul/projects/src/Models/TaskStage.php`
- `plugins/webkul/sales/src/Models/Order.php`
- `plugins/webkul/sales/src/Models/OrderLine.php`
- `plugins/webkul/sales/src/Models/AdvancedPaymentInvoice.php`
- `plugins/webkul/purchases/src/Models/Order.php`
- `plugins/webkul/purchases/src/Models/RequisitionLine.php`
- `plugins/webkul/products/src/Models/PriceList.php`
- `plugins/webkul/products/src/Models/Packaging.php`
- And all employee/recruitment/support models with company_id

### Step 3: Test & Verify

```bash
# After implementation, run:
php artisan tinker

# Test query scoping
>>> $user = User::first();
>>> Auth::login($user);
>>> Task::count(); // Should only count tasks for user's default_company_id
>>> Task::whereCompanyId(999)->first(); // Should return null (security check)
```

### Step 4: Add Helper Scopes (Optional but Recommended)

```php
// In each model:
public function scopeForCurrentCompany(Builder $query)
{
    return $query->where('company_id', auth()->user()->default_company_id);
}

public function scopeForCompany(Builder $query, $companyId)
{
    return $query->where('company_id', $companyId);
}
```

---

## Current Issues to Fix Before Implementation

1. **Server not starting** - Fix this first before testing multi-tenancy
   - Previous error: `php artisan serve` Exit Code 1
   - Need to debug server startup

2. **No Company Data** - Database might be empty
   - Create test company via migrations or database seeder
   - Create test user with `default_company_id` set

3. **Filament Shield Permissions** - May need company-context awareness
   - Check if permissions should be company-specific

---

## Files Involved

### Core Multi-Tenancy Files
- `plugins/webkul/support/src/Models/Company.php` - Company model
- `plugins/webkul/support/database/migrations/2024_12_10_092657_create_companies_table.php`
- `plugins/webkul/support/database/migrations/2024_12_10_100944_create_user_allowed_companies_table.php`
- `plugins/webkul/security/src/Models/User.php` - User relations
- `plugins/webkul/security/src/Models/Scopes/UserPermissionScope.php` - Reference for scoping pattern

### Models to Update (~30+ models)
All located in `plugins/webkul/[module]/src/Models/`

- projects: Project, ProjectStage, Task, TaskStage
- sales: Order, OrderLine, AdvancedPaymentInvoice, Team
- purchases: Order, RequisitionLine
- products: PriceList, Packaging
- employees: * (all employee-related models)
- recruitments: Candidate, Applicant
- support: ActivityPlan
- time-off: * (all time-off models)

---

## Testing Checklist

- [ ] Server starts without errors
- [ ] Database populated with test company & user
- [ ] CompanyScope created and tested
- [ ] All models updated with scope
- [ ] Manual tests in tinker pass
- [ ] Filament resources show only current company data
- [ ] Users can't access other company's records via API
- [ ] Company switching works (if implementing company switcher)

---

## Additional Considerations

### Company Switcher (Optional Future Feature)
Users could switch between their allowed companies:
```php
// User changes active company
Auth::user()->update(['default_company_id' => $newCompanyId]);
```

### Hierarchical Companies
The `parent_id` field allows company hierarchies:
```php
Company::where('parent_id', $parentCompanyId)->get();
```

### Future: API & External Access
When adding APIs, CompanyScope will automatically apply authentication context across endpoints.

---

## Implementation Order

1. **Get server running** ← START HERE
2. Create test data (Company + User)
3. Create CompanyScope class
4. Update 5-10 critical models (Projects, Tasks, Sales)
5. Test with Filament UI
6. Update remaining models
7. Full security audit

---

## References

- **User Permission Scope:** `plugins/webkul/security/src/Models/Scopes/UserPermissionScope.php`
- **Filament Documentation:** Multi-tenancy patterns
- **Laravel Scopes:** https://laravel.com/docs/eloquent#global-scopes
