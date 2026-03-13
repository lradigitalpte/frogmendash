<?php

namespace Webkul\RovInspection\Filament\Resources\RovProjectResource\Pages;

use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Webkul\RovInspection\Filament\Resources\RovProjectResource;
use Webkul\RovInspection\Models\InspectionPoint;
use Webkul\RovInspection\Models\ProjectStructure;

class ManageMedia extends ManageRelatedRecords
{
    protected static string $resource = RovProjectResource::class;

    /**
     * Media is accessed via structures → all media for the project.
     * We override the relationship query to load across all structures.
     */
    protected static string $relationship = 'structures';

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-film';

    public static function getNavigationLabel(): string
    {
        return 'Media';
    }

    /**
     * Override the query so the table shows inspection_media rows,
     * not project_structures rows.
     */
    public function getTableQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return \Webkul\RovInspection\Models\InspectionMedia::query()
            ->whereIn(
                'structure_id',
                ProjectStructure::where('rov_project_id', $this->getRecord()->id)->pluck('id')
            )
            ->with(['structure', 'inspectionPoint']);
    }

    public function form(Schema $schema): Schema
    {
        $projectId = $this->getRecord()->id;

        $structureOptions = ProjectStructure::where('rov_project_id', $projectId)
            ->orderBy('sort')
            ->pluck('name', 'id');

        return $schema
            ->components([
                Select::make('structure_id')
                    ->label('Structure')
                    ->options($structureOptions)
                    ->required()
                    ->native(false)
                    ->live()
                    ->helperText('Which structure does this media belong to?'),
                Select::make('inspection_point_id')
                    ->label('Link to Observation Pin (optional)')
                    ->options(function ($get) {
                        $structureId = $get('structure_id');
                        if (! $structureId) {
                            return [];
                        }

                        return InspectionPoint::whereHas(
                            'inspectionView',
                            fn ($q) => $q->where('structure_id', $structureId)
                        )
                            ->get()
                            ->mapWithKeys(fn ($p) => [
                                $p->id => ($p->observation_id ?? 'Point '.$p->point_number).' — '.($p->finding_type ?? $p->description ?? '…'),
                            ]);
                    })
                    ->nullable()
                    ->native(false)
                    ->searchable()
                    ->helperText('Linking to a pin means this video/image will appear when a viewer clicks that pin.'),
                TextInput::make('file_name')
                    ->label('Display Name')
                    ->placeholder('e.g. Dive_PILE_1A')
                    ->required()
                    ->maxLength(255),
                Select::make('media_type')
                    ->label('Media Type')
                    ->options(['video' => 'Video', 'image' => 'Image'])
                    ->required()
                    ->native(false),
                FileUpload::make('file_path')
                    ->label('Upload File')
                    ->disk('public')
                    ->directory('rov-inspection/media')
                    ->maxSize(512000)
                    ->acceptedFileTypes(['video/mp4', 'video/webm', 'image/jpeg', 'image/png', 'image/webp'])
                    ->columnSpanFull()
                    ->helperText('Max 500 MB. Accepted: MP4, WebM, JPEG, PNG, WebP.'),
            ])
            ->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('file_name')
            ->defaultSort('created_at', 'desc')
            ->columns([
                ImageColumn::make('thumbnail_url')
                    ->label('Preview')
                    ->height(48)
                    ->width(72)
                    ->extraImgAttributes(['class' => 'object-cover rounded'])
                    ->defaultImageUrl(fn ($record) => $record->isVideo()
                        ? 'https://ui-avatars.com/api/?name=VID&background=1e293b&color=94a3b8&size=64'
                        : 'https://ui-avatars.com/api/?name=IMG&background=e0f2fe&color=0284c7&size=64'
                    ),
                TextColumn::make('file_name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->weight(\Filament\Support\Enums\FontWeight::SemiBold),
                TextColumn::make('media_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn ($state) => $state === 'video' ? 'primary' : 'info')
                    ->formatStateUsing(fn ($state) => ucfirst($state ?? '—')),
                TextColumn::make('structure.name')
                    ->label('Structure')
                    ->placeholder('—')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('inspectionPoint.observation_id')
                    ->label('Linked Pin')
                    ->placeholder('Not linked')
                    ->badge()
                    ->color(fn ($state) => $state ? 'warning' : 'gray')
                    ->icon(fn ($state) => $state ? 'heroicon-o-map-pin' : null),
                TextColumn::make('human_file_size')
                    ->label('Size')
                    ->placeholder('—'),
                TextColumn::make('created_at')
                    ->label('Uploaded')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('structure_id')
                    ->label('Structure')
                    ->options(
                        fn () => ProjectStructure::where('rov_project_id', $this->getRecord()->id)
                            ->orderBy('sort')
                            ->pluck('name', 'id')
                    )
                    ->native(false),
                SelectFilter::make('media_type')
                    ->label('Type')
                    ->options(['video' => 'Video', 'image' => 'Image'])
                    ->native(false),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Upload Media')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['uploaded_by'] = Auth::id();

                        return $data;
                    })
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title('Media Uploaded')
                            ->body('You can link this file to an observation pin from the Structures tab.')
                    ),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make()
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title('Media Updated')
                        ),
                    DeleteAction::make()
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title('Media Deleted')
                        ),
                ]),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateIcon('heroicon-o-film')
            ->emptyStateHeading('No Media Uploaded')
            ->emptyStateDescription('Upload ROV videos and inspection images. Link them to observation pins so clients can play the footage directly from the annotated diagram.');
    }
}
