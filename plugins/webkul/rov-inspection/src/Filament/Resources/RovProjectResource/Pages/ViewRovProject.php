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
            Action::make('view_structures')
                ->label('Manage Structures')
                ->icon('heroicon-o-building-office')
                ->color('info')
                ->url(fn () => \Webkul\RovInspection\Filament\Resources\RovProjectResource::getUrl('structures', ['record' => $this->getRecord()])),
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
