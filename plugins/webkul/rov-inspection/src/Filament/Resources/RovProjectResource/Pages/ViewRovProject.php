<?php

namespace Webkul\RovInspection\Filament\Resources\RovProjectResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Webkul\RovInspection\Filament\Resources\RovProjectResource;

class ViewRovProject extends ViewRecord
{
    protected static string $resource = RovProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('map_annotation')
                ->label('Annotate Map')
                ->icon('heroicon-o-map')
                ->color('info')
                ->url(fn () => '/admin/rov-inspection/map?project='.$this->getRecord()->id)
                ->visible(fn () => $this->getRecord()->site_map_path !== null),
            EditAction::make(),
            DeleteAction::make()
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title('Project Deleted')
                        ->body('The inspection project has been deleted.'),
                ),
        ];
    }
}
