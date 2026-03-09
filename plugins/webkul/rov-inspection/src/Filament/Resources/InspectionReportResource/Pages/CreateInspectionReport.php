<?php

namespace Webkul\RovInspection\Filament\Resources\InspectionReportResource\Pages;

use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Webkul\RovInspection\Filament\Resources\InspectionReportResource;

class CreateInspectionReport extends CreateRecord
{
    protected static string $resource = InspectionReportResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function getCreatedNotification(): Notification
    {
        return Notification::make()
            ->success()
            ->title('Report Created')
            ->body('The inspection report has been created successfully.');
    }
}
