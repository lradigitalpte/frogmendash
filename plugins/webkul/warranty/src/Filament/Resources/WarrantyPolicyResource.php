<?php

namespace Webkul\Warranty\Filament\Resources;

use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea as FormTextarea;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Forms\Components\Select;
use Webkul\Warranty\Enums\StartTrigger;
use Webkul\Warranty\Filament\Resources\WarrantyPolicyResource\Pages;
use Webkul\Warranty\Models\WarrantyPolicy;

class WarrantyPolicyResource extends Resource
{
    protected static ?string $model = WarrantyPolicy::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?int $navigationSort = 81;

    public static function getNavigationLabel(): string
    {
        return 'Warranty Policies';
    }

    public static function getNavigationGroup(): string
    {
        return 'Sales';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Policy details')
                ->schema([
                    TextInput::make('name')
                        ->label('Policy name')
                        ->required()
                        ->maxLength(255)
                        ->placeholder('e.g. Standard 12-Month ROV Warranty')
                        ->columnSpanFull(),

                    FormTextarea::make('description')
                        ->label('Description')
                        ->rows(3)
                        ->columnSpanFull(),

                    Grid::make(3)->schema([
                        TextInput::make('duration_months')
                            ->label('Duration (months)')
                            ->numeric()
                            ->required()
                            ->default(12)
                            ->minValue(1)
                            ->maxValue(120),

                        Select::make('start_trigger')
                            ->label('Warranty starts from')
                            ->options(StartTrigger::options())
                            ->default(StartTrigger::DeliveryDate->value)
                            ->required(),

                        TextInput::make('max_visits_per_year')
                            ->label('Max service visits / year')
                            ->numeric()
                            ->nullable()
                            ->minValue(0),
                    ]),
                ]),

            Section::make('Coverage')
                ->schema([
                    TagsInput::make('coverage_json')
                        ->label('Coverage tags')
                        ->placeholder('Add coverage item and press Enter…')
                        ->suggestions([
                            'hull',
                            'electronics',
                            'cameras',
                            'thrusters',
                            'lights',
                            'tether',
                            'hydraulics',
                            'labour',
                            'spare-parts',
                        ])
                        ->columnSpanFull(),

                    Grid::make(3)->schema([
                        Toggle::make('include_spare_parts')
                            ->label('Include spare parts')
                            ->default(false),

                        Toggle::make('include_labour')
                            ->label('Include labour')
                            ->default(false),

                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ]),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Policy name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('duration_months')
                    ->label('Duration')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => $state.' months'),

                TextColumn::make('start_trigger')
                    ->label('Starts from')
                    ->badge()
                    ->formatStateUsing(fn ($state) => StartTrigger::from($state)->getLabel()),

                IconColumn::make('include_spare_parts')
                    ->label('Parts')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle'),

                IconColumn::make('include_labour')
                    ->label('Labour')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle'),

                TextColumn::make('warranties_count')
                    ->label('# Warranties')
                    ->counts('warranties')
                    ->sortable()
                    ->badge()
                    ->color('gray'),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
            ])
            ->filters([])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ])
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListWarrantyPolicies::route('/'),
            'create' => Pages\CreateWarrantyPolicy::route('/create'),
            'edit'   => Pages\EditWarrantyPolicy::route('/{record}/edit'),
        ];
    }
}
