<?php

namespace Webkul\RovInspection\Filament\Resources\RovProjectResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use BackedEnum;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Webkul\RovInspection\Enums\Severity;
use Webkul\RovInspection\Filament\Resources\RovProjectResource;

class ManageInspectionPoints extends ManageRelatedRecords
{
    protected static string $resource = RovProjectResource::class;

    protected static string $relationship = 'inspectionPoints';

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-map-pin';

    public static function getNavigationLabel(): string
    {
        return 'Inspection Points';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('annotate_map')
                ->label('Open Map')
                ->icon('heroicon-o-map')
                ->color('info')
                ->url(fn () => '/admin/rov-inspection/map?project='.$this->getRecord()->id)
                ->visible(fn () => $this->getRecord()->site_map_path !== null),
        ];
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('label')
                    ->label('Point Label')
                    ->placeholder('e.g. Point A - Corrosion on weld joint')
                    ->required()
                    ->maxLength(255),
                TextInput::make('point_number')
                    ->label('Point Number')
                    ->numeric()
                    ->minValue(1),
                Select::make('severity')
                    ->label('Severity')
                    ->options(Severity::options())
                    ->native(false)
                    ->nullable(),
                TextInput::make('defect_type')
                    ->label('Defect / Finding Type')
                    ->placeholder('e.g. Corrosion, Marine Growth, Mechanical Damage')
                    ->nullable(),
                Textarea::make('description')
                    ->label('Description')
                    ->rows(3)
                    ->nullable(),
                Textarea::make('recommendations')
                    ->label('Recommendations')
                    ->rows(3)
                    ->nullable(),
            ])
            ->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('label')
            ->columns([
                TextColumn::make('point_number')
                    ->label('#')
                    ->sortable()
                    ->width('60px'),
                TextColumn::make('label')
                    ->label('Label')
                    ->searchable()
                    ->sortable()
                    ->weight(\Filament\Support\Enums\FontWeight::SemiBold),
                TextColumn::make('defect_type')
                    ->label('Finding Type')
                    ->searchable()
                    ->placeholder('--'),
                TextColumn::make('severity')
                    ->label('Severity')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state ? (Severity::tryFrom($state)?->getLabel() ?? ucfirst($state)) : '--')
                    ->color(fn ($state) => $state ? (Severity::tryFrom($state)?->getColor() ?? 'gray') : 'gray'),
                TextColumn::make('media_count')
                    ->counts('media')
                    ->label('Media Files')
                    ->badge()
                    ->color('info')
                    ->icon('heroicon-o-photo'),
                TextColumn::make('created_at')
                    ->label('Added')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('point_number')
            ->headerActions([
                CreateAction::make()
                    ->label('Add Inspection Point')
                    ->icon('heroicon-o-plus-circle')
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title('Inspection Point Added')
                    ),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make()
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title('Point Updated'),
                        ),
                    DeleteAction::make()
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title('Point Deleted'),
                        ),
                ]),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateIcon('heroicon-o-map-pin')
            ->emptyStateHeading('No Inspection Points')
            ->emptyStateDescription('Add inspection points to mark findings on the site map.');
    }
}
