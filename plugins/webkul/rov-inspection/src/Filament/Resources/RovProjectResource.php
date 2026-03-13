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
use Webkul\RovInspection\Filament\Resources\RovProjectResource\Pages\ManageMedia;
use Webkul\RovInspection\Filament\Resources\RovProjectResource\Pages\ManageObservations;
use Webkul\RovInspection\Filament\Resources\RovProjectResource\Pages\ManageReports;
use Webkul\RovInspection\Filament\Resources\RovProjectResource\Pages\ManageStructures;
use Webkul\RovInspection\Filament\Resources\RovProjectResource\Pages\ViewRovProject;
use Webkul\RovInspection\Models\RovProject;

class RovProjectResource extends Resource
{
    protected static ?string $model = RovProject::class;

    protected static ?string $slug = 'rov-inspection/projects';

    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getModelLabel(): string
    {
        return 'Inspection Project';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Inspection Projects';
    }

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

                        Section::make('GPS Location')
                            ->description('Used to pin the project on a satellite map in the client report.')
                            ->schema([
                                TextInput::make('latitude')
                                    ->label('Latitude')
                                    ->numeric()
                                    ->placeholder('e.g. 25.1972')
                                    ->prefixIcon('heroicon-o-globe-alt'),
                                TextInput::make('longitude')
                                    ->label('Longitude')
                                    ->numeric()
                                    ->placeholder('e.g. 55.2744')
                                    ->prefixIcon('heroicon-o-globe-alt'),
                            ])
                            ->columns(2),

                        Section::make('Plan View')
                            ->description('Top-down CAD or engineering drawing shown in the Plan View modal on client reports.')
                            ->schema([
                                FileUpload::make('plan_view_path')
                                    ->label('Upload Plan View Drawing')
                                    ->image()
                                    ->imageEditor()
                                    ->maxSize(20480)
                                    ->disk('public')
                                    ->directory('rov-inspection/plan-views')
                                    ->helperText('Upload a top-down CAD, architectural or engineering plan drawing.'),
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
                                    ->relationship('company', 'name', fn ($q) => $q ? $q->forCurrentUser() : $q)
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
                TextColumn::make('structures_count')
                    ->label('Structures')
                    ->counts('structures')
                    ->badge()
                    ->color('info')
                    ->icon('heroicon-o-building-office'),
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

                        Section::make('GPS Location')
                            ->schema([
                                TextEntry::make('latitude')
                                    ->label('Latitude')
                                    ->icon('heroicon-o-globe-alt')
                                    ->placeholder('—'),
                                TextEntry::make('longitude')
                                    ->label('Longitude')
                                    ->icon('heroicon-o-globe-alt')
                                    ->placeholder('—'),
                            ])
                            ->columns(2),

                        Section::make('Plan View')
                            ->schema([
                                ImageEntry::make('plan_view_path')
                                    ->label('')
                                    ->disk('public')
                                    ->height(300)
                                    ->extraImgAttributes(['class' => 'rounded-lg border w-full object-contain'])
                                    ->hidden(fn ($record) => ! $record->plan_view_path),
                                TextEntry::make('no_plan_view')
                                    ->label('')
                                    ->state('No plan view drawing uploaded yet.')
                                    ->visible(fn ($record) => ! $record->plan_view_path),
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
            ManageStructures::class,
            ManageObservations::class,
            ManageMedia::class,
            ManageReports::class,
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index'        => ListRovProjects::route('/'),
            'create'       => CreateRovProject::route('/create'),
            'view'         => ViewRovProject::route('/{record}'),
            'edit'         => EditRovProject::route('/{record}/edit'),
            'structures'   => ManageStructures::route('/{record}/structures'),
            'observations' => ManageObservations::route('/{record}/observations'),
            'media'        => ManageMedia::route('/{record}/media'),
            'reports'      => ManageReports::route('/{record}/reports'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withTrashed();
    }
}
