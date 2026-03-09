<?php

namespace Webkul\RovInspection\Filament\Resources\InspectionReportResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Webkul\RovInspection\Filament\Resources\InspectionReportResource;

class EditInspectionReport extends EditRecord
{
    protected static string $resource = InspectionReportResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function getSavedNotification(): Notification
    {
        return Notification::make()
            ->success()
            ->title('Report Updated')
            ->body('The report has been saved.');
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->successNotification(
                    Notification::make()->success()->title('Report Deleted'),
                ),
        ];
    }
}
