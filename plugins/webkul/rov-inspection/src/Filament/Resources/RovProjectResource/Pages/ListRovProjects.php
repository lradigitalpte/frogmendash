<?php

namespace Webkul\RovInspection\Filament\Resources\RovProjectResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Webkul\RovInspection\Enums\ProjectStatus;
use Webkul\RovInspection\Filament\Resources\RovProjectResource;
use Webkul\TableViews\Filament\Components\PresetView;
use Webkul\TableViews\Filament\Concerns\HasTableViews;

class ListRovProjects extends ListRecords
{
    use HasTableViews;

    protected static string $resource = RovProjectResource::class;

    public function getPresetTableViews(): array
    {
        return [
            'active_projects' => PresetView::make('Active Projects')
                ->icon('heroicon-s-arrow-path')
                ->favorite()
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', ProjectStatus::InProgress->value)),

            'draft_projects' => PresetView::make('Draft Projects')
                ->icon('heroicon-s-pencil')
                ->favorite()
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', ProjectStatus::Draft->value)),

            'completed_projects' => PresetView::make('Completed Projects')
                ->icon('heroicon-s-check-circle')
                ->favorite()
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', ProjectStatus::Completed->value)),

            'archived_projects' => PresetView::make('Archived')
                ->icon('heroicon-s-archive-box')
                ->favorite()
                ->modifyQueryUsing(fn (Builder $query) => $query->onlyTrashed()),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('New Inspection Project')
                ->icon('heroicon-o-plus-circle'),
        ];
    }
}
