<?php

namespace Webkul\Security\Filament\Resources\CompanyResource\Pages;

use BezhanSalleh\FilamentShield\Support\Utils;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;
use Webkul\Account\TenantProvisioner;
use Webkul\Partner\Models\Partner;
use Webkul\Security\Filament\Resources\CompanyResource;
use Webkul\Security\Models\Scopes\CompanyScope;
use Webkul\Support\Models\Company;
use Webkul\TableViews\Filament\Concerns\HasTableViews;

class ListCompanies extends ListRecords
{
    use HasTableViews;

    protected static string $resource = CompanyResource::class;

    public function getTabs(): array
    {
        return [
            'all' => Tab::make(__('security::filament/resources/company/pages/list-company.tabs.all'))
                ->badge(Company::count()),
            'archived' => Tab::make(__('security::filament/resources/company/pages/list-company.tabs.archived'))
                ->badge(Company::onlyTrashed()->count())
                ->modifyQueryUsing(function ($query) {
                    return $query->onlyTrashed();
                }),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('addTenant')
                ->label(__('Add tenant (company + first user)'))
                ->icon('heroicon-o-building-office-2')
                ->form([
                    TextInput::make('company_name')
                        ->label(__('Company name'))
                        ->required()
                        ->maxLength(255),
                    TextInput::make('admin_name')
                        ->label(__('Tenant admin name'))
                        ->required()
                        ->maxLength(255),
                    TextInput::make('admin_email')
                        ->label(__('Tenant admin email'))
                        ->email()
                        ->required()
                        ->unique('users', 'email')
                        ->maxLength(255),
                    TextInput::make('admin_password')
                        ->label(__('Tenant admin password'))
                        ->password()
                        ->required()
                        ->rule(Password::default())
                        ->maxLength(255),
                ])
                ->action(function (array $data): void {
                    $userModel = app(Utils::getAuthProviderFQCN());
                    $roleName = Utils::getPanelUserRoleName();
                    $user = null;
                    $company = null;

                    // Companies table requires partner_id FK. Create company's partner first (outside transaction to avoid lock timeout).
                    $companyPartner = Partner::withoutGlobalScope(CompanyScope::class)->create([
                        'creator_id'       => Auth::id(),
                        'sub_type'         => 'company',
                        'company_registry' => null,
                        'name'             => $data['company_name'],
                        'email'            => null,
                        'website'          => null,
                        'tax_id'           => null,
                        'phone'            => null,
                        'mobile'           => null,
                        'color'            => null,
                        'street1'          => null,
                        'street2'          => null,
                        'city'             => null,
                        'zip'              => null,
                        'state_id'         => null,
                        'country_id'       => null,
                        'parent_id'        => null,
                        'company_id'       => null,
                    ]);

                    DB::transaction(function () use ($data, $userModel, $roleName, $companyPartner, &$user, &$company) {
                        Model::withoutEvents(function () use ($data, $companyPartner, &$company) {
                            $company = Company::create([
                                'name'       => $data['company_name'],
                                'is_active'  => true,
                                'partner_id' => $companyPartner->id,
                            ]);
                        });

                        Model::withoutEvents(function () use ($data, $userModel, $company, &$user) {
                            $user = $userModel::create([
                                'name'                => $data['admin_name'],
                                'email'               => $data['admin_email'],
                                'password'            => $data['admin_password'],
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

                    // Set partner's company_id now that we have the company id; then create user's partner
                    $companyPartner->updateQuietly(['company_id' => $company->id]);
                    $user->save();

                    TenantProvisioner::provisionAll($company->fresh());

                    Notification::make()
                        ->success()
                        ->title(__('Tenant created'))
                        ->body(__('Company and first user created. They can log in at :url', ['url' => url('/admin/login')]))
                        ->send();
                }),
            CreateAction::make()->icon('heroicon-o-plus-circle')
                ->label(__('security::filament/resources/company/pages/list-company.header-actions.create.label')),
        ];
    }
}
