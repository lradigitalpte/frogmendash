<?php

namespace Webkul\RovInspection\Filament\Resources\RovProjectResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\RichEditor;
use Filament\Notifications\Notification;
use BackedEnum;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Webkul\RovInspection\Enums\ReportStatus;
use Webkul\RovInspection\Filament\Resources\RovProjectResource;

class ManageReports extends ManageRelatedRecords
{
    protected static string $resource = RovProjectResource::class;

    protected static string $relationship = 'reports';

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-document-text';

    public static function getNavigationLabel(): string
    {
        return 'Reports';
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->label('Report Title')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('e.g. Post-Survey Inspection Report – Q1 2026')
                    ->columnSpanFull(),
                Textarea::make('summary')
                    ->label('Executive Summary')
                    ->rows(3)
                    ->columnSpanFull(),
                RichEditor::make('full_report')
                    ->label('Full Report')
                    ->columnSpanFull(),
                Textarea::make('conclusions')
                    ->label('Conclusions')
                    ->rows(3),
                Textarea::make('recommendations')
                    ->label('Recommendations')
                    ->rows(3),
                Select::make('status')
                    ->label('Status')
                    ->options(ReportStatus::options())
                    ->default(ReportStatus::Draft->value)
                    ->native(false)
                    ->required(),
            ])
            ->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                TextColumn::make('title')
                    ->label('Title')
                    ->searchable()
                    ->sortable()
                    ->weight(\Filament\Support\Enums\FontWeight::SemiBold),
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
                    ->label('Shared Date')
                    ->dateTime('d M Y, H:i')
                    ->placeholder('Not Shared'),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->headerActions([
                CreateAction::make()
                    ->label('New Report')
                    ->icon('heroicon-o-plus-circle')
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title('Report Created'),
                    ),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make()
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title('Report Updated'),
                        ),
                    Action::make('share')
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
                                ->body('Report share link: '.url('/report/'.$record->shared_link_hash))
                                ->send();
                        })
                        ->hidden(fn ($record) => $record->shared_link_hash !== null),
                    Action::make('copy_link')
                        ->label('Copy Share Link')
                        ->icon('heroicon-o-clipboard')
                        ->color('success')
                        ->extraAttributes(fn ($record) => [
                            'x-data'  => '',
                            'x-on:click' => "navigator.clipboard.writeText('".url('/report/'.$record->shared_link_hash)."')",
                        ])
                        ->visible(fn ($record) => $record->shared_link_hash !== null),
                    DeleteAction::make()
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title('Report Deleted'),
                        ),
                ]),
            ])
            ->emptyStateIcon('heroicon-o-document-text')
            ->emptyStateHeading('No Reports Yet')
            ->emptyStateDescription('Create your first inspection report for this project.');
    }
}
