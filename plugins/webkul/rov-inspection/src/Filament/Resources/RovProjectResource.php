<?php

namespace Webkul\RovInspection\Filament\Resources;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Pages\Page;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Webkul\RovInspection\Enums\ProjectStatus;
use Webkul\RovInspection\Filament\Resources\RovProjectResource\Pages\CreateRovProject;
use Webkul\RovInspection\Filament\Resources\RovProjectResource\Pages\EditRovProject;
use Webkul\RovInspection\Filament\Resources\RovProjectResource\Pages\ListRovProjects;
use Webkul\RovInspection\Filament\Resources\RovProjectResource\Pages\ManageInspectionPoints;
use Webkul\RovInspection\Filament\Resources\RovProjectResource\Pages\ManageReports;
use Webkul\RovInspection\Filament\Resources\RovProjectResource\Pages\ViewRovProject;
use Webkul\RovInspection\Models\RovProject;

class RovProjectResource extends Resource
{
    protected static ?string $model = RovProject::class;

    protected static ?string $slug = 'rov-inspection/projects';

    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationLabel(): string
    {
        return 'Inspection Projects';
    }

    public static function getNavigationGroup(): string
    {
        return __('admin.navigation.rov-inspection');
    }

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-clipboard-document-list';
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'location', 'customer.name'];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Customer' => $record->customer?->name ?? '—',
            'Status'   => $record->status ?? '—',
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Group::make()
                    ->schema([
                        Section::make('Project Information')
                            ->schema([
                                TextInput::make('name')
                                    ->label('Project Name')
                                    ->required()
                                    ->maxLength(255)
                                    ->autofocus()
                                    ->placeholder('e.g. Pipeline ROV Survey – Block 14')
                                    ->extraInputAttributes(['style' => 'font-size: 1.5rem;height: 3rem;']),
                                Textarea::make('description')
                                    ->label('Description')
                                    ->rows(3),
                            ]),

                        Section::make('Details')
                            ->schema([
                                TextInput::make('location')
                                    ->label('Site Location')
                                    ->placeholder('e.g. Gulf of Guinea, Block 14')
                                    ->prefixIcon('heroicon-o-map-pin'),
                                Select::make('customer_id')
                                    ->label('Client / Customer')
                                    ->relationship('customer', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->nullable(),
                                DatePicker::make('start_date')
                                    ->label('Start Date')
                                    ->native(false)
                                    ->suffixIcon('heroicon-o-calendar'),
                                DatePicker::make('end_date')
                                    ->label('End Date')
                                    ->native(false)
                                    ->suffixIcon('heroicon-o-calendar')
                                    ->afterOrEqual('start_date'),
                            ])
                            ->columns(2),

                        Section::make('Site Map')
                            ->schema([
                                FileUpload::make('site_map_path')
                                    ->label('Upload Site Map / Blueprint')
                                    ->image()
                                    ->imageEditor()
                                    ->maxSize(20480)
                                    ->disk('public')
                                    ->directory('rov-inspection/site-maps')
                                    ->helperText('Upload a site map, blueprint or diagram. Inspection points will be plotted on this image.'),
                            ]),
                    ])
                    ->columnSpan(['lg' => 2]),

                Group::make()
                    ->schema([
                        Section::make('Status & Assignment')
                            ->schema([
                                Select::make('status')
                                    ->label('Status')
                                    ->options(ProjectStatus::options())
                                    ->default(ProjectStatus::Draft->value)
                                    ->required()
                                    ->native(false),
                                Select::make('company_id')
                                    ->label('Company')
                                    ->relationship('company', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->default(fn () => filament()->auth()->user()?->default_company_id)
                                    ->nullable(),
                            ]),
                    ])
                    ->columnSpan(['lg' => 1]),
            ])
            ->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Project')
                    ->searchable()
                    ->sortable()
                    ->weight(\Filament\Support\Enums\FontWeight::SemiBold),
                TextColumn::make('location')
                    ->label('Site Location')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-map-pin')
                    ->placeholder('—'),
                TextColumn::make('customer.name')
                    ->label('Client')
                    ->searchable()
                    ->sortable()
                    ->placeholder('—'),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => ProjectStatus::tryFrom($state)?->getLabel() ?? ucfirst($state))
                    ->color(fn (string $state) => ProjectStatus::tryFrom($state)?->getColor() ?? 'gray')
                    ->icon(fn (string $state) => ProjectStatus::tryFrom($state)?->getIcon() ?? null),
                TextColumn::make('inspection_points_count')
                    ->label('Points')
                    ->counts('inspectionPoints')
                    ->badge()
                    ->color('info')
                    ->icon('heroicon-o-map-pin'),
                TextColumn::make('start_date')
                    ->label('Start Date')
                    ->date('d M Y')
                    ->sortable()
                    ->placeholder('—'),
                TextColumn::make('end_date')
                    ->label('End Date')
                    ->date('d M Y')
                    ->sortable()
                    ->placeholder('—'),
                TextColumn::make('creator.name')
                    ->label('Created By')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(ProjectStatus::options()),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make()
                        ->hidden(fn ($record) => $record->trashed()),
                    RestoreAction::make()
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title('Project Restored')
                                ->body('The inspection project has been restored.'),
                        ),
                    DeleteAction::make()
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title('Project Deleted')
                                ->body('The inspection project has been deleted.'),
                        ),
                    ForceDeleteAction::make()
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title('Project Permanently Deleted'),
                        ),
                ]),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ])
            ->emptyStateIcon('heroicon-o-map')
            ->emptyStateHeading('No Inspection Projects')
            ->emptyStateDescription('Create your first ROV inspection project to get started.');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Group::make()
                    ->schema([
                        Section::make('Project Information')
                            ->schema([
                                TextEntry::make('name')
                                    ->label('Project Name')
                                    ->size(\Filament\Support\Enums\TextSize::Large)
                                    ->weight(\Filament\Support\Enums\FontWeight::Bold),
                                TextEntry::make('description')
                                    ->label('Description')
                                    ->placeholder('—')
                                    ->columnSpanFull(),
                            ]),

                        Section::make('Details')
                            ->schema([
                                TextEntry::make('location')
                                    ->label('Site Location')
                                    ->icon('heroicon-o-map-pin')
                                    ->placeholder('—'),
                                TextEntry::make('customer.name')
                                    ->label('Client')
                                    ->placeholder('—'),
                                TextEntry::make('start_date')
                                    ->label('Start Date')
                                    ->date('d M Y')
                                    ->placeholder('—'),
                                TextEntry::make('end_date')
                                    ->label('End Date')
                                    ->date('d M Y')
                                    ->placeholder('—'),
                            ])
                            ->columns(2),

                        Section::make('Site Map')
                            ->schema([
                                ImageEntry::make('site_map_path')
                                    ->label('')
                                    ->disk('public')
                                    ->height(400)
                                    ->extraImgAttributes(['class' => 'rounded-lg border w-full object-contain'])
                                    ->hidden(fn ($record) => ! $record->site_map_path),
                                TextEntry::make('no_map')
                                    ->label('')
                                    ->state('No site map uploaded yet.')
                                    ->visible(fn ($record) => ! $record->site_map_path),
                            ]),
                    ])
                    ->columnSpan(['lg' => 2]),

                Group::make()
                    ->schema([
                        Section::make('Status')
                            ->schema([
                                TextEntry::make('status')
                                    ->label('Status')
                                    ->badge()
                                    ->formatStateUsing(fn ($state) => ProjectStatus::tryFrom($state)?->getLabel() ?? ucfirst($state))
                                    ->color(fn ($state) => ProjectStatus::tryFrom($state)?->getColor() ?? 'gray')
                                    ->icon(fn ($state) => ProjectStatus::tryFrom($state)?->getIcon()),
                                TextEntry::make('company.name')
                                    ->label('Company')
                                    ->placeholder('—'),
                                TextEntry::make('creator.name')
                                    ->label('Created By')
                                    ->placeholder('—'),
                                TextEntry::make('created_at')
                                    ->label('Created')
                                    ->dateTime('d M Y, H:i'),
                                TextEntry::make('updated_at')
                                    ->label('Last Updated')
                                    ->dateTime('d M Y, H:i'),
                            ]),
                    ])
                    ->columnSpan(['lg' => 1]),
            ])
            ->columns(3);
    }

    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            ViewRovProject::class,
            EditRovProject::class,
            ManageInspectionPoints::class,
            ManageReports::class,
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index'             => ListRovProjects::route('/'),
            'create'            => CreateRovProject::route('/create'),
            'view'              => ViewRovProject::route('/{record}'),
            'edit'              => EditRovProject::route('/{record}/edit'),
            'inspection-points' => ManageInspectionPoints::route('/{record}/inspection-points'),
            'reports'           => ManageReports::route('/{record}/reports'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withTrashed();
    }
}
