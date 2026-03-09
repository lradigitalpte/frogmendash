<?php

namespace Webkul\RovInspection\Filament\Resources\InspectionReportResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Webkul\RovInspection\Enums\ReportStatus;
use Webkul\RovInspection\Filament\Resources\InspectionReportResource;

class ViewInspectionReport extends ViewRecord
{
    protected static string $resource = InspectionReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            Action::make('share')
                ->label('Generate Share Link')
                ->icon('heroicon-o-share')
                ->color('info')
                ->action(function () {
                    $record = $this->getRecord();
                    $record->generateShareLink();
                    $record->status = ReportStatus::Shared->value;
                    $record->save();

                    Notification::make()
                        ->success()
                        ->title('Share Link Generated')
                        ->body('Share URL: '.url('/report/'.$record->shared_link_hash))
                        ->send();

                    $this->refreshFormData(['status', 'shared_link_hash', 'shared_date']);
                })
                ->hidden(fn () => $this->getRecord()->shared_link_hash !== null),
            Action::make('view_client')
                ->label('Open Client View')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->color('success')
                ->url(fn () => url('/report/'.$this->getRecord()->shared_link_hash))
                ->openUrlInNewTab()
                ->visible(fn () => $this->getRecord()->shared_link_hash !== null),
            DeleteAction::make()
                ->successNotification(
                    Notification::make()->success()->title('Report Deleted'),
                ),
        ];
    }
}
