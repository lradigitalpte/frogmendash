<?php

namespace Webkul\RovInspection\Filament\Resources\RovProjectResource\Pages;

use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Webkul\RovInspection\Filament\Resources\RovProjectResource;

class CreateRovProject extends CreateRecord
{
    protected static string $resource = RovProjectResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['creator_id'] = Auth::id();
        $data['company_id'] ??= Auth::user()?->default_company_id;

        return $data;
    }

    protected function getCreatedNotification(): Notification
    {
        return Notification::make()
            ->success()
            ->title('Inspection Project Created')
            ->body('The ROV inspection project has been created successfully.');
    }
}
