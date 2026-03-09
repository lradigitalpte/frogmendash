<?php

namespace Webkul\RovInspection\Filament\Pages;

use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Livewire\Attributes\On;
use Webkul\RovInspection\Models\InspectionPoint;
use Webkul\RovInspection\Models\RovProject;

class MapAnnotationPage extends Page
{
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-map';

    protected static ?string $navigationLabel = 'Map Annotation';

    protected static ?string $slug = 'rov-inspection/map';

    protected static ?string $title = 'Map Annotation';

    protected static bool $shouldRegisterNavigation = false;

    public ?int $projectId = null;

    public ?RovProject $project = null;

    public array $points = [];

    public function mount(): void
    {
        $this->projectId = request()->integer('project', 0) ?: null;

        if ($this->projectId) {
            $this->project = RovProject::withoutGlobalScopes()->find($this->projectId);
            $this->loadPoints();
        }
    }

    public function loadPoints(): void
    {
        if (! $this->project) {
            return;
        }

        $this->points = $this->project->inspectionPoints()
            ->get()
            ->map(fn ($point) => [
                'id'           => $point->id,
                'label'        => $point->label,
                'point_number' => $point->point_number,
                'x'            => $point->x_coordinate,
                'y'            => $point->y_coordinate,
                'severity'     => $point->severity,
                'defect_type'  => $point->defect_type,
                'description'  => $point->description,
            ])
            ->toArray();
    }

    #[On('map-point-placed')]
    public function handleMapPointPlaced(float $x, float $y): void
    {
        if (! $this->project) {
            return;
        }

        $nextNumber = $this->project->inspectionPoints()->max('point_number') + 1;

        $point = InspectionPoint::create([
            'rov_project_id' => $this->project->id,
            'point_number'   => $nextNumber,
            'label'          => 'Point '.$nextNumber,
            'x_coordinate'   => $x,
            'y_coordinate'   => $y,
        ]);

        $this->points[] = [
            'id'           => $point->id,
            'label'        => $point->label,
            'point_number' => $point->point_number,
            'x'            => $point->x_coordinate,
            'y'            => $point->y_coordinate,
            'severity'     => null,
            'defect_type'  => null,
            'description'  => null,
        ];

        $this->dispatch('points-updated', points: $this->points);

        Notification::make()
            ->success()
            ->title('Point Added')
            ->body("Point {$nextNumber} placed on the map.")
            ->send();
    }

    #[On('map-point-deleted')]
    public function handleMapPointDeleted(int $pointId): void
    {
        InspectionPoint::find($pointId)?->delete();

        $this->points = array_values(array_filter($this->points, fn ($p) => $p['id'] !== $pointId));
        $this->dispatch('points-updated', points: $this->points);

        Notification::make()
            ->success()
            ->title('Point Removed')
            ->send();
    }

    public function getView(): string
    {
        return 'rov-inspection::filament.pages.map-annotation';
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin.navigation.rov-inspection');
    }
}
