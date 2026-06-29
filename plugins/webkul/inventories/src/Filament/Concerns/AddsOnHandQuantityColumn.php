<?php

namespace Webkul\Inventory\Filament\Concerns;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Webkul\Inventory\Support\OnHandQuantity;
use Webkul\Inventory\Support\QuantityDisplay;
use Webkul\PluginManager\Package;

trait AddsOnHandQuantityColumn
{
    protected static function withOnHandQuantityColumn(Table $table): Table
    {
        if (! Package::isPluginInstalled('inventories')) {
            return $table;
        }

        $table->modifyQueryUsing(fn ($query) => OnHandQuantity::addSubquery($query));

        return $table->pushColumns([
            TextColumn::make('on_hand_quantity')
                ->label(__('inventories::filament/clusters/products/resources/product.table.columns.on-hand-quantity'))
                ->badge()
                ->formatStateUsing(fn ($state) => QuantityDisplay::format($state))
                ->color(fn ($state): string => (float) $state > 0 ? 'success' : 'gray')
                ->placeholder('0')
                ->sortable(),
        ]);
    }
}
