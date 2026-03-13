<?php

namespace Webkul\PluginManager\Console\Commands;

use BezhanSalleh\FilamentShield\Support\Utils;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Webkul\Account\TenantProvisioner;
use Webkul\Partner\Models\Partner;
use Webkul\Security\Models\Scopes\CompanyScope;
use Webkul\Support\Models\Company;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

/**
 * Creates an independent tenant the same way the installer creates the first company:
 * 1. Create the company
 * 2. Create the first user for that company (with role so they can log in)
 * 3. Link user to company (default_company_id, allowedCompanies, creator_id)
 *
 * The tenant admin gets the same panel role as the installer's admin (e.g. Admin)
 * but NOT is_default/super_admin, so they cannot create other tenants.
 */
class CreateTenant extends Command
{
    protected $signature = 'erp:tenant:create
        {--company-name= : Company name}
        {--admin-name= : Tenant admin full name}
        {--admin-email= : Tenant admin email}
        {--admin-password= : Tenant admin password}';

    protected $description = 'Create a new tenant (company + first user). Same idea as installer, but for an independent company.';

    public function handle(): int
    {
        $this->info('Creating new tenant (company + first user)...');

        $companyName = $this->option('company-name') ?: text(
            label: 'Company name',
            required: true,
            placeholder: 'Acme Inc',
        );

        $userModel = app(Utils::getAuthProviderFQCN());

        $adminName = $this->option('admin-name') ?: text(
            label: 'Tenant admin name',
            required: true,
            placeholder: 'Jane Doe',
        );

        $adminEmail = $this->option('admin-email') ?: $this->ask('Tenant admin email');
        if (! $adminEmail || ! filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
            $this->error('Valid email is required.');

            return self::FAILURE;
        }
        if ($userModel::where('email', $adminEmail)->exists()) {
            $this->error('A user with this email already exists.');

            return self::FAILURE;
        }

        $adminPassword = $this->option('admin-password') ?: password(
            label: 'Tenant admin password',
            required: true,
            validate: fn ($v) => strlen($v) >= 8 ? null : 'Password must be at least 8 characters.',
        );

        $roleName = Utils::getPanelUserRoleName();
        $company = null;
        $user = null;

        $companyPartner = Partner::withoutGlobalScope(CompanyScope::class)->create([
            'creator_id' => Auth::id() ?? 1,
            'sub_type'   => 'company',
            'name'       => $companyName,
        ]);

        DB::transaction(function () use ($companyName, $adminName, $adminEmail, $adminPassword, $userModel, $roleName, $companyPartner, &$company, &$user) {
            Model::withoutEvents(function () use ($companyName, $companyPartner, &$company) {
                $company = Company::create([
                    'name'       => $companyName,
                    'is_active'  => true,
                    'partner_id' => $companyPartner->id,
                ]);
            });

            Model::withoutEvents(function () use ($adminName, $adminEmail, $adminPassword, $userModel, $company, &$user) {
                $user = $userModel::create([
                    'name'                => $adminName,
                    'email'               => $adminEmail,
                    'password'            => $adminPassword,
                    'default_company_id'  => $company->id,
                    'resource_permission' => 'global',
                    'is_default'          => false,
                ]);
            });

            $user->allowedCompanies()->attach($company->id);

            if (! $user->hasRole($roleName)) {
                $user->assignRole($roleName);
            }

            $company->update(['creator_id' => $user->id]);
        });

        $companyPartner->updateQuietly(['company_id' => $company->id]);
        $user->save();

        TenantProvisioner::provisionAll($company->fresh());

        $this->info('Tenant created successfully.');
        $this->line("  Company: {$companyName}");
        $this->line("  Admin:   {$adminEmail}");
        $this->line('They can log in at /admin/login with that email and password.');

        return self::SUCCESS;
    }
}
