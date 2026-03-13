<?php

namespace Webkul\RovInspection\Filament\Resources\RovProjectResource\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Webkul\RovInspection\Filament\Resources\RovProjectResource;

class ManageStructures extends ManageRelatedRecords
{
    protected static string $resource = RovProjectResource::class;

    protected static string $relationship = 'structures';

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-building-office';

    public static function getNavigationLabel(): string
    {
        return 'Structures';
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Structure Name')
                    ->placeholder('e.g. PILE_1, Dolphin_West, Mooring_Pile_2')
                    ->required()
                    ->maxLength(100),
                TextInput::make('sort')
                    ->label('Display Order')
                    ->numeric()
                    ->default(0)
                    ->minValue(0)
                    ->helperText('Lower number = displayed first.'),
                Textarea::make('description')
                    ->label('Description')
                    ->rows(2)
                    ->columnSpanFull(),
                FileUpload::make('photo_path')
                    ->label('Surface Photo')
                    ->image()
                    ->maxSize(10240)
                    ->disk('public')
                    ->directory('rov-inspection/structure-photos')
                    ->helperText('Above-water or surface photo of this structure. Shown in the Inspection Image gallery.'),
                FileUpload::make('diagram_path')
                    ->label('Annotatable Diagram')
                    ->image()
                    ->maxSize(20480)
                    ->disk('public')
                    ->directory('rov-inspection/diagrams')
                    ->helperText('Engineering elevation, cross-section or plan drawing. Inspection pins will be plotted on this image.'),
            ])
            ->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->defaultSort('sort')
            ->columns([
                ImageColumn::make('photo_path')
                    ->label('Photo')
                    ->disk('public')
                    ->height(48)
                    ->width(64)
                    ->extraImgAttributes(['class' => 'object-cover rounded'])
                    ->defaultImageUrl(fn () => 'https://ui-avatars.com/api/?name=STR&background=e2e8f0&color=475569&size=64'),
                TextColumn::make('name')
                    ->label('Structure')
                    ->searchable()
                    ->sortable()
                    ->weight(\Filament\Support\Enums\FontWeight::SemiBold),
                TextColumn::make('views_count')
                    ->label('Views')
                    ->counts('views')
                    ->badge()
                    ->color('primary')
                    ->icon('heroicon-o-eye'),
                TextColumn::make('all_points_count')
                    ->label('Observations')
                    ->getStateUsing(fn ($record) => $record->allPoints()->count())
                    ->badge()
                    ->color('warning')
                    ->icon('heroicon-o-map-pin'),
                TextColumn::make('media_count')
                    ->label('Media Files')
                    ->counts('media')
                    ->badge()
                    ->color('info')
                    ->icon('heroicon-o-film'),
                TextColumn::make('diagram_path')
                    ->label('Diagram')
                    ->formatStateUsing(fn ($state) => $state ? '✓ Uploaded' : '— None')
                    ->badge()
                    ->color(fn ($state) => $state ? 'success' : 'gray'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Add Structure')
                    ->icon('heroicon-o-plus-circle')
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title('Structure Added')
                            ->body('You can now upload a diagram and start annotating.')
                    ),
            ])
            ->recordActions([
                Action::make('annotate')
                    ->label('Annotate')
                    ->icon('heroicon-o-pencil-square')
                    ->color('primary')
                    ->url(fn ($record) => '/admin/rov-inspection/map?structure='.$record->id)
                    ->visible(fn ($record) => (bool) $record->diagram_path)
                    ->openUrlInNewTab(false),
                ActionGroup::make([
                    EditAction::make()
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title('Structure Updated')
                        ),
                    DeleteAction::make()
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title('Structure Deleted')
                        ),
                ]),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateIcon('heroicon-o-building-office')
            ->emptyStateHeading('No Structures Yet')
            ->emptyStateDescription('Add structures (piles, dolphins, pontoons…) to organise your inspection. Each structure gets its own annotatable diagram.');
    }
}
