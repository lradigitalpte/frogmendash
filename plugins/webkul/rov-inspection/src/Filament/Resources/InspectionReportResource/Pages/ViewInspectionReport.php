<?php

namespace Webkul\RovInspection\Filament\Resources\InspectionReportResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
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
                ->label(fn () => $this->getRecord()->shared_link_hash ? 'Share Link' : 'Generate Share Link')
                ->icon('heroicon-o-share')
                ->color('info')
                ->modalHeading(fn () => $this->getRecord()->shared_link_hash ? 'Share Link' : 'Share Link Generated')
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Close')
                ->modalContent(function () {
                    $record = $this->getRecord();
                    if (! $record->shared_link_hash) {
                        $record->generateShareLink();
                        $record->status = ReportStatus::Shared->value;
                        $record->save();
                        $this->refreshFormData(['status', 'shared_link_hash', 'shared_date']);
                    }

                    return view('rov-inspection::filament.actions.share-link-modal', [
                        'url' => url('/report/' . $record->shared_link_hash),
                    ]);
                }),
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
