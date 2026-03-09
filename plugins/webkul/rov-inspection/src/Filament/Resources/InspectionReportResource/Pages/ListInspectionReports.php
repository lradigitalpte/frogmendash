<?php

namespace Webkul\RovInspection\Filament\Resources\InspectionReportResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Webkul\RovInspection\Filament\Resources\InspectionReportResource;

class ListInspectionReports extends ListRecords
{
    protected static string $resource = InspectionReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('New Report')
                ->icon('heroicon-o-plus-circle'),
        ];
    }
}
