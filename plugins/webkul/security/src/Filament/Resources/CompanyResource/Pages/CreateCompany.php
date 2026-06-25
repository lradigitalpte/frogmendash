<?php

namespace Webkul\Security\Filament\Resources\CompanyResource\Pages;

use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Webkul\Security\Filament\Resources\CompanyResource;
use Webkul\Security\Models\User;

class CreateCompany extends CreateRecord
{
    protected static string $resource = CompanyResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title(__('security::filament/resources/company/pages/create-company.notification.title'))
            ->body(__('security::filament/resources/company/pages/create-company.notification.body'));
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['creator_id'] = Auth::user()->id;

        // Model B security boundary: a tenant admin may only create BRANCHES of
        // their own company — never a top-level tenant, and never a branch under
        // someone else's company. Force the parent regardless of submitted data.
        // The platform admin is unrestricted (creates top-level tenants).
        if (! User::isPlatformAdmin()) {
            $data['parent_id'] = Auth::user()->default_company_id;
        }

        return $data;
    }
}
