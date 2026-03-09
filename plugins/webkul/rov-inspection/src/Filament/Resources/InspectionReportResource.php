<?php

namespace Webkul\RovInspection\Filament\Resources;

use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Webkul\RovInspection\Enums\ReportStatus;
use Webkul\RovInspection\Filament\Resources\InspectionReportResource\Pages\CreateInspectionReport;
use Webkul\RovInspection\Filament\Resources\InspectionReportResource\Pages\EditInspectionReport;
use Webkul\RovInspection\Filament\Resources\InspectionReportResource\Pages\ListInspectionReports;
use Webkul\RovInspection\Filament\Resources\InspectionReportResource\Pages\ViewInspectionReport;
use Webkul\RovInspection\Models\InspectionReport;

class InspectionReportResource extends Resource
{
    protected static ?string $model = InspectionReport::class;

    protected static ?string $slug = 'rov-inspection/reports';

    protected static ?string $recordTitleAttribute = 'title';

    public static function getNavigationLabel(): string
    {
        return 'Reports';
    }

    public static function getNavigationGroup(): string
    {
        return __('admin.navigation.rov-inspection');
    }

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-document-text';
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['title', 'project.name'];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Project' => $record->project?->name ?? '--',
            'Status'  => $record->status ?? '--',
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Group::make()
                    ->schema([
                        Section::make('Report Details')
                            ->schema([
                                TextInput::make('title')
                                    ->label('Report Title')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('e.g. Post-Survey Inspection Report')
                                    ->extraInputAttributes(['style' => 'font-size: 1.5rem;height: 3rem;']),
                                Select::make('rov_project_id')
                                    ->label('Inspection Project')
                                    ->relationship('project', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required(),
                            ]),

                        Section::make('Content')
                            ->schema([
                                Textarea::make('summary')
                                    ->label('Executive Summary')
                                    ->rows(3)
                                    ->columnSpanFull(),
                                RichEditor::make('full_report')
                                    ->label('Full Report Body')
                                    ->columnSpanFull(),
                                Textarea::make('conclusions')
                                    ->label('Conclusions')
                                    ->rows(3),
                                Textarea::make('recommendations')
                                    ->label('Recommendations')
                                    ->rows(3),
                            ])
                            ->columns(2),
                    ])
                    ->columnSpan(['lg' => 2]),

                Group::make()
                    ->schema([
                        Section::make('Status & Sharing')
                            ->schema([
                                Select::make('status')
                                    ->label('Status')
                                    ->options(ReportStatus::options())
                                    ->default(ReportStatus::Draft->value)
                                    ->native(false)
                                    ->required(),
                                Toggle::make('client_can_download')
                                    ->label('Allow Client to Download')
                                    ->helperText('Allow the client to download a PDF copy')
                                    ->default(false),
                                Toggle::make('client_can_print')
                                    ->label('Allow Client to Print')
                                    ->default(false),
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
                TextColumn::make('title')
                    ->label('Report Title')
                    ->searchable()
                    ->sortable()
                    ->weight(\Filament\Support\Enums\FontWeight::SemiBold),
                TextColumn::make('project.name')
                    ->label('Project')
                    ->searchable()
                    ->sortable()
                    ->placeholder('--'),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => ReportStatus::tryFrom($state)?->getLabel() ?? ucfirst($state))
                    ->color(fn ($state) => ReportStatus::tryFrom($state)?->getColor() ?? 'gray'),
                TextColumn::make('shared_link_hash')
                    ->label('Share Link')
                    ->formatStateUsing(fn ($state) => $state ? 'Active' : 'Not Shared')
                    ->badge()
                    ->color(fn ($state) => $state ? 'success' : 'gray'),
                TextColumn::make('shared_date')
                    ->label('Shared')
                    ->dateTime('d M Y')
                    ->placeholder('--')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                ActionGroup::make([
                    EditAction::make()
                        ->successNotification(
                            Notification::make()->success()->title('Report Updated'),
                        ),
                    Tables\Actions\Action::make('share')
                        ->label('Generate Share Link')
                        ->icon('heroicon-o-share')
                        ->color('info')
                        ->action(function ($record) {
                            $record->generateShareLink();
                            $record->status = ReportStatus::Shared->value;
                            $record->save();

                            Notification::make()
                                ->success()
                                ->title('Share Link Generated')
                                ->body('Share URL: '.url('/report/'.$record->shared_link_hash))
                                ->send();
                        })
                        ->hidden(fn ($record) => $record->shared_link_hash !== null),
                    Tables\Actions\Action::make('view_client')
                        ->label('Open Client View')
                        ->icon('heroicon-o-arrow-top-right-on-square')
                        ->color('success')
                        ->url(fn ($record) => url('/report/'.$record->shared_link_hash))
                        ->openUrlInNewTab()
                        ->visible(fn ($record) => $record->shared_link_hash !== null),
                    DeleteAction::make()
                        ->successNotification(
                            Notification::make()->success()->title('Report Deleted'),
                        ),
                ]),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateIcon('heroicon-o-document-text')
            ->emptyStateHeading('No Reports')
            ->emptyStateDescription('Generate inspection reports for your projects.');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Group::make()
                    ->schema([
                        Section::make('Report Details')
                            ->schema([
                                TextEntry::make('title')
                                    ->label('Title')
                                    ->size(\Filament\Support\Enums\TextSize::Large)
                                    ->weight(\Filament\Support\Enums\FontWeight::Bold),
                                TextEntry::make('project.name')
                                    ->label('Project')
                                    ->placeholder('--'),
                            ]),
                        Section::make('Content')
                            ->schema([
                                TextEntry::make('summary')
                                    ->label('Executive Summary')
                                    ->placeholder('--')
                                    ->columnSpanFull(),
                                TextEntry::make('full_report')
                                    ->label('Full Report')
                                    ->html()
                                    ->placeholder('--')
                                    ->columnSpanFull(),
                                TextEntry::make('conclusions')
                                    ->label('Conclusions')
                                    ->placeholder('--'),
                                TextEntry::make('recommendations')
                                    ->label('Recommendations')
                                    ->placeholder('--'),
                            ])
                            ->columns(2),
                    ])
                    ->columnSpan(['lg' => 2]),

                Group::make()
                    ->schema([
                        Section::make('Status')
                            ->schema([
                                TextEntry::make('status')
                                    ->label('Status')
                                    ->badge()
                                    ->formatStateUsing(fn ($state) => ReportStatus::tryFrom($state)?->getLabel() ?? ucfirst($state))
                                    ->color(fn ($state) => ReportStatus::tryFrom($state)?->getColor() ?? 'gray'),
                                TextEntry::make('shared_date')
                                    ->label('Shared Date')
                                    ->dateTime('d M Y, H:i')
                                    ->placeholder('Not shared'),
                                TextEntry::make('created_at')
                                    ->label('Created')
                                    ->dateTime('d M Y, H:i'),
                            ]),
                    ])
                    ->columnSpan(['lg' => 1]),
            ])
            ->columns(3);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListInspectionReports::route('/'),
            'create' => CreateInspectionReport::route('/create'),
            'view'   => ViewInspectionReport::route('/{record}'),
            'edit'   => EditInspectionReport::route('/{record}/edit'),
        ];
    }
}
