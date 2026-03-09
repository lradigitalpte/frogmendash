<?php

namespace Webkul\RovInspection\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Webkul\RovInspection\Enums\ProjectStatus;
use Webkul\RovInspection\Models\InspectionReport;
use Webkul\RovInspection\Models\RovProject;

class RovInspectionPage extends Page
{
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-home';

    protected static ?string $title = 'ROV Overview';

    protected static ?string $navigationLabel = 'Overview';

    protected static ?string $slug = 'rov-inspections';

    public array $stats = [];

    public function mount(): void
    {
        $this->stats = [
            'total_projects'      => RovProject::count(),
            'active_projects'     => RovProject::where('status', ProjectStatus::InProgress->value)->count(),
            'completed_projects'  => RovProject::where('status', ProjectStatus::Completed->value)->count(),
            'total_reports'       => InspectionReport::count(),
            'shared_reports'      => InspectionReport::whereNotNull('shared_link_hash')->count(),
        ];
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin.navigation.rov-inspection');
    }

    public static function getNavigationSort(): ?int
    {
        return 1;
    }

    public function getView(): string
    {
        return 'rov-inspection::filament.pages.rov-inspection';
    }
}
