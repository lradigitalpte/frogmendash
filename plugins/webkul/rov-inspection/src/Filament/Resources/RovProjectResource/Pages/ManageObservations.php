<?php

namespace Webkul\RovInspection\Filament\Resources\RovProjectResource\Pages;

use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Webkul\RovInspection\Enums\Severity;
use Webkul\RovInspection\Filament\Resources\RovProjectResource;
use Webkul\RovInspection\Models\InspectionView;
use Webkul\RovInspection\Models\ProjectStructure;

/**
 * Shows all observation pins across every structure and view belonging to this project.
 * Uses a custom query instead of a direct model relationship.
 */
class ManageObservations extends ManageRelatedRecords
{
    protected static string $resource = RovProjectResource::class;

    protected static string $relationship = 'structures'; // not used directly; query is overridden

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-map-pin';

    public static function getNavigationLabel(): string
    {
        return 'Observations';
    }

    /**
     * Override the table query to load ALL inspection points for this project,
     * traversing Project → Structures → Views → Points.
     */
    public function getTableQuery(): Builder
    {
        $structureIds = ProjectStructure::where('rov_project_id', $this->getRecord()->id)->pluck('id');
        $viewIds      = InspectionView::whereIn('structure_id', $structureIds)->pluck('id');

        return \Webkul\RovInspection\Models\InspectionPoint::whereIn('inspection_view_id', $viewIds)
            ->with(['inspectionView.structure', 'media']);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('observation_id')
                    ->label('Observation ID')
                    ->disabled()
                    ->dehydrated(false),
                Select::make('severity')
                    ->label('Severity')
                    ->options(Severity::options())
                    ->native(false),
                TextInput::make('finding_type')
                    ->label('Finding Type')
                    ->placeholder('Corrosion, Marine Growth…'),
                TextInput::make('dive_location')
                    ->label('Dive Location')
                    ->placeholder('Plank A1, Pile 1A…'),
                TextInput::make('depth_m')
                    ->label('Depth (m)')
                    ->numeric()
                    ->step(0.1),
                TextInput::make('dimension_mm')
                    ->label('Dimension (mm)')
                    ->placeholder('67.00 x 28.18'),
                Textarea::make('description')
                    ->label('Description')
                    ->rows(3)
                    ->columnSpanFull(),
                Textarea::make('recommendations')
                    ->label('Recommendations')
                    ->rows(3)
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->defaultSort('inspection_view_id')
            ->columns([
                TextColumn::make('observation_id')
                    ->label('ID')
                    ->sortable()
                    ->weight(\Filament\Support\Enums\FontWeight::Bold)
                    ->width('60px'),
                TextColumn::make('inspectionView.structure.name')
                    ->label('Structure')
                    ->badge()
                    ->color('gray')
                    ->sortable(),
                TextColumn::make('inspectionView.name')
                    ->label('View')
                    ->badge()
                    ->color('primary'),
                TextColumn::make('finding_type')
                    ->label('Finding')
                    ->searchable()
                    ->placeholder('—')
                    ->weight(\Filament\Support\Enums\FontWeight::SemiBold),
                TextColumn::make('severity')
                    ->label('Severity')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state ? (Severity::tryFrom($state)?->getLabel() ?? ucfirst($state)) : '—')
                    ->color(fn ($state) => $state ? (Severity::tryFrom($state)?->getColor() ?? 'gray') : 'gray'),
                TextColumn::make('dive_location')
                    ->label('Location')
                    ->searchable()
                    ->placeholder('—'),
                TextColumn::make('depth_m')
                    ->label('Depth (m)')
                    ->numeric(1)
                    ->placeholder('—'),
                TextColumn::make('dimension_mm')
                    ->label('Dimension (mm)')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('media_count')
                    ->label('Media')
                    ->getStateUsing(fn ($record) => $record->media->count())
                    ->badge()
                    ->color(fn ($state) => $state > 0 ? 'info' : 'gray')
                    ->icon('heroicon-o-film'),
            ])
            ->filters([
                SelectFilter::make('structure')
                    ->label('Structure')
                    ->query(function (Builder $query, array $data) {
                        if (filled($data['value'])) {
                            $viewIds = InspectionView::where('structure_id', $data['value'])->pluck('id');
                            $query->whereIn('inspection_view_id', $viewIds);
                        }
                    })
                    ->options(
                        fn () => ProjectStructure::where('rov_project_id', $this->getRecord()->id)
                            ->orderBy('sort')
                            ->pluck('name', 'id')
                    )
                    ->native(false),
                SelectFilter::make('severity')
                    ->label('Severity')
                    ->options(Severity::options())
                    ->native(false),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make()
                        ->successNotification(Notification::make()->success()->title('Observation Updated')),
                    DeleteAction::make()
                        ->successNotification(Notification::make()->success()->title('Observation Deleted')),
                ]),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateIcon('heroicon-o-map-pin')
            ->emptyStateHeading('No Observations Yet')
            ->emptyStateDescription('Add structures, upload diagrams, and annotate them to create observations.');
    }
}
