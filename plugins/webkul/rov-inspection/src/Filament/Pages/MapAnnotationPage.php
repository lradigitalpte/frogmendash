<?php

namespace Webkul\RovInspection\Filament\Pages;

use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Livewire\Attributes\On;
use Webkul\RovInspection\Models\InspectionMedia;
use Webkul\RovInspection\Models\InspectionPoint;
use Webkul\RovInspection\Models\InspectionView;
use Webkul\RovInspection\Models\ProjectStructure;

class MapAnnotationPage extends Page
{
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-map';

    protected static ?string $navigationLabel = 'Annotate Diagram';

    protected static ?string $slug = 'rov-inspection/map';

    protected static ?string $title = 'Annotate Diagram';

    protected static bool $shouldRegisterNavigation = false;

    // ── State ────────────────────────────────────────────────────────────────

    public ?int $structureId = null;

    public ?int $viewId = null;

    public ?ProjectStructure $structure = null;

    public ?InspectionView $currentView = null;

    /** All inspection views for the current structure. */
    public array $availableViews = [];

    /** Observation pins rendered on the canvas. */
    public array $points = [];

    // ── Lifecycle ────────────────────────────────────────────────────────────

    public function mount(): void
    {
        $this->structureId = request()->integer('structure') ?: null;
        $this->viewId      = request()->integer('view') ?: null;

        if ($this->structureId) {
            $this->structure = ProjectStructure::with('views')->find($this->structureId);
        }

        if ($this->structure) {
            $this->availableViews = $this->structure->views->map(fn ($v) => [
                'id'        => $v->id,
                'name'      => $v->name,
                'view_type' => $v->view_type,
            ])->toArray();

            if (! $this->viewId && $this->availableViews) {
                $this->viewId = $this->availableViews[0]['id'];
            }

            $this->loadCurrentView();
            $this->loadPoints();
        }
    }

    // ── Data loading ─────────────────────────────────────────────────────────

    public function loadCurrentView(): void
    {
        if ($this->viewId) {
            $this->currentView = InspectionView::find($this->viewId);
        }
    }

    public function loadPoints(): void
    {
        if (! $this->viewId) {
            $this->points = [];

            return;
        }

        $this->points = InspectionPoint::where('inspection_view_id', $this->viewId)
            ->with('media')
            ->orderBy('point_number')
            ->get()
            ->map(fn ($p) => $this->pointToArray($p))
            ->toArray();
    }

    private function pointToArray(InspectionPoint $p): array
    {
        return [
            'id'             => $p->id,
            'observation_id' => $p->observation_id,
            'point_number'   => $p->point_number,
            'label'          => $p->label,
            'x'              => $p->x_coordinate,
            'y'              => $p->y_coordinate,
            'severity'       => $p->severity,
            'finding_type'   => $p->finding_type,
            'description'    => $p->description,
            'dive_location'  => $p->dive_location,
            'depth_m'        => $p->depth_m,
            'dimension_mm'   => $p->dimension_mm,
            'recommendations' => $p->recommendations,
            'media'          => $p->media->map(fn ($m) => [
                'id'            => $m->id,
                'file_name'     => $m->file_name,
                'media_type'    => $m->media_type,
                'url'           => $m->url,
                'thumbnail_url' => $m->thumbnail_url,
            ])->toArray(),
        ];
    }

    // ── View switching ────────────────────────────────────────────────────────

    public function switchView(int $viewId): void
    {
        $this->viewId = $viewId;
        $this->loadCurrentView();
        $this->loadPoints();
        $this->dispatch('points-updated', points: $this->points);
    }

    public function createView(string $name, string $viewType): void
    {
        if (! $this->structure) {
            return;
        }

        $view = InspectionView::create([
            'name'       => $name,
            'view_type'  => $viewType,
            'structure_id' => $this->structure->id,
        ]);

        $this->availableViews[] = [
            'id'        => $view->id,
            'name'      => $view->name,
            'view_type' => $view->view_type,
        ];

        $this->switchView($view->id);

        Notification::make()
            ->success()
            ->title('Inspection View Created')
            ->body("\"{$name}\" is ready for annotation.")
            ->send();
    }

    // ── Pin events (dispatched from Alpine.js) ────────────────────────────────

    #[On('map-point-placed')]
    public function handleMapPointPlaced(float $x, float $y): void
    {
        if (! $this->viewId) {
            Notification::make()->warning()->title('Select a view first')->send();

            return;
        }

        $point = InspectionPoint::create([
            'inspection_view_id' => $this->viewId,
            'x_coordinate'       => $x,
            'y_coordinate'       => $y,
            'severity'           => 'minor',
        ]);

        $this->points[] = $this->pointToArray($point->fresh('media'));
        $this->dispatch('points-updated', points: $this->points);

        Notification::make()
            ->success()
            ->title('Pin Placed')
            ->body("{$point->observation_id} added. Click the pin to add details.")
            ->send();
    }

    #[On('map-point-updated')]
    public function handleMapPointUpdated(int $pointId, array $data): void
    {
        $point = InspectionPoint::find($pointId);

        if (! $point) {
            return;
        }

        $point->update(array_filter([
            'severity'        => $data['severity'] ?? null,
            'finding_type'    => $data['finding_type'] ?? null,
            'description'     => $data['description'] ?? null,
            'dive_location'   => $data['dive_location'] ?? null,
            'depth_m'         => $data['depth_m'] ?? null,
            'dimension_mm'    => $data['dimension_mm'] ?? null,
            'recommendations' => $data['recommendations'] ?? null,
        ], fn ($v) => $v !== null));

        // Refresh the local points array
        $this->loadPoints();
        $this->dispatch('points-updated', points: $this->points);

        Notification::make()->success()->title('Observation Updated')->send();
    }

    #[On('map-point-media-linked')]
    public function handleMediaLinked(int $pointId, int $mediaId): void
    {
        InspectionMedia::where('id', $mediaId)->update([
            'inspection_point_id' => $pointId,
        ]);

        $this->loadPoints();
        $this->dispatch('points-updated', points: $this->points);

        Notification::make()->success()->title('Media Linked to Pin')->send();
    }

    #[On('map-point-media-unlinked')]
    public function handleMediaUnlinked(int $mediaId): void
    {
        InspectionMedia::where('id', $mediaId)->update([
            'inspection_point_id' => null,
        ]);

        $this->loadPoints();
        $this->dispatch('points-updated', points: $this->points);

        Notification::make()->success()->title('Media Unlinked')->send();
    }

    #[On('map-point-deleted')]
    public function handleMapPointDeleted(int $pointId): void
    {
        InspectionPoint::find($pointId)?->delete();

        $this->points = array_values(array_filter($this->points, fn ($p) => $p['id'] !== $pointId));
        $this->dispatch('points-updated', points: $this->points);

        Notification::make()->success()->title('Pin Removed')->send();
    }

    // ── Available media for linking ───────────────────────────────────────────

    public function getStructureMediaProperty(): array
    {
        if (! $this->structure) {
            return [];
        }

        return InspectionMedia::where('structure_id', $this->structure->id)
            ->get()
            ->map(fn ($m) => [
                'id'                   => $m->id,
                'file_name'            => $m->file_name,
                'media_type'           => $m->media_type,
                'url'                  => $m->url,
                'thumbnail_url'        => $m->thumbnail_url,
                'inspection_point_id'  => $m->inspection_point_id,
            ])
            ->toArray();
    }

    // ── View ──────────────────────────────────────────────────────────────────

    public function getView(): string
    {
        return 'rov-inspection::filament.pages.map-annotation';
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin.navigation.rov-inspection');
    }
}
