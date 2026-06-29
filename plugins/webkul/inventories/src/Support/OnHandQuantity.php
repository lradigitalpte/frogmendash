<?php

namespace Webkul\Inventory\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Webkul\Inventory\Enums\LocationType;
use Webkul\Inventory\Models\ProductQuantity;

final class OnHandQuantity
{
    public static function forProduct(object $product): float
    {
        $query = ProductQuantity::query()
            ->whereHas('location', function (Builder $locationQuery) {
                $locationQuery->where('type', LocationType::INTERNAL)
                    ->where('is_scrap', false);
            });

        if (! empty($product->is_configurable)) {
            $variantIds = DB::table('products_products')
                ->where('parent_id', $product->id)
                ->pluck('id');

            $query->where(function (Builder $productQuery) use ($product, $variantIds) {
                $productQuery->where('product_id', $product->id);

                if ($variantIds->isNotEmpty()) {
                    $productQuery->orWhereIn('product_id', $variantIds);
                }
            });
        } else {
            $query->where('product_id', $product->id);
        }

        return (float) $query->sum('quantity');
    }

    public static function addSubquery(Builder $query): Builder
    {
        $internalType = LocationType::INTERNAL->value;

        return $query->addSelect([
            'on_hand_quantity' => ProductQuantity::query()
                ->selectRaw('COALESCE(SUM(inventories_product_quantities.quantity), 0)')
                ->join('inventories_locations', 'inventories_locations.id', '=', 'inventories_product_quantities.location_id')
                ->where('inventories_locations.type', $internalType)
                ->where('inventories_locations.is_scrap', false)
                ->where(function (Builder $productQuery) {
                    $productQuery->whereColumn(
                        'inventories_product_quantities.product_id',
                        'products_products.id'
                    )->orWhereIn('inventories_product_quantities.product_id', function ($subQuery) {
                        $subQuery->select('id')
                            ->from('products_products as variants')
                            ->whereColumn('variants.parent_id', 'products_products.id');
                    });
                }),
        ]);
    }
}
