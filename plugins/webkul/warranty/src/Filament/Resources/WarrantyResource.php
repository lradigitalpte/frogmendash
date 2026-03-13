<?php

namespace Webkul\Warranty\Filament\Resources;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea as FormTextarea;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\TextSize;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Webkul\Warranty\Enums\StartTrigger;
use Webkul\Warranty\Enums\WarrantyStatus;
use Webkul\Warranty\Filament\Resources\WarrantyResource\Pages;
use Webkul\Warranty\Models\Warranty;
use Webkul\Warranty\Models\WarrantyPolicy;
use Webkul\Warranty\Services\WarrantyGenerator;

class WarrantyResource extends Resource
{
    protected static ?string $model = Warranty::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';

    protected static ?int $navigationSort = 82;

    public static function getNavigationLabel(): string
    {
        return 'Warranties';
    }

    public static function getNavigationGroup(): string
    {
        return 'Sales';
    }

    public static function getNavigationBadge(): ?string
    {
        $expiringSoon = Warranty::withoutGlobalScopes()
            ->where('company_id', Auth::user()?->default_company_id)
            ->expiringSoon()
            ->count();

        return $expiringSoon > 0 ? (string) $expiringSoon : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    // ── Form ─────────────────────────────────────────────────────────────────

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Warranty details')
                ->schema([
                    Grid::make(2)->schema([
                        Select::make('product_id')
                            ->label('Product')
                            ->required()
                            ->relationship('product', 'name')
                            ->searchable()
                            ->preload()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if (! $state) {
                                    return;
                                }
                                // Auto-fill policy from the product's default
                                $product = \Webkul\Product\Models\Product::find($state);
                                if ($product?->warranty_policy_id) {
                                    $set('warranty_policy_id', $product->warranty_policy_id);
                                    $policy = WarrantyPolicy::find($product->warranty_policy_id);
                                    if ($policy) {
                                        $set('duration_months', $policy->duration_months);
                                        $set('start_trigger', $policy->start_trigger);
                                    }
                                }
                            }),

                        Select::make('warranty_policy_id')
                            ->label('Warranty policy')
                            ->relationship('policy', 'name')
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if (! $state) {
                                    return;
                                }
                                $policy = WarrantyPolicy::find($state);
                                if ($policy) {
                                    $set('duration_months', $policy->duration_months);
                                    $set('start_trigger', $policy->start_trigger);
                                }
                            }),
                    ]),

                    Grid::make(2)->schema([
                        TextInput::make('serial_number')
                            ->label('Serial number')
                            ->maxLength(255)
                            ->nullable(),

                        TextInput::make('asset_tag')
                            ->label('Asset tag')
                            ->maxLength(255)
                            ->nullable(),
                    ]),

                    Select::make('customer_id')
                        ->label('Customer')
                        ->required()
                        ->relationship('customer', 'name')
                        ->searchable()
                        ->preload(),
                ]),

            Section::make('Warranty period')
                ->schema([
                    Grid::make(3)->schema([
                        Select::make('start_trigger')
                            ->label('Starts from')
                            ->options(StartTrigger::options())
                            ->default(StartTrigger::DeliveryDate->value)
                            ->reactive(),

                        TextInput::make('duration_months')
                            ->label('Duration (months)')
                            ->numeric()
                            ->default(12)
                            ->minValue(1)
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                $start = $get('start_date');
                                if ($start && $state) {
                                    $set('end_date', \Carbon\Carbon::parse($start)
                                        ->addMonths((int) $state)
                                        ->toDateString());
                                }
                            }),

                        Select::make('status')
                            ->label('Status')
                            ->options(collect(WarrantyStatus::cases())
                                ->mapWithKeys(fn ($c) => [$c->value => $c->getLabel()])
                                ->toArray())
                            ->default(WarrantyStatus::Draft->value)
                            ->required(),
                    ]),

                    Grid::make(2)->schema([
                        DatePicker::make('start_date')
                            ->label('Start date')
                            ->nullable()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                $months = $get('duration_months');
                                if ($state && $months) {
                                    $set('end_date', \Carbon\Carbon::parse($state)
                                        ->addMonths((int) $months)
                                        ->toDateString());
                                    // Auto-activate if dates are set
                                    $set('status', WarrantyStatus::Active->value);
                                }
                            }),

                        DatePicker::make('end_date')
                            ->label('End date')
                            ->nullable(),
                    ]),
                ]),

            Section::make('Source documents')
                ->collapsed()
                ->schema([
                    Grid::make(2)->schema([
                        Select::make('sales_order_id')
                            ->label('Sales order')
                            ->relationship('salesOrder', 'name')
                            ->searchable()
                            ->nullable(),

                        Select::make('delivery_id')
                            ->label('Delivery')
                            ->relationship('delivery', 'name')
                            ->searchable()
                            ->nullable(),
                    ]),
                ]),

            Section::make('Notes')
                ->collapsed()
                ->schema([
                    FormTextarea::make('notes')
                        ->label('Internal notes')
                        ->rows(4)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    // ── Table ─────────────────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('customer.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),

                TextColumn::make('product.name')
                    ->label('Product')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('serial_number')
                    ->label('Serial #')
                    ->searchable()
                    ->placeholder('—'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => WarrantyStatus::from($state)->getLabel())
                    ->color(fn ($state) => WarrantyStatus::from($state)->getColor())
                    ->icon(fn ($state) => WarrantyStatus::from($state)->getIcon()),

                TextColumn::make('start_date')
                    ->label('Start')
                    ->date()
                    ->sortable(),

                TextColumn::make('end_date')
                    ->label('Expires')
                    ->date()
                    ->sortable()
                    ->color(fn ($record) => match (true) {
                        $record->status === WarrantyStatus::Expired->value         => 'danger',
                        $record->isExpiringSoon()                                   => 'warning',
                        default                                                     => null,
                    }),

                TextColumn::make('days_remaining')
                    ->label('Days left')
                    ->getStateUsing(fn ($record) => $record->days_remaining)
                    ->formatStateUsing(fn ($state) => match (true) {
                        $state === null  => '—',
                        $state < 0       => abs($state).' days ago',
                        $state === 0     => 'Expires today',
                        default          => $state.' days',
                    })
                    ->color(fn ($record) => match (true) {
                        $record->days_remaining === null                             => null,
                        $record->days_remaining < 0                                 => 'danger',
                        $record->days_remaining <= 30                               => 'warning',
                        default                                                     => 'success',
                    })
                    ->sortable(query: fn (Builder $query, string $direction) => $query->orderBy('end_date', $direction)),

                TextColumn::make('policy.name')
                    ->label('Policy')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(collect(WarrantyStatus::cases())
                        ->mapWithKeys(fn ($c) => [$c->value => $c->getLabel()])
                        ->toArray()),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('end_date', 'asc');
    }

    // ── Infolist (View page) ──────────────────────────────────────────────────

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Warranty details')
                ->schema([
                    Grid::make(3)->schema([
                        TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->formatStateUsing(fn ($state) => WarrantyStatus::from($state)->getLabel())
                            ->color(fn ($state) => WarrantyStatus::from($state)->getColor())
                            ->icon(fn ($state) => WarrantyStatus::from($state)->getIcon()),

                        TextEntry::make('days_remaining')
                            ->label('Days remaining')
                            ->getStateUsing(fn ($record) => $record->days_remaining)
                            ->formatStateUsing(fn ($state) => match (true) {
                                $state === null  => '—',
                                $state < 0       => 'Expired '.abs($state).' days ago',
                                $state === 0     => 'Expires today',
                                default          => $state.' days',
                            })
                            ->color(fn ($record) => match (true) {
                                $record->days_remaining === null  => null,
                                $record->days_remaining < 0      => 'danger',
                                $record->days_remaining <= 30     => 'warning',
                                default                           => 'success',
                            })
                            ->size(TextSize::Large)
                            ->weight('bold'),

                        TextEntry::make('policy.name')
                            ->label('Policy')
                            ->placeholder('—'),
                    ]),

                    Grid::make(2)->schema([
                        TextEntry::make('product.name')->label('Product'),
                        TextEntry::make('customer.name')->label('Customer'),
                        TextEntry::make('serial_number')->label('Serial number')->placeholder('—'),
                        TextEntry::make('asset_tag')->label('Asset tag')->placeholder('—'),
                    ]),
                ]),

            Section::make('Warranty period')
                ->schema([
                    Grid::make(3)->schema([
                        TextEntry::make('start_date')->label('Start date')->date(),
                        TextEntry::make('end_date')->label('End date')->date(),
                        TextEntry::make('duration_months')
                            ->label('Duration')
                            ->formatStateUsing(fn ($state) => $state.' months'),
                    ]),

                    Grid::make(2)->schema([
                        TextEntry::make('start_trigger')
                            ->label('Countdown starts from')
                            ->formatStateUsing(fn ($state) => StartTrigger::from($state)->getLabel()),

                        TextEntry::make('coverage_snapshot_json')
                            ->label('Coverage')
                            ->formatStateUsing(fn ($state) => is_array($state)
                                ? implode(', ', array_map('ucfirst', $state))
                                : '—'),
                    ]),
                ]),

            Section::make('Source documents')
                ->schema([
                    Grid::make(2)->schema([
                        TextEntry::make('salesOrder.name')->label('Sales order')->placeholder('—'),
                        TextEntry::make('delivery.name')->label('Delivery')->placeholder('—'),
                    ]),
                ])
                ->collapsed(),

            Section::make('Notes')
                ->schema([
                    TextEntry::make('notes')->label('')->columnSpanFull()->placeholder('—'),
                ])
                ->collapsed(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListWarranties::route('/'),
            'create' => Pages\CreateWarranty::route('/create'),
            'view'   => Pages\ViewWarranty::route('/{record}'),
            'edit'   => Pages\EditWarranty::route('/{record}/edit'),
        ];
    }
}
