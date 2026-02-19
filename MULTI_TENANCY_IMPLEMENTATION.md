# Multi-Tenancy Implementation Guide

**Status:** 70% Complete - Foundation exists, automatic query scoping needed  
**Last Updated:** February 18, 2026  
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

## What's Missing ❌

### No Automatic Company-Based Query Scoping

**Problem:**
```php
// These queries DON'T automatically filter by company_id
Task::all();                    // ❌ Returns ALL tasks (security issue!)
Task::where('status', 'open')->get();  // ❌ Could include other companies' data

// Users can accidentally (or maliciously) see other companies' data
```

**Should be:**
```php
Task::forCurrentCompany()->get();  // ✅ Only current company's tasks
```

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
